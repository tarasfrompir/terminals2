<?php

class majordroid_tts extends tts_addon {

    function __construct($terminal) {
        $this->terminal = $terminal;
        $this->title="MajorDroid";
        $this->setting = json_decode($this->terminal['TTS_SETING'], true);
        $this->description = 'Используется на устройствах которые имеют в себе MajorDroid API.';
        $this->port = empty($this->setting['TTS_PORT'])?7999:$this->setting['TTS_PORT'];
        parent::__construct($terminal);
    }

    function say_message($message, $terminal) //SETTINGS_SITE_LANGUAGE_CODE=код языка
    {
        return $this->sendMajorDroidCommand('tts:'.$message['MESSAGE']);
    }
    
	function ask($phrase, $level=0) {
        return $this->sendMajorDroidCommand('ask:'.$phrase);
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
        $file = FALSE;
        $random   = FALSE;
        $loop     = FALSE;
        $repeat   = FALSE;
        $muted = FALSE;

        $url = 'http://'.$this->terminal['HOST'].':'. $this->port.'/jsonrpc?request={"method": "get", "params": ["volume", "nvolume", "mvolume"], "id": "id890"}';
        $result = json_decode(getURL($url, 0), true);
 
        DebMes($result);
         if ($result) {
            $this->data    = array(
                'track_id' => (int) $track_id, //ID of currently playing track (in playlist). Integer. If unknown (playback stopped or playlist is empty) = -1.
                'name' => (string) $name, //Current speed for playing media. float.
                'file' => (string) $file, //Current link for media in device. String.
                'length' => (int) $length, //Track length in seconds. Integer. If unknown = 0. 
                'time' => (int) $time, //Current playback progress (in seconds). If unknown = 0. 
                'state' => (string) $state, //Playback status. String: stopped/playing/paused/unknown 
                'volume' => intval($volume), // Volume level in percent. Integer. Some players may have values greater than 100.
                'muted' => (int) $muted, // Volume level in percent. Integer. Some players may have values greater than 100.
                'random' => (boolean) $random, // Random mode. Boolean. 
                'loop' => (boolean) $loop, // Loop mode. Boolean.
                'repeat' => (string) $repeat //Repeat mode. Boolean.
            );
        } 
        return $this->data;
    }
	
    function sendMajorDroidCommand($cmd) {
        if ($this->terminal['HOST']) {
            $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            if ($socket === false) {
                return 0;
            }
            $result = socket_connect($socket, $this->terminal['HOST'], $this->port);
            if ($result === false) {
                return 0;
            }
            $result = socket_write($socket, $cmd, strlen($cmd));
            usleep(500000);
            socket_close($socket);
            return $result;
        }
    }
}
