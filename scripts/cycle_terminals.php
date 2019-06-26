<?php

chdir(dirname(__FILE__) . '/../');

include_once './config.php';
include_once './lib/loader.php';
include_once './lib/threads.php';

set_time_limit(0);

include_once("./load_settings.php");
include_once(DIR_MODULES . "control_modules/control_modules.class.php");

$ctl = new control_modules();

include_once(DIR_MODULES . 'terminals/terminals.class.php');

$terminals = new terminals();

$checked_time = 0;

echo date("H:i:s") . " running " . basename(__FILE__) . PHP_EOL;

setGlobal((str_replace('.php', '', basename(__FILE__))) . 'Run', time(), 1);

while (1) {
    if (time() - $checked_time > 30) {
        $checked_time = time();
        setGlobal((str_replace('.php', '', basename(__FILE__))) . 'Run', time(), 1);
    }
    
    $message = SQLSelectOne("SELECT * FROM shouts WHERE SOURCE LIKE '%^%' AND FILE_LINK='' ORDER BY ID ASC");
    if ($message) {
        $out_terminals = explode("^", $message['SOURCE']);
        foreach ($out_terminals as $terminals) {
            $terminal = SQLSelectOne("SELECT * FROM terminals WHERE ID = '" . $terminals . "'");
            //DebMes('Проверяем наличие файла для запуска отделный поток для терминала ' . $terminal['ID'] . ' ' . microtime(true), 'terminals2');
            // запускаем все что имеет function sayttotext
            if (file_exists(DIR_MODULES . 'app_player/addons/' . $terminal['PLAYER_TYPE'] . '.addon.php')) {
                if (strpos(file_get_contents(DIR_MODULES . 'app_player/addons/' . $terminal['PLAYER_TYPE'] . '.addon.php'), "function sayttotext")) {
                    DebMes('Запускаем очередь в отделный поток для soobcsheniya ' . $message['MESSAGE'] . ' ' . microtime(true), 'terminals2');
                    sayToTextSafe($message['ID'], $terminal['ID']);
                    //sayToText($message['ID'], $terminal['ID']);
                    DebMes('Ochered zapushena для soobcsheniya ' . $message['MESSAGE'] . ' ' . microtime(true), 'terminals2');
                    $message['SOURCE'] = str_replace($terminal['ID'] . '^', '', $message['SOURCE']);
                }
            } else {
                // sleduyushiy tip terminalov
            }
        }
        processSubscriptionsSafe($message['EVENT'], array('level' => $message['IMPORTANCE'], 'message' => $message['MESSAGE'], 'id' => $message['ID']));
        SQLUpdate('shouts', $message);
    }
	
	$message = SQLSelectOne("SELECT * FROM shouts WHERE SOURCE LIKE '%^%' AND FILE_LINK != '' ORDER BY ID ASC");
    if ($message) {
        $out_terminals = explode("^", $message['SOURCE']);
        foreach ($out_terminals as $terminals) {
            $terminal = SQLSelectOne("SELECT * FROM terminals WHERE ID = '" . $terminals . "'");
            //DebMes('Проверяем наличие файла для запуска отделный поток для терминала ' . $terminal['ID'] . ' ' . microtime(true), 'terminals2');
            // запускаем все что имеет function sayttotext
            if (file_exists(DIR_MODULES . 'app_player/addons/' . $terminal['PLAYER_TYPE'] . '.addon.php')) {
                if (strpos(file_get_contents(DIR_MODULES . 'app_player/addons/' . $terminal['PLAYER_TYPE'] . '.addon.php'), "function sayttotext")) {
                    DebMes('Запускаем очередь в отделный поток для soobcsheniya ' . $message['MESSAGE'] . ' ' . microtime(true), 'terminals2');
                    sayToTextSafe($message['ID'], $terminal['ID']);
                    //sayToText($message['ID'], $terminal['ID']);
                    DebMes('Ochered zapushena для soobcsheniya ' . $message['MESSAGE'] . ' ' . microtime(true), 'terminals2');
                    $message['SOURCE'] = str_replace($terminal['ID'] . '^', '', $message['SOURCE']);
                }
            } else {
                // sleduyushiy tip terminalov
            }
        }
        SQLUpdate('shouts', $message);
    }
    usleep(200000);
    if (file_exists('./reboot') || IsSet($_GET['onetime'])) {
        exit;
    }
}

DebMes("Unexpected close of cycle: " . basename(__FILE__));
