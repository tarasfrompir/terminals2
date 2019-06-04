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

function Update_Queue_sayToText($terminal)
{
    $rec            = SQLSelectOne("SELECT * FROM jobs WHERE PROCESSED = 0 AND TITLE LIKE '" . $terminal . '-' . "%' ORDER BY `TITLE` ASC ");
    $runtime        = strtotime("now")+1;
    $expire         = (strtotime($rec['EXPIRE'])) - (strtotime($rec['RUNTIME']));
    $rec['RUNTIME'] = date('Y-m-d H:i:s', $runtime);
    $rec['EXPIRE']  = date('Y-m-d H:i:s', $runtime + $expire);
    SQLUpdate('jobs', $rec);
}
function sayToText($terminals, $message, $event, $lang, $langfull)
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
    $out = $player->sayttotext($message, $event, $lang, $langfull);
    while (!$out) {
        $out = $player->sayttotext($message, $event, $lang, $langfull);
    }
    Update_Queue_sayToText($terminal['NAME']);
}
