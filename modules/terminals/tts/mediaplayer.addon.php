<?php

class mediaplayer extends tts_addon
{
    function __construct($terminal)
    {
        $this->title = "MediaPlayer";
        parent::__construct($terminal);
    }
    
    // Say
    public function say_message($message, $terminal) //SETTINGS_SITE_LANGUAGE_CODE=код языка
    {
        if ($message['CACHED_FILENAME']) {
            if (file_exists($message['CACHED_FILENAME'])) {
                if (preg_match('/\/cms\/cached.+/', $message['CACHED_FILENAME'], $m)) {
                    $message['CACHED_FILENAME'] = 'http://' . getLocalIp() . $m[0];
                    stopMedia($terminal['HOST']);
                    setPlayerVolume($terminal['HOST'], $terminal['MESSAGE_VOLUME_LEVEL']);
                    playMedia($message['CACHED_FILENAME'], $terminal['NAME']);
                    sleep($message['MESSAGE_DURATION']);
                    $this->success = TRUE;
                    $this->message = 'OK';
                } else {
                    $this->success = FALSE;
                    $this->message = 'Input is missing!';
                }
            } else {
                $this->success = FALSE;
                $this->message = 'Command execution error!';
            }
        } else {
            $this->success = FALSE;
            $this->message = 'Input is missing!';
        }
        return $this->success;
    }
}
