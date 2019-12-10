<?php
// Get all terminals
function getAllTerminals($limit = -1, $order = 'ID', $sort = 'ASC') {
    $sqlQuery = 'SELECT * FROM `terminals` ORDER BY `' . DBSafe($order) . '` ' . DBSafe($sort);
    if ($limit >= 0) {
        $sqlQuery.= ' LIMIT ' . intval($limit);
    }
    if (!$terminals = SQLSelect($sqlQuery)) {
        $terminals = array(NULL);
    }
    return $terminals;
}
// Get terminal by id
function getTerminalByID($id) {
    $sqlQuery = 'SELECT * FROM `terminals` WHERE `ID` = ' . abs(intval($id));
    $terminal = SQLSelectOne($sqlQuery);
    return $terminal;
}
// Get terminal by name
function getTerminalsByName($name, $limit = -1, $order = 'ID', $sort = 'ASC') {
    $sqlQuery = "SELECT * FROM `terminals` WHERE `NAME` = '" . DBSafe($name) . "' OR `TITLE` = '" . DBSafe($name) . "' ORDER BY `" . DBSafe($order) . "` " . DBSafe($sort);
    if ($limit >= 0) {
        $sqlQuery.= ' LIMIT ' . intval($limit);
    }
    if (!$terminals = SQLSelect($sqlQuery)) {
        $terminals = array(NULL);
    }
    return $terminals;
}
// Get terminals by host or ip address
function getTerminalsByHost($host, $limit = -1, $order = 'ID', $sort = 'ASC') {
    $localhost = array('localhost', '127.0.0.1', 'ip6-localhost', 'ip6-loopback', 'ipv6-localhost', 'ipv6-loopback', '::1', '0:0:0:0:0:0:0:1',);
    if (in_array(strtolower($host), $localhost)) {
        $sqlQuery = "SELECT * FROM `terminals` WHERE `HOST` = '" . implode("' OR `HOST` = '", $localhost) . "' ORDER BY `" . DBSafe($order) . "` " . DBSafe($sort);
    } else {
        $sqlQuery = "SELECT * FROM `terminals` WHERE `HOST` = '" . DBSafe($host) . "' ORDER BY `" . DBSafe($order) . "` " . DBSafe($sort);
    }
    if ($limit >= 0) {
        $sqlQuery.= ' LIMIT ' . intval($limit);
    }
    if (!$terminals = SQLSelect($sqlQuery)) {
        $terminals = array(NULL);
    }
    return $terminals;
}
// Get terminals that can play
function getTerminalsCanPlay($limit = -1, $order = 'ID', $sort = 'ASC') {
    $sqlQuery = "SELECT * FROM `terminals` WHERE `CANPLAY` = 1 ORDER BY `" . DBSafe($order) . "` " . DBSafe($sort);
    if ($limit >= 0) {
        $sqlQuery.= ' LIMIT ' . intval($limit);
    }
    if (!$terminals = SQLSelect($sqlQuery)) {
        $terminals = array(NULL);
    }
    return $terminals;
}
// Get terminals by player type
function getTerminalsByPlayer($player, $limit = -1, $order = 'ID', $sort = 'ASC') {
    $sqlQuery = "SELECT * FROM `terminals` WHERE `PLAYER_TYPE` = '" . DBSafe($player) . "' ORDER BY `" . DBSafe($order) . "` " . DBSafe($sort);
    if ($limit >= 0) {
        $sqlQuery.= ' LIMIT ' . intval($limit);
    }
    if (!$terminals = SQLSelect($sqlQuery)) {
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
    $sqlQuery = "SELECT * FROM `terminals` WHERE `IS_ONLINE` = 1 ORDER BY `" . DBSafe($order) . "` " . DBSafe($sort);
    if ($limit >= 0) {
        $sqlQuery.= ' LIMIT ' . intval($limit);
    }
    if (!$terminals = SQLSelect($sqlQuery)) {
        $terminals = array(NULL);
    }
    return $terminals;
}
// Get MajorDroid terminals
//function getMajorDroidTerminals($limit = -1, $order = 'ID', $sort = 'ASC') {
//    $sqlQuery = "SELECT * FROM `terminals` WHERE `MAJORDROID_API` = 1 ORDER BY `" . DBSafe($order) . "` " . DBSafe($sort);
//    if ($limit >= 0) {
//        $sqlQuery.= ' LIMIT ' . intval($limit);
//    }
//    if (!$terminals = SQLSelect($sqlQuery)) {
//        $terminals = array(NULL);
//    }
//    return $terminals;
//}
// Get terminals by CANTTS
function getTerminalsByCANTTS($order = 'ID', $sort = 'ASC') {
    $sqlQuery = "SELECT * FROM `terminals` WHERE `CANTTS` = '" . DBSafe('1') . "' ORDER BY `" . DBSafe($order) . "` " . DBSafe($sort);
    if (!$terminals = SQLSelect($sqlQuery)) {
        $terminals = array(NULL);
    }
    return $terminals;
}
// Get local ip
function getLocalIp() {
    global $local_ip_address_cached;
    if (isset($local_ip_address_cached)) {
        $local_ip_address = $local_ip_address_cached;
    } else {
        $s = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_connect($s, '8.8.8.8', 53); // connecting to a UDP address doesn't send packets
        socket_getsockname($s, $local_ip_address, $port);
        @socket_shutdown($s, 2);
        socket_close($s);
        if (!$local_ip_address) {
            $main_terminal = getTerminalsByName('MAIN') [0];
            if ($main_terminal['HOST']) {
                $local_ip_address = $main_terminal['HOST'];
            }
        }
        if ($local_ip_address) {
            $local_ip_address_cached = $local_ip_address;
        }
    }
    return $local_ip_address;
}
/**
 * This function change  position on the played media in player
 * @param mixed $host Host (default 'localhost') name or ip of terminal
 * @param mixed $time second (default 0) to positon from start time
 */
function seekPlayerPosition($host = 'localhost', $time = 0) {
    if (!$terminal = getTerminalsByName($host, 1) [0]) {
        $terminal = getTerminalsByHost($host, 1) [0];
    }
    if (!$terminal) {
        return;
    }
    include_once (DIR_MODULES . 'app_player/app_player.class.php');
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
function getPlayerStatus($host = 'localhost') {
    if (!$terminal = getTerminalsByName($host, 1) [0]) {
        $terminal = getTerminalsByHost($host, 1) [0];
    }
    if (!$terminal) {
        return;
    }
    include_once (DIR_MODULES . 'app_player/app_player.class.php');
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
function getMediaDurationSeconds($file) {
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
function playMedia($path, $host = 'localhost', $safe_play = FALSE) {
    if (defined('SETTINGS_HOOK_PLAYMEDIA') && SETTINGS_HOOK_PLAYMEDIA != '') {
        eval(SETTINGS_HOOK_PLAYMEDIA);
    }
    if (!$terminal = getTerminalsByName($host, 1) [0]) {
        $terminal = getTerminalsByHost($host, 1) [0];
    }
    if (!$terminal['ID']) {
        $terminal = getTerminalsCanPlay(1) [0];
    }
    if (!$terminal['ID']) {
        $terminal = getMainTerminal();
    }
    if (!$terminal['ID']) {
        $terminal = getAllTerminals(1) [0];
    }
    if (!$terminal['ID']) {
        return 0;
    }
    include_once (DIR_MODULES . 'app_player/app_player.class.php');
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
function stopMedia($host = 'localhost') {
    if (!$terminal = getTerminalsByName($host, 1) [0]) {
        $terminal = getTerminalsByHost($host, 1) [0];
    }
    if (!$terminal['ID']) {
        $terminal = getTerminalsCanPlay(1) [0];
    }
    if (!$terminal['ID']) {
        $terminal = getMainTerminal();
    }
    if (!$terminal['ID']) {
        $terminal = getAllTerminals(1) [0];
    }
    if (!$terminal['ID']) {
        return 0;
    }
    include_once (DIR_MODULES . 'app_player/app_player.class.php');
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
function setPlayerVolume($host = 'localhost', $level = 0) {
    if (!$terminal = getTerminalsByName($host, 1) [0]) {
        $terminal = getTerminalsByHost($host, 1) [0];
    }
    if (!$terminal) {
        return;
    }
    include_once (DIR_MODULES . 'app_player/app_player.class.php');
    $player = new app_player();
    $player->play_terminal = $terminal['NAME']; // Имя терминала
    $player->command = 'set_volume'; // Команда
    $player->param = $level; // Параметр
    $player->ajax = TRUE;
    $player->intCall = TRUE;
    $player->usual($out);
    return $player->json['message'];
}
/**
 * This function change volume for message on the terminal
 * @param mixed $host Host (default 'localhost') name or ip of terminal
 * @param mixed $level level of volume (default 0) to positon from start time
 */
function setMessageVolume($host = 'localhost', $level = 0) {
    if (!$terminal = getTerminalsByName($host, 1) [0]) {
        $terminal = getTerminalsByHost($host, 1) [0];
    }
    if (!$terminal) {
        return;
    }
	$terminal['MESSAGE_VOLUME_LEVEL'] = $level;
    SQLUpdate('terminals', $terminal);
    return true;
}
function setTerminalMML($host = 'localhost', $mml = 0) {
    if (!$terminal = getTerminalsByName($host, 1) [0]) {
        $terminal = getTerminalsByHost($host, 1) [0];
    }
    if (!$terminal['ID']) {
        $terminal = getTerminalsCanPlay(1) [0];
    }
    if (!$terminal['ID']) {
        $terminal = getMainTerminal();
    }
    if (!$terminal['ID']) {
        $terminal = getAllTerminals(1) [0];
    }
    if (!$terminal['ID']) {
        return 0;
    }
    $terminal['MIN_MSG_LEVEL'] = $mml;
    SQLUpdate('terminals', $terminal);
    return true;
}
// check terminal
function pingTerminal($terminal, $details) {
    if (!$terminal) {
        return;
    }
    //sg($details['LINKED_OBJECT'] . '.busy', 1);
    if ($details['ID']) {
        $rec['ID'] = $details['ID'];
    }
    // пробуем найти встроенные функции пинга для этого вида терминала
    $addon_file = DIR_MODULES . 'terminals/tts/' . $details['TTS_TYPE'] . '.addon.php';
    if (file_exists($addon_file)) {
        include_once DIR_MODULES . 'terminals/tts_addon.class.php';
        include_once ($addon_file);
        $ping_terminal = new $details['TTS_TYPE']($details);
        if (method_exists($ping_terminal, 'ping')) {
            $out = $ping_terminal->ping();
            DebMes("Try to ping - " . $terminal . ' with class function', 'terminals');
        } else {
            $out = ping($details['HOST']);
            DebMes("Try to ping - " . $terminal . ' with standart function', 'terminals');
        }
    }
    if ($out) {
        sg($details['LINKED_OBJECT'] . '.alive', '1');
        $rec['LATEST_ACTIVITY'] = date('Y-m-d H:i:s');
        $rec['LATEST_REQUEST_TIME'] = date('Y-m-d H:i:s');
        $rec['IS_ONLINE'] = 1;
        DebMes("Terminal - " . $terminal . ' is online', 'terminals');
    } else {
        sg($details['LINKED_OBJECT'] . '.alive', '0');
        $rec['LATEST_REQUEST_TIME'] = date('Y-m-d H:i:s');
        $rec['IS_ONLINE'] = 0;
        DebMes("Terminal - " . $terminal . ' is offline', 'terminals');
    }
    SQLUpdate('terminals', $rec);
    //sg($details['LINKED_OBJECT'] . '.busy', 0);
}
// check terminal Safe
function pingTerminalSafe($terminal, $details = '') {
    if (!is_array($details)) {
        $details = array();
    }
    $data = array('pingTerminal' => 1, 'terminal' => $terminal, 'params' => json_encode($details));
    if (session_id()) {
        $data[session_name() ] = session_id();
    }
    $url = BASE_URL . '/objects/?' . http_build_query($data);
    if (is_array($params)) {
        foreach ($params as $k => $v) {
            $url.= '&' . $k . '=' . urlencode($v);
        }
    }
    getURLBackground($url, 0);
}
function send_message($terminalname, $message, $terminal) {
    include_once (DIR_MODULES . "terminals/terminals.class.php");
    $ter = new terminals();
    $ter->getConfig();
    include_once DIR_MODULES . 'terminals/tts_addon.class.php';
    $addon_file = DIR_MODULES . 'terminals/tts/' . $terminal['TTS_TYPE'] . '.addon.php';

    // проверим существует ли такой аддон для терминала
    if (file_exists($addon_file) AND $terminal['TTS_TYPE']) {
        include_once ($addon_file);
        $tts = new $terminal['TTS_TYPE']($terminal);
    } else {
        return;
    }
    if ($ter->config['LOG_ENABLED']) DebMes("Terminal " . $terminal['NAME'] . " class load", 'terminals');
 
    // берем настройки ТТС терминала со всей информацией
	$tts_setting = json_decode($terminal['TTS_SETING'], true);
	
    // берем информацию о состоянии терминала громкость, воспроизводимое  и т.д.
    try {
        if (method_exists($tts, 'status') AND !gg($terminal['LINKED_OBJECT'] . '.playerdata')) {
            if ($ter->config['LOG_ENABLED']) DebMes("Terminal " . $terminal['NAME'] . " get info abaut media", 'terminals');
            $restore_data = $tts->status();
        } else {
            if ($ter->config['LOG_ENABLED']) DebMes("Terminal -" . $terminalname . " have restored data or class have not function status", 'terminals');
        }
    }
    catch(Exception $e) {
        if ($ter->config['LOG_ENABLED']) DebMes("Terminal " . $terminal['NAME'] . " have wrong setting", 'terminals');
    }
    if (stripos($restore_data['file'], '/cms/cached/voice') === false AND $restore_data) {
        if ($ter->config['LOG_ENABLED']) DebMes("Write info about terminal state  - " . json_encode($restore_data, JSON_UNESCAPED_UNICODE) . "to : " . $terminalname, 'terminals');
        sg($terminal['LINKED_OBJECT'] . '.playerdata', json_encode($restore_data));
    } else {
        if ($ter->config['LOG_ENABLED']) DebMes("Terminal -" . $terminalname . " dont get status. Maybe  system message ?", 'terminals');
    }

    // пробуем остановить медиа на терминале
    try {
        if (method_exists($tts, 'stop')) {
            if ($ter->config['LOG_ENABLED']) DebMes("Terminal " . $terminal['NAME'] . " woth stopped", 'terminals');
            $tts->stop();
        } else {
            if ($ter->config['LOG_ENABLED']) DebMes("Terminal -" . $terminalname . " have not function stop", 'terminals');
        }
    }
    catch(Exception $e) {
        if ($ter->config['LOG_ENABLED']) DebMes("Terminal " . $terminal['NAME'] . " have wrong setting", 'terminals');
    }

    //включим екран перед подачей сообщений если необходимо
    try {
        if ($tts_setting['TTS_USE_DISPLAY'] AND method_exists($tts, 'turn_on_display')) {
            if ($ter->config['LOG_ENABLED']) DebMes("Terminal " . $terminal['NAME'] . " turn_on_display", 'terminals');
            $tts->turn_on_display($terminal);
        } else {
            if ($ter->config['LOG_ENABLED']) DebMes("Terminal -" . $terminalname . " class have not function turn_on_display", 'terminals');
        }
    }
    catch(Exception $e) {
        if ($ter->config['LOG_ENABLED']) DebMes("Terminal " . $terminal['NAME'] . " have wrong setting", 'terminals');
    }

    //установим яркость екрана перед подачей сообщений если необходимо
    try {
        if ($tts_setting['TTS_USE_DISPLAY'] AND method_exists($tts, 'set_brightness_display')) {
            if ($ter->config['LOG_ENABLED']) DebMes("Terminal " . $terminal['NAME'] . " set_brightness_display", 'terminals');
		    $tts->set_brightness_display($terminal,$tts_setting['TTS_BRIGHTNESS_DISPLAY']);
        } else {
            if ($ter->config['LOG_ENABLED']) DebMes("Terminal -" . $terminalname . " class have not function set_brightness_display", 'terminals');
        }
    }
    catch(Exception $e) {
        if ($ter->config['LOG_ENABLED']) DebMes("Terminal " . $terminal['NAME'] . " have wrong setting", 'terminals');
    }
	
    //установим громкость для сообщений
    try {
        if (method_exists($tts, 'set_volume')) {
            if ($ter->config['LOG_ENABLED']) DebMes("Terminal " . $terminal['NAME'] . " set volume", 'terminals');
            $tts->set_volume($terminal['MESSAGE_VOLUME_LEVEL']);
        } else {
            if ($ter->config['LOG_ENABLED']) DebMes("Terminal -" . $terminalname . " class have not function set volume", 'terminals');
        }
    }
    catch(Exception $e) {
        if ($ter->config['LOG_ENABLED']) DebMes("Terminal " . $terminal['NAME'] . " have wrong setting", 'terminals');
    }

    // запишем уровень громкости на терминале
    if ($restore_data['volume']) {
        $out['ID'] = $terminal['ID'];
        $out['TERMINAL_VOLUME_LEVEL'] = $restore_data['volume'];
        SQLUpdate('terminals', $out);
    }

    // попробуем отправить сообщение на терминал
    try {
        if ($ter->config['LOG_ENABLED']) DebMes("Sending Message - " . json_encode($message, JSON_UNESCAPED_UNICODE) . "to : " . $terminalname, 'terminals');
        if (method_exists($tts, 'say_message')) {
            $out = $tts->say_message($message, $terminal);
            if ($ter->config['LOG_ENABLED']) DebMes("Terminal say with say_message function on terminal - " . $terminalname, 'terminals');
        } else if (method_exists($tts, 'say_media_message')) {
            $out = $tts->say_media_message($message, $terminal);
            if ($ter->config['LOG_ENABLED']) DebMes("Terminal say with say_media_message function on terminal - " . $terminalname, 'terminals');
        } else {
            sleep(1);
            if ($ter->config['LOG_ENABLED']) DebMes("Terminal not right configured - " . $terminalname, 'terminals');
        }
        if (!$out) {
            if ($ter->config['LOG_ENABLED']) DebMes("ERROR with Sending Message - " . json_encode($message, JSON_UNESCAPED_UNICODE) . "to : " . $terminalname, 'terminals');
            $rec = SQLSelectOne("SELECT SOURCE FROM shouts WHERE ID = '" . $message['ID'] . "'");
            $rec['SOURCE'] = $rec['SOURCE'] . $terminal['ID'] . '^';
            SQLUpdate('shouts', $rec);
            pingTerminalSafe($terminal['NAME'], $terminal);
        } else {
            if ($ter->config['LOG_ENABLED']) DebMes("Message - " . json_encode($message, JSON_UNESCAPED_UNICODE) . " sending to : " . $terminalname . ' sucessfull', 'terminals');
        }
    }
    catch(Exception $e) {
        if ($ter->config['LOG_ENABLED']) DebMes("Terminal terminated, not work addon - " . $terminalname, 'terminals');
    }
	
    // установим флаг того что терминал освободился
    sg($terminal['LINKED_OBJECT'] . '.busy', 0);
}
function send_messageSafe($message, $terminal) {
    $data = array('send_message' => 1, 'terminalname' => $terminal['NAME'], 'message' => json_encode($message), 'terminal' => json_encode($terminal));
    if (session_id()) {
        $data[session_name() ] = session_id();
    }
    $url = BASE_URL . '/objects/?' . http_build_query($data);
    if (is_array($message)) {
        foreach ($message as $k => $v) {
            $url.= '&' . $k . '=' . urlencode($v);
        }
    }
    if (is_array($terminal)) {
        foreach ($terminal as $k => $v) {
            $url.= '&' . $k . '=' . urlencode($v);
        }
    }
    getURLBackground($url, 0);
    return 1;
}

function restore_media($terminalname, $restore, $terminal) {
    include_once (DIR_MODULES . "terminals/terminals.class.php");
    $ter = new terminals();
    $ter->getConfig();
    include_once DIR_MODULES . 'terminals/tts_addon.class.php';
    $addon_file = DIR_MODULES . 'terminals/tts/' . $terminal['TTS_TYPE'] . '.addon.php';
    if (file_exists($addon_file) AND $terminal['TTS_TYPE']) {
        include_once ($addon_file);
        $tts = new $terminal['TTS_TYPE']($terminal);
    } else {
        return;
    }
    if ($ter->config['LOG_ENABLED']) DebMes("Terminal " . $terminal['NAME'] . " class load", 'terminals');

	// восстановим звук
    try {
        if ($restore['volume'] AND method_exists($tts, 'set_volume')) {
            if ($ter->config['LOG_ENABLED']) DebMes("Terminal " . $terminal['NAME'] . " restore volume", 'terminals');
            $tts->set_volume($restore['volume']);
        } else {
            if ($ter->config['LOG_ENABLED']) DebMes("Terminal -" . $terminalname . " have restored data or class have not function set volume", 'terminals');
        }
    }
    catch(Exception $e) {
        if ($ter->config['LOG_ENABLED']) DebMes("Terminal " . $terminal['NAME'] . " have wrong setting", 'terminals');
    }

    // восстановим медиа	
    try {
        if ($restore['file'] AND method_exists($tts, 'play')) {
            if ($ter->config['LOG_ENABLED']) DebMes("Terminal " . $terminal['NAME'] . " restore media", 'terminals');
            $tts->play($restore['file'], 0);
        } else {
            if ($ter->config['LOG_ENABLED']) DebMes("Terminal -" . $terminalname . " have not media to restore", 'terminals');
        }
    }
    catch(Exception $e) {
        if ($ter->config['LOG_ENABLED']) DebMes("Terminal " . $terminal['NAME'] . " have wrong setting", 'terminals');
    }
	
    //установим яркость екрана назад и при том что экран был включен, после всех сообщений
    try {
        if ($restore['display_state']=='On' AND method_exists($tts, 'turn_off_display')) {
            if ($ter->config['LOG_ENABLED']) DebMes("Terminal " . $terminal['NAME'] . " set_brightness_display", 'terminals');
            $tts->set_brightness_display($terminal, $restore['brightness']);
        } else {
            if ($ter->config['LOG_ENABLED']) DebMes("Terminal -" . $terminalname . " class have not function set_brightness_display or dont need set brightness (display off)", 'terminals');
        }
    }
    catch(Exception $e) {
        if ($ter->config['LOG_ENABLED']) DebMes("Terminal " . $terminal['NAME'] . " have wrong setting", 'terminals');
    }

    //выключим екран после всех сообщений если экран был выключен
    try {
        if ($restore['display_state']=='Off' AND method_exists($tts, 'turn_off_display')) {
            if ($ter->config['LOG_ENABLED']) DebMes("Terminal " . $terminal['NAME'] . " turn_off_display", 'terminals');
            $tts->turn_off_display($terminal);
        } else {
            if ($ter->config['LOG_ENABLED']) DebMes("Terminal -" . $terminalname . " class have not function turn_off_display or dont need turn off display", 'terminals');
        }
    }
    catch(Exception $e) {
        if ($ter->config['LOG_ENABLED']) DebMes("Terminal " . $terminal['NAME'] . " have wrong setting", 'terminals');
    }
	
    sg($terminal['LINKED_OBJECT'] . '.playerdata', '');
    sg($terminal['LINKED_OBJECT'] . '.busy', 0);
}
function restore_mediaSafe($restore, $terminal) {
    $data = array('restore_media' => 1, 'terminalname' => $terminal['NAME'], 'restore' => json_encode($restore), 'terminal' => json_encode($terminal));
    if (session_id()) {
        $data[session_name() ] = session_id();
    }
    $url = BASE_URL . '/objects/?' . http_build_query($data);
    if (is_array($restore)) {
        foreach ($restore as $k => $v) {
            $url.= '&' . $k . '=' . urlencode($v);
        }
    }
    if (is_array($terminal)) {
        foreach ($terminal as $k => $v) {
            $url.= '&' . $k . '=' . urlencode($v);
        }
    }
    getURLBackground($url, 0);
    return 1;
}
