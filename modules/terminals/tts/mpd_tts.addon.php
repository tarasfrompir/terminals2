<?php

/*
	Addon MPD for app_player
*/

class mpd_tts extends tts_addon {

	// Private properties
	private $mpd;

	// Constructor
	function __construct($terminal) {
            $this->title = 'Music Player Daemon (MPD)';
            $this->description = '<b>Описание:</b>&nbsp; Воспроизведение звука через кроссплатформенный музыкальный проигрыватель, который имеет клиент-серверную архитектуру.<br>';

            // Network
            $this->terminal = $terminal;
            $this->setting  = json_decode($this->terminal['TTS_SETING'], true);
            $this->port     = empty($this->setting['TTS_PORT']) ? 6600 : $this->setting['TTS_PORT'];
            $this->password = $this->setting['TTS_PASSWORD'];
		// MPD
		include_once(DIR_MODULES.'app_player/libs/mpd/mpd.class.php');
	}

	// Private: MPD connect
	private function mpd_connect() {
		$this->mpd = new mpd_player($this->terminal['HOST'], $this->port, $this->password);
		if($this->mpd->connected) {
			$this->success = TRUE;
		} else {
			$this->success = FALSE;
		}
		return $this->success;
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
        
        $this->mpd_connect();
		$this->mpd->PLClear();
		$this->mpd->PLAdd($message_link);
		$this->mpd->Play();
		$this->mpd->Disconnect();
		sleep($message['MESSAGE_DURATION']);
        $this->success = TRUE;
        return $this->success;
    }
	
	// Play
	function play($input, $time = 0) {
		if(strlen($input)) {
			if($this->mpd_connect()) {
				$this->mpd->PLClear();
				$this->mpd->PLAdd($input);
				$this->mpd->Play();
				$this->mpd->Disconnect();
				$this->success = TRUE;
			}
		} else {
			$this->success = FALSE;
		}
		return $this->success;
	}

	// Stop
	function stop() {
		if($this->mpd_connect()) {
			$this->mpd->Stop();
			$this->mpd->Disconnect();
			$this->reset_properties();
			$this->success = TRUE;
		}
		return $this->success;
	}

	// Set volume
	function set_volume($level) {
		if(strlen($level)) {
			if($this->mpd_connect()) {
				$this->mpd->SetVolume((int)$level);
				$this->mpd->Disconnect();
				$this->reset_properties();
				$this->success = TRUE;
			}
		} else {
			$this->success = FALSE;
		}
		return $this->success;
	}
    // ping terminal
    function ping()
    {
        if ($this->mpd_connect()) {
            $this->success = TRUE;
        } else {
            $this->success = FALSE;
        }
        return $this->success;
    }

}

?>
