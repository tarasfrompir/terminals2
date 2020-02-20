<?php

/*
Addon Chromecast for app_player
*/

class chromecast_media extends app_player_addon
{
    
    // Constructor
    function __construct($terminal)
    {
        $this->title       = 'Google Chromecast';
        $this->description = '<b>Описание:</b>&nbsp; Воспроизведение звука на всех устройства поддерживающих протокол Chromecast (CASTv2) от компании Google.<br>';
		$this->description .= 'Воспроизведение видео на терминале этого типа пока не поддерживается.<br>';
		$this->description .= '<b>Настройка:</b>&nbsp; Порт доступа по умолчанию 8009 (если по умолчанию, можно не указывать).';
        $this->terminal = $terminal;
        $this->terminal['PLAYER_PORT'] = (empty($this->terminal['PLAYER_PORT']) ? 8009 : $this->terminal['PLAYER_PORT']);

        $this->reset_properties();        
        // Chromecast
        include_once(DIR_MODULES . 'app_player/libs/castv2/Chromecast.php');
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
        
        $cc = new GChromecast($this->terminal['HOST'], $this->terminal['PLAYER_PORT']);
        $cc->requestId = time();
        $status = $cc->getStatus();
        $cc->requestId = time();
        $result = $cc->getMediaSession();
        
		$this->data    = array(
			    'playlist_id' => (int)$playlist_id, // номер или имя плейлиста 
                'playlist_content' => $playlist_content, // содержимое плейлиста должен быть ВСЕГДА МАССИВ 
                                                         // обязательно $playlist_content[$i]['pos'] - номер трека
                                                         // обязательно $playlist_content[$i]['file'] - адрес трека
                                                         // возможно $playlist_content[$i]['Artist'] - артист
                                                         // возможно $playlist_content[$i]['Title'] - название трека
                'track_id' => (int) $result['status'][0]['media']['tracks'][0]['trackId'], //ID of currently playing track (in playlist). Integer. If unknown (playback stopped or playlist is empty) = -1.
				'name' => (string) $name, //Current speed for playing media. float.
				'file' => (string) $result['status'][0]['media']['contentId'], //Current link for media in device. String.
                'length' => (int) $result['status'][0]['media']['duration'], //Track length in seconds. Integer. If unknown = 0. 
                'time' => (int) $result['status'][0]['currentTime'], //Current playback progress (in seconds). If unknown = 0. 
                'state' => (string) strtolower($result['status'][0]['playerState']), //Playback status. String: stopped/playing/paused/unknown 
                'volume' => (int)($status['status']['volume']['level']*100), // Volume level in percent. Integer. Some players may have values greater than 100.
                'muted' => (int) $result['status'][0]['volume']['muted'], // Volume level in percent. Integer. Some players may have values greater than 100.
                'random' => (int) $random, // Random mode. Boolean. 
                'loop' => (int) $loop, // Loop mode. Boolean.
                'repeat' => (string) $result['status'][0]['repeatMode'], //Repeat mode. Boolean.
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
    
    
    // Play
    function play($input) //SETTINGS_SITE_LANGUAGE_CODE=код языка
    {
        $this->reset_properties();
		if (strlen($input)) {
            try {
                $cc = new GChromecast($this->terminal['HOST'], $this->terminal['PLAYER_PORT']);
                $cc->requestId = time();
                $cc->load($input, 0);
                $cc->play();
				$this->success = TRUE;
                $this->message = 'Ok!';
            }
            catch (Exception $e) {
                $this->success = FALSE;
                $this->message = $e->getMessage();
            }
        } else {
            $this->success = FALSE;
            $this->message = 'Input is missing!';
        }
        return $this->success;
    }
    
    // Pause
    function pause()
    {
        $this->reset_properties();
        try {
            $cc            = new GChromecast($this->terminal['HOST'], $this->terminal['PLAYER_PORT']);
            $cc->requestId = time();
            $cc->pause();
            $this->success = TRUE;
            $this->message = 'OK';
        }
        catch (Exception $e) {
            $this->success = FALSE;
            $this->message = $e->getMessage();
        }
        return $this->success;
    }
    
    // Stop
    function stop()
    {
        $this->reset_properties();
        try {
            $cc            = new GChromecast($this->terminal['HOST'], $this->terminal['PLAYER_PORT']);
            $cc->requestId = time();
            $cc->stop();
            $this->success = TRUE;
            $this->message = 'OK';
        }
        catch (Exception $e) {
            $this->success = FALSE;
            $this->message = $e->getMessage();
        }
        return $this->success;
    }
    
    // Set volume
    function set_volume($level)
    {
        $this->reset_properties();
        if (strlen($level)) {
            try {
                $cc            = new GChromecast($this->terminal['HOST'], $this->terminal['PLAYER_PORT']);
                $cc->requestId = time();
                $level         = $level / 100;
                $cc->SetVolume($level);
                $this->success = TRUE;
                $this->message = 'OK';
            }
            catch (Exception $e) {
                $this->success = FALSE;
                $this->message = $e->getMessage();
            }
        } else {
            $this->success = FALSE;
            $this->message = 'Level is missing!';
        }
        return $this->success;
    }
    
}

?>
