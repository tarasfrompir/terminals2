<?php
// Get all terminals
function getAllTerminals($limit = -1, $order = 'ID', $sort = 'ASC') {
	$sqlQuery = 'SELECT * FROM `terminals` ORDER BY `'.DBSafe($order).'` '.DBSafe($sort);
	if($limit >= 0) {
		$sqlQuery .= ' LIMIT '.intval($limit);
	}
	if(!$terminals = SQLSelect($sqlQuery)) {
		$terminals = array(NULL);
	}
	return $terminals;
}
// Get terminal by id
function getTerminalByID($id) {
	$sqlQuery = 'SELECT * FROM `terminals` WHERE `ID` = '.abs(intval($id));
	$terminal = SQLSelectOne($sqlQuery);
	return $terminal;
}
// Get terminal by name
function getTerminalsByName($name, $limit = -1, $order = 'ID', $sort = 'ASC') {
	$sqlQuery = "SELECT * FROM `terminals` WHERE `NAME` = '".DBSafe($name)."' OR `TITLE` = '".DBSafe($name)."' ORDER BY `".DBSafe($order)."` ".DBSafe($sort);
	if($limit >= 0) {
		$sqlQuery .= ' LIMIT '.intval($limit);
	}
	if(!$terminals = SQLSelect($sqlQuery)) {
		$terminals = array(NULL);
	}
	return $terminals;
}
// Get terminals by host or ip address
function getTerminalsByHost($host, $limit = -1, $order = 'ID', $sort = 'ASC') {
	$localhost = array(
		'localhost',
		'127.0.0.1',
		'ip6-localhost',
		'ip6-loopback',
		'ipv6-localhost',
		'ipv6-loopback',
		'::1',
		'0:0:0:0:0:0:0:1',
	);
	if(in_array(strtolower($host), $localhost)) {
		$sqlQuery = "SELECT * FROM `terminals` WHERE `HOST` = '".implode("' OR `HOST` = '", $localhost)."' ORDER BY `".DBSafe($order)."` ".DBSafe($sort);
	} else {
		$sqlQuery = "SELECT * FROM `terminals` WHERE `HOST` = '".DBSafe($host)."' ORDER BY `".DBSafe($order)."` ".DBSafe($sort);
	}
	if($limit >= 0) {
		$sqlQuery .= ' LIMIT '.intval($limit);
	}
	if(!$terminals = SQLSelect($sqlQuery)) {
		$terminals = array(NULL);
	}
	return $terminals;
}
// Get terminals that can play
function getTerminalsCanPlay($limit = -1, $order = 'ID', $sort = 'ASC') {
	$sqlQuery = "SELECT * FROM `terminals` WHERE `CANPLAY` = 1 ORDER BY `".DBSafe($order)."` ".DBSafe($sort);
	if($limit >= 0) {
		$sqlQuery .= ' LIMIT '.intval($limit);
	}
	if(!$terminals = SQLSelect($sqlQuery)) {
		$terminals = array(NULL);
	}
	return $terminals;
}
// Get terminals by player type
function getTerminalsByPlayer($player, $limit = -1, $order = 'ID', $sort = 'ASC') {
	$sqlQuery = "SELECT * FROM `terminals` WHERE `PLAYER_TYPE` = '".DBSafe($player)."' ORDER BY `".DBSafe($order)."` ".DBSafe($sort);
	if($limit >= 0) {
		$sqlQuery .= ' LIMIT '.intval($limit);
	}
	if(!$terminals = SQLSelect($sqlQuery)) {
		$terminals = array(NULL);
	}
	return $terminals;
}
// Get main terminal
function getMainTerminal() {
	$sqlQuery = "SELECT * FROM `terminals` WHERE `NAME` = 'MAIN'";
	$terminal = SQLSelectOne($sqlQuery);
	return $terminal;
}
// Get online terminals
function getOnlineTerminals($limit = -1, $order = 'ID', $sort = 'ASC') {
	$sqlQuery = "SELECT * FROM `terminals` WHERE `IS_ONLINE` = 1 ORDER BY `".DBSafe($order)."` ".DBSafe($sort);
	if($limit >= 0) {
		$sqlQuery .= ' LIMIT '.intval($limit);
	}
	if(!$terminals = SQLSelect($sqlQuery)) {
		$terminals = array(NULL);
	}
	return $terminals;
}
// Get MajorDroid terminals
function getMajorDroidTerminals($limit = -1, $order = 'ID', $sort = 'ASC') {
	$sqlQuery = "SELECT * FROM `terminals` WHERE `MAJORDROID_API` = 1 ORDER BY `".DBSafe($order)."` ".DBSafe($sort);
	if($limit >= 0) {
		$sqlQuery .= ' LIMIT '.intval($limit);
	}
	if(!$terminals = SQLSelect($sqlQuery)) {
		$terminals = array(NULL);
	}
	return $terminals;
}
// Get terminals by CANTTS
function getTerminalsByCANTTS($order = 'ID', $sort = 'ASC') {
	$sqlQuery = "SELECT * FROM `terminals` WHERE `CANTTS` = '".DBSafe('1')."' ORDER BY `".DBSafe($order)."` ".DBSafe($sort);
	if(!$terminals = SQLSelect($sqlQuery)) {
		$terminals = array(NULL);
	}
	return $terminals;
}
// Get local ip 
function getLocalIp() {
	global $local_ip_address_cached;
	if (isset($local_ip_address_cached)) {
		$local_ip_address=$local_ip_address_cached;
	} else {
		$s = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		socket_connect($s, '8.8.8.8', 53);  // connecting to a UDP address doesn't send packets
		socket_getsockname($s, $local_ip_address, $port);
		@socket_shutdown($s, 2);
		socket_close($s);
		if (!$local_ip_address) {
			$main_terminal=getTerminalsByName('MAIN')[0];
			if ($main_terminal['HOST']) {
				$local_ip_address=$main_terminal['HOST'];
			}
		}
		if ($local_ip_address) {
			$local_ip_address_cached=$local_ip_address;
		}
	}
	return $local_ip_address;
}
/**
 * This function change  position on the played media in player
 * @param mixed $host Host (default 'localhost') name or ip of terminal
 * @param mixed $time second (default 0) to positon from start time
 */
function seekPlayerPosition($host = 'localhost', $time = 0)
{
    if (!$terminal = getTerminalsByName($host, 1)[0]) {
        $terminal = getTerminalsByHost($host, 1)[0];
    }
    if (!$terminal) {
        return;
    }
    include_once(DIR_MODULES . 'app_player/app_player.class.php');
    $player = new app_player();
    $player->play_terminal = $terminal['NAME']; // Имя терминала
    $player->command = 'seek'; // Команда
    $player->param = $time; // Параметр
    $player->ajax = TRUE;
    $player->intCall = TRUE;
    $player->usual($out);
    return $player->json['message'];
}
/**
 * Summary of player status
 * @param mixed $host Host (default 'localhost') name or ip of terminal
 * @return  'id'              => (int), //ID of currently playing track (in playlist). Integer. If unknown (playback stopped or playlist is empty) = -1.
 *          'name'            => (string), //Playback status. String: stopped/playing/paused/transporting/unknown
 *          'file'            => (string), //Current link for media in device. String.
 *          'track_id'        => (int)$track_id, //ID of currently playing track (in playlist). Integer. If unknown (playback stopped or playlist is empty) = -1.
 *          'length'          => (int)$length, //Track length in seconds. Integer. If unknown = 0.
 *          'time'            => (int)$time, //Current playback progress (in seconds). If unknown = 0.
 *          'state'           => (string)$state, //Playback status. String: stopped/playing/paused/unknown
 *          'volume'          => (int)$volume, // Volume level in percent. Integer. Some players may have values greater than 100.
 *          'random'          => (boolean)$random, // Random mode. Boolean.
 *          'loop'            => (boolean)$loop, // Loop mode. Boolean.
 *          'repeat'          => (boolean)$repeat, //Repeat mode. Boolean.
 */
function getPlayerStatus($host = 'localhost')
{
    if (!$terminal = getTerminalsByName($host, 1)[0]) {
        $terminal = getTerminalsByHost($host, 1)[0];
    }
    if (!$terminal) {
        return;
    }
    include_once(DIR_MODULES . 'app_player/app_player.class.php');
    $player = new app_player();
    $player->play_terminal = $terminal['NAME']; // Имя терминала
    $player->command = 'pl_get'; // Команда
    $player->ajax = TRUE;
    $player->intCall = TRUE;
    $player->usual($out);
    $terminal = array();
    if ($player->json['success'] && is_array($player->json['data'])) {
        $terminal = array_merge($terminal, $player->json['data']);
        //DebMes($player->json['data']);
    } else {
        // Если произошла ошибка, выводим ее описание
        return ($player->json['message']);
    }
    $player->command = 'status'; // Команда
    $player->ajax = TRUE;
    $player->intCall = TRUE;
    $player->usual($out);
    if ($player->json['success'] && is_array($player->json['data'])) {
        $terminal = array_merge($terminal, $player->json['data']);
        //DebMes($player->json['data']);
        return ($terminal);
    } else {
        // Если произошла ошибка, выводим ее описание
        return ($player->json['message']);
    }
}
function getMediaDurationSeconds($file)
{
    if (!defined('PATH_TO_FFMPEG')) {
        if (IsWindowsOS()) {
            define("PATH_TO_FFMPEG", SERVER_ROOT . '/apps/ffmpeg/ffmpeg.exe');
        } else {
            define("PATH_TO_FFMPEG", 'ffmpeg');
        }
    }
    $dur = shell_exec(PATH_TO_FFMPEG . " -i " . $file . " 2>&1");
    if (preg_match("/: Invalid /", $dur)) {
        return false;
    }
    preg_match("/Duration: (.{2}):(.{2}):(.{2})/", $dur, $duration);
    if (!isset($duration[1])) {
        return false;
    }
    $hours = $duration[1];
    $minutes = $duration[2];
    $seconds = $duration[3];
    return $seconds + ($minutes * 60) + ($hours * 60 * 60);
}
/**
 * Summary of playMedia
 * @param mixed $path Path
 * @param mixed $host Host (default 'localhost')
 * @return int
 */
function playMedia($path, $host = 'localhost', $safe_play = FALSE)
{
    if (defined('SETTINGS_HOOK_PLAYMEDIA') && SETTINGS_HOOK_PLAYMEDIA != '') {
        eval(SETTINGS_HOOK_PLAYMEDIA);
    }
    if (!$terminal = getTerminalsByName($host, 1)[0]) {
        $terminal = getTerminalsByHost($host, 1)[0];
    }
    if (!$terminal['ID']) {
        $terminal = getTerminalsCanPlay(1)[0];
    }
    if (!$terminal['ID']) {
        $terminal = getMainTerminal();
    }
    if (!$terminal['ID']) {
        $terminal = getAllTerminals(1)[0];
    }
    if (!$terminal['ID']) {
        return 0;
    }
    include_once(DIR_MODULES . 'app_player/app_player.class.php');
    $player = new app_player();
    $player->play_terminal = $terminal['NAME']; // Имя терминала
    $player->command = ($safe_play ? 'safe_play' : 'play'); // Команда
    $player->param = $path; // Параметр
    $player->ajax = TRUE;
    $player->intCall = TRUE;
    $player->usual($out);
    return $player->json['message'];
}
/**
 * Summary of stopMedia
 * @param mixed $host Host (default 'localhost')
 * @return int
 */
function stopMedia($host = 'localhost')
{
    if (!$terminal = getTerminalsByName($host, 1)[0]) {
        $terminal = getTerminalsByHost($host, 1)[0];
    }
    if (!$terminal['ID']) {
        $terminal = getTerminalsCanPlay(1)[0];
    }
    if (!$terminal['ID']) {
        $terminal = getMainTerminal();
    }
    if (!$terminal['ID']) {
        $terminal = getAllTerminals(1)[0];
    }
    if (!$terminal['ID']) {
        return 0;
    }
    include_once(DIR_MODULES . 'app_player/app_player.class.php');
    $player = new app_player();
    $player->play_terminal = $terminal['NAME']; // Имя терминала
    $player->command = 'stop'; // Команда
    $player->ajax = TRUE;
    $player->intCall = TRUE;
    $player->usual($out);
    return $player->json['message'];
}
/**
 * This function change volume on the terminal
 * @param mixed $host Host (default 'localhost') name or ip of terminal
 * @param mixed $level level of volume (default 0) to positon from start time
 */
function setPlayerVolume($host = 'localhost', $level = 0)
{
    if (!$terminal = getTerminalsByName($host, 1)[0]) {
        $terminal = getTerminalsByHost($host, 1)[0];
    }
    if (!$terminal) {
        return;
    }
    include_once(DIR_MODULES . 'app_player/app_player.class.php');
    $player = new app_player();
    $player->play_terminal = $terminal['NAME']; // Имя терминала
    $player->command = 'set_volume'; // Команда
    $player->param = $level; // Параметр
    $player->ajax = TRUE;
    $player->intCall = TRUE;
    $player->usual($out);
    return $player->json['message'];
}
function setTerminalMML($host = 'localhost', $mml=0) {
    if (!$terminal = getTerminalsByName($host, 1)[0]) {
        $terminal = getTerminalsByHost($host, 1)[0];
    }
    if (!$terminal['ID']) {
        $terminal = getTerminalsCanPlay(1)[0];
    }
    if (!$terminal['ID']) {
        $terminal = getMainTerminal();
    }
    if (!$terminal['ID']) {
        $terminal = getAllTerminals(1)[0];
    }
    if (!$terminal['ID']) {
        return 0;
    }
	$terminal['MIN_MSG_LEVEL'] = $mml;
	SQLUpdate('terminals', $terminal);
	return true;
}
// check terminal 
function pingTerminal($terminal, $details)
{
    if ($details['ID']) {
        $rec['ID'] = $details['ID'];
    }
    DebMes("Try to ping terminal - " . $terminal , 'terminals');
    if (ping($details['HOST'])) {
        sg($details['LINKED_OBJECT'] . '.status', '1');
        $rec['LATEST_ACTIVITY'] = date('Y-m-d H:i:s');
        $rec['IS_ONLINE'] = 1;
        DebMes("Terminal - " . $terminal .' is online', 'terminals');
    } else {
        sg($details['LINKED_OBJECT'] . '.status', '0');
        $rec['IS_ONLINE'] = 0;
        DebMes("Terminal - " . $terminal .' is offline', 'terminals');
    }
    SQLUpdate('terminals', $rec);
}
// check terminal Safe
function pingTerminalSafe($terminal, $details = '')
{
    if (!is_array($details)) {
        $details = array();
    }
    $data = array(
        'pingTerminal' => 1,
        'terminal' => $terminal,
		'params' => json_encode($details)
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
function send_message($terminalname, $message, $terminal)
{
    include_once(DIR_MODULES . "terminals/terminals.class.php");
    $ter = new terminals();
    $ter->getConfig();
    if (!$terminal) {
        return;
    }
    try {
        // получаем состояние плеераесли еще нету 
        if ($terminal['TTS_TYPE'] == 'mediaplayer' AND !gg($terminal['LINKED_OBJECT'] . '.playerdata')) {
            $restore_data = getPlayerStatus($terminal['NAME']);
		// если это не файл из сообщения системы
	    if (stripos($restore_data['file'], '/cms/cached/voice') === false) {
                // остановим медиа
                stopMedia($terminalname);
                if ($ter->config['LOG_ENABLED']) DebMes("Write info about terminal state  - " . json_encode($restore_data, JSON_UNESCAPED_UNICODE) . "to : " . $terminalname , 'terminals');
                sg($terminal['LINKED_OBJECT'] . '.playerdata', json_encode($restore_data));
                //установим громкость
                setPlayerVolume($terminalname, $terminal['MESSAGE_VOLUME_LEVEL']);
                $out['ID'] = $terminal['ID'];
                $out['TERMINAL_VOLUME_LEVEL'] = $restore_data['volume'];
                SQLUpdate('terminals', $out);
            }
        }
	} catch(Exception $e) {
           if ($ter->config['LOG_ENABLED']) DebMes("Terminal job terminated, not get information abaut terminal - " . $terminalname , 'terminals');
	}
	try {
		if ($ter->config['LOG_ENABLED']) DebMes("Sending Message - " . json_encode($message, JSON_UNESCAPED_UNICODE) . "to : " . $terminalname , 'terminals');
		include_once DIR_MODULES . 'terminals/tts_addon.class.php';
		$addon_file = DIR_MODULES . 'terminals/tts/' . $terminal['TTS_TYPE'] . '.addon.php';
		if (file_exists($addon_file)) {
			include_once($addon_file);
			$tts = new $terminal['TTS_TYPE']($terminal);
			$out = $tts->say_message($message, $terminal);
			if (!$out) {
				if ($ter->config['LOG_ENABLED']) DebMes("ERROR with Sending Message - " . json_encode($message, JSON_UNESCAPED_UNICODE) . "to : " . $terminalname , 'terminals');
				$rec = SQLSelectOne("SELECT SOURCE FROM shouts WHERE ID = '".$message['ID']."'");
				$rec['SOURCE'] = $rec['SOURCE'].$terminal['ID'] . '^';
				SQLUpdate('shouts', $rec);
				pingTerminalSafe($terminal['NAME'], $terminal);
			} else {
				if ($ter->config['LOG_ENABLED']) DebMes("Message - " . json_encode($message, JSON_UNESCAPED_UNICODE) . " sending to : " . $terminalname .' sucessfull', 'terminals');
			}
		} else {
            sleep (1);
            if ($ter->config['LOG_ENABLED']) DebMes("Terminal not right configured - " . $terminalname , 'terminals');
		}
	} catch(Exception $e) {
           if ($ter->config['LOG_ENABLED']) DebMes("Terminal terminated, not work addon - " . $terminalname , 'terminals');
	}
	sg($terminal['LINKED_OBJECT'].'.basy',0);	
}
function send_messageSafe($message, $terminal)
{
    $data = array(
        'send_message' => 1,
        'terminalname' => $terminal['NAME'],
        'message' => json_encode($message),
        'terminal' => json_encode($terminal)
    );
    if (session_id()) {
        $data[session_name()] = session_id();
    }
    $url = BASE_URL . '/objects/?' . http_build_query($data);
    if (is_array($message)) {
        foreach ($message as $k => $v) {
            $url .= '&' . $k . '=' . urlencode($v);
        }
    }
    if (is_array($terminal)) {
        foreach ($terminal as $k => $v) {
            $url .= '&' . $k . '=' . urlencode($v);
        }
    }
    getURLBackground($url, 0);
    return 1;
}
