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
	$this->description = '<b>Описание:</b>&nbsp; Работает на всех устройства поддерживающих протокол Chromecast (CASTv2) от компании Google.<p>';
	$this->description .= '<b>Поддерживаемые возможности:</b>&nbsp;say(), sayTo(), sayReply()<p>';
	$this->description .= '<b>Проверка доступности:</b>&nbsp;service_ping (пингование устройства проводится проверкой состояния сервиса)';
	    
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
	
    // Set volume
    function set_volume($level)
    {
        if (strlen($level)) {
            try {
                $cc = new GChromecast($this->terminal['HOST'], empty($this->setting['TTS_PORT']) ? 8009 : $this->setting['TTS_PORT']);
                $cc->requestId = time();
                $level = round($level / 100, 1);
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
