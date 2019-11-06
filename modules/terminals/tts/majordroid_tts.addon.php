<?php

class majordroid_tts extends tts_addon {

    function __construct($terminal) 
    {
        $this->terminal = $terminal;
        $this->title="MajorDroid";
        $this->setting = json_decode($this->terminal['TTS_SETING'], true);
        $this->description = '<b>Описание:</b>&nbsp; Используется на устройствах которые поддерживаают MajorDroid API.<br>';
	$this->description .= '<b>Проверка доступности:</b>&nbsp;service_ping (пингование устройства проводится проверкой состояния сервиса).<br>';
	$this->description .= '<b>Настройка:</b>&nbsp; Порт доступа по умолчанию 7999 (если по умолчанию, можно не указывать).<br>';
	$this->description .= '<b>Поддерживаемые возможности:</b>&nbsp;say(), sayTo(), sayReply(), ask().';

        $this->port = empty($this->setting['TTS_PORT'])?7999:$this->setting['TTS_PORT'];
        parent::__construct($terminal);
    }

    function say_message($message, $terminal) //SETTINGS_SITE_LANGUAGE_CODE=код языка
    {
        return $this->sendMajorDroidCommand('tts:'.$message['MESSAGE']);
    }
	
    function ask($phrase, $level=0) 
    {
        return $this->sendMajorDroidCommand('ask:'.$phrase);
    }
	
    function set_volume($volume=0) 
    {
        return $this->sendMajorDroidCommand('volume:'.$volume);
    }
	
    function stop() 
    {
        return $this->sendMajorDroidCommand('pause');
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
