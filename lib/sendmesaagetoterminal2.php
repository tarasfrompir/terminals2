<?php
// check terminal 
function pingTerminal($terminal)
{
    //DebMes("Терминал-".$terminal . ' офлайн и его запускаем на пинг '. microtime(true), 'terminals2');
    $Cheked_terminal = SQLSelectOne("SELECT * FROM terminals WHERE NAME = '" . $terminal . "' OR TITLE = '" . $terminal . "' OR HOST = '" . $terminal . "'");
    if (ping($Cheked_terminal['HOST'])) {
        //DebMes("Пропингованый Терминал-".$terminal . ' онлайн и обновляем его статус '. microtime(true), 'terminals2');
        sg($Cheked_terminal['LINKED_OBJECT'] . '.status', '1');
        $Cheked_terminal['LATEST_ACTIVITY'] = date('Y-m-d H:i:s');
        $Cheked_terminal['IS_ONLINE']       = 1;
    } else {
        //DebMes("Пропингованый Терминал-".$terminal . ' офлайн и обновляем его статус '. microtime(true), 'terminals2');
        sg($Cheked_terminal['LINKED_OBJECT'] . '.status', '0');
        $Cheked_terminal['LATEST_ACTIVITY'] = date('Y-m-d H:i:s');
        $Cheked_terminal['IS_ONLINE']       = 0;
    }
    SQLUpdate('terminals', $Cheked_terminal);
    //DebMes("Пропингованый Терминал-".$terminal . ' состояние обновлено '. microtime(true), 'terminals2');
}


// check terminal Safe
function pingTerminalSafe($terminal)
{
    $data = array(
        'pingTerminal' => 1,
        'terminal' => $terminal
    );
    if (session_id()) {
        $data[session_name()] = session_id();
    }
    $url = BASE_URL . '/objects/?' . http_build_query($data);
    if (is_array($params)) {
        foreach ($params as $k => $v) {
            $url .= '&' . $k . '=' . urlencode($v);
        }
    }
    getURLBackground($url, 0);
    return 1;
}

function send_message_to_terminal($message, $terminal)
{
    include_once(DIR_MODULES . 'app_player/addons.php');
    include_once(DIR_MODULES . 'app_player/addons/' . $terminal['PLAYER_TYPE'] . '.addon.php');
    if (class_exists($terminal['PLAYER_TYPE'])) {
        if (is_subclass_of($terminal['PLAYER_TYPE'], 'app_player_addon', TRUE)) {
            $player = new $terminal['PLAYER_TYPE']($terminal);
        }
    }
    $out = $player->say_message($message, $terminal);
	usleep(100000);
	//DebMes('out - '. $out. ' '.$terminal['NAME']);
	if (!$out) {
            $rec = SQLSelectOne("SELECT * FROM shouts WHERE ID = '".$message['ID']."'");
            $rec['SOURCE'] = $rec['SOURCE'].$terminal['ID'] . '^';
            SQLUpdate('shouts', $rec);
	}
	sg($terminal['LINKED_OBJECT'].'.BASY',0);	
}

function send_message_to_terminalSafe($message, $terminal)
{
    $data = array(
        'send_message_to_terminal' => 1,
        'message' => $message,
        'terminal' => $terminal
    );
    if (session_id()) {
        $data[session_name()] = session_id();
    }
    $url = BASE_URL . '/objects/?' . http_build_query($data);
    if (is_array($params)) {
        foreach ($params as $k => $v) {
            $url .= '&' . $k . '=' . urlencode($v);
        }
    }
    getURLBackground($url, 0);
}

