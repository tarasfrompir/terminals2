<?php

/*
Addon MPD for tts
*/

class mpd_tts extends tts_addon {

    // Constructor
    function __construct($terminal) {
    
        $this->title       = 'Music Player Daemon (MPD)';
        $this->description = '<b>Описание:</b>&nbsp; Воспроизведение звука через кроссплатформенный музыкальный проигрыватель, который имеет клиент-серверную архитектуру.<br>';

	parent::__construct($terminal);
        if (!$this->terminal['HOST']) return false;
	    
        $this->setting     = json_decode($this->terminal['TTS_SETING'], true);
        $this->port        = empty($this->setting['TTS_PORT']) ? 6600 : $this->setting['TTS_PORT'];
        $this->password    = $this->setting['TTS_PASSWORD'];

        // MPD
        include_once(DIR_MODULES . 'app_player/libs/mpd/mpd.class.php');
        $this->mpd = new mpd_player($this->terminal['HOST'], $this->port, $this->password);   
        $this->mpd->Disconnect();
        register_shutdown_function("catchTimeoutTerminals");
    }
    
    // Say
    function say_media_message($message, $terminal) {
        if (!$this->mpd->mpd_sock OR !$this->mpd->connected) $this->mpd->Connect();
        if ($this->mpd->connected) {
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
            $this->mpd->SetRepeat(0);
            $this->mpd->SetRandom(0);
            $this->mpd->SetCrossfade(0);
            $this->mpd->PLClear();
            $this->mpd->PLAddFile($message_link);
            if ($this->mpd->Play()) {
                sleep($message['MESSAGE_DURATION']+1);
                $this->success = TRUE;                
            } else {
                $this->success = FALSE;
            }
        } else {
            $this->success = FALSE;
        }
        if ($this->mpd->mpd_sock AND $this->mpd->connected) $this->mpd->Disconnect();
        return $this->success;
    }

    // Set volume
    function set_volume($level=0) {
        if (!$this->mpd->mpd_sock OR !$this->mpd->connected) $this->mpd->Connect();
        if ($this->mpd->connected) {
            try {
                if ($this->mpd->SetVolume($level)) {
                    $this->success = TRUE;
                } else {
                    $this->success = FALSE;
                }
            }
            catch (Exception $e) {
                $this->success = FALSE;
            }
        } else {
            $this->success = FALSE;
        }
        if ($this->mpd->mpd_sock AND $this->mpd->connected) $this->mpd->Disconnect();
        return $this->success;
    }
    
    // Stop
    function stop() {
        if (!$this->mpd->mpd_sock OR !$this->mpd->connected) $this->mpd->Connect();
        if ($this->mpd->connected) {
            try {
                if ($this->mpd->Stop()) {
                    $this->success = TRUE;
                } else {
                    $this->success = TRUE;
                }
            }
            catch (Exception $e) {
                $this->success = FALSE;
            }
        } else {
            $this->success = FALSE;
        }
        if ($this->mpd->mpd_sock AND $this->mpd->connected) $this->mpd->Disconnect();
        return $this->success;
    }
    
    // ping terminal
    function ping() {
        if (!$this->mpd->mpd_sock OR !$this->mpd->connected) $this->mpd->Connect();
        if ($this->mpd->connected) {
            if ($this->mpd->Ping()) {
            $this->success = TRUE;    
            } else {
                $this->success = FALSE;
            }
        } else {
            $this->success = FALSE;
        }
        if ($this->mpd->mpd_sock AND $this->mpd->connected) $this->mpd->Disconnect();
        return $this->success;
    }
    
   	// Get terminal status
    function terminal_status()
    {
        if (!$this->mpd->mpd_sock OR !$this->mpd->connected) $this->mpd->Connect();
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
	
        // get status
        if ($this->mpd->connected) {
            $result = $this->mpd->GetStatus();
        }
		
        $out_data = array(
                'listening_keyphrase' =>(string) strtolower($listening_keyphrase), // ключевое слово терминал для  начала распознавания (-1 - не поддерживается терминалом)
                'volume_media' => (int)$result['volume'], // громкость медиа на терминале (-1 - не поддерживается терминалом)
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
