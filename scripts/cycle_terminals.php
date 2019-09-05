<?php

chdir(dirname(__FILE__) . '/../');
include_once("./config.php");
include_once("./lib/loader.php");
include_once("./lib/threads.php");
set_time_limit(0);

include_once("./load_settings.php");

echo date("H:i:s") . " Running " . basename(__FILE__) . PHP_EOL;

echo date("H:i:s") . " Init module " . PHP_EOL;

$checked_time = 0;

// set all terminal as free when restart cycle
$terminalss = getObjectsByProperty('basy', '==', '1');
foreach ($terminalss as $terminals) {
	if (!$terminals ) {
            continue;
	}
    $terminal = SQLSelectOne("SELECT * FROM terminals WHERE LINKED_OBJECT = '" . $terminals . "'");
    sg($terminal['LINKED_OBJECT'] . '.basy', 0);
}

//SQLExec("UPDATE shouts SET SOURCE = '' ");
		
// get number last message
$number_message = SQLSelectOne("SELECT * FROM shouts ORDER BY ID DESC");
$number_message = $number_message['ID'] + 1;

DebMes('Start terminals cycle');
while (1) {
    // time update cicle of terminal
    if (time() - $checked_time > 10) {
        $checked_time = time();
        setGlobal((str_replace('.php', '', basename(__FILE__))) . 'Run', time(), 1);
    }
    //время жизни сообщений
    if (time() - $clear_message > 60) {
        $clear_message = time();
        SQLExec("UPDATE shouts SET SOURCE = '' WHERE ADDED < (NOW() - INTERVAL 1 MINUTE)");
    }
    
    // CHEK next message for terminals ready
    $message = SQLSelectOne("SELECT 1 FROM shouts WHERE ID = '" . $number_message . "'");
    
    if ($message) {
        $number_message = $number_message + 1;
    } else {
         usleep(500000);
    }
    // chek all old message and send message to terminals
    $out_terminals = getObjectsByProperty('basy', '==', '0');
    foreach ($out_terminals as $terminals) {
        if (!$terminals ) {
            continue;
	}
        $terminal = SQLSelectOne("SELECT * FROM terminals WHERE LINKED_OBJECT = '" . $terminals . "'");
        $old_message = SQLSelectOne("SELECT * FROM shouts WHERE ID <= '" . $number_message . "' AND SOURCE LIKE '%" . $terminal['ID'] . "^%' ORDER BY ID ASC");
        // если есть сообщение для этого терминала то пускаем его
        if ($old_message['ID'] AND $terminal['IS_ONLINE']) {
            // если в состоянии плеера нету данных для восстановления, то запоминаем ее
            if (!gg($terminal['LINKED_OBJECT'] . '.playerdata') AND $terminal['TTS_TYPE'] == 'mediaplayer') {
                $player_state = getPlayerStatus($terminal['NAME']);
                if (is_array($player_state) AND $player_state['file'] AND strpos($player_state['file'], 'cached/voice') == false) {
                    sg($terminal['LINKED_OBJECT'] . '.playerdata', json_encode($player_state));
                }
            }
	    if (($terminal['TTS_TYPE'] == 'mediaplayer' OR $terminal['TTS_TYPE'] == 'mainterminal') AND !$old_message['CACHED_FILENAME']) {
		usleep(100000);
	        continue;
	    }
            // убираем запись айди терминала из таблицы шутс - если не воспроизведется то вернет эту запись функция send_message($old_message, $terminal);
            $old_message['SOURCE'] = str_replace($terminal['ID'] . '^', '', $old_message['SOURCE']);
            SQLUpdate('shouts', $old_message);
	    sg($terminal['LINKED_OBJECT'] . '.basy', 1);
            send_messageSafe($old_message, $terminal);
        // если же терминал отпингован и к нему нету доступа то удаляем его из очереди
        } else if ($old_message['ID'] AND !$terminal['IS_ONLINE']) {
            $old_message['SOURCE'] = str_replace($terminal['ID'] . '^', '', $old_message['SOURCE']);
            SQLUpdate('shouts', $old_message);
        } else if ($restored_info = json_decode(gg($terminal['LINKED_OBJECT'] . '.playerdata'), true) AND $terminal['TTS_TYPE'] == 'mediaplayer' AND $terminal['IS_ONLINE']) {
            // inache vosstanavlivaem vosproizvodimoe
            stopMedia($terminal['HOST']);
	   // восстанавливаем громкость если необходимо
            if ($restored_info['volume'] != $terminal['TERMINAL_VOLUME_LEVEL']) {
                setPlayerVolume($terminal['HOST'], $restored_info['volume']);
	    }
            playMedia($restored_info['file'], $terminal['NAME']);
            seekPlayerPosition($terminal['NAME'], $restored_info['time']);
            sg($terminal['LINKED_OBJECT'] . '.playerdata', '');
        } else if ($restored_info = json_decode(gg($terminal['LINKED_OBJECT'] . '.playerdata'), true) AND $terminal['TTS_TYPE'] == 'mediaplayer' AND !$terminal['IS_ONLINE']) {
            sg($terminal['LINKED_OBJECT'] . '.playerdata', '');			
		}

        usleep(200000);
	}
    
    if (file_exists('./reboot') || IsSet($_GET['onetime'])) {
        exit;
    }
}

DebMes("Unexpected close of cycle: " . basename(__FILE__));

