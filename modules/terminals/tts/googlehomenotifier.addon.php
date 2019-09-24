<?php

class googlehomenotifier extends tts_addon {

    function __construct($terminal) {
        $this->terminal = $terminal;
        // содержит в себе все настройки терминала кроме айпи адреса
        $this->setting = json_decode($this->terminal['TTS_SETING'], true);
        $this->title="GoogleHomeNotifier";
        $this->description = 'Работатет с устройствами Гугл Нотифиер. Передает текстовые сообзения с параметром языка настроеннго в мажордомо. Порт по умолчанию -8091';
        parent::__construct($terminal);
    }

    // Say
    public function say_message($message, $terminal) //SETTINGS_SITE_LANGUAGE_CODE=код языка
    {
        if($message['MESSAGE']) {
            $port = $this->setting['TTS_PORT'];
            $language = SETTINGS_SITE_LANGUAGE;
            if (!$port) {
                $port = '8091';
            }
            $host = $this->terminal['HOST'];
            $url = 'http://' . $host . ':' . $port . '/google-home-notifier?language=' . SETTINGS_SITE_LANGUAGE_CODE . '&text=' . urlencode($message['MESSAGE']);
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
