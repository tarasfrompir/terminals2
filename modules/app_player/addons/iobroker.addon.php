<?php

/*
	Addon iobroker.paw HTTP for app_player
*/

class iobroker extends app_player_addon {
	
	// Private properties
	private $curl;
	private $address;
	
	// Constructor
	function __construct($terminal) {
		$this->title = 'ioBroker.paw';
		$this->description = '<b>Описание:</b>&nbsp; Воспроизведение звука через отправку ссылки на андроид с помощью ioBroker.paw. Из управления работает только громкость.<br>';
		$this->description .= 'Воспроизведение видео на терминале этого типа поддерживается.<br>';
		$this->description .= '<b>Настройка:</b>&nbsp; Не забудьте активировать HTTP интерфейс в настройках ioBroker.paw и включть работу сервиса кнопкой: Connection<br>';
		$this->description .= '<b>Описание:</b>&nbsp;Для работы использует &nbsp;<a href="https://play.google.com/store/apps/details?id=ru.codedevice.iobrokerpawii">ioBroker.paw</a>';
		
		$this->terminal = $terminal;
		$this->reset_properties();
		
		// Curl
		$this->curl = curl_init();
		$this->address = 'http://'.$this->terminal['HOST'].':'.(empty($this->terminal['PLAYER_PORT'])?8080:$this->terminal['PLAYER_PORT']);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
		if($this->terminal['PLAYER_USERNAME'] || $this->terminal['PLAYER_PASSWORD']) {
			curl_setopt($this->curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
			curl_setopt($this->curl, CURLOPT_USERPWD, $this->terminal['PLAYER_USERNAME'].':'.$this->terminal['PLAYER_PASSWORD']);
		}
	}
	
	// Destructor
	function destroy() {
		curl_close($this->curl);
	}
		
	// Play
	function play($input) {
		$this->reset_properties();
		if(strlen($input)) {
			$input = preg_replace('/\\\\$/is', '', $input);
			$url = $this->address . "/api/set.json?link=" . urlencode($input);
			getURLBackground($url,0);
		} 
		return $this->success;
	}
	
	function stop() {
		$this->reset_properties();
		$url = $this->address . "/api/set.json?link=" . BASE_URL . "/stop.mp3";
		getURLBackground($url,0); 
		return $this->success;
	}
	
	// Set volume
	function set_volume($level) {
		$this->reset_properties();
                $data =  json_decode(getURL($this->address . "/api/get.json",0), true); 
		$music_max = $data['volume']['music-max'];
		if(strlen($level)) {
			$level = round((int)$level * $music_max / 100);
			getURLBackground($this->address . "/api/set.json?volume=" . urlencode($level),0);
		}
		return $this->success;
	}
	    // Get player status
    function status()
    {
        $this->reset_properties();
        // Defaults
        $track_id = -1;
        $length   = 0;
        $time     = 0;
        $state    = 'unknown';
        $volume   = 0;
        $muted    = FALSE;
        $random   = FALSE;
        $loop     = FALSE;
        $repeat   = FALSE;
        
        $result = json_decode(getURL($this->address . "/api/get.json",0), true); 
        //DebMes($result);
        if ($result) {
            $this->reset_properties();
            $this->success = TRUE;
            $this->message = 'OK';
            $this->data    = array(
                'track_id' => (int) $track_id, //ID of currently playing track (in playlist). Integer. If unknown (playback stopped or playlist is empty) = -1.
                'length' => (int) $length, //Track length in seconds. Integer. If unknown = 0. 
                'time' => (int)  $time, //Current playback progress (in seconds). If unknown = 0. 
                'state' => (string) strtolower($state), //Playback status. String: stopped/playing/paused/unknown 
                'volume' => intval($result['volume']['music-max']/$result['volume']['music']*100), // Volume level in percent. Integer. Some players may have values greater than 100.
                'muted' => (boolean)  $muted, // Muted mode. Boolean.
                'random' => (boolean) $random, // Random mode. Boolean. 
                'loop' => (boolean) $loop, // Loop mode. Boolean.
                'repeat' => (string) $result['status'][0]['repeatMode'] //Repeat mode. Boolean.
            );
        }
        return $this->success;
    }
}

?>
