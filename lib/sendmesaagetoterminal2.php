<?php
function send_message_to_terminal($terminal, $message, $event, $member, $level, $filename, $linkfile, $lang, $langfull, $timeshift)
{
    if (!$terminal) {
        return 0;
    }
    
    $url = BASE_URL . ROOTHTML . 'ajax/app_player.html?';
    $url .= "&command=say";
    $url .= "&play_terminal=" . $terminal;
    $url .= "&param=" . urlencode($terminal . ',' . $message . ',' . $event . ',' . $member . ',' . $level . ',' . $filename . ',' . $linkfile . ',' . $lang . ',' . $langfull . ',' . $timeshift);
    getURLBackground($url);
    return 1;
}

function sayToText($terminals, $event)
{
    // Addons main class
    $terminal = SQLSelectOne("SELECT * FROM terminals WHERE NAME = '" . $terminals . "' OR TITLE = '" . $terminals . "'");
    // Addons main class
    include_once(DIR_MODULES . 'app_player/addons.php');
    // Load addon
    if (file_exists(DIR_MODULES . 'app_player/addons/' . $terminal['PLAYER_TYPE'] . '.addon.php')) {
        include_once(DIR_MODULES . 'app_player/addons/' . $terminal['PLAYER_TYPE'] . '.addon.php');
        if (class_exists($terminal['PLAYER_TYPE'])) {
            if (is_subclass_of($terminal['PLAYER_TYPE'], 'app_player_addon', TRUE)) {
                $player = new $terminal['PLAYER_TYPE']($terminal);
            }
        }
    }
	
	$messages = SQLSelect("SELECT * FROM shouts WHERE SOURCE LIKE '%".$terminal['ID']."^%' ORDER BY ID ASC");
	foreach ($messages as $message) {
		$out = $player->sayttotext($message['MESSAGE'], $event);
        while (!$out) {
            $out = $player->sayttotext($message['MESSAGE'], $event);
        }
		$message['SOURCE'] = str_replace($terminal['ID'].'^', "", $message['SOURCE']);
        SQLUpdate('shouts', $message);
	}
}

function sayToTextSafe($terminals, $event)
{
    $data = array(
        'sayToText' => 1,
        'terminals' => $terminals,
        'event' => $event,
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
    $result = getURLBackground($url, 0);
    return $result;
}
