<?php

class mdmpiterminal extends tts_addon
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
        return $this->data;
    }
    
		// Get terminal status
    function terminal_status()
    {
        // Defaults
        $listening_keyphrase = -1;
		$volume_media        = -1;
        $volume_ring         = -1;
        $volume_alarm        = -1;
        $volume_notification = -1;
        $brightness_auto     = -1;
        $recognition         = -1;
        $fullscreen          = -1;
        $brightness          = -1;
        $display_state       = -1;
        $battery             = -1;
		
		if ($volume = $this->sendMajorDroidCommand('get:volume')) $volume_media = $volume;
			
	
        $out_data = array(
                'listening_keyphrase' =>(string) strtolower($listening_keyphrase), // ключевое слово терминал для  начала распознавания (-1 - не поддерживается терминалом)
				'volume_media' => (int)$volume_media, // громкость медиа на терминале (-1 - не поддерживается терминалом)
                'volume_ring' => (int)$volume_ring, // громкость звонка к пользователям на терминале (-1 - не поддерживается терминалом)
                'volume_alarm' => (int)$volume_alarm, // громкость аварийных сообщений на терминале (-1 - не поддерживается терминалом)
                'volume_notification' => (int)$volume_notification, // громкость простых сообщений на терминале (-1 - не поддерживается терминалом)
                'brightness_auto' => (int) $brightness_auto, // автояркость включена или выключена 1 или 0 (-1 - не поддерживается терминалом)
                'recognition' => (int) $recognition, // распознавание на терминале включена или выключена 1 или 0 (-1 - не поддерживается терминалом)
                'fullscreen' => (int) $recognition, // полноекранный режим на терминале включена или выключена 1 или 0 (-1 - не поддерживается терминалом)
				'brightness' => (int) $brightness, // яркость екрана (-1 - не поддерживается терминалом)
				'battery' => (int) $battery, // заряд акумулятора терминала в процентах (-1 - не поддерживается терминалом)
                'display_state'=> (int) $display_state, // 1, 0  - состояние дисплея (-1 - не поддерживается терминалом)
            );
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
