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
$term         = SQLSelect("SELECT * FROM terminals");
foreach ($term as $t) {
    sg($t['LINKED_OBJECT'] . '.TerminalState', 0);
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
            if ($ter->config['LOG_ENABLED'])
                DebMes("Timeout for ping terminals is null minutes, set default 30 minutes", 'terminals');
            $ter->config['TERMINALS_PING'] = 30;
        }
        if (!$ter->config['TERMINALS_TIMEOUT']) {
            if ($ter->config['LOG_ENABLED'])
                DebMes("Timeout for message is null minutes, set default 10 minutes", 'terminals');
            $ter->config['TERMINALS_TIMEOUT'] = 10;
        }
        $checked_time = time();
        setGlobal((str_replace('.php', '', basename(__FILE__))) . 'Run', time(), 1);
    }
    //время жизни сообщений
    if (time() - $clear_message > 60 * $ter->config['TERMINALS_TIMEOUT']) {
        $clear_message = time();
        $result        = SQLSelect("SELECT COUNT(ID) FROM shouts WHERE SOURCE != '' AND ADDED > (NOW() - INTERVAL " . $ter->config['TERMINALS_TIMEOUT'] . " MINUTE)");
        if ($result[0]['COUNT(ID)'] > 0) {
            SQLExec("UPDATE shouts SET SOURCE = '' WHERE SOURCE != '' AND ADDED < (NOW() - INTERVAL " . $ter->config['TERMINALS_TIMEOUT'] . " MINUTE)");
            if ($ter->config['LOG_ENABLED'])
                DebMes("Clear message - when can not to play. For timeouts - " . $ter->config['TERMINALS_TIMEOUT'], 'terminals');
        }
    }
    
    // CHEK next message for terminals ready
    $message = SQLSelectOne("SELECT 1 FROM shouts WHERE ID = '" . $number_message . "'");
    if ($message) {
        $number_message = $number_message + 1;
        if ($ter->config['LOG_ENABLED'])
            DebMes("Next message number - " . $number_message, 'terminals');
    } else {
        sleep(1);
    }
    
    $out_terminals = getObjectsByProperty('TerminalState', '==', '0');
    foreach ($out_terminals as $terminals) {
        // если нету свободных терминалов пропускаем
        if (!$terminals) {
            if ($ter->config['LOG_ENABLED']) DebMes("Terminal is busy. See properties TerminalState in object " . $terminals, 'terminals');
            continue;
        }

        $terminal = SQLSelectOne("SELECT * FROM terminals WHERE LINKED_OBJECT = '" . $terminals . "'");
        
        // если пустой терминал пропускаем
        if (!$terminal['ID']) { 
            DebMes("Cannot find terminal for this object - " . $terminals . ". Object must be deleted.", 'terminals');
            continue;
        }
        // если терминал не воспроизводит сообщения то пропускаем его в этой итерации
        if (!$terminal['CANTTS']) { 
            continue;
        }
		
        // если в терминале отсутствует привязанный обьект или терминал отключен от воспроизведения то выведем ошибку 
        if (!$terminal['LINKED_OBJECT'] OR !$terminal['TTS_TYPE']) {
            if ($ter->config['LOG_ENABLED']) DebMes("Cannot find link object or cannot play message for this terminal - " . str_ireplace("terminal_", "", $terminals) . ". Please re-save this terminal for proper operation.", 'terminals');
            $params               = array();
            if (!$terminal['LINKED_OBJECT']) {
                $params["ERROR"]      = 'Терминал ' . str_ireplace("terminal_", "", $terminals) .' не имеет привязанного обьекта. Для дальнейшей работы терминала необходимо пересохранить его в модуле Терминалы';
            } else if (!$terminal['LINKED_OBJECT']) {
                $params["ERROR"]      = 'Терминал ' . str_ireplace("terminal_", "", $terminals) .' отключен для воспроизведения сообщения (непонятно почему попал в список). Для дальнейшей работы терминала необходимо пересохранить его в модуле Терминалы';
            }
            callMethodSafe($terminals . '.MessageError', $params);
            continue;
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
            try {
                if ($ter->config['LOG_ENABLED']) DebMes("PingSafe terminal " . $terminal['NAME'], 'terminals');
                //установим флаг занятости терминала 
                sg($terminal['LINKED_OBJECT'] . '.TerminalState', 1);
                pingTerminalSafe($terminal['NAME'], $terminal);
                continue;
            }
            catch (Exception $e) {
                if ($ter->config['LOG_ENABLED']) DebMes("ОШИБКА!!! Пингование терминала " . $terminal['NAME'] . " завершилось ошибкой", 'terminals');
            }
        }
        
        // берем первоочередное сообщение  
        $old_message = SQLSelectOne("SELECT * FROM shouts WHERE ID <= '" . $number_message . "' AND SOURCE LIKE '%" . $terminal['ID'] . "^%' ORDER BY ID ASC");
        
        // если отсутствует сообщение и есть инфа для восстановления состояния терминала или вопросизведения тормеинала то восстанавливаем состояние
        // и переходим на следующий свободный терминал
        if (!$old_message['ID'] OR !$old_message['SOURCE']) {
            if ($terminal['IS_ONLINE'] AND (gg($terminal['LINKED_OBJECT'] . '.playerdata') OR gg($terminal['LINKED_OBJECT'] . '.terminaldata'))) {
                try {
                    sg($terminal['LINKED_OBJECT'] . '.TerminalState', 1);
                    restore_terminal_stateSafe($terminal);
                    continue;
                }
                catch (Exception $e) {
                    if ($ter->config['LOG_ENABLED']) DebMes("ОШИБКА!!! Восстановление медиаконтента на  терминале - " . $terminal['NAME'] . " завершилось ошибкой", 'terminals');
                }
            }
        } else if (!$old_message['SOURCE'] OR !$old_message['MESSAGE']) {
            // если нечего восстанавливать просто пропускаем итерацию - 
            // иногда попадаются пустые записи ИД терминалов
            continue;
        }
        
        // если есть сообщение НО терминал оффлайн удаляем из работы эту запись 
        // и пропускаем (пингуется дополнительно - если вернется с ошибкой отправления)
        if ($old_message['ID'] AND !$terminal['IS_ONLINE']) {
            try {
                $old_message['SOURCE'] = str_replace($terminal['ID'] . '^', '', $old_message['SOURCE']);
                SQLUpdate('shouts', $old_message);
                if ($ter->config['LOG_ENABLED']) DebMes("Disable message - " . $terminal['NAME'], 'terminals');
                $params               = array();
                $params['NAME']       = $terminal['NAME'];
                $params["MESSAGE"]    = $old_message['MESSAGE'];
                $params["ERROR"]      = 'Терминал ушел в офлайн. Сообщение удалено';
                $params["IMPORTANCE"] = $old_message['IMPORTANCE'];
                callMethodSafe($terminals . '.MessageError', $params);
                continue;
            }
            catch (Exception $e) {
                if ($ter->config['LOG_ENABLED']) DebMes("ОШИБКА!!! Ввыполнение метода MessageError с ошибкой 'Терминал ушел в офлайн. Сообщение удалено' на  терминале - " . $terminal['NAME'] . " завершилось ошибкой", 'terminals');
            }
        }
        
        // если есть сообщение НО не сгенерирован звук (остутсвует в информации о сообщении запись) в течении 2 минут 
        // удаляем сообщение из очереди для терминалов воспроизводящих звук
        if ($old_message['CACHED_FILENAME'] AND strtotime($old_message['ADDED']) + 2 * 60 < time() AND method_exists($tts[$terminal['ID']], 'say_media_message')) {
            try {
                $old_message['SOURCE'] = str_replace($terminal['ID'] . '^', '', $old_message['SOURCE']);
                SQLUpdate('shouts', $old_message);
                if ($ter->config['LOG_ENABLED']) DebMes("Disable message not generated sound  - " . $terminal['NAME'], 'terminals');
                $params               = array();
                $params['NAME']       = $terminal['NAME'];
                $params["MESSAGE"]    = $old_message['MESSAGE'];
                $params["ERROR"]      = 'Не сгенерирован звук модулем генератора речи для голосового терминала в течении 2 минут. Сообщение удалено';
                $params["IMPORTANCE"] = $old_message['IMPORTANCE'];
                callMethodSafe($terminals . '.MessageError', $params);
                continue;
            }
            catch (Exception $e) {
                if ($ter->config['LOG_ENABLED']) DebMes("ОШИБКА!!! Ввыполнение метода MessageError с ошибкой 'Не сгенерирован звук модулем генератора речи для голосового терминала в течении 2 минут. Сообщение удалено' на  терминале - " . $terminal['NAME'] . " завершилось ошибкой", 'terminals');
            }
        }
        
        // если есть сообщение и есть запись о существовании файла НО не сгенерирован звук (отсутсвтует файл)
        // удаляем сообщение из очереди для терминалов воспроизводящих звук
        if ($old_message['CACHED_FILENAME'] AND !file_exists($old_message['CACHED_FILENAME']) AND method_exists($tts[$terminal['ID']], 'say_media_message')) {
            try {
                $old_message['SOURCE'] = str_replace($terminal['ID'] . '^', '', $old_message['SOURCE']);
                SQLUpdate('shouts', $old_message);
                if ($ter->config['LOG_ENABLED']) DebMes("Disable message not generated sound  - " . $terminal['NAME'], 'terminals');
                $params               = array();
                $params['NAME']       = $terminal['NAME'];
                $params["MESSAGE"]    = $old_message['MESSAGE'];
                $params["ERROR"]      = 'Отсутствует сгенерированный файл аудио сообщения. Для голосового терминала. Сообщение удалено';
                $params["IMPORTANCE"] = $old_message['IMPORTANCE'];
                callMethodSafe($terminals . '.MessageError', $params);
                continue;
            }
            catch (Exception $e) {
                if ($ter->config['LOG_ENABLED']) DebMes("ОШИБКА!!! Ввыполнение метода MessageError с ошибкой 'Отсутствует сгенерированный файл аудио сообщения. Для голосового терминала. Сообщение удалено' на  терминале - " . $terminal['NAME'] . " завершилось ошибкой", 'terminals');
            }
        }

        // если тип терминала передающий только текстовое сообщение  
        // запускаем его воспроизведение
        if (method_exists($tts[$terminal['ID']], 'say_message') AND $old_message['SOURCE']) {
            try {
                // убираем запись айди терминала из таблицы шутс - если не воспроизведется то вернет эту запись функция send_message($old_message, $terminal);
                $old_message['SOURCE'] = str_replace($terminal['ID'] . '^', '', $old_message['SOURCE']);
                SQLUpdate('shouts', $old_message);
                //записываем что терминал занят
                sg($terminal['LINKED_OBJECT'] . '.TerminalState', 1);
                //передаем сообщение на терминал передающий только текстовое сообщение 
                send_messageSafe($old_message, $terminal);
                if ($ter->config['LOG_ENABLED']) DebMes("Send message with text to terminal - " . $terminal['NAME'], 'terminals');
                continue;
            }
            catch (Exception $e) {
                if ($ter->config['LOG_ENABLED']) DebMes("ОШИБКА!!! Передача текстового сообщения на  терминале - " . $terminal['NAME'] . " с типом терминала- " . $terminal['TTS_TYPE'] . " завершилось ошибкой", 'terminals');
            }
        }
	    
        // если тип терминала воспроизводящий аудио и нету еще сгенерированного файла пропускаем
        if (method_exists($tts[$terminal['ID']], 'say_media_message') AND !$old_message['CACHED_FILENAME']) {
            continue;
        }
        
        // если тип терминала передающий медиа сообщение
        // иначе запускаем его воспроизведение
        if (method_exists($tts[$terminal['ID']], 'say_media_message') AND $old_message['CACHED_FILENAME'] AND $old_message['SOURCE']) {
            try {
                // убираем запись айди терминала из таблицы шутс - если не воспроизведется то вернет эту запись функция send_message($old_message, $terminal);
                $old_message['SOURCE'] = str_replace($terminal['ID'] . '^', '', $old_message['SOURCE']);
                SQLUpdate('shouts', $old_message);
                //записываем что терминал занят
                sg($terminal['LINKED_OBJECT'] . '.TerminalState', 1);
                //передаем сообщение на терминалы воспроизводящие аудио
                send_messageSafe($old_message, $terminal);
                if ($ter->config['LOG_ENABLED']) DebMes("Send message with media to terminal - " . $terminal['NAME'], 'terminals');
                continue;
            }
            catch (Exception $e) {
                if ($ter->config['LOG_ENABLED']) DebMes("ОШИБКА!!! Передача аудио сообщения на  терминале - " . $terminal['NAME'] . " с типом терминала- " . $terminal['TTS_TYPE'] . " завершилось ошибкой", 'terminals');
            }
        }
        
    }
    
    if (file_exists('./reboot') || IsSet($_GET['onetime'])) {
        if ($ter->config['LOG_ENABLED']) DebMes("Цикл перезапущен по команде ребут от сервера ", 'terminals');
        exit;
    }
}
DebMes("Unexpected close of cycle: " . basename(__FILE__));
if ($ter->config['LOG_ENABLED']) DebMes("Цикл неожиданно завершился по неизвестной причине", 'terminals');
