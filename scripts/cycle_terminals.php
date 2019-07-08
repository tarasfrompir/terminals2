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

// set all message cheked
SQLExec("UPDATE shouts SET CHEKED='1'");

// set all terminal as free when restart cycle
$terminals = SQLSelect("SELECT * FROM terminals");
foreach ($terminals as $terminal) {
	 sg($terminal['LINKED_OBJECT'] . '.BASY', 0);
}

while (1) {
    if (time() - $checked_time > 10) {
        $checked_time = time();
        setGlobal((str_replace('.php', '', basename(__FILE__))) . 'Run', time(), 1);
    }
    //время жизни сообщений
    if (time() - $clear_message > 180) {
        $clear_message = time();
        SQLExec("UPDATE shouts SET SOURCE='' WHERE ADDED< (NOW() - INTERVAL 3 MINUTE)");
        SQLExec("UPDATE shouts SET CHEKED='1' WHERE ADDED< (NOW() - INTERVAL 3 MINUTE) ");
    }
    // отправка только текстовых сообщений
    $message = SQLSelectOne("SELECT * FROM shouts WHERE SOURCE LIKE '%^' AND FILE_LINK='' AND CHEKED = '0' ORDER BY ID ASC");
    if ($message) {
		$generatetts = false;
        $out_terminals = explode("^", $message['SOURCE']);
        foreach ($out_terminals as $terminals) {
            $terminal = SQLSelectOne("SELECT * FROM terminals WHERE ID = '" . $terminals . "'");
            // pinguem terminal
            if (!$terminal['IS_ONLINE']) {
                pingTerminalSafe($terminal['NAME']);
            }
            //DebMes('Проверяем наличие файла для запуска отделный поток для терминала ' . $terminal['ID'] . ' ' . microtime(true), 'terminals2');
            // запускаем все что имеет function sayttotext
            if (file_exists(DIR_MODULES . 'app_player/addons/' . $terminal['PLAYER_TYPE'] . '.addon.php')) {
                if ($terminal['IS_ONLINE'] AND $terminal['CANPLAY'] AND $terminal['CANTTS'] AND $message['IMPORTANCE'] >= $terminal['MIN_MSG_LEVEL']) {
                    if (strpos(file_get_contents(DIR_MODULES . 'app_player/addons/' . $terminal['PLAYER_TYPE'] . '.addon.php'), "function sayttotext")) {
                        //DebMes('Запускаем очередь в отделный поток для soobcsheniya ' . $message['MESSAGE'] . ' ' . microtime(true), 'terminals2');
                        sayToTextSafe($message['ID'], $terminal['ID']);
						$message['SOURCE'] = str_replace($terminal['ID'] . '^', '', $message['SOURCE']);
                        //sayToText($message['ID'], $terminal['ID']);
                        //DebMes('Ochered zapushena для soobcsheniya ' . $message['MESSAGE'] . ' ' . microtime(true), 'terminals2');
                    } else {
                        $generatetts = true;
						DebMes('$generatetts'.$generatetts);
					}						
                }
            }
            
        }
		if ($generatetts) {
			processSubscriptionsSafe($message['EVENT'], array('level' => $message['IMPORTANCE'], 'message' => $message['MESSAGE'], 'id' => $message['ID']));
		}
        // pomechaem chto obrabotano i zapusheno na generaciyu rechi i obnovlyaem v baze
        $message['CHEKED'] = '1';
        SQLUpdate('shouts', $message);
    }
    usleep(500000);
    // отправка сообщений сгенерированных ТТС
    $message = SQLSelectOne("SELECT * FROM shouts WHERE SOURCE LIKE '%^' AND FILE_LINK != '' AND CHEKED = '1' ORDER BY ID ASC");
    if ($message) {
        $out_terminals = explode("^", $message['SOURCE']);
        foreach ($out_terminals as $terminals) {
            $terminal = SQLSelectOne("SELECT * FROM terminals WHERE ID = '" . $terminals . "'");
            //DebMes('Проверяем наличие файла для запуска отделный поток для терминала ' . $terminal['ID'] . ' ' . microtime(true), 'terminals2');
            // запускаем все что имеет function sayttotext
            if (file_exists(DIR_MODULES . 'app_player/addons/' . $terminal['PLAYER_TYPE'] . '.addon.php') AND !gg($terminal['LINKED_OBJECT'] . '.BASY')) {
                if ($terminal['IS_ONLINE'] AND $terminal['CANPLAY'] AND $terminal['CANTTS'] AND $message['IMPORTANCE'] >= $terminal['MIN_MSG_LEVEL']) {
                    if (strpos(file_get_contents(DIR_MODULES . 'app_player/addons/' . $terminal['PLAYER_TYPE'] . '.addon.php'), "function sayToMedia")) {
                        sg($terminal['LINKED_OBJECT'] . '.BASY', 1);
                        sayTToMediaSafe($message['ID'], $terminal['ID']);
						$message['SOURCE'] = str_replace($terminal['ID'] . '^', '', $message['SOURCE']);
                        //sayToText($message['ID'], $terminal['ID']);
                        //DebMes('Ochered zapushena для soobcsheniya ' . $message['MESSAGE'] . ' ' . microtime(true), 'terminals2');
                    }
                }
            }
        }
        SQLUpdate('shouts', $message);
    }

    if (file_exists('./reboot') || IsSet($_GET['onetime'])) {
        exit;
    }
}

DebMes("Unexpected close of cycle: " . basename(__FILE__));
