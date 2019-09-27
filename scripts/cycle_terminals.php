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
    sg($t['LINKED_OBJECT'] . '.busy', '0');
}

// reset all message when reload cicle
//SQLExec("UPDATE shouts SET SOURCE = '' ");


// get number last message
$number_message = SQLSelectOne("SELECT * FROM shouts ORDER BY ID DESC");
$number_message = $number_message['ID'] + 1;

DebMes( date("H:i:s") . " Running " . basename(__FILE__) );

if ($ter->config['TERMINALS_TIMEOUT']) {
	$terminals_time_out = $ter->config['TERMINALS_TIMEOUT'];
} else {
    $terminals_time_out = 10;
}
if ($ter->config['LOG_ENABLED']) DebMes("Get timeout for message - " . $terminals_time_out. " minutes", 'terminals');

while (1) {
    // time update cicle of terminal
    if (time() - $checked_time > 60) {
        $ter->getConfig();
        $checked_time = time();
        setGlobal((str_replace('.php', '', basename(__FILE__))) . 'Run', time(), 1);
    }
    //время жизни сообщений
    if (time() - $clear_message > 60*$terminals_time_out) {
        $clear_message = time();
        SQLExec("UPDATE shouts SET SOURCE = '' WHERE ADDED < (NOW() - INTERVAL ".$terminals_time_out." MINUTE)");
		// когда в настройках терминалов изменили таймаут для сообщений то получаем по новой
		if (!$ter->config['TERMINALS_TIMEOUT'] ) {
			$terminals_time_out = 10;
		} else if ($ter->config['TERMINALS_TIMEOUT'] != $terminals_time_out ) {
			$terminals_time_out = $ter->config['TERMINALS_TIMEOUT'];
	        if ($ter->config['LOG_ENABLED']) DebMes("Change timeout for message to " . $terminals_time_out. " minutes", 'terminals');
		}
    }
    // CHEK next message for terminals ready
    $message = SQLSelectOne("SELECT 1 FROM shouts WHERE ID = '" . $number_message . "'");
    if ($message) {
        $number_message = $number_message + 1;
        if ($ter->config['LOG_ENABLED']) DebMes("Max nomber message - " . $number_message, 'terminals');
    } else {
        sleep(1);
    }
    
    $out_terminals = getObjectsByProperty('busy', '==', '0');
    foreach ($out_terminals as $terminals) {

        // если пустой терминал пропускаем
        if (!$terminals) {
            if ($ter->config['LOG_ENABLED']) DebMes("No free terminal " . $terminals, 'terminals');
            continue;
        }
        $terminal = SQLSelectOne("SELECT * FROM terminals WHERE LINKED_OBJECT = '" . $terminals . "'");
 
        if (!$terminal['TTS_TYPE'] OR (!method_exists($tts[$terminal['ID']],'__construct') AND file_exists(DIR_MODULES . 'terminals/tts/' . $terminal['TTS_TYPE'] . '.addon.php'))) {
            include_once(DIR_MODULES . 'terminals/tts/' . $terminal['TTS_TYPE'] . '.addon.php');
    		// обьявляем новый обьект которого нет в массиве $tts
            $tts[$terminal['ID']] = new $terminal['TTS_TYPE']($terminal);
            if ($ter->config['LOG_ENABLED']) DebMes("Add terminal to array tts objects -" . $terminal['NAME'], 'terminals');
    	}

        // если терминалы офлайн то пингуем их
		if (!$ter->config['TERMINALS_PING'] ) {
     		$ter->config['TERMINALS_PING'] = 10;
	    }
        if (!$terminal['IS_ONLINE'] AND (time() > 60*$ter->config['TERMINALS_PING'] + strtotime($terminal['LATEST_REQUEST_TIME']))) {
            if ($ter->config['LOG_ENABLED']) DebMes("PingSafe terminal" . $terminal['NAME'], 'terminals');
            pingTerminalSafe($terminal['NAME'], $terminal);
        }

        // berem pervoocherednoe soobsheniye 
        $old_message = SQLSelectOne("SELECT * FROM shouts WHERE ID <= '" . $number_message . "' AND SOURCE LIKE '%" . $terminal['ID'] . "^%' ORDER BY ID ASC");

        // если отсутствует сообщение и есть инфа для восстановления то восстанавливаем воспроизводимое
        if (!$old_message['MESSAGE']) {
            try {
                $restored = json_decode(gg($terminal['LINKED_OBJECT'] . '.playerdata'), true);
                if ($restored['volume'] AND method_exists($tts[$terminal['ID']],'set_volume')) {
                    if ($ter->config['LOG_ENABLED']) DebMes("Restore volume on the terminal - " . $terminal['NAME'], 'terminals');
                    $tts[$terminal['ID']]->set_volume($restored['volume']);
				}
				// если есть файл для воспроизведения то тоже его восстанавливаем
                if ($restored['file'] AND method_exists($tts[$terminal['ID']],'play')) {
                    $tts[$terminal['ID']]->play($restored['file'], $restored['time']);
                    if ($ter->config['LOG_ENABLED']) DebMes("Restore media on the terminal - " . $terminal['NAME'], 'terminals');
                }
                sg($terminal['LINKED_OBJECT'] . '.playerdata', '');
                continue;
            }
            catch (Exception $e) {
                if ($ter->config['LOG_ENABLED']) DebMes("Error with restore playaed - " . $terminal['NAME'], 'terminals');
            }
        }

        // если терминал оффлайн удаляем из работы эту запись и пропускаем (пингуется дополнительно - если вернется с ошибкой отправления)
        if (!$terminal['IS_ONLINE'] OR !$terminal['TTS_TYPE']) {
            $old_message['SOURCE'] = str_replace($terminal['ID'] . '^', '', $old_message['SOURCE']);
            SQLUpdate('shouts', $old_message);
            if ($ter->config['LOG_ENABLED']) DebMes("Disable message - " . $terminal['NAME'], 'terminals');
            continue;
        }

        // для остальных плееров просто пропускаем итерацию и при отсутствии сообщения 
        if (!$old_message['MESSAGE']) {
            continue;
        }
		
        // если тип терминала воспроизводящий аудио и нету еще сгенерированного файла пропускаем 
        if (method_exists($tts[$terminal['ID']],'say_media_message') AND !$old_message['CACHED_FILENAME']) {
            continue;
        }

        // иначе запускаем его воспроизведение
        if (method_exists($tts[$terminal['ID']],'say_media_message') AND $old_message['CACHED_FILENAME']) {
            // убираем запись айди терминала из таблицы шутс - если не воспроизведется то вернет эту запись функция send_message($old_message, $terminal);
            $old_message['SOURCE'] = str_replace($terminal['ID'] . '^', '', $old_message['SOURCE']);
            SQLUpdate('shouts', $old_message);
            //записываем что терминал занят
            sg($terminal['LINKED_OBJECT'] . '.basy', 1);
            //передаем сообщение на терминалы воспроизводящие аудио
            send_messageSafe($old_message, $terminal);
            if ($ter->config['LOG_ENABLED']) DebMes("Send message - " . $terminal['NAME'], 'terminals');
            continue;
        }

        // если тип терминала передающий только текстовое сообщение  
        // запускаем его воспроизведение
        if (method_exists($tts[$terminal['ID']],'say_message')) {
            // убираем запись айди терминала из таблицы шутс - если не воспроизведется то вернет эту запись функция send_message($old_message, $terminal);
            $old_message['SOURCE'] = str_replace($terminal['ID'] . '^', '', $old_message['SOURCE']);
            SQLUpdate('shouts', $old_message);
            //записываем что терминал занят
            sg($terminal['LINKED_OBJECT'] . '.basy', 1);
            //передаем сообщение на терминал передающий только текстовое сообщение 
            send_messageSafe($old_message, $terminal);
            if ($ter->config['LOG_ENABLED']) DebMes("Send message - " . $terminal['NAME'], 'terminals');
            continue;
        }
    }
    
    if (file_exists('./reboot') || IsSet($_GET['onetime'])) {
        exit;
    }
}
DebMes("Unexpected close of cycle: " . basename(__FILE__));
