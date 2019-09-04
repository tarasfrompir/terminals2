<?php

class googlehomenotifier extends tts_addon {

    function __construct($terminal) {
        $this->title="GoogleHomeNotifier API";
        parent::__construct($terminal);
    }

    // Say
    public function say_message($message, $terminal) //SETTINGS_SITE_LANGUAGE_CODE=код языка
    {
        if($message['MESSAGE']) {
            $port = $this->terminal['PLAYER_PORT'];
            $language = SETTINGS_SITE_LANGUAGE;
            if (!$port) {
                $port = '8091';
            }
            $host = $this->terminal['HOST'];
            $url = 'http://' . $host . ':' . $port . '/google-home-notifier?language=' . $language . '&text=' . urlencode($message['MESSAGE']);
            getURL($url, 0);
        	usleep (200000);
            $this->success = TRUE;
            $this->message = 'OK';
        } else {
            $this->success = FALSE;
            $this->message = 'Input is missing!';
        }
        return $this->success;
    }
}
