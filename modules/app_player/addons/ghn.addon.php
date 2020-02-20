<?php

/*
	Addon Google Home Notifier for app_player
*/

class ghn_media extends app_player_addon {

	// Private properties
	private $address;
	
	// Constructor
	function __construct($terminal) {
		$this->title = 'Google Home Notifier';
		$this->description = '<b>Описание:</b>&nbsp; Воспроизведение звука на google home устройствах через запущенный сервис &nbsp;<a href="https://github.com/noelportugal/google-home-notifier">google-home</a>. Передает текстовые сообщения с параметром языка, выбранного Вами в мажордомо.<br>Ссылка на &nbsp;<a href="https://connect.smartliving.ru/profile/1502/blog38.html">how-to</a>.<br>Ссылка на &nbsp;<a href="https://mjdm.ru/forum/viewtopic.php?f=23&t=5042&hilit=google+home">тему форума</a>.<br>';
		$this->description .= '<b>Восстановление воспроизведения после TTS:</b>&nbsp; Нет (если ТТС такого же типа, что и плеер). Если же тип ТТС и тип плеера для терминала различны, то плейлист плеера при ТТС не потеряется при любых обстоятельствах).<br>';
		$this->description .= '<b>Проверка доступности:</b>&nbsp;??? нужно разбираться ???.<br>';
		$this->description .= '<b>Настройка:</b>&nbsp; Порт доступа по умолчанию 8091 (если по умолчанию, можно не указывать).';
		
		$this->terminal = $terminal;
		$this->reset_properties();
		
		// Network
		$this->terminal['PLAYER_PORT'] = (empty($this->terminal['PLAYER_PORT'])?8091:$this->terminal['PLAYER_PORT']);
		$this->address = 'http://'.$this->terminal['HOST'].':'.$this->terminal['PLAYER_PORT'];
	}

	// Play
	function play($input) {
		$this->reset_properties();
		if(strlen($input)) {
			if(getURL($this->address.'/google-home-notifier?text='.urlencode($input), 0)) {
				$this->success = TRUE;
				$this->message = 'OK';
			} else {
				$this->success = FALSE;
				$this->message = 'Command execution error!';
			}
		} else {
			$this->success = FALSE;
			$this->message = 'Input is missing!';
		}
		return $this->success;
	}

	// Stop
	function stop() {
		$this->reset_properties();
		if(getURL($this->address.'/google-home-notifier?text='.urlencode('http://somefakeurl.stream/'), 0)) {
			$this->success = TRUE;
			$this->message = 'OK';
		} else {
			$this->success = FALSE;
			$this->message = 'Command execution error!';
		}
		return $this->success;
	}
	
	// Get player status
    function status()
    {
        $this->reset_properties();
        // Defaults
		$playlist_id = -1;
		$playlist_content = array();
        $track_id = -1;
		$name     = -1;
		$file     = -1;
        $length   = -1;
        $time     = -1;
        $state    = -1;
        $volume   = -1;
		$muted    = -1;
        $random   = -1;
        $loop     = -1;
        $repeat   = -1;
        $crossfade= -1;
		$speed = -1;
		
        $this->data = array(
                'playlist_id' => (int)$playlist_id, // номер или имя плейлиста 
                'playlist_content' => $playlist_content, // содержимое плейлиста должен быть ВСЕГДА МАССИВ 
                                                         // обязательно $playlist_content[$i]['pos'] - номер трека
                                                         // обязательно $playlist_content[$i]['file'] - адрес трека
                                                         // возможно $playlist_content[$i]['Artist'] - артист
                                                         // возможно $playlist_content[$i]['Title'] - название трека
				'track_id' => (int) track_id, //ID of currently playing track (in playlist). Integer. If unknown (playback stopped or playlist is empty) = -1.
			    'name' => (string) $name, //Current speed for playing media. float.
				'file' => (string) $file, //Current link for media in device. String.
                'length' => (int) $length, //Track length in seconds. Integer. If unknown = 0. 
                'time' => (int) $time, //Current playback progress (in seconds). If unknown = 0. 
                'state' => (string) strtolower($state), //Playback status. String: stopped/playing/paused/unknown 
                'volume' => (int)$volume, // Volume level in percent. Integer. Some players may have values greater than 100.
                'muted' => (int) $random, // Volume level in percent. Integer. Some players may have values greater than 100.
                'random' => (int) $random, // Random mode. Boolean. 
                'loop' => (int) $loop, // Loop mode. Boolean.
                'repeat' => (int) $repeat, //Repeat mode. Boolean.
                'crossfade' => (int) $crossfade, // crossfade
                'speed' => (int) $speed, // crossfade
            );
		// удаляем из массива пустые данные
		foreach ($this->data as $key => $value) {
			if ($value == '-1' or !$value) unset($this->data[$key]);
		}
				        
        $this->success = TRUE;
        $this->message = 'OK';
        return $this->success;
    }

}

?>
