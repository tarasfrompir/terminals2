<?php
function remote_file_exists($url){
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if( $httpCode == 200 ){return true;}
    return false;
}

function get_remote_filesize($url)
{
    $head = array_change_key_case(get_headers($url, 1));
    // content-length of download (in bytes), read from Content-Length: field
    $clen = isset($head['content-length']) ? $head['content-length'] : 0;

    // cannot retrieve file size, return "-1"
    if (!$clen) {
        return '0';
    }
    return $clen; // return size in bytes
}

function get_audio_file_info($file)
{
    if (!defined('PATH_TO_FFMPEG')) {
        if (IsWindowsOS()) {
            define("PATH_TO_FFMPEG", SERVER_ROOT . '/apps/ffmpeg/ffmpeg.exe');
        } else {
            define("PATH_TO_FFMPEG", 'ffmpeg');
        }
    }
    $data = shell_exec(PATH_TO_FFMPEG . " -i " . $file . " 2>&1");
    //DebMes ($data);
    if (preg_match("/: Invalid /", $data)) {
        return false;
    }
    //get duration
    preg_match("/Duration: (.{2}):(.{2}):(.{2})/", $data, $duration);
    if (!isset($duration[1])) {
        return false;
    }
    $hours = $duration[1];
    $minutes = $duration[2];
    $seconds = $duration[3];
	$out['duration'] = $seconds + ($minutes * 60) + ($hours * 60 * 60);
	// get all info about codec
	preg_match("/.+Audio: (.+), (.\d+) Hz, (.\w+), \w(.\d+)\w?, (.\d+) kb/", $data, $format);
	$out['format'] = $format[1];
	$out['sample_rate'] = $format[2];
	$out['type'] = $format[3];
	$out['codec'] = $format[4];
	$out['bitrate'] = $format[5];
	
    return $out;
}

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

function sayTToMedia($messageid, $terminalid)
{
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
        $out = $player->sayToMedia($message['FILE_LINK'], $message['TIME_MESSAGE']);
        $count = $count+1;
    }

	sleep($message['TIME_MESSAGE']+1);
	sg($terminal['LINKED_OBJECT'].'.BASY',0);
}

function sayTToMediaSafe($messageid, $terminalid)
{
    //DebMes('Получили очередь в отдельный поток для терминала ' . $terminalid . ' ' . microtime(true), 'terminals2');
    $data = array(
        'sayTToMedia' => 1,
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

