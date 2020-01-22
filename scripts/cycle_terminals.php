<?php
chdir(dirname(__FILE__) . '/../');
include_once("./config.php");
include_once("./lib/loader.php");
include_once("./lib/threads.php");
set_time_limit(0);
include_once("./load_settings.php");
include_once(DIR_MODULES . "terminals/terminals.class.php");
include_once DIR_MODULES . 'terminals/tts_addon.class.php';
// обьявляем массив обектов дабы не грузить их всегда 
$tts = array();
// берем конфигурацию с модуля терминалов - общие настройки
$ter = new terminals();
$ter->getConfig();
$checked_time = 0;
// set all terminal as free when restart cycle
$term = SQLSelect("SELECT * FROM terminals");
foreach ($term as $t) {
    sg($t['LINKED_OBJECT'] . '.busy', 0);
}
// reset all message when reload cicle
//SQLExec("UPDATE shouts SET SOURCE = '' ");
// get number last message
$number_message = SQLSelectOne("SELECT * FROM shouts ORDER BY ID DESC");
$number_message = $number_message['ID'] + 1;
DebMes(date("H:i:s") . " Running " . basename(__FILE__));
while (1) {
    // time update cicle of terminal
    if (time() - $checked_time > 60) {
        // проверка на установленность терминалов2
        if (!function_exists('catchTimeoutTerminals')) {
            DebMes("Удалите цикл - cycle_terminals.php , поскольку вы не используете модуль Модификацию Терминалов 2");
            setGlobal('cycle_terminalsAutoRestart', '0');
            setGlobal('cycle_terminalsControl', 'stop');
        }
        $ter->getConfig();
        if (!$ter->config['TERMINALS_PING']) {
            if ($ter->config['LOG_ENABLED']) DebMes("Timeout for ping terminals is null minutes, set default 30 minutes", 'terminals');
            $ter->config['TERMINALS_PING'] = 30;
        }
        if (!$ter->config['TERMINALS_TIMEOUT']) {
			if ($ter->config['LOG_ENABLED']) DebMes("Timeout for message is null minutes, set default 10 minutes", 'terminals');
            $ter->config['TERMINALS_TIMEOUT'] = 10;
        }
        $checked_time = time();
        setGlobal((str_replace('.php', '', basename(__FILE__))) . 'Run', time(), 1);
    }
    //время жизни сообщений
    if (time() - $clear_message > 60 * $ter->config['TERMINALS_TIMEOUT']) {
        $clear_message = time();
        $result = SQLSelect("SELECT COUNT(ID) FROM shouts WHERE SOURCE != '' AND ADDED > (NOW() - INTERVAL " . $ter->config['TERMINALS_TIMEOUT'] . " MINUTE)");
        if ($result[0]['COUNT(ID)']>0) {
            SQLExec("UPDATE shouts SET SOURCE = '' WHERE SOURCE != '' AND ADDED < (NOW() - INTERVAL " . $ter->config['TERMINALS_TIMEOUT'] . " MINUTE)");
            if ($ter->config['LOG_ENABLED']) DebMes ("Clear message - when can not to play. For timeouts - ".$ter->config['TERMINALS_TIMEOUT'], 'terminals');
        }
    }

    // CHEK next message for terminals ready
    $message = SQLSelectOne("SELECT 1 FROM shouts WHERE ID = '" . $number_message . "'");
    if ($message) {
        $number_message = $number_message + 1;
        if ($ter->config['LOG_ENABLED']) DebMes("Next message number - " . $number_message, 'terminals');
    } else {
        sleep(1);
    }
    
    $out_terminals = getObjectsByProperty('busy', '==', '0');
    foreach ($out_terminals as $terminals) {
        // если пустой обьект пропускаем
        if (!$terminals) {
            if ($ter->config['LOG_ENABLED']) DebMes("No free terminal " . $terminals, 'terminals');
            continue;
        }
        $terminal = SQLSelectOne("SELECT * FROM terminals WHERE LINKED_OBJECT = '" . $terminals . "'");
        
        // если пустой терминал пропускаем
        if (!$terminal['ID']) {
            DebMes ("Cannot find terminal for this object - " . $terminals . ". Object must be deleted.", 'terminals');
            continue ;
        }

        // обьявляем новый обьект которого нет в массиве $tts                
        if (!$tts[$terminal['ID']] AND $terminal['TTS_TYPE'] AND file_exists(DIR_MODULES . 'terminals/tts/' . $terminal['TTS_TYPE'] . '.addon.php')) {
            include_once(DIR_MODULES . 'terminals/tts/' . $terminal['TTS_TYPE'] . '.addon.php');
            $tts[$terminal['ID']] = new $terminal['TTS_TYPE']($terminal);
            if ($ter->config['LOG_ENABLED']) DebMes("Add terminal to array tts objects -" . $terminal['NAME'], 'terminals');
            continue;
        }

        // если терминал СВОБОДНЫЙ и офлайн то пингуем его
        if (!$terminal['IS_ONLINE'] AND (time() > 60 * $ter->config['TERMINALS_PING'] + strtotime($terminal['LATEST_REQUEST_TIME']))) {
            if ($ter->config['LOG_ENABLED']) DebMes("PingSafe terminal " . $terminal['NAME'], 'terminals');
            pingTerminalSafe($terminal['NAME'], $terminal);
            continue;
        }
        
        // berem pervoocherednoe soobsheniye 
        $old_message = SQLSelectOne("SELECT * FROM shouts WHERE ID <= '" . $number_message . "' AND SOURCE LIKE '%" . $terminal['ID'] . "^%' ORDER BY ID ASC");
        
        // если отсутствует сообщение и есть инфа для восстановления то восстанавливаем воспроизводимое
        // и переходим на следующий свободный терминал
        if (!$old_message['ID'] OR !$old_message['SOURCE'] ) {
            if ($terminal['IS_ONLINE'] AND $restore = json_decode(gg($terminal['LINKED_OBJECT'] . '.playerdata'), true)) {
                sg($terminal['LINKED_OBJECT'] . '.busy', 1);
				restore_mediaSafe($restore, $terminal);
				continue;
			}
        } else if (!$old_message['SOURCE'] OR !$old_message['MESSAGE']) {
		    // если нечего восстанавливать просто пропускаем итерацию - 
			// иногда попадаются пустые записи ИД терминалов
            continue;
		}

        // если есть сообщение НО терминал оффлайн удаляем из работы эту запись 
        // и пропускаем (пингуется дополнительно - если вернется с ошибкой отправления)
        if (($old_message['ID'] AND !$terminal['IS_ONLINE']) OR !$terminal['TTS_TYPE']) {
            $old_message['SOURCE'] = str_replace($terminal['ID'] . '^', '', $old_message['SOURCE']);
            SQLUpdate('shouts', $old_message);
            //if ($ter->config['LOG_ENABLED']) DebMes("Disable message - " . $terminal['NAME'], 'terminals');
            continue;
        }
		
        // если есть сообщение НО не сгенерирован звук в течении 1 минуты
        // удаляем сообщение из очереди для терминалов воспроизводящих звук
        if ($old_message['CACHED_FILENAME'] AND  strtotime($old_message['ADDED'])+1*60 < time() AND method_exists($tts[$terminal['ID']], 'say_media_message')) {
			$old_message['SOURCE'] = str_replace($terminal['ID'] . '^', '', $old_message['SOURCE']);
            SQLUpdate('shouts', $old_message);
            if ($ter->config['LOG_ENABLED']) DebMes("Disable message not generated sound  - " . $terminal['NAME'], 'terminals');
            continue;
        }
        
		// если есть сообщение и есть запись о существовании файла НО не сгенерирован звук (отсутсвтует файл)
        // удаляем сообщение из очереди для терминалов воспроизводящих звук
        if ($old_message['CACHED_FILENAME'] AND !file_exists($old_message['CACHED_FILENAME']) AND method_exists($tts[$terminal['ID']], 'say_media_message')) {
			$old_message['SOURCE'] = str_replace($terminal['ID'] . '^', '', $old_message['SOURCE']);
            SQLUpdate('shouts', $old_message);
            if ($ter->config['LOG_ENABLED']) DebMes("Disable message not generated sound  - " . $terminal['NAME'], 'terminals');
            continue;
        }
		
        // если тип терминала воспроизводящий аудио и нету еще сгенерированного файла пропускаем
        if (method_exists($tts[$terminal['ID']], 'say_media_message') AND !$old_message['CACHED_FILENAME']) {
            continue;
        }
        
        // если тип терминала передающий только текстовое сообщение  
        // запускаем его воспроизведение
        if (method_exists($tts[$terminal['ID']], 'say_message') AND $old_message['SOURCE']) {
            // убираем запись айди терминала из таблицы шутс - если не воспроизведется то вернет эту запись функция send_message($old_message, $terminal);
            $old_message['SOURCE'] = str_replace($terminal['ID'] . '^', '', $old_message['SOURCE']);
            SQLUpdate('shouts', $old_message);
            //записываем что терминал занят
            sg($terminal['LINKED_OBJECT'] . '.busy', 1);
            //передаем сообщение на терминал передающий только текстовое сообщение 
            send_messageSafe($old_message, $terminal);
            if ($ter->config['LOG_ENABLED']) DebMes("Send message with text to terminal - " . $terminal['NAME'], 'terminals');
            continue;
        }
        
        // если тип терминала передающий медиа сообщение
        // иначе запускаем его воспроизведение
        if (method_exists($tts[$terminal['ID']], 'say_media_message') AND $old_message['CACHED_FILENAME'] AND $old_message['SOURCE']) {
            // убираем запись айди терминала из таблицы шутс - если не воспроизведется то вернет эту запись функция send_message($old_message, $terminal);
            $old_message['SOURCE'] = str_replace($terminal['ID'] . '^', '', $old_message['SOURCE']);
            SQLUpdate('shouts', $old_message);
            //записываем что терминал занят
            sg($terminal['LINKED_OBJECT'] . '.busy', 1);
            //передаем сообщение на терминалы воспроизводящие аудио
            send_messageSafe($old_message, $terminal);
            if ($ter->config['LOG_ENABLED']) DebMes("Send message with media to terminal - " . $terminal['NAME'], 'terminals');
            continue;
        }

    }
    
    if (file_exists('./reboot') || IsSet($_GET['onetime'])) {
        exit;
    }
}
DebMes("Unexpected close of cycle: " . basename(__FILE__));
