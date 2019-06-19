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

function sayToText($terminals)
{
    // Addons main class
	DebMes('Выбираем все параметры терминала '.$terminal.' '. microtime(true), 'terminals2');
    $terminal = SQLSelectOne("SELECT * FROM terminals WHERE NAME = '" . $terminals . "' OR TITLE = '" . $terminals . "'");
    // Addons main class
	DebMes('Подключаем класс функции сайтутекст для воспроизведения сообщения '.$terminal.' '. microtime(true), 'terminals2');
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
	DebMes('Выбираем все сообщения которые есть в очереди терминала '.$terminal.' '. microtime(true), 'terminals2');
    $messages = SQLSelect("SELECT * FROM shouts WHERE SOURCE LIKE '%".$terminal['ID']."^%' ORDER BY ID ASC");
    foreach ($messages as $message) {
		DebMes('Отправляем сообщение '.$message['MESSAGE'].' в терминал '.$terminal.' '. microtime(true), 'terminals2');
        $out = $player->sayttotext($message['MESSAGE'], $event);
        while (!$out) {
            $out = $player->sayttotext($message['MESSAGE'], $event);
			DebMes('ПОВТОРНО Отправляем сообщение '.$message['MESSAGE'].' в терминал '.$terminal.' '. microtime(true), 'terminals2');
        }
	    $message['SOURCE'] = str_replace($terminal['ID'].'^', "", $message['SOURCE']);
		DebMes('Удаляем терминал для сообщения '.$message['MESSAGE'].' в таблице шутс из очереди'.$terminal.' '. microtime(true), 'terminals2');
        SQLUpdate('shouts', $message);
	}
}

function sayToTextSafe($terminals)
{
    $data = array(
        'sayToText' => 1,
        'terminals' => $terminals,
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
	DebMes('Запускаем очередь в отделный поток для терминала '.$terminals.' '. microtime(true), 'terminals2');
    $result = getURLBackground($url, 0);
    return $result;
}

// check terminal Safe
function pingTerminalSafe($terminal)
{
    $data = array(
        'pingTerminal' => 1,
        'terminal' => $terminal,
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

// check terminal Safe
function pingTerminal($terminal)
{
	DebMes("Терминал-".$terminal . ' офлайн и его запускаем на пинг '. microtime(true), 'terminals2');
    $Cheked_terminal = SQLSelectOne("SELECT * FROM terminals WHERE NAME = '" . $terminal . "' OR TITLE = '" . $terminal . "' OR HOST = '" . $terminal . "'");
    if (ping($Cheked_terminal['HOST'])) {
        DebMes("Пропингованый Терминал-".$terminal . ' онлайн и обновляем его статус '. microtime(true), 'terminals2');
        sg($Cheked_terminal['LINKED_OBJECT'] . '.status', '1');
        $Cheked_terminal['LATEST_ACTIVITY'] = date('Y-m-d H:i:s');
        $Cheked_terminal['IS_ONLINE']       = 1;
    } else {
        DebMes("Пропингованый Терминал-".$terminal . ' офлайн и обновляем его статус '. microtime(true), 'terminals2');
        sg($Cheked_terminal['LINKED_OBJECT'] . '.status', '0');
        $Cheked_terminal['LATEST_ACTIVITY'] = date('Y-m-d H:i:s');
        $Cheked_terminal['IS_ONLINE']       = 0;
    }
    SQLUpdate('terminals', $Cheked_terminal);
    DebMes("Пропингованый Терминал-".$terminal . ' состояние обновлено '. microtime(true), 'terminals2');
}

