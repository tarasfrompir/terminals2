<?php
chdir(dirname(__FILE__) . '/../');
include_once("./config.php");
include_once("./lib/loader.php");
include_once("./lib/threads.php");
set_time_limit(0);
include_once("./load_settings.php");
echo date("H:i:s") . " Running " . basename(__FILE__) . PHP_EOL;
echo date("H:i:s") . " Init module " . PHP_EOL;
include_once(DIR_MODULES . "terminals/terminals.class.php");
$ter = new terminals();
$checked_time = 0;
// set all terminal as free when restart cycle
$term = SQLSelect("SELECT * FROM terminals");
foreach ($term as $t) {
    sg($t['LINKED_OBJECT'] . '.BASY', 0);
}
//SQLExec("UPDATE shouts SET SOURCE = '' ");
// get number last message
$number_message = SQLSelectOne("SELECT * FROM shouts ORDER BY ID DESC");
$number_message = $number_message['ID'] + 1;
DebMes("Start number message - " . $number_message, 'terminals');
DebMes('Start terminals cycle');
// берем конфигурацию с модуля терминалов
$ter->getConfig();
if ($ter->config['TERMINALS_TIMEOUT']) {
	$terminals_time_out = $ter->config['TERMINALS_TIMEOUT'];
} else {
    $terminals_time_out = 10;
}
if ($ter->config['LOG_ENABLED']) DebMes("Get timeout for message - " . $terminals_time_out. " minutes", 'terminals');
while (1) {
    // time update cicle of terminal
    if (time() - $checked_time > 10) {
        $checked_time = time();
        setGlobal((str_replace('.php', '', basename(__FILE__))) . 'Run', time(), 1);
    }
    //время жизни сообщений
    if (time() - $clear_message > 60*$terminals_time_out) {
        $clear_message = time();
        SQLExec("UPDATE shouts SET SOURCE = '' WHERE ADDED < (NOW() - INTERVAL ".$terminals_time_out." MINUTE)");
		// когда в настройках терминалов изменили таймаут для сообщений то получаем по новой
		$ter->getConfig();
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
    
    $out_terminals = getObjectsByProperty('basy', '==', '0');
    foreach ($out_terminals as $terminals) {
        // если пустой терминал пропускаем
        if (!$terminals) {
            if ($ter->config['LOG_ENABLED']) DebMes("No free terminal " . $terminals, 'terminals');
            continue;
        }
        $terminal = SQLSelectOne("SELECT * FROM terminals WHERE LINKED_OBJECT = '" . $terminals . "'");
        // если пустая инфа о терминале пропускаем
        if (!$terminal) {
            if ($ter->config['LOG_ENABLED']) DebMes("No information of terminal" . $terminal['NAME'], 'terminals');
            continue;
        }
        $old_message = SQLSelectOne("SELECT * FROM shouts WHERE ID <= '" . $number_message . "' AND SOURCE LIKE '%" . $terminal['ID'] . "^%' ORDER BY ID ASC");
        // если отсутствует сообщение и тип плеер и есть инфа для восстановления то восстанавливаем воспроизводимое
        if (!$old_message['MESSAGE'] AND $terminal['TTS_TYPE'] == 'mediaplayer') {
            try {
                $restored = json_decode(gg($terminal['LINKED_OBJECT'] . '.playerdata'), true);
                if (is_array($restored)) {
                    if ($ter->config['LOG_ENABLED']) DebMes("Restore volume on the terminal - " . $terminal['NAME'], 'terminals');
                    setPlayerVolume($terminal['NAME'], $restored['volume']);
                    // если есть файл для воспроизведения то тоже его восстанавливаем
                    if ($restored['file']) {
                        playMedia($restored['file'], $terminal['NAME']);
                        if ($ter->config['LOG_ENABLED']) DebMes("Restore media on the terminal - " . $terminal['NAME'], 'terminals');
                    }
                }
                sg($terminal['LINKED_OBJECT'] . '.playerdata', '');
                continue;
            }
            catch (Exception $e) {
                if ($ter->config['LOG_ENABLED']) DebMes("Error with restore playaed - " . $terminal['NAME'], 'terminals');
            }
        }
        // для остальных плееров просто пропускаем итерацию и при отсутствии сообщения 
        if (!$old_message['MESSAGE']) {
            continue;
        }
        // если терминал оффлайн удаляем из работы эту запись и пропускаем (пингуется дополнительно - если вернется с ошибкой отправления)
        if (!$terminal['IS_ONLINE']) {
            $old_message['SOURCE'] = str_replace($terminal['ID'] . '^', '', $old_message['SOURCE']);
            SQLUpdate('shouts', $old_message);
            if ($ter->config['LOG_ENABLED']) DebMes("Disable message - " . $terminal['NAME'], 'terminals');
            continue;
        }
        // если тип терминала воспроизводящий аудио и нету еще сгенерированного файла пропускаем 
        if (($terminal['TTS_TYPE'] == 'mediaplayer' OR $terminal['TTS_TYPE'] == 'mainterminal' OR $terminal['TTS_TYPE'] == 'alicevox') AND !$old_message['CACHED_FILENAME']) {
            continue;
        } else if (($terminal['TTS_TYPE'] == 'mediaplayer' OR $terminal['TTS_TYPE'] == 'mainterminal' OR $terminal['TTS_TYPE'] == 'alicevox') AND $old_message['CACHED_FILENAME']) {
            // иначе запускаем его воспроизведение
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
        if ($terminal['TTS_TYPE'] == 'majordroid' OR $terminal['TTS_TYPE'] == 'telegramm' OR $terminal['TTS_TYPE'] == 'googlehomenotifier') {
            // запускаем его воспроизведение
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
