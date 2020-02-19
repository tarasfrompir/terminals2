<?php

class mjdmterminal extends tts_addon
{
    
    function __construct($terminal)
    {
        $this->terminal    = $terminal;
        $this->title       = "MJDM Terminal";
        $this->setting     = json_decode($this->terminal['TTS_SETING'], true);
        $this->description = '<b>Описание:</b>&nbsp; Используется на устройствах которые поддерживаают MJDM Terminal.<br>';
        $this->description .= '<b>Настройка:</b>&nbsp; Порт доступа по умолчанию 7999 (если по умолчанию, можно не указывать).<br>';
        $this->description .= '<b>Поддерживаемые возможности:</b>&nbsp;say(), sayTo(), sayReply(), ask().';
        
        $this->port = empty($this->setting['TTS_PORT']) ? 7999 : $this->setting['TTS_PORT'];
        parent::__construct($terminal);
    }
    
    function say_message($message, $terminal) //SETTINGS_SITE_LANGUAGE_CODE=код языка
    {
        return $this->sendMjdmCommand('tts:' . $message['MESSAGE']);
    }
    
    function play($input, $time = 0) //SETTINGS_SITE_LANGUAGE_CODE=код языка
    {
        return $this->sendMjdmCommand('play:' . $input);
    }
    
    function ask($phrase, $level = 0)
    {
        return $this->sendMjdmCommand('ask:' . $phrase);
    }
    
    function set_volume($volume = 0)
    {
        return $this->sendMjdmCommand('volume:' . $volume);
    }
    
    function stop()
    {
        return $this->sendMjdmCommand('stop');
    }
	
    function set_brightness_display($brightness, $time=0)
    {
        // установим яркость дисплея
        return $this->sendMjdmCommand('brightness:' . $brightness);
    }
    
    // Get player status
    function status()
    {
        // Defaults
        $playlistid    = -1;
        $playlist_content = '';
        $track_id      = -1;
        $length        = 0;
        $time          = 0;
        $file          = '';
        $name          = 'unknow';
        $state         = 'unknown';
        $volume        = 0;
        $random        = FALSE;
        $loop          = FALSE;
        $repeat        = FALSE;
        $brightness    = '';
        $display_state = false;
        $battery = false;
        $result   = json_decode($this->sendMjdmCommand('status'));
        
       if ($result) {
            $this->data = array(
                'playlist_id' => $playlistid, // номер или имя плейлиста 
                'playlist_content' => $playlist_content, // содержимое плейлиста должен быть ВСЕГДА МАССИВ 
                                                         // обязательно $playlist_content[$i]['pos'] - номер трека
                                                         // обязательно $playlist_content[$i]['file'] - адрес трека
                                                         // возможно $playlist_content[$i]['Artist'] - артист
                                                         // возможно $playlist_content[$i]['Title'] - название трека
                'track_id' => (int) $track_id, //текущий номер трека
                'name' => (string) $name, //текущее имя трека 
                'file' => (string) $file, //ссылка на текущий файл .
                'length' => intval($result['duration']), //длинна плейлиста (песни). 
                'time' => intval($result['time']), //текущая позиции по времени трека 
                'state' => (string) strtolower($result['state']), //Playback status. String: stopped/playing/paused/unknown 
                'volume' => (int)rtrim($result['volume_media'],'%'), // Volume level in percent. Integer. Some players may have values greater than 100.
                'muted' => intval($result['muted']), // Volume level in percent. Integer. Some players may have values greater than 100.
                'random' => (boolean) $result['random'], // Random mode. Boolean. 
                'loop' => (boolean) $result['loop'], // Loop mode. Boolean.
                'crossfade' => (boolean) $result['xfade'], // crossfade
                'repeat' => (boolean) $result['repeat'], //Repeat mode. Boolean.
                'brightness' => (int) rtrim($result['brightness'],'%'), // яркость екрана
		'battery' => (int) rtrim($result['battery'],'%'), // заряд терминала екрана
                'display_state' => (boolean) ($display_state) // состояние екрана включен (выключен)
            );
        }
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
		$result              = json_decode($this->sendMjdmCommand('status'));
	
        $out_data = array(
                'listening_keyphrase' =>(string) strtolower(($result['listening_keyphrase'])), // ключевое слово терминал для  начала распознавания (-1 - не поддерживается терминалом)
				'volume_media' => (int)rtrim($result['volume_media'],'%'), // громкость медиа на терминале (-1 - не поддерживается терминалом)
                'volume_ring' => (int)rtrim($result['volume_ring'],'%'), // громкость звонка к пользователям на терминале (-1 - не поддерживается терминалом)
                'volume_alarm' => (int)rtrim($result['volume_alarm'],'%'), // громкость аварийных сообщений на терминале (-1 - не поддерживается терминалом)
                'volume_notification' => (int)rtrim($result['volume_notification'],'%'), // громкость простых сообщений на терминале (-1 - не поддерживается терминалом)
                'brightness_auto' => (boolean) $result['brightness_auto'], // автояркость включена или выключена true или false (-1 - не поддерживается терминалом)
                'recognition' => (boolean) $result['recognition'], // распознавание на терминале включена или выключена true или false (-1 - не поддерживается терминалом)
                'fullscreen' => (boolean) $result['recognition'], // полноекранный режим на терминале включена или выключена true или false (-1 - не поддерживается терминалом)
				'brightness' => (int)rtrim($result['brightness'],'%'), // яркость екрана (-1 - не поддерживается терминалом)
				'battery' => (int) rtrim($result['battery'],'%'), // заряд акумулятора терминала в процентах (-1 - не поддерживается терминалом)
                'display_state'=> (int) $display_state, // 1, 0  - состояние дисплея (-1 - не поддерживается терминалом)
            );
		
		// удаляем из массива пустые данные
		foreach ($out_data as $key => $value) {
			if ($value == '-1') unset($out_data[$key]); ;
		}
        return $out_data;
    }
	
    function sendMjdmCommand($cmd)
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
