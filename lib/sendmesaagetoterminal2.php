<?php
function send_message_to_terminal ($terminal, $message, $event, $member, $level, $filename, $linkfile, $lang, $langfull ) {
    if (!$terminal) {
        return 0;
    }
	
    $url = BASE_URL . ROOTHTML . 'ajax/app_player.html?';
    $url .= "&command=" . ($safe_say ? 'safe_say' : 'say');
    $url .= "&command=say";
    $url .= "&play_terminal=" . $terminal;
    $url .= "&param=" . urlencode($terminal.','.$message.','.$event.','.$member.','.$level.','.$linkfile.','.$lang.','.$langfull);
    getURLBackground($url);
    return 1;
}
