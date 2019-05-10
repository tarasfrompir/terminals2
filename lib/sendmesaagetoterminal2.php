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
