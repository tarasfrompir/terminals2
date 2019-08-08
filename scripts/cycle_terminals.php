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
		SQLExec("UPDATE shouts SET CHEKED='1' WHERE ADDED< (NOW() - INTERVAL 3 MINUTE)");
    }
	
    // отправка сообщений сгенерированных ТТС
    $message = SQLSelectOne("SELECT * FROM shouts WHERE SOURCE LIKE '%^' AND CHEKED='1' ORDER BY ID ASC");
    if ($message) {
        $out_terminals = explode("^", $message['SOURCE']);
        foreach ($out_terminals as $terminals) {
			if (!$terminals) continue;
			$terminal = SQLSelectOne("SELECT * FROM terminals WHERE ID = '" . $terminals . "'");
			// запускаем все что имеет function sayttotext
            if (!gg($terminal['LINKED_OBJECT'] . '.BASY')) {
                sg($terminal['LINKED_OBJECT'] . '.BASY', 1);
                send_message_to_terminalSafe($message, $terminal);
            }
        }
    }
    usleep(300000);
    
    if (file_exists('./reboot') || IsSet($_GET['onetime'])) {
        exit;
    }
}

DebMes("Unexpected close of cycle: " . basename(__FILE__));
