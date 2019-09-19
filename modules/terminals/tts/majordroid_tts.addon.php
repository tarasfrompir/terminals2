<?php

class majordroid_tts extends tts_addon {

    function __construct($terminal) {
        $this->terminal = $terminal;
        $this->title="MajorDroid";
        $this->description = 'Используется на устройствах которые имеют в себе MajorDroid API.';
        $this->port = (empty($this->terminal['TTS_PORT'])?7999:$this->terminal['TTS_PORT']);
        parent::__construct($terminal);
    }

    function say_message($message, $terminal) //SETTINGS_SITE_LANGUAGE_CODE=код языка
    {
        return $this->sendMajorDroidCommand('tts:'.$message['MESSAGE']);
    }
    
	function ask($phrase, $level=0) {
        return $this->sendMajorDroidCommand('ask:'.$phrase);
    }
	
    function sendMajorDroidCommand($cmd) {
        if ($this->terminal['HOST']) {
            if (!preg_match('/^\d/', $address)) return 0;
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
