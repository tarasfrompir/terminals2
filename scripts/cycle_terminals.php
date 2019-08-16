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
SQLExec("UPDATE shouts SET SOURCE='' ");
SQLExec("UPDATE shouts SET CHEKED='1'");
		
// set all terminal as free when restart cycle
$terminals = SQLSelect("SELECT * FROM terminals");
foreach ($terminals as $terminal) {
    sg($terminal['LINKED_OBJECT'] . '.BASY', 0);
} 

// get number last message
//$message = SQLSelectOne("SELECT * FROM shouts ORDER BY ID DESC");
$number_message = SQLSelectOne("SELECT * FROM shouts ORDER BY ID DESC")['ID'];
$number_message = $number_message + 1;
echo 'start nomber '.$number_message.PHP_EOL;
while (1) {
	// time update cicle of terminal
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

	// CHEK next message for terminals ready
    $message = SQLSelectOne("SELECT * FROM shouts WHERE ID='" . $number_message ."'");

	if ($message and $message['CHEKED'] == 1 ) {
	    $number_message = $number_message + 1;
		usleep(100000);
        //DebMes($number_message);
		echo $number_message.PHP_EOL;
	
	}
	
  	// chek all old message and send message to terminals
    $out_terminals = getObjectsByProperty('BASY', '==', '0');
	foreach ($out_terminals as $terminals) {
		if (!$terminals) {
			continue;
		}
		$terminal = SQLSelectOne("SELECT * FROM terminals WHERE LINKED_OBJECT = '" . $terminals . "'");
		$old_message = SQLSelectOne("SELECT * FROM shouts WHERE ID <= '" . $number_message ."' AND SOURCE LIKE '%" . $terminal['ID'] . "^%' AND CHEKED = '1' ORDER BY ID ASC");
	
		// запускаем все что имеет function sayttotext
		if ($old_message) {
			sg($terminal['LINKED_OBJECT'] . '.BASY', 1);
			$old_message['SOURCE'] = str_replace($terminal['ID'] . '^', '', $old_message['SOURCE']);
			send_message_to_terminalSafe($old_message, $terminal);
		}
		if ($old_message) {
			SQLUpdate('shouts', $old_message);
		}
	}   

    
    if (file_exists('./reboot') || IsSet($_GET['onetime'])) {
        exit;
    }
}

DebMes("Unexpected close of cycle: " . basename(__FILE__));
