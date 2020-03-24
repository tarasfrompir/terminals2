<?php

/*
Addon VLC HTTP for app_player
*/

class vlcweb_tts extends tts_addon
{
    
    // Constructor
    function __construct($terminal)
    {
        $this->title       = 'VLC через HTTP';
        $this->description = '<b>Описание:</b>&nbsp; Работает с VideoLAN Client (VLC). Управление VLC производится по протоколу HTTP.<br>';
        $this->description .= '<b>Проверка доступности:</b>&nbsp;ip_ping.<br>';
        $this->description .= '<b>Настройка:</b>&nbsp; Не забудьте активировать HTTP (web) интерфейс в настройках VLC<br>';
        $this->description .= '(Инструменты -> Настройки -> Все -> Основные интерфейсы -> Дополнительные модули интерфейса -> Web)<br>';
        $this->description .= 'и установить для него пароль (Основные интерфейсы -> Lua -> HTTP -> Пароль).<br>';
        $this->description .= '<b>Поддерживаемые возможности:</b>&nbsp;say(), sayTo(), sayReply().';

        $this->terminal = $terminal;
        if (!$this->terminal['HOST']) return false;
        $this->setting = json_decode($this->terminal['TTS_SETING'], true);

        $this->address = 'http://' . $this->terminal['HOST'] . ':' . (empty($this->setting['TTS_PORT']) ? 8080 : $this->setting['TTS_PORT']);
       
    }
    
    // Private: VLC-WEB request
    private function vlcweb_request($path, $data = array())
    {
        $params = array();
        foreach ($data as $key => $value) {
            if (is_string($key)) {
                $params[] = $key . '=' . urlencode($value);
            } else {
                $params[] = $value;
            }
        }
        $params = implode('&', $params);
		
		// init curl
        $curl = curl_init();
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		if ($this->setting['TTS_USERNAME'] OR $this->setting['TTS_PASSWORD']) {
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($curl, CURLOPT_USERPWD, $this->setting['TTS_USERNAME'] . ':' . $this->setting['TTS_PASSWORD']);
        }
        curl_setopt($curl, CURLOPT_URL, $this->address . '/requests/' . $path . (strlen($params) ? '?' . $params : ''));

        if ($result = curl_exec($curl)) {
            $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            switch ($code) {
                case 200:
                    $this->success = TRUE;
                    $this->message = 'OK';
                    $this->data    = $result;
                    break;
                case 401:
                    $this->success = FALSE;
                    $this->message = 'Authorization failed!';
                    break;
                default:
                    $this->success = FALSE;
                    $this->message = 'Unknown error (code ' . $code . ')!';
            }
        } else {
            $this->success = FALSE;
        }
        curl_close($curl);
        return $this->success;
    }
    
    // Private: VLC-WEB parse XML
    private function vlcweb_parse_xml($data)
    {
        
        try {
            if ($xml = @new SimpleXMLElement($data)) {
                $this->success = TRUE;
                $this->message = 'OK';
                $this->data    = $xml;
            } else {
                $this->success = FALSE;
                $this->message = 'SimpleXMLElement error!';
            }
        }
        catch (Exception $e) {
            $this->success = FALSE;
            $this->message = $e->getMessage();
        }
        return $this->success;
    }
    
    public function say_media_message($message, $terminal) //SETTINGS_SITE_LANGUAGE_CODE=код языка
    {
		$outlink = $message['CACHED_FILENAME'];
        // берем ссылку http
        if (preg_match('/\/cms\/cached.+/', $outlink, $m)) {
            $server_ip = getLocalIp();
            if (!$server_ip) {
                DebMes("Server IP not found", 'terminals');
                return false;
            } else {
                $message_link = 'http://' . $server_ip . $m[0];
            }
        }
        // cleare playlist
        $this->vlcweb_request('status.xml', array('command' => 'pl_empty'));
        // play message
        if (file_exists($outlink)) {
            $message_link = preg_replace('/\\\\$/is', '', $message_link);
            if ($this->vlcweb_request('status.xml', array(
                'command' => 'in_play',
                'input' => $message_link
            ))) {
                if ($this->vlcweb_parse_xml($this->data)) {
                    $this->success = TRUE;
                    sleep($message['MESSAGE_DURATION']);
                } else {
                    $this->success = FALSE;
                }
            } else {
                $this->success = FALSE;
            }
        } else {
            $this->success = FALSE;
        }
        return $this->success;
    }
    
    // Set volume
    public function set_volume($level)
    {
        if (strlen($level)) {
            $level = round((int) $level * 256 / 100);
            if ($this->vlcweb_request('status.xml', array('command' => 'volume','val' => (int) $level))) {
                if ($this->vlcweb_parse_xml($this->data)) {
                    $this->success = TRUE;
                }
            } else {
                $this->success = FALSE;
            }
        } else {
            $this->success = FALSE;
        }
        return $this->success;
    }
    
    public function play_media ($link) 
    {
        // берем ссылку http
        if (preg_match('/\/cms\/cached.+/', $link, $m)) {
            $server_ip = getLocalIp();
            if (!$server_ip) {
                DebMes("Server IP not found", 'terminals');
                return false;
            } else {
                $message_link = 'http://' . $server_ip . $m[0];
            }
        }
        // cleare playlist
        $this->vlcweb_request('status.xml', array('command' => 'pl_empty'));
        // play audio
        if (file_exists($link)) {
            $message_link = preg_replace('/\\\\$/is', '', $message_link);
            if ($this->vlcweb_request('status.xml', array(
                'command' => 'in_play',
                'input' => $message_link
            ))) {
                if ($this->vlcweb_parse_xml($this->data)) {
                    $this->success = TRUE;
                    sleep(2);
                } else {
                    $this->success = FALSE;
                }
            } else {
                $this->success = FALSE;
            }
        } else {
            $this->success = FALSE;
        }
        return $this->success;
    }
}
?>
