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
    // отправка только текстовых сообщений
    $message = SQLSelectOne("SELECT * FROM shouts WHERE SOURCE LIKE '%^%' AND FILE_LINK='' ORDER BY ID ASC");
    if ($message) {
        $out_terminals = explode("^", $message['SOURCE']);
        foreach ($out_terminals as $terminals) {
            $terminal = SQLSelectOne("SELECT * FROM terminals WHERE ID = '" . $terminals . "'");
			// pinguem terminal
			if (!$terminal['IS_ONLINE'] ) {
                pingTerminalSafe($terminal['NAME']);
            }
            //DebMes('Проверяем наличие файла для запуска отделный поток для терминала ' . $terminal['ID'] . ' ' . microtime(true), 'terminals2');
            // запускаем все что имеет function sayttotext
            if (file_exists(DIR_MODULES . 'app_player/addons/' . $terminal['PLAYER_TYPE'] . '.addon.php') ) {
                if (strpos(file_get_contents(DIR_MODULES . 'app_player/addons/' . $terminal['PLAYER_TYPE'] . '.addon.php'), "function sayttotext")) {
	                if ($terminal['IS_ONLINE']  AND $terminal['CANPLAY'] AND $terminal['CANTTS']) {
                        //DebMes('Запускаем очередь в отделный поток для soobcsheniya ' . $message['MESSAGE'] . ' ' . microtime(true), 'terminals2');
                        sayToTextSafe($message['ID'], $terminal['ID']);
                        //sayToText($message['ID'], $terminal['ID']);
                        //DebMes('Ochered zapushena для soobcsheniya ' . $message['MESSAGE'] . ' ' . microtime(true), 'terminals2');
					}
					$message['SOURCE'] = str_replace($terminal['ID'] . '^', '', $message['SOURCE']);
                } else {
					$message['SOURCE'] = str_replace($terminal['ID'] . '^', '', $message['SOURCE']);
				}
				
			}

        }
        //processSubscriptionsSafe($message['EVENT'], array('level' => $message['IMPORTANCE'], 'message' => $message['MESSAGE'], 'id' => $message['ID']));
        SQLUpdate('shouts', $message);
    }
    // отправка сообщений сгенерированных ТТС
	$message = SQLSelectOne("SELECT * FROM shouts WHERE SOURCE LIKE '%^%' AND FILE_LINK != '' ORDER BY ID ASC");
    if ($message) {
        $out_terminals = explode("^", $message['SOURCE']);
        foreach ($out_terminals as $terminals) {
            $terminal = SQLSelectOne("SELECT * FROM terminals WHERE ID = '" . $terminals . "'");
            //DebMes('Проверяем наличие файла для запуска отделный поток для терминала ' . $terminal['ID'] . ' ' . microtime(true), 'terminals2');
            // запускаем все что имеет function sayttotext
            if (file_exists(DIR_MODULES . 'app_player/addons/' . $terminal['PLAYER_TYPE'] . '.addon.php' )  AND !gg($terminal['LINKED_OBJECT'].'.BASY')) {
                if (strpos(file_get_contents(DIR_MODULES . 'app_player/addons/' . $terminal['PLAYER_TYPE'] . '.addon.php'), "function sayToMedia") ) {
					if ($terminal['IS_ONLINE']  AND $terminal['CANPLAY'] AND $terminal['CANTTS']) {
						sg ($terminal['LINKED_OBJECT'].'.BASY',1);
                        sayTToMediaSafe($message['ID'], $terminal['ID']);
                        //sayToText($message['ID'], $terminal['ID']);
                        //DebMes('Ochered zapushena для soobcsheniya ' . $message['MESSAGE'] . ' ' . microtime(true), 'terminals2');
					}
                    $message['SOURCE'] = str_replace($terminal['ID'] . '^', '', $message['SOURCE']);
                } else {
					$message['SOURCE'] = str_replace($terminal['ID'] . '^', '', $message['SOURCE']);
				}
				
            }
		
        }
        SQLUpdate('shouts', $message);
    }
    //DebMes( 'term id'.$terminal['ID']);
    //DebMes(gg ('terminal4.BASY'));
    usleep(500000);
    if (file_exists('./reboot') || IsSet($_GET['onetime'])) {
        exit;
    }
}

DebMes("Unexpected close of cycle: " . basename(__FILE__));
