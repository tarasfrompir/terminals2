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
$term = SQLSelect("SELECT * FROM terminals");
foreach ($term as $t) {
    sg($t['LINKED_OBJECT'] . '.BASY', 0);
} 

//SQLExec("UPDATE shouts SET SOURCE = '' ");
// get number last message
$number_message = SQLSelectOne("SELECT * FROM shouts ORDER BY ID DESC");
$number_message = $number_message['ID'] + 1;
DebMes("Max nomber message - " . $number_message, 'terminals');
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
        DebMes("Max nomber message - " . $number_message, 'terminals');
    } else {
        sleep(1);
    }
    
    $out_terminals = getObjectsByProperty('basy', '==', '0');
    DebMes("Array of free terminal ".serialize($out_terminals) , 'terminals');
    foreach ($out_terminals as $terminals) {
        // если пустой терминал пропускаем
        if (!$terminals) {
            DebMes("No free terminals", 'terminals');
            continue;
        }
        $terminal    = SQLSelectOne("SELECT * FROM terminals WHERE LINKED_OBJECT = '" . $terminals . "'");
        $old_message = SQLSelectOne("SELECT * FROM shouts WHERE ID <= '" . $number_message . "' AND SOURCE LIKE '%" . $terminal['ID'] . "^%' ORDER BY ID ASC");
        // если отсутствует сообщение пропускаем
        if (!$old_message['MESSAGE']) {
            continue;
        }
        // если терминал оффлайн удаляем из работы эту запись и пропускаем (пингуется дополнительно - если вернется с ошибкой отправления)
        if (!$terminal['IS_ONLINE']) {
            $old_message['SOURCE'] = str_replace($terminal['ID'] . '^', '', $old_message['SOURCE']);
            SQLUpdate('shouts', $old_message);
            DebMes("Disable message - " . $terminal['NAME'], 'terminals');
            continue;
        }
        // если тип терминала воспроизводящий аудио и нету еще сгенерированного файла пропускаем 
        if (($terminal['TTS_TYPE'] == 'mediaplayer' OR $terminal['TTS_TYPE'] == 'mainterminal') AND !$old_message['CACHED_FILENAME']) {
            continue;
        } else if (($terminal['TTS_TYPE'] == 'mediaplayer' OR $terminal['TTS_TYPE'] == 'mainterminal') AND $old_message['CACHED_FILENAME']) {
            // иначе запускаем его воспроизведение
            // убираем запись айди терминала из таблицы шутс - если не воспроизведется то вернет эту запись функция send_message($old_message, $terminal);
            $old_message['SOURCE'] = str_replace($terminal['ID'] . '^', '', $old_message['SOURCE']);
            SQLUpdate('shouts', $old_message);
            //записываем что терминал занят
            sg($terminal['LINKED_OBJECT'] . '.basy', 1);
            //передаем сообщение на терминалы воспроизводящие аудио
            send_messageSafe($old_message, $terminal);
            DebMes("Send message - " . $terminal['NAME'], 'terminals');
        }
        // если тип терминала передающий только текстовое сообщение  
        if (!$terminal['TTS_TYPE'] == 'mediaplayer' OR !$terminal['TTS_TYPE'] == 'mainterminal') {
            // запускаем его воспроизведение
            // убираем запись айди терминала из таблицы шутс - если не воспроизведется то вернет эту запись функция send_message($old_message, $terminal);
            $old_message['SOURCE'] = str_replace($terminal['ID'] . '^', '', $old_message['SOURCE']);
            SQLUpdate('shouts', $old_message);
            //записываем что терминал занят
            sg($terminal['LINKED_OBJECT'] . '.basy', 1);
            //передаем сообщение на терминал передающий только текстовое сообщение 
            send_messageSafe($old_message, $terminal);
            DebMes("Send message - " . $terminal['NAME'], 'terminals');
        }
    }
    DebMes('Start terminals cycle');
    
    if (file_exists('./reboot') || IsSet($_GET['onetime'])) {
        exit;
    }
}
DebMes("Unexpected close of cycle: " . basename(__FILE__));
