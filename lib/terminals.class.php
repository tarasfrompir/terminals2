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
    if ($limit >= 0) {<?php
chdir(dirname(__FILE__) . '/../');
include_once("./config.php");
include_once("./lib/loader.php");
include_once("./lib/threads.php");
set_time_limit(0);
include_once("./load_settings.php");
include_once(DIR_MODULES . "terminals/terminals.class.php");
include_once DIR_MODULES . 'terminals/tts_addon.class.php';
// обьявляем массив обектов дабы не грузить их всегда 
$tts = array();
// берем конфигурацию с модуля терминалов - общие настройки
$ter = new terminals();
$ter->getConfig();
$checked_time = 0;
// set all terminal as free when restart cycle
$term = SQLSelect("SELECT * FROM terminals");
foreach ($term as $t) {
    sg($t['LINKED_OBJECT'] . '.busy', 0);
}
// reset all message when reload cicle
//SQLExec("UPDATE shouts SET SOURCE = '' ");
// get number last message
$number_message = SQLSelectOne("SELECT * FROM shouts ORDER BY ID DESC");
$number_message = $number_message['ID'] + 1;
DebMes(date("H:i:s") . " Running " . basename(__FILE__));
if ($ter->config['TERMINALS_TIMEOUT']) {
    $terminals_time_out = $ter->config['TERMINALS_TIMEOUT'];
} else {
    $terminals_time_out = 10;
}
if ($ter->config['LOG_ENABLED']) DebMes("Get timeout for message - " . $terminals_time_out . " minutes", 'terminals');
while (1) {
    // time update cicle of terminal
    if (time() - $checked_time > 60) {
        $ter->getConfig();
        if (!$ter->config['TERMINALS_PING']) {
            $ter->config['TERMINALS_PING'] = 30;
        }
        if (!$ter->config['TERMINALS_TIMEOUT']) {
            $ter->config['TERMINALS_TIMEOUT'] = 10;
        }
        $checked_time = time();
        setGlobal((str_replace('.php', '', basename(__FILE__))) . 'Run', time(), 1);
    }
    //время жизни сообщений
    if (time() - $clear_message > 60 * $ter->config['TERMINALS_TIMEOUT']) {
        $clear_message = time();
        SQLExec("UPDATE shouts SET SOURCE = '' WHERE ADDED < (NOW() - INTERVAL " . $ter->config['TERMINALS_TIMEOUT'] . " MINUTE)");
    }
    // CHEK next message for terminals ready
    $message = SQLSelectOne("SELECT 1 FROM shouts WHERE ID = '" . $number_message . "'");
    if ($message) {
        $number_message = $number_message + 1;
        if ($ter->config['LOG_ENABLED']) DebMes("Max nomber message - " . $number_message, 'terminals');
    } else {
        sleep(1);
    }
    // CHEK next message for terminals ready
    $message = SQLSelectOne("SELECT 1 FROM shouts WHERE ID = '" . $number_message . "'");
    if ($message) {
        $number_message = $number_message + 1;
        if ($ter->config['LOG_ENABLED']) DebMes("Max nomber message - " . $number_message, 'terminals');
    } else {
        sleep(1);
    }
    
    $out_terminals = getObjectsByProperty('busy', '==', '0');
    foreach ($out_terminals as $terminals) {
        // если пустой обьект пропускаем
        if (!$terminals) {
            if ($ter->config['LOG_ENABLED']) DebMes("No free terminal " . $terminals, 'terminals');
            continue;
        }
        $terminal = SQLSelectOne("SELECT * FROM terminals WHERE LINKED_OBJECT = '" . $terminals . "'");
        
        // если пустой терминал пропускаем
        if (!$terminal['ID']) {
            DebMes ("Cannot find terminal for this object - " . $terminals . ". Object must be deleted.", 'terminals');
            continue ;
        }

        // обьявляем новый обьект которого нет в массиве $tts                
        if (!$tts[$terminal['ID']] AND $terminal['TTS_TYPE'] AND file_exists(DIR_MODULES . 'terminals/tts/' . $terminal['TTS_TYPE'] . '.addon.php')) {
            include_once(DIR_MODULES . 'terminals/tts/' . $terminal['TTS_TYPE'] . '.addon.php');
            $tts[$terminal['ID']] = new $terminal['TTS_TYPE']($terminal);
            if ($ter->config['LOG_ENABLED']) DebMes("Add terminal to array tts objects -" . $terminal['NAME'], 'terminals');
            continue;
        }

        // если терминал СВОБОДНЫЙ и офлайн то пингуем его
        if (!$terminal['IS_ONLINE'] AND (time() > 60 * $ter->config['TERMINALS_PING'] + strtotime($terminal['LATEST_REQUEST_TIME']))) {
            if ($ter->config['LOG_ENABLED']) DebMes("PingSafe terminal " . $terminal['NAME'], 'terminals');
            sg($terminal['LINKED_OBJECT'] . '.busy', 1);
            pingTerminalSafe($terminal['NAME'], $terminal);
            continue;
        }
        
        // berem pervoocherednoe soobsheniye 
        $old_message = SQLSelectOne("SELECT * FROM shouts WHERE ID <= '" . $number_message . "' AND SOURCE LIKE '%" . $terminal['ID'] . "^%' ORDER BY ID ASC");
        
        // если отсутствует сообщение и есть инфа для восстановления то восстанавливаем воспроизводимое
        // и переходим на следующий свободный терминал
        if (!$old_message['ID'] OR !$old_message['SOURCE'] ) {
            if ($terminal['IS_ONLINE'] AND $restore = json_decode(gg($terminal['LINKED_OBJECT'] . '.playerdata'), true)) {
                sg($terminal['LINKED_OBJECT'] . '.busy', 1);
				restore_mediaSafe($restore, $terminal);
				continue;
			}
        } else if (!$old_message['SOURCE'] OR !$old_message['MESSAGE']) {
		    // если нечего восстанавливать просто пропускаем итерацию - 
			// иногда попадаются пустые записи ИД терминалов
            continue;
		}

        // если есть сообщение НО терминал оффлайн удаляем из работы эту запись 
        // и пропускаем (пингуется дополнительно - если вернется с ошибкой отправления)
        if (($old_message['ID'] AND !$terminal['IS_ONLINE']) OR !$terminal['TTS_TYPE']) {
            $old_message['SOURCE'] = str_replace($terminal['ID'] . '^', '', $old_message['SOURCE']);
            SQLUpdate('shouts', $old_message);
            if ($ter->config['LOG_ENABLED']) DebMes("Disable message - " . $terminal['NAME'], 'terminals');
            continue;
        }
		
        // если есть сообщение НО не сгенерирован звук в течении 1 минуты
        // удаляем сообщение из очереди для терминалов воспроизводящих звук
        if ($old_message['CACHED_FILENAME'] AND  strtotime($old_message['ADDED'])+1*60 < time() AND method_exists($tts[$terminal['ID']], 'say_media_message')) {
			$old_message['SOURCE'] = str_replace($terminal['ID'] . '^', '', $old_message['SOURCE']);
            SQLUpdate('shouts', $old_message);
            if ($ter->config['LOG_ENABLED']) DebMes("Disable message not generated sound  - " . $terminal['NAME'], 'terminals');
            continue;
        }
        
		// если есть сообщение и есть запись о существовании файла НО не сгенерирован звук (отсутсвтует файл)
        // удаляем сообщение из очереди для терминалов воспроизводящих звук
        if ($old_message['CACHED_FILENAME'] AND !file_exists($old_message['CACHED_FILENAME']) AND method_exists($tts[$terminal['ID']], 'say_media_message')) {
			$old_message['SOURCE'] = str_replace($terminal['ID'] . '^', '', $old_message['SOURCE']);
            SQLUpdate('shouts', $old_message);
            if ($ter->config['LOG_ENABLED']) DebMes("Disable message not generated sound  - " . $terminal['NAME'], 'terminals');
            continue;
        }
		
        // если тип терминала воспроизводящий аудио и нету еще сгенерированного файла пропускаем
        if (method_exists($tts[$terminal['ID']], 'say_media_message') AND !$old_message['CACHED_FILENAME']) {
            continue;
        }
        
        // если тип терминала передающий только текстовое сообщение  
        // запускаем его воспроизведение
        if (method_exists($tts[$terminal['ID']], 'say_message') AND $old_message['SOURCE']) {
            // убираем запись айди терминала из таблицы шутс - если не воспроизведется то вернет эту запись функция send_message($old_message, $terminal);
            $old_message['SOURCE'] = str_replace($terminal['ID'] . '^', '', $old_message['SOURCE']);
            SQLUpdate('shouts', $old_message);
            //записываем что терминал занят
            sg($terminal['LINKED_OBJECT'] . '.busy', 1);
            //передаем сообщение на терминал передающий только текстовое сообщение 
            send_messageSafe($old_message, $terminal);
            if ($ter->config['LOG_ENABLED']) DebMes("Send message with text to terminal - " . $terminal['NAME'], 'terminals');
            continue;
        }
        
        // если тип терминала передающий медиа сообщение
        // иначе запускаем его воспроизведение
        if (method_exists($tts[$terminal['ID']], 'say_media_message') AND $old_message['CACHED_FILENAME'] AND $old_message['SOURCE']) {
            // убираем запись айди терминала из таблицы шутс - если не воспроизведется то вернет эту запись функция send_message($old_message, $terminal);
            $old_message['SOURCE'] = str_replace($terminal['ID'] . '^', '', $old_message['SOURCE']);
            SQLUpdate('shouts', $old_message);
            //записываем что терминал занят
            sg($terminal['LINKED_OBJECT'] . '.busy', 1);
            //передаем сообщение на терминалы воспроизводящие аудио
            send_messageSafe($old_message, $terminal);
            if ($ter->config['LOG_ENABLED']) DebMes("Send message with media to terminal - " . $terminal['NAME'], 'terminals');
            continue;
        }

    }
    
    if (file_exists('./reboot') || IsSet($_GET['onetime'])) {
        exit;
    }
}
DebMes("Unexpected close of cycle: " . basename(__FILE__));

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
function getMajorDroidTerminals($limit = -1, $order = 'ID', $sort = 'ASC') {
    $sqlQuery = "SELECT * FROM `terminals` WHERE `MAJORDROID_API` = 1 ORDER BY `" . DBSafe($order) . "` " . DBSafe($sort);
    if ($limit >= 0) {
        $sqlQuery.= ' LIMIT ' . intval($limit);
    }
    if (!$terminals = SQLSelect($sqlQuery)) {
        $terminals = array(NULL);
    }
    return $terminals;
}
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
    if (file_exists($addon_file) AND $terminal['TTS_TYPE']) {
        include_once ($addon_file);
        $tts = new $terminal['TTS_TYPE']($terminal);
    } else {
        return;
    }
    if ($ter->config['LOG_ENABLED']) DebMes("Terminal " . $terminal['NAME'] . " class load", 'terminals');
    // get terminal info
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
    try {
        // остановим медиа
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
    try {
        //установим громкость для сообщений
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
    // try send message to terminal
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
    // get terminal info
    try {
		// восстановим звук
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
    try {
        // восстановим медиа
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
