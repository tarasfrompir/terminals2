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

function sayToText($messageid, $terminalid)
{
    //DebMes('Запущена очередь в отделный поток для терминала ' . $terminalid . ' ' . microtime(true), 'terminals2');
    $message  = SQLSelectOne("SELECT * FROM shouts WHERE ID = '" . $messageid . "'");
    $terminal = SQLSelectOne("SELECT * FROM terminals WHERE ID = '" . $terminalid . "'");
    include_once(DIR_MODULES . 'app_player/addons.php');
    include_once(DIR_MODULES . 'app_player/addons/' . $terminal['PLAYER_TYPE'] . '.addon.php');
    if (class_exists($terminal['PLAYER_TYPE'])) {
        if (is_subclass_of($terminal['PLAYER_TYPE'], 'app_player_addon', TRUE)) {
            $player = new $terminal['PLAYER_TYPE']($terminal);
        }
    }
    //DebMes('Отправлено сообщение для терминала ' . $terminalid . ' ' . microtime(true), 'terminals2');
    while (!$out AND $count <2) {
        $out = $player->sayttotext($message['MESSAGE'], $message['EVENT']);
        $count = $count+1;
    }
}

function sayToTextSafe($messageid, $terminalid)
{
    //DebMes('Получили очередь в отдельный поток для терминала ' . $terminalid . ' ' . microtime(true), 'terminals2');
    $data = array(
        'sayToText' => 1,
        'messageid' => $messageid,
        'terminalid' => $terminalid
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
    //DebMes('Запущена очередь в отделный поток для терминала ' . $terminals . ' ' . microtime(true), 'terminals2');
}

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
    $result = getURLBackground($url, 0);
    return $result;
}

/**
 * Summary of say
 * @param mixed $ph Phrase
 * @param mixed $level Level (default 0)
 * @param mixed $member_id Member ID (default 0)
 * @return void
 */
function saynew($ph, $level = 0, $member_id = 0, $source = '')
{

    //dprint(date('Y-m-d H:i:s')." Say started",false);

    verbose_log("SAY (level: $level; member: $member; source: $source): " . $ph);
    //DebMes("SAY (level: $level; member: $member; source: $source): ".$ph,'say');

    $rec = array();
    $rec['MESSAGE'] = $ph;
    $rec['ADDED'] = date('Y-m-d H:i:s');
    $rec['ROOM_ID'] = 0;
    $rec['MEMBER_ID'] = $member_id;
    $rec['EVENT'] = 'SAY';
    $rec['SOURCE'] = '';
    $terminals = array();
    $terminals = getTerminalsByCANTTS();
            
    foreach ($terminals as $terminal) {
         $rec['SOURCE'] .= $terminal['ID'] . '^';
    }
    
    if ($level > 0) $rec['IMPORTANCE'] = $level;
    $rec['ID'] = SQLInsert('shouts', $rec);

    if ($member_id) {
        //$processed = processSubscriptionsSafe('COMMAND', array('level' => $level, 'message' => $ph, 'member_id' => $member_id, 'source' => $source));
        return;
    }

    if (defined('SETTINGS_HOOK_BEFORE_SAY') && SETTINGS_HOOK_BEFORE_SAY != '') {
        eval(SETTINGS_HOOK_BEFORE_SAY);
    }


    if ($level >= (int)getGlobal('minMsgLevel') && !$ignoreVoice && !$member_id) {
        if (!defined('SETTINGS_SPEAK_SIGNAL') || SETTINGS_SPEAK_SIGNAL == '1') {
            $passed = time() - (int)getGlobal('lastSayTime');
            if ($passed > 20) {
                playSound('dingdong', 1, $level);
            }
        }
    }

    setGlobal('lastSayTime', time());
    setGlobal('lastSayMessage', $ph);

    //processSubscriptionsSafe('SAY', array('level' => $level, 'message' => $ph, 'member_id' => $member_id)); //, 'ignoreVoice'=>$ignoreVoice

    if (defined('SETTINGS_HOOK_AFTER_SAY') && SETTINGS_HOOK_AFTER_SAY != '') {
        eval(SETTINGS_HOOK_AFTER_SAY);
    }
    //dprint(date('Y-m-d H:i:s')." Say OK",false);

}
