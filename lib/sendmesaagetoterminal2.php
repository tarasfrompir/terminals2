<?php
function send_message_to_terminal($terminal, $message, $event, $member, $level, $filename, $linkfile, $lang, $langfull, $timeshift ) {
    if (!$terminal) {
        return 0;
    }
	
    $url = BASE_URL . ROOTHTML . 'ajax/app_player.html?';
    $url .= "&command=say";
    $url .= "&play_terminal=" . $terminal;
    $url .= "&param=" . urlencode($terminal.','.$message.','.$event.','.$member.','.$level.','.$filename.','.$linkfile.','.$lang.','.$langfull.','.$timeshift);
    getURLBackground($url);
    return 1;
}

/**
 * Summary of playMedia
 * @param mixed $path Path
 * @param mixed $host Host (default 'localhost')
 * @return int
 */
function saytts($terminal, $message, $event, $member, $level, $lang, $langfull) {
    if (!$terminal) {
        return 0;
    }
	
    $url = BASE_URL . ROOTHTML . 'ajax/app_player.html?';
    $url .= "&command=" . 'say_tts';
    $url .= "&play_terminal=" . $terminal;
    $url .= "&param=" . urlencode($terminal.','.$message.','.$event.','.$member.','.$level.','.$lang.','.$langfull);
    //DebMes($url,'playmedia');
    getURLBackground($url);
    return 1;

}
