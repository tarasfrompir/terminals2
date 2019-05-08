<?php
function send_message_to_terminal ($terminal, $filename, $ipfilename, $level, $message, $event, $langcode, $langfullcode )
{
    if (!$terminal) {
        $terminal = 'MAIN';
    }

    if (!$terminal) {
        return 0;
    }
	
    $url = BASE_URL . ROOTHTML . 'ajax/app_player.html?';
    $url .= "&command=" . ($safe_say ? 'safe_say' : 'say');
    $url .= "&command=say";
    $url .= "&play_terminal=" . $terminal;
    $url .= "&param=" . urlencode($filename.','.$ipfilename.','.$level.','.$message.','.$event.','.$langcode.','.$langfullcode);
    getURLBackground($url);
    return 1;
}

// Get terminals by CANTTS
function getTerminalsByCANTTS($order = 'ID', $sort = 'ASC') {
	$sqlQuery = "SELECT * FROM `terminals` WHERE `CANTTS` = '".DBSafe('1')."' ORDER BY `".DBSafe($order)."` ".DBSafe($sort);
	if(!$terminals = SQLSelect($sqlQuery)) {
		$terminals = array(NULL);
	}
	return $terminals;
}
