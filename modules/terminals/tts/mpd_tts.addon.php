<?php

/*
Addon MPD for tts
*/

class mpd_tts extends tts_addon {

    // Constructor
    function __construct($terminal) {
        $this->title       = 'Music Player Daemon (MPD)';
        $this->description = '<b>Описание:</b>&nbsp; Воспроизведение звука через кроссплатформенный музыкальный проигрыватель, который имеет клиент-серверную архитектуру.<br>';
        $this->terminal    = $terminal;
        $this->setting     = json_decode($this->terminal['TTS_SETING'], true);
        $this->port        = empty($this->setting['TTS_PORT']) ? 6600 : $this->setting['TTS_PORT'];
        $this->password    = $this->setting['TTS_PASSWORD'];
        // MPD
        include (DIR_MODULES . 'app_player/libs/mpd/mpd.class.php');
        $this->mpd = new mpd_player($this->terminal['HOST'], $this->port, $this->password);        
    }
    
    // Say
    function say_media_message($message, $terminal) {
        if ($this->mpd->connected) {
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
            //DebMes($this->mpd->SetVolume(50));
            //DebMes($this->mpd->GetStatus());
            //DebMes($this->mpd->Stop());
            //DebMes($this->mpd->GetStatus());
            //DebMes($this->mpd->Play());
            $this->mpd->SetRepeat(0);
            $this->mpd->SetRandom(0);
            $this->mpd->SetCrossfade(0);
            $this->mpd->PLClear();
            $this->mpd->PLAdd($message_link);
            if ($this->mpd->Play()) {
                sleep($message['MESSAGE_DURATION']);
                $this->success = TRUE;                
            } else {
                $this->success = FALSE;
            }
        } else {
            $this->success = FALSE;
        }
        $this->mpd->Disconnect();
        return $this->success;
    }

    // Set volume
    function set_volume($level) {
        if ($this->mpd->connected) {
            try {
                if ($this->mpd->SetVolume($level)) {
                    $this->success = TRUE;
                } else {
                    $this->success = FALSE;
                }
            }
            catch (Exception $e) {
                $this->success = FALSE;
            }
        } else {
            $this->success = FALSE;
        }
        return $this->success;
    }
    
    // Play
    function play($input, $time = 0) {
        if ($this->mpd->connected) {
            try {
                $this->mpd->PLClear();
                $this->mpd->PLAdd($message_link);
                if ($this->mpd->$this->mpd->Play($input, $time)) {
                    $this->success = TRUE;
                } else {
                    $this->success = FALSE;
                }
            }
            catch (Exception $e) {
                $this->success = FALSE;
            }
        } else {
            $this->success = FALSE;
        }
        return $this->success;
    }

    // Stop
    function stop() {
        if ($this->mpd->connected) {
            try {
                if ($this->mpd->Stop()) {
                    $this->success = TRUE;
                } else {
                    $this->success = TRUE;
                }
            }
            catch (Exception $e) {
                $this->success = FALSE;
            }
        } else {
            $this->success = FALSE;
        }
        return $this->success;
    }
    
    // ping terminal
    function ping() {
        if ($this->mpd->connected) {
            if ($this->mpd->Ping()) {
            $this->success = TRUE;    
            } else {
                $this->success = FALSE;
            }
        } else {
            $this->success = FALSE;
        }
        $this->mpd->Disconnect();
        return $this->success;
    }
    
    // Get player status
    function status() {
        // Defaults
        $playlistid    = -1;
        $track_id      = -1;
        $length        = 0;
        $time          = 0;
        $name          = 'unknow';
        $state         = 'unknown';
        $volume        = 0;
        $random        = FALSE;
        $loop          = FALSE;
        $repeat        = FALSE;
        $brightness    = '';
        $display_state = false;


        if ($this->mpd->connected) {
            $result = $this->mpd->GetStatus();
        }
        // получаем плейлист - возможно он не сохранен поэтому получаем его полностью
        if ($this->mpd->connected) {
            $playlist_content = $this->mpd->GetPlaylistinfo ();
        }
     
        if ($result) {
            $this->data = array(
                'playlist_id' => $result['playlist'], // номер или имя плейлиста 
                'playlist_content' => $playlist_content, // содержимое плейлиста должен быть ВСЕГДА МАССИВ 
                                                         // обязательно $playlist_content[$i]['pos'] - номер трека
                                                         // обязательно $playlist_content[$i]['file'] - адрес трека
                                                         // возможно $playlist_content[$i]['Artist'] - артист
                                                         // возможно $playlist_content[$i]['Title'] - название трека
                'track_id' => (int) $result['songid'], //текущий номер трека
                'name' => (string) $name, //текущее имя трека 
                'file' => (string) $result['file'], //ссылка на текущий файл .
                'length' => intval($result['duration']), //длинна плейлиста (песни). 
                'time' => intval($result['time']), //текущая позиции по времени трека 
                'state' => (string) strtolower($result['state']), //Playback status. String: stopped/playing/paused/unknown 
                'volume' => intval($status['volume']), // Volume level in percent. Integer. Some players may have values greater than 100.
                'muted' => intval($result['muted']), // Volume level in percent. Integer. Some players may have values greater than 100.
                'random' => (boolean) $result['random'], // Random mode. Boolean. 
                'loop' => (boolean) $result['loop'], // Loop mode. Boolean.
                'crossfade' => (boolean) $result['xfade'], // crossfade
                'repeat' => (boolean) $result['repeat'], //Repeat mode. Boolean.
                'brightness' => $brightness, // яркость екрана
                'display_state' => (boolean) ($display_state) // состояние екрана включен (выключен)
            );
        }
        $this->mpd->Disconnect();
        return $this->data;
    }
    
}

?>
