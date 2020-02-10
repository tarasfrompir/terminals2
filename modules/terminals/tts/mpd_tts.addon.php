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
            DebMes($this->mpd->GetStatus());
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
    function statuss() {
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
        if ($result) {
            $this->data = array(
                'playlist_id' => $result['playlist'], // numder or name playlist 
                'track_id' => (int) $result['songid'], //ID of currently playing track (in playlist). Integer. If unknown (playback stopped or playlist is empty) = -1.
                'name' => (string) $name, //Current speed for playing media. float.
                'file' => (string) $result['file'], //Current link for media in device. String.
                'length' => intval($result['duration']), //Track length in seconds. Integer. If unknown = 0. 
                'time' => intval($result['time']), //Current playback progress (in seconds). If unknown = 0. 
                'state' => (string) strtolower($result['state']), //Playback status. String: stopped/playing/paused/unknown 
                'volume' => intval($status['volume']), // Volume level in percent. Integer. Some players may have values greater than 100.
                'muted' => intval($result['muted']), // Volume level in percent. Integer. Some players may have values greater than 100.
                'random' => (boolean) $result['random'], // Random mode. Boolean. 
                'loop' => (boolean) $result['loop'], // Loop mode. Boolean.
                'repeat' => (boolean) $result['repeat'], //Repeat mode. Boolean.
                'brightness' => $brightness,
                'display_state' => (boolean) ($display_state)
            );
        }
        $this->mpd->Disconnect();
        DebMes($result);
        return $this->data;
    }
    
}

?>
