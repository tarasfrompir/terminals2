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
            $this->mpd->PLClear();
            $this->mpd->PLAdd($message_link);
            if ($this->mpd->Play()) {
                sleep($message['MESSAGE_DURATION']);
                $this->mpd->Stop();
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
}

?>
