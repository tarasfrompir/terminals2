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
        $this->description = 'Описание: Цифровой медиаплеер от компании Google.';
        //$this->terminal['PLAYER_PORT'] = (empty($this->terminal['PLAYER_PORT']) ? 8009 : $this->terminal['PLAYER_PORT']);
        $this->terminal = $terminal;
        $this->setting = json_decode($this->terminal['TTS_SETING'], true);       
        // Chromecast
        include_once(DIR_MODULES . 'app_player/libs/castv2/Chromecast.php');
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

        $cc = new GChromecast($this->terminal['HOST'], empty($this->setting['TTS_PORT']) ? 8009 : $this->setting['TTS_PORT']);
        $cc->requestId = time();
        $cc->load($message_link, 0);
        $cc->play();
        //DebMes($response);

        sleep($message['TIME_MESSAGE']);
        $this->success = TRUE;
        $this->message = 'Play files';
        return $this->success;
    }
	// Get player status
    function status()
    {
        // Defaults
        $track_id = -1;
        $length   = 0;
        $time     = 0;
        $name     = 'unknow';
        $state    = 'unknown';
        $volume   = 0;
        $random   = FALSE;
        $loop     = FALSE;
        $repeat   = FALSE;
        
        $cc = new GChromecast($this->terminal['HOST'], empty($this->setting['TTS_PORT']) ? 8009 : $this->setting['TTS_PORT']);
        $cc->requestId = time();
        $status = $cc->getStatus();
        $cc->requestId = time();
        $result = $cc->getMediaSession(); 
        //DebMes($result);
         if ($result) {
            $this->data    = array(
                'track_id' => (int) $result['status'][0]['media']['tracks'][0]['trackId'], //ID of currently playing track (in playlist). Integer. If unknown (playback stopped or playlist is empty) = -1.
				'name' => (string) $name, //Current speed for playing media. float.
                'file' => (string) $result['status'][0]['media']['contentId'], //Current link for media in device. String.
                'length' => (int) $result['status'][0]['media']['duration'], //Track length in seconds. Integer. If unknown = 0. 
                'time' => (int) $result['status'][0]['currentTime'], //Current playback progress (in seconds). If unknown = 0. 
                'state' => (string) strtolower($result['status'][0]['playerState']), //Playback status. String: stopped/playing/paused/unknown 
                'volume' => intval($status['status']['volume']['level']*100), // Volume level in percent. Integer. Some players may have values greater than 100.
                'muted' => (int) $result['status'][0]['volume']['muted'], // Volume level in percent. Integer. Some players may have values greater than 100.
                'random' => (boolean) $random, // Random mode. Boolean. 
                'loop' => (boolean) $loop, // Loop mode. Boolean.
                'repeat' => (string) $result['status'][0]['repeatMode'] //Repeat mode. Boolean.
            );
        } 
        return $this->data;
    }
	
    // Set volume
    function set_volume($level)
    {
        if (strlen($level)) {
            try {
                $cc            = new GChromecast($this->terminal['HOST'], empty($this->setting['TTS_PORT']) ? 8009 : $this->setting['TTS_PORT']);
                $cc->requestId = time();
                $level         = round($level / 100, 1);
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
	
    // Say
    function play($input, $time) //SETTINGS_SITE_LANGUAGE_CODE=код языка
    {
		if (strlen($input)) {
            try {
                $cc = new GChromecast($this->terminal['HOST'], empty($this->setting['TTS_PORT']) ? 8009 : $this->setting['TTS_PORT']);
                $cc->requestId = time();
                $cc->load($input, $time);
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
	
	    // Stop
    function stop()
    {
        try {
            $cc = new GChromecast($this->terminal['HOST'], empty($this->setting['TTS_PORT']) ? 8009 : $this->setting['TTS_PORT']);
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
}

?>