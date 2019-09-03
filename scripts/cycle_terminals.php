<?php

chdir(dirname(__FILE__) . '/../');

include_once './config.php';
include_once './lib/loader.php';
include_once './lib/threads.php';

set_time_limit(0);

include_once("./load_settings.php");

include_once(DIR_MODULES . 'terminals/terminals.class.php');

$terminals = new terminals();

$checked_time = 0;

echo date("H:i:s") . " running " . basename(__FILE__) . PHP_EOL;

// set all message clear
SQLExec("UPDATE shouts SET SOURCE='' ");

// set all terminal as free when restart cycle
$terminals = SQLSelect("SELECT * FROM terminals");
foreach ($terminals as $terminal) {
    sg($terminal['LINKED_OBJECT'] . '.basy', 0);
}

// get number last message
$number_message = SQLSelectOne("SELECT * FROM shouts ORDER BY ID DESC");
$number_message = $number_message['ID'] + 1;
DebMes('Start terminals cycle');
while (1) {
    // time update cicle of terminal
    if (time() - $checked_time > 60) {
        $checked_time = time();
        setGlobal((str_replace('.php', '', basename(__FILE__))) . 'Run', time(), 1);
    }
    //время жизни сообщений
    if (time() - $clear_message > 300) {
        $clear_message = time();
        SQLExec("UPDATE shouts SET SOURCE = '' WHERE ADDED < (NOW() - INTERVAL 5 MINUTE)");
    }
    
    // CHEK next message for terminals ready
    $message = SQLSelectOne("SELECT * FROM shouts WHERE ID='" . $number_message . "'");
    
    if ($message) {
        $number_message = $number_message + 1;
    } else {
        sleep(1);
    }
    // chek all old message and send message to terminals
    $out_terminals = getObjectsByProperty('basy', '==', '0');
    foreach ($out_terminals as $terminals) {
        if (!$terminals) {
            continue;
        }
        $terminal    = SQLSelectOne("SELECT * FROM terminals WHERE LINKED_OBJECT = '" . $terminals . "'");
        $old_message = SQLSelectOne("SELECT * FROM shouts WHERE ID <= '" . $number_message . "' AND SOURCE LIKE '%" . $terminal['ID'] . "^%' ORDER BY ID ASC");
        // если есть сообщение для этого терминала то пускаем его
        if ($old_message) {
            $old_message['SOURCE'] = str_replace($terminal['ID'] . '^', '', $old_message['SOURCE']);
            SQLUpdate('shouts', $old_message);
            // если в состоянии плеера нету данных для восстановления, то запоминаем ее
            if (!gg($terminal['LINKED_OBJECT'] . '.playerdata') AND $terminal['TTS_TYPE'] == 'mediaplayer') {
                $player_state = getPlayerStatus($terminal['NAME']);
                if (is_array($player_state) AND $player_state['file'] AND strpos($player_state['file'], 'cached/voice') == false) {
                    sg($terminal['LINKED_OBJECT'] . '.playerdata', json_encode($player_state));
                }
            }
            sg($terminal['LINKED_OBJECT'] . '.basy', 1);
            send_messageSafe($old_message, $terminal);
        } else if ($restored_info = json_decode(gg($terminal['LINKED_OBJECT'] . '.playerdata'), true) AND $terminal['TTS_TYPE'] == 'mediaplayer') {
            // inache vosstanavlivaem vosproizvodimoe
            stopMedia($terminal['HOST']);
            setPlayerVolume($terminal['HOST'], $restored_info['volume']);
            playMedia($restored_info['file'], $terminal['NAME']);
            seekPlayerPosition($terminal['NAME'], $restored_info['time']);
            sg($terminal['LINKED_OBJECT'] . '.playerdata', '');
        }
    }
    
    if (file_exists('./reboot') || IsSet($_GET['onetime'])) {
        exit;
    }
}

DebMes("Unexpected close of cycle: " . basename(__FILE__));
