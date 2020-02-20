<?php

/*
Addon dnla for app_player
*/

class dnla_media extends app_player_addon
{
    function __construct($terminal)
    {
        $this->title       = 'Устройства с поддержкой протокола DLNA';
        $this->description = '<b>Описание:</b>&nbsp; Воспроизведение звука на всех устройства поддерживающих протокол DNLA.<br>';
        $this->description .= 'Воспроизведение видео на терминале этого типа пока не поддерживается.<br>';
		$this->description .= '<b>Восстановление воспроизведения после TTS:</b>&nbsp; Да (если ТТС такого же типа, что и плеер). Если же тип ТТС и тип плеера для терминала различны, то плейлист плеера при ТТС не потеряется при любых обстоятельствах).<br>';
		$this->description .= '<b>Проверка доступности:</b>&nbsp;service_ping.<br>';
		$this->description .= '<b>Настройка:</b>&nbsp; Адрес управления вида http://ip:port/ (указывать не нужно, т.к. определяется автоматически и может отличаться для различных устройств).';
        $this->terminal = $terminal;
        $this->reset_properties();
        
        // proverka na otvet
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->terminal['PLAYER_CONTROL_ADDRESS']);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $content = curl_exec($ch);
        $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // автозаполнение поля PLAYER_CONTROL_ADDRESS при его отсутствии
        if ($retcode != 200 OR !stripos($content, 'AVTransport')) {
            // сделано специально для тех устройств которые периодически меняют свои порты и ссылки  на CONTROL_ADDRESS
            $this->terminal['PLAYER_CONTROL_ADDRESS'] = $this->search($this->terminal['HOST']);
            if ($this->terminal['PLAYER_CONTROL_ADDRESS']) {
                $rec = SQLSelectOne('SELECT * FROM terminals WHERE HOST="' . $this->terminal['HOST'] . '"');
                $rec['PLAYER_CONTROL_ADDRESS'] = $this->terminal['PLAYER_CONTROL_ADDRESS'];
                if (is_string($rec['PLAYER_CONTROL_ADDRESS'])) {
                    SQLUpdate('terminals', $rec); // update
                    //DebMes('Добавлен адрес управления устройством - '.$rec['PLAYER_CONTROL_ADDRESS']);
				}
            }
        }
        include_once(DIR_MODULES . 'app_player/libs/MediaRenderer/MediaRenderer.php');
        include_once(DIR_MODULES . 'app_player/libs/MediaRenderer/MediaRendererVolume.php');
        
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
        
        // создаем хмл документ
        $doc          = new \DOMDocument();
        //  для получения уровня громкости
        $remotevolume = new MediaRendererVolume($this->terminal['PLAYER_CONTROL_ADDRESS']);
        $response     = $remotevolume->GetVolume();
        $doc->loadXML($response);
        $volume   = $doc->getElementsByTagName('CurrentVolume')->item(0)->nodeValue;
        // Для получения состояния плеера
        $remote   = new MediaRenderer($this->terminal['PLAYER_CONTROL_ADDRESS']);
        $response = $remote->getState();
        $doc->loadXML($response);
        $state = $doc->getElementsByTagName('CurrentTransportState')->item(0)->nodeValue;
        if ($state == 'TRANSITIONING') {
            $state = 'playing';
        }
        //Debmes ('current_speed '.$current_speed);
        // получаем местоположение трека 
		$response = $remote->getPosition();
        $doc->loadXML($response);
        $track_id = $doc->getElementsByTagName('Track')->item(0)->nodeValue;
        $length   = $remote->parse_to_second($doc->getElementsByTagName('TrackDuration')->item(0)->nodeValue);
        $time     = $remote->parse_to_second($doc->getElementsByTagName('RelTime')->item(0)->nodeValue);
        $file = $doc->getElementsByTagName('TrackURI')->item(0)->nodeValue;

		$this->data = array(
                'playlist_id' => (int)$playlist_id, // номер или имя плейлиста 
                'playlist_content' => $playlist_content, // содержимое плейлиста должен быть ВСЕГДА МАССИВ 
                                                         // обязательно $playlist_content[$i]['pos'] - номер трека
                                                         // обязательно $playlist_content[$i]['file'] - адрес трека
                                                         // возможно $playlist_content[$i]['Artist'] - артист
                                                         // возможно $playlist_content[$i]['Title'] - название трека
				'track_id' => (int) track_id, //ID of currently playing track (in playlist). Integer. If unknown (playback stopped or playlist is empty) = -1.
			    'name' => (string) $name, //Current speed for playing media. float.
				'file' => (string) $file, //Current link for media in device. String.
                'length' => (int) $length, //Track length in seconds. Integer. If unknown = 0. 
                'time' => (int) $time, //Current playback progress (in seconds). If unknown = 0. 
                'state' => (string) strtolower($state), //Playback status. String: stopped/playing/paused/unknown 
                'volume' => (int)$volume, // Volume level in percent. Integer. Some players may have values greater than 100.
                'muted' => (int) $random, // Volume level in percent. Integer. Some players may have values greater than 100.
                'random' => (int) $random, // Random mode. Boolean. 
                'loop' => (int) $loop, // Loop mode. Boolean.
                'repeat' => (int) $repeat, //Repeat mode. Boolean.
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
   
    // Get media volume level
    function get_volume()
    {
        if ($this->status()) {
            $volume        = $this->data['volume'];
            $this->success = TRUE;
            $this->message = 'Volume get';
            $this->data    = $volume;
        } else if (strtolower($this->terminal['HOST']) == 'localhost' || $this->terminal['HOST'] == '127.0.0.1') {
            $this->reset_properties(array(
                'success' => TRUE,
                'message' => 'OK'
            ));
            $this->data    = (int) getGlobal('ThisComputer.volumeMediaLevel');
            $this->success = TRUE;
            $this->message = 'Volume get';
        }
        return $this->success;
    }

    // Pause
    function pause()
    {
        $this->reset_properties();
        $remote   = new MediaRenderer($this->terminal['PLAYER_CONTROL_ADDRESS']);
        $response = $remote->pause();
        if ($response) {
            $this->success = TRUE;
            $this->message = 'Pause enabled';
        } else {
            $this->success = FALSE;
            $this->message = 'Command execution error!';
        }
        return $this->success;
    }
    
    // Next
    function next()
    {
        $this->reset_properties();
        $remote   = new MediaRenderer($this->terminal['PLAYER_CONTROL_ADDRESS']);
        $response = $remote->next();
        if ($response) {
            $this->success = TRUE;
            $this->message = 'Next file changed';
        } else {
            $this->success = FALSE;
            $this->message = 'Command execution error!';
        }
        return $this->success;
    }
    
    // Previous
    function previous()
    {
        $this->reset_properties();
        $remote   = new MediaRenderer($this->terminal['PLAYER_CONTROL_ADDRESS']);
        $response = $remote->previous();
        if ($response) {
            $this->success = TRUE;
            $this->message = 'Previous file changed';
        } else {
            $this->success = FALSE;
            $this->message = 'Command execution error!';
        }
        return $this->success;
    }
    
    // Set volume
    function set_volume($level)
    {
        $this->reset_properties();
        $remotevolume = new MediaRendererVolume($this->terminal['PLAYER_CONTROL_ADDRESS']);
        $response     = $remotevolume->SetVolume($level);
        if ($response) {
            $this->success = TRUE;
            $this->message = 'Volume changed';
        } else {
            $this->success = FALSE;
            $this->message = 'Command execution error!';
        }
        return $this->success;
    }
    
    // Stop
    function stop()
    {
        $this->reset_properties();
        $remote   = new MediaRenderer($this->terminal['PLAYER_CONTROL_ADDRESS']);
        $response = $remote->stop();
        if ($response) {
            $this->success = TRUE;
            $this->message = 'Stop play';
        } else {
            $this->success = FALSE;
            $this->message = 'Command execution error!';
        }
        return $this->success;
    }
    
    // Play
    function play($input)
    {
        $this->reset_properties();
        $remote = new MediaRenderer($this->terminal['PLAYER_CONTROL_ADDRESS']);
        // для радио 101 ру
        if (stripos($input, '?userid=0&setst')) {
            $input = stristr($input, '&setst', True) . '.mp4';
        }
        $response = $remote->play($input);
        if ($response) {
            $this->success = TRUE;
            $this->message = 'Play files';
        } else {
            $this->success = FALSE;
            $this->message = 'Command execution error!';
        }
        return $this->success;
    }
    
    // Seek
    function seek($position)
    {
        $this->reset_properties();
        $remote   = new MediaRenderer($this->terminal['PLAYER_CONTROL_ADDRESS']);
        $response = $remote->seek($position);
        if ($remote) {
            $this->success = TRUE;
            $this->message = 'Position changed';
        } else {
            $this->success = FALSE;
            $this->message = 'Command execution error!';
        }
        return $this->success;
    }
    
    // функция автозаполнения поля PLAYER_CONTROL_ADDRESS при его отсутствии
    private function search($ip = '239.255.255.250')
    {
        if (!$ip) {
            return false;
        }
        //create the socket
        $socket = socket_create(AF_INET, SOCK_DGRAM, 0);
        socket_set_option($socket, SOL_SOCKET, SO_BROADCAST, true);
        //all
        $request = 'M-SEARCH * HTTP/1.1' . "\r\n";
        $request .= 'HOST: 239.255.255.250:1900' . "\r\n";
        $request .= 'MAN: "ssdp:discover"' . "\r\n";
        $request .= 'MX: 2' . "\r\n";
        $request .= 'ST: ssdp:all' . "\r\n";
        $request .= 'USER-AGENT: Majordomo/ver-x.x UDAP/2.0 Win/7' . "\r\n";
        $request .= "\r\n";
        
        @socket_sendto($socket, $request, strlen($request), 0, $ip, 1900);
        
        // send the data from socket
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array(
            'sec' => '1',
            'usec' => '128'
        ));
        do {
            $buf = null;
            if (($len = @socket_recvfrom($socket, $buf, 2048, 0, $ip, $port)) == -1) {
                echo "socket_read() failed: " . socket_strerror(socket_last_error()) . "\n";
            }
            if (!is_null($buf)) {
                $messages = explode("\r\n", $buf);
                foreach ($messages as $row) {
                    if (stripos($row, 'loca') === 0 and stripos($row, $this->terminal['HOST'])) {
                        $response = str_ireplace('location: ', '', $row);
                        $out = str_ireplace('location:', '', $response);
                    }
                    if (stripos($row, 'AVTransport')) {
						$out = $response;
                        break;
                    }
                }
            }
        } while (!is_null($buf));
        socket_close($socket);
        return $out;
    }
}
?>
