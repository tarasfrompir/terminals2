<?php

/*
	Addon VLC GUI for app_player
*/

class vlc_tts extends tts_addon {
	
	// Constructor
	function __construct($terminal) {
		$this->title = 'VLC (VideoLAN)';
		$this->description = 'Управление VLC через GUI интерфейс. ';
		$this->description .= 'В настоящее время доступно только для Windows. ';
		$this->description .= 'Поддерживает ограниченный набор команд. ';
		
		$this->terminal = $terminal;
        $this->setting = json_decode($this->terminal['TTS_SETING'], true);
		
		// Curl
		$this->curl = curl_init();
		$this->address = 'http://'.$this->terminal['HOST'].':'.(empty($this->setting['TTS_PORT'])?80:$this->setting['TTS_PORT']);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
		if($this->setting['TTS_USERNAME'] || $this->setting['TTS_PASSWORD']) {
			curl_setopt($this->curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
			curl_setopt($this->curl, CURLOPT_USERPWD, $this->setting['TTS_USERNAME'].':'.$this->setting['TTS_PASSWORD']);
		}
        parent::__construct($terminal);
	}
	
	// Destructor
	function destroy() {
		curl_close($this->curl);
	}

    // Say
    function say_media_message($message, $terminal) { //SETTINGS_SITE_LANGUAGE_CODE=код языка
        if(strlen($message['MESSAGE'])) {
			$message['CACHED_FILENAME'] = preg_replace('/\\\\$/is', '', $message['CACHED_FILENAME']);
			$message['CACHED_FILENAME'] = preg_replace('/\/$/is', '', $message['CACHED_FILENAME']);
			if(!preg_match('/^http/', $message['CACHED_FILENAME'])) {
				$message['CACHED_FILENAME'] = str_replace('/', "\\", $message['CACHED_FILENAME']);
			}
			$this->stop();

			curl_setopt($this->curl, CURLOPT_URL, $this->address.'/rc/?command=vlc_play&param='.urlencode("'".$message['CACHED_FILENAME']."'"));
			//DebMes( $this->address.'/rc/?command=vlc_play&param='.urlencode("'".$message['CACHED_FILENAME']."'"));
			if($result = curl_exec($this->curl)) {
				if($result == 'OK') {
					$this->success = TRUE;
					$this->message = 'OK';
				} else {
					$this->success = FALSE;
					$this->message = $result;
				}
			} else {
				$this->success = FALSE;
				$this->message = 'RC interface not available!';
			}
		} else {
			$this->success = FALSE;
			$this->message = 'Input is missing!';
		}
		return $this->success;
	}
	
	// Play
	function play($input) {
		if(strlen($input)) {
			$input = preg_replace('/\\\\$/is', '', $input);
			$input = preg_replace('/\/$/is', '', $input);
			if(!preg_match('/^http/', $input)) {
				$input = str_replace('/', "\\", $input);
			}
			$this->stop();

			curl_setopt($this->curl, CURLOPT_URL, $this->address.'/rc/?command=vlc_play&param='.urlencode("'".$input."'"));
			if($result = curl_exec($this->curl)) {
				if($result == 'OK') {
					$this->success = TRUE;
					$this->message = 'OK';
				} else {
					$this->success = FALSE;
					$this->message = $result;
				}
			} else {
				$this->success = FALSE;
				$this->message = 'RC interface not available!';
			}
		} else {
			$this->success = FALSE;
			$this->message = 'Input is missing!';
		}
		return $this->success;
	}
	
	// Stop
	function stop() {
		curl_setopt($this->curl, CURLOPT_URL, $this->address.'/rc/?command=vlc_close');
		if($result = curl_exec($this->curl)) {
			if($result == 'OK') {
				$this->success = TRUE;
				$this->message = 'OK';
			} else {
				$this->success = FALSE;
				$this->message = $result;
			}
		} else {
			$this->success = FALSE;
			$this->message = 'RC interface not available!';
		}
		return $this->success;
	}
	
	// Default command
	function command($command, $parameter) {
		$this->reset_properties();
		curl_setopt($this->curl, CURLOPT_URL, $this->address.'/rc/?command='.urlencode($command).(strlen($parameter)?'&param='.urlencode($parameter):''));
		if($result = curl_exec($this->curl)) {
			if($result == 'OK') {
				$json['success'] = TRUE;
				$json['message'] = 'OK';
			} else {
				$json['success'] = FALSE;
				$json['message'] = $result;
			}
		} else {
			$this->success = FALSE;
			$this->message = 'RC interface not available!';
		}
		return $this->success;
	}
	
}

?>
