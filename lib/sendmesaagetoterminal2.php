<?php
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

function send_message_to_terminal($message, $terminal)
{
    include_once(DIR_MODULES . 'app_player/addons.php');
    include_once(DIR_MODULES . 'app_player/addons/' . $terminal['PLAYER_TYPE'] . '.addon.php');
    if (class_exists($terminal['PLAYER_TYPE'])) {
        if (is_subclass_of($terminal['PLAYER_TYPE'], 'app_player_addon', TRUE)) {
            $player = new $terminal['PLAYER_TYPE']($terminal);
        }
    }
    //DebMes('Отправлено сообщение для терминала ' . $terminalid . ' ' . microtime(true), 'terminals2');

    $out = $player->say_message($message, $terminal);

/* 	if ($out) {
	    $rec = SQLSelectOne("SELECT * FROM shouts WHERE ID = '".$message['ID']."'");
        $rec['SOURCE'] = str_replace($terminal['ID'] . '^', '', $message['SOURCE']);
        SQLUpdate('shouts', $rec);
	}
	sg($terminal['LINKED_OBJECT'].'.BASY',0); */
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

