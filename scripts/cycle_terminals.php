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

while (1)
{
   if (time() - $checked_time > 30)
   {
      $checked_time = time();
      setGlobal((str_replace('.php', '', basename(__FILE__))) . 'Run', time(), 1);
   }

    $messages = SQLSelect("SELECT * FROM shouts WHERE SOURCE LIKE '%^%' ORDER BY ID ASC");
	if ($messages) {
		foreach ($messages as $message) {
			$out_terminals = explode("^", $message['SOURCE']);	
			foreach ($out_terminals as $terminals) {
				$terminal = SQLSelectOne("SELECT * FROM terminals WHERE ID = '" . $terminals . "'");
				// проверяем чтобы был файл и function sayttotext и отправляем
                if (file_exists(DIR_MODULES . 'app_player/addons/' . $terminal['PLAYER_TYPE'] . '.addon.php')) {
					if (strpos(file_get_contents(DIR_MODULES . 'app_player/addons/' . $terminal['PLAYER_TYPE'] . '.addon.php'), "function sayttotext")) {
						include_once(DIR_MODULES . 'app_player/addons.php');
						include_once(DIR_MODULES . 'app_player/addons/' . $terminal['PLAYER_TYPE'] . '.addon.php');
						if (class_exists($terminal['PLAYER_TYPE'])) {
							if (is_subclass_of($terminal['PLAYER_TYPE'], 'app_player_addon', TRUE)) {
								$player = new $terminal['PLAYER_TYPE']($terminal);
							}
						}
						
						$out = $player->sayttotext($message['MESSAGE'], $message['EVENT']);
						while (!$out) {
							$out = $player->sayttotext($message['MESSAGE'], $message['EVENT']);
						}
					}
				}
                // конец блока function sayttotext
				
				$message['SOURCE'] = str_replace($terminal['ID'] . '^', "", $message['SOURCE']);
				SQLUpdate('shouts', $message); 
			}
		}
	}
    usleep(500000);
	   if (file_exists('./reboot') || IsSet($_GET['onetime']))
   {
      exit;
   }
}

DebMes("Unexpected close of cycle: " . basename(__FILE__));
