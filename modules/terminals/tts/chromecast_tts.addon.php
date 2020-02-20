<?php

/*
Addon Chromecast for app_player
*/

class chromecast_tts extends tts_addon
{
    
    // Constructor
    function __construct($terminal)
    {
        $this->title       = 'Google Chromecast';
        $this->description = '<b>Описание:</b>&nbsp; Работает на всех устройства поддерживающих протокол Chromecast (CASTv2) от компании Google.<br>';
        $this->description .= '<b>Проверка доступности:</b>&nbsp;service_ping (пингование устройства проводится проверкой состояния сервиса).<br>';
        $this->description .= '<b>Настройка:</b>&nbsp; Порт доступа по умолчанию 8009 (если по умолчанию, можно не указывать).<br>';
        $this->description .= '<b>Поддерживаемые возможности:</b>&nbsp;say(), sayTo(), sayReply().';
        $this->terminal = $terminal;
        if (!$this->terminal['HOST'])
            return false;
        $this->setting  = json_decode($this->terminal['TTS_SETING'], true);
        $this->port     = empty($this->setting['TTS_PORT']) ? 8009 : $this->setting['TTS_PORT'];
        // Chromecast
        include_once(DIR_MODULES . 'app_player/libs/castv2/Chromecast.php');
        register_shutdown_function("catchTimeoutTerminals");
        //set_time_limit(2);
    }
    
    // Say
    function say_media_message($message, $terminal) //SETTINGS_SITE_LANGUAGE_CODE=код языка
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
        
        $cc            = new GChromecast($this->terminal['HOST'], $this->port);
        $cc->requestId = time();
        $cc->load($message_link, 0);
        $cc->requestId = time();
        $response      = $cc->play();
        if ($response) {
            //set_time_limit(2+$message['MESSAGE_DURATION']);
            sleep($message['MESSAGE_DURATION']);
            $this->success = TRUE;
        } else {
            $this->success = FALSE;
        }
        return $this->success;
    }
    
    // Set volume
    function set_volume($level)
    {
        if (strlen($level)) {
            try {
                $cc            = new GChromecast($this->terminal['HOST'], $this->port);
                $cc->requestId = time();
                $level         = $level / 100;
                $cc->SetVolume($level);
                $this->success = TRUE;
            }
            catch (Exception $e) {
                $this->success = FALSE;
            }
        } else {
            $this->success = FALSE;
        }
        return $this->success;
    }
    
    // Say
    function play($input, $time = 0) //SETTINGS_SITE_LANGUAGE_CODE=код языка
    {
        if (strlen($input)) {
            try {
                $cc            = new GChromecast($this->terminal['HOST'], $this->port);
                $cc->requestId = time();
                $cc->load($input, $time);
                $cc->requestId = time();
                $cc->play();
                $this->success = TRUE;
            }
            catch (Exception $e) {
                $this->success = FALSE;
            }
        } else {
            $this->success = FALSE;
        }
        return $this->success;
    }
    
    // Stop
    function stop()
    {
        try {
            $cc            = new GChromecast($this->terminal['HOST'], $this->port);
            $cc->requestId = time();
            $cc->stop();
            $this->success = TRUE;
        }
        catch (Exception $e) {
            $this->success = FALSE;
        }
        return $this->success;
    }
    
    // Get player status
    function status()
    {
        // Defaults
        $track_id      = -1;
        $length        = 0;
        $time          = 0;
        $name          = 'unknow';
        $state         = 'unknown';
        $volume        = 0;
        $random        = FALSE;
        $loop          = FALSE;
        $repeat        = FALSE;
        $brightness    = '';
        $display_state = false;
        
        $cc            = new GChromecast($this->terminal['HOST'], $this->port);
        $cc->requestId = time();
        $status        = $cc->getStatus();
        $cc->requestId = time();
        $result        = $cc->getMediaSession();
        //DebMes($result);
        if ($result) {
            $this->data = array(
                'track_id' => (int) $result['status'][0]['media']['tracks'][0]['trackId'], //ID of currently playing track (in playlist). Integer. If unknown (playback stopped or playlist is empty) = -1.
                'name' => (string) $name, //Current speed for playing media. float.
                'file' => (string) $result['status'][0]['media']['contentId'], //Current link for media in device. String.
                'length' => (int) $result['status'][0]['media']['duration'], //Track length in seconds. Integer. If unknown = 0. 
                'time' => (int) $result['status'][0]['currentTime'], //Current playback progress (in seconds). If unknown = 0. 
                'state' => (string) strtolower($result['status'][0]['playerState']), //Playback status. String: stopped/playing/paused/unknown 
                'volume' => intval($status['status']['volume']['level'] * 100), // Volume level in percent. Integer. Some players may have values greater than 100.
                'muted' => (int) $result['status'][0]['volume']['muted'], // Volume level in percent. Integer. Some players may have values greater than 100.
                'random' => (boolean) $random, // Random mode. Boolean. 
                'loop' => (boolean) $loop, // Loop mode. Boolean.
                'repeat' => (string) $result['status'][0]['repeatMode'], //Repeat mode. Boolean.
                'brightness' => intval($brightness),
                'display_state' => (boolean) ($display_state)
            );
        }
        return $this->data;
    }
    
    // ping terminal
    function ping()
    {
        // proverka na otvet
        $cc            = new GChromecast($this->terminal['HOST'], $this->port);
        $cc->requestId = time();
        $status        = $cc->getStatus();
        if (is_array($status)) {
            $this->success = TRUE;
        } else {
            $this->success = FALSE;
        }
        return $this->success;
    }
	
	// Get terminal status
    function terminal_status()
    {
        // Defaults
        $listening_keyphrase = -1;
		$volume_media        = -1;
        $volume_ring         = -1;
        $volume_alarm        = -1;
        $volume_notification = -1;
        $brightness_auto     = -1;
        $recognition         = -1;
        $fullscreen          = -1;
        $brightness          = -1;
        $display_state       = -1;
        $battery             = -1;
	
        $out_data = array(
                'listening_keyphrase' =>(string) strtolower($listening_keyphrase), // ключевое слово терминал для  начала распознавания (-1 - не поддерживается терминалом)
				'volume_media' => (int)$volume_media, // громкость медиа на терминале (-1 - не поддерживается терминалом)
                'volume_ring' => (int)$volume_ring, // громкость звонка к пользователям на терминале (-1 - не поддерживается терминалом)
                'volume_alarm' => (int)$volume_alarm, // громкость аварийных сообщений на терминале (-1 - не поддерживается терминалом)
                'volume_notification' => (int)$volume_notification, // громкость простых сообщений на терминале (-1 - не поддерживается терминалом)
                'brightness_auto' => (int) $brightness_auto, // автояркость включена или выключена 1 или 0 (-1 - не поддерживается терминалом)
                'recognition' => (int) $recognition, // распознавание на терминале включена или выключена 1 или 0 (-1 - не поддерживается терминалом)
                'fullscreen' => (int) $recognition, // полноекранный режим на терминале включена или выключена 1 или 0 (-1 - не поддерживается терминалом)
				'brightness' => (int) $brightness, // яркость екрана (-1 - не поддерживается терминалом)
				'battery' => (int) $battery, // заряд акумулятора терминала в процентах (-1 - не поддерживается терминалом)
                'display_state'=> (int) $display_state, // 1, 0  - состояние дисплея (-1 - не поддерживается терминалом)
            );
		
		// удаляем из массива пустые данные
		foreach ($out_data as $key => $value) {
			if ($value == '-1') unset($out_data[$key]); ;
		}
        return $out_data;
    }
}

?>
