<?php

class majordroid_tts extends tts_addon
{
    
    function __construct($terminal)
    {
        $this->terminal    = $terminal;
        $this->title       = "MDMPiTerminal";
        $this->setting     = json_decode($this->terminal['TTS_SETING'], true);
        $this->description = '<b>Описание:</b>&nbsp; Используется на устройствах которые поддерживаают MDMPiTerminal.<br>';
        $this->description .= '<b>Проверка доступности:</b>&nbsp;service_ping (пингование устройства проводится проверкой состояния сервиса).<br>';
        $this->description .= '<b>Настройка:</b>&nbsp; Порт доступа по умолчанию 7999 (если по умолчанию, можно не указывать).<br>';
        $this->description .= '<b>Поддерживаемые возможности:</b>&nbsp;say(), sayTo(), sayReply(), ask().';
        
        $this->port = empty($this->setting['TTS_PORT']) ? 7999 : $this->setting['TTS_PORT'];
        parent::__construct($terminal);
    }
    
    function say_message($message, $terminal) //SETTINGS_SITE_LANGUAGE_CODE=код языка
    {
        return $this->sendMajorDroidCommand('tts:' . $message['MESSAGE']);
    }
    
    function play($input, $time = 0) //SETTINGS_SITE_LANGUAGE_CODE=код языка
    {
        return $this->sendMajorDroidCommand('play:' . $input);
    }
    
    function ask($phrase, $level = 0)
    {
        return $this->sendMajorDroidCommand('ask:' . $phrase);
    }
    
    function set_volume($volume = 0)
    {
        return $this->sendMajorDroidCommand('volume:' . $volume);
    }
    
    function stop()
    {
        return $this->sendMajorDroidCommand('pause');
    }
    
    // Get player status
    function status()
    {
        // Defaults
        $track_id = -1;
        $length   = 0;
        $time     = 0;
        $name     = '';
        $file     = '';
        $state    = 'unknown';
        $volume   = $this->sendMajorDroidCommand('get:volume');
        $random   = FALSE;
        $loop     = FALSE;
        $repeat   = FALSE;
        
        //DebMes($result);
        if ($result) {
            $this->data = array(
                'track_id' => (int) $track_id, //ID of currently playing track (in playlist). Integer. If unknown (playback stopped or playlist is empty) = -1.
                'name' => (string) $name, //Current speed for playing media. float.
                'file' => (string) $file, //Current link for media in device. String.
                'length' => (int) $length, //Track length in seconds. Integer. If unknown = 0. 
                'time' => (int) $time, //Current playback progress (in seconds). If unknown = 0. 
                'state' => (string) $state, //Playback status. String: stopped/playing/paused/unknown 
                'volume' => (int) str_replace("volume:", "", $volume), // Volume level in percent. Integer. Some players may have values greater than 100 answer volume:23.
                'muted' => (int) false,
                'random' => (boolean) $random, // Random mode. Boolean. 
                'loop' => (boolean) $loop, // Loop mode. Boolean.
                'repeat' => (string) $repeat //Repeat mode. Boolean.
            );
        }
        return $this->data;
    }
    
    function sendMajorDroidCommand($cmd)
    {
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
