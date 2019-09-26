<?php

/*
Addon dnla for app_player
*/

class dnla_tts extends tts_addon
{
    function __construct($terminal)
    {
        $this->title       = 'Устройства с поддержкой протокола DLNA';
        $this->description = 'Проигрывание системных сообщений ';
        $this->description .= 'на всех устройства поддерживающих протокол DNLA. ';
        $this->terminal = $terminal;
        $this->setting = json_decode($this->terminal['TTS_SETING'], true);
        
        // proverka na otvet
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->setting['TTS_CONTROL_ADDRESS']);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $content = curl_exec($ch);
        $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // автозаполнение поля PLAYER_CONTROL_ADDRESS при его отсутствии
        if ($retcode != 200 OR !stripos($content, 'AVTransport')) {
            // сделано специально для тех устройств которые периодически меняют свои порты и ссылки  на CONTROL_ADDRESS
            $this->setting['TTS_CONTROL_ADDRESS'] = $this->search($this->terminal['HOST']);
            if ($this->setting['TTS_CONTROL_ADDRESS']) {
                $rec = SQLSelectOne('SELECT * FROM terminals WHERE HOST="' . $this->terminal['HOST'] . '"');
                $rec['TTS_SETING'] = json_encode($this->setting);
                if (is_string($rec['TTS_SETING'])) {
                    SQLUpdate('terminals', $rec); // update
                    //DebMes('Добавлен адрес управления устройством - '.$rec['PLAYER_CONTROL_ADDRESS']);
                }
            }
        }
        include_once(DIR_MODULES . 'app_player/libs/MediaRenderer/MediaRenderer.php');
        include_once(DIR_MODULES . 'app_player/libs/MediaRenderer/MediaRendererVolume.php');
        
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

        //DebMes("Url to file " . $message_link);
        // конец блока получения ссылки на файл 
        $remote = new MediaRenderer($this->setting['TTS_CONTROL_ADDRESS']);
        $response = $remote->play($message_link);
        if ($response) {
            $this->success = TRUE;
            $this->message = 'Play files';
        } else {
            $this->success = FALSE;
            $this->message = 'Command execution error!';
        }
        sleep($message['TIME_MESSAGE']);
        return $this->success;
    }

    // Get player status
    function status()
    {
        // Defaults
        $track_id      = -1;
        $length        = 0;
        $time          = 0;
        $state         = 'unknown';
        $volume        = 0;
        $random        = FALSE;
        $loop          = FALSE;
        $repeat        = FALSE;
        $current_speed = 1;
        $curren_url    = '';
        
        // создаем хмл документ
        $doc = new \DOMDocument();
        // Для получения состояния плеера
        $remote   = new MediaRenderer($this->setting['TTS_CONTROL_ADDRESS']);
        $response = $remote->getState();
        $doc->loadXML($response);
        $state = $doc->getElementsByTagName('CurrentTransportState')->item(0)->nodeValue;
        if ($state == 'TRANSITIONING') {
            $state = 'playing';
        }
        //Debmes ($response);
        $response = $remote->getPosition();
        $doc->loadXML($response);
        $track_id = $doc->getElementsByTagName('Track')->item(0)->nodeValue;
        $name = 'Played url on the device';
        $curren_url = $doc->getElementsByTagName('TrackURI')->item(0)->nodeValue;
        $length = $remote->parse_to_second($doc->getElementsByTagName('TrackDuration')->item(0)->nodeValue);
        $time = $remote->parse_to_second($doc->getElementsByTagName('RelTime')->item(0)->nodeValue);
        //  для получения уровня громкости
        $remotevolume = new MediaRendererVolume($this->setting['TTS_CONTROL_ADDRESS']);
        $response = $remotevolume->GetVolume();
        $doc->loadXML($response);
        $volume   = $doc->getElementsByTagName('CurrentVolume')->item(0)->nodeValue;
        // Results
        if ($response) {
            $this->data    = array(
                'track_id' => (int) $track_id, //ID of currently playing track (in playlist). Integer. If unknown (playback stopped or playlist is empty) = -1.
                'name' => (string) $name, //Current speed for playing media. float.
                'file' => (string) $curren_url, //Current link for media in device. String.
                'length' => (int) $length, //Track length in seconds. Integer. If unknown = 0. 
                'time' => (int) $time, //Current playback progress (in seconds). If unknown = 0. 
                'state' => (string) strtolower($state), //Playback status. String: stopped/playing/paused/unknown 
                'volume' => (int) $volume, // Volume level in percent. Integer. Some players may have values greater than 100.
                'random' => (boolean) $random, // Random mode. Boolean. 
                'loop' => (boolean) $loop, // Loop mode. Boolean.
                'repeat' => (boolean) $repeat //Repeat mode. Boolean.
            );
        } else {
			$this->data = false;
		}
        return $this->data;
    }

    // Play
    function play($input, $position=0)
    {
        $remote = new MediaRenderer($this->setting['TTS_CONTROL_ADDRESS']);
        $response = $remote->load($input);
        $response = $remote->seek($position);
        $response = $remote->play();
        if ($response) {
            $this->success = TRUE;
            $this->message = 'Play files';
        } else {
            $this->success = FALSE;
            $this->message = 'Command execution error!';
        }
        return $this->success;
    }
	
    // Set volume
    function set_volume($level)
    {
        $remotevolume = new MediaRendererVolume($this->setting['TTS_CONTROL_ADDRESS']);
        $response = $remotevolume->SetVolume($level);
        if ($response) {
            $this->success = TRUE;
            $this->message = 'Volume changed';
        } else {
            $this->success = FALSE;
            $this->message = 'Command execution error!';
        }
        return $this->success;
    }
    
    // ping terminal
    function ping()
    {
        // proverka na otvet
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->setting['TTS_CONTROL_ADDRESS']);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $content = curl_exec($ch);
        $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($retcode != 200 OR !stripos($content, 'AVTransport')) {
            $this->success = FALSE;
            $this->message = 'Command execution error!';
        } else {
            $this->success = TRUE;
            $this->message = 'Volume changed';
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
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => '1', 'usec' => '128'));
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
