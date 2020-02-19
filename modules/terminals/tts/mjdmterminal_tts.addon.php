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
	
    function set_brightness_display($terminal, $brightness, $time=0)
    {
        // установим яркость дисплея
        return $this->sendMjdmCommand('brightness:' . $volume)
        return true;
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
