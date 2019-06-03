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

function Update_Queue_saytext ($terminal) {
	// vibiraem vse soobsheniya dla terminala s sortirovkoy po nazvaniyu
    $all_messages = SQLSelect("SELECT * FROM jobs WHERE TITLE LIKE'" . 'target-' . $terminal. '-number-' . "%' ORDER BY `TITLE` ASC");
    $first_fields = reset($all_messages);
    $runtime      = strtotime("now");
    foreach ($all_messages as $message) {
        $expire          = (strtotime($message['EXPIRE'])) - (strtotime($message['RUNTIME']));
        $rec['ID']       = $message['ID'];
        $rec['TITLE']    = $message['TITLE'];
        $rec['COMMANDS'] = $message['COMMANDS'];
        $rec['RUNTIME']  = date('Y-m-d H:i:s', $runtime);
        $rec['EXPIRE']   = date('Y-m-d H:i:s', $runtime + $expire);
        // proverka i udaleniye odinakovih soobsheniy
        if ($prev_message['TITLE'] == $message['TITLE']) {
            SQLExec("DELETE FROM jobs WHERE ID='" . $rec['ID'] . "'");
        } else {
            SQLUpdate('jobs', $rec);
        }
        $runtime      = $runtime + $expire;
        $prev_message = $message;
        }
}
