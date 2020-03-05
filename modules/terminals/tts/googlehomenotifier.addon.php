<?php

class googlehomenotifier extends tts_addon {

    function __construct($terminal) {
        $this->title="GoogleHomeNotifier";
        $this->description = '<b>Описание:</b>&nbsp; Работает с google home устройствами через запущенный сервис &nbsp;<a href="https://github.com/noelportugal/google-home-notifier">google-home</a>. Передает текстовые сообщения с параметром языка, выбранного Вами в мажордомо.<br>Ссылка на &nbsp;<a href="https://connect.smartliving.ru/profile/1502/blog38.html">how-to</a>.<br>Ссылка на &nbsp;<a href="https://mjdm.ru/forum/viewtopic.php?f=23&t=5042&hilit=google+home">тему форума</a>.<br>';
        $this->description .= '<b>Проверка доступности:</b>&nbsp; ??? нужно разбираться ???.<br>';
        $this->description .= '<b>Настройка:</b>&nbsp; Порт доступа по умолчанию 8091 (если по умолчанию, можно не указывать).<br>';
        $this->description .= '<b>Поддерживаемые возможности:</b>&nbsp;say(), sayTo(), sayReply().';
        $this->terminal = $terminal;
        if (!$this->terminal['HOST']) return false;
	    
        // содержит в себе все настройки терминала кроме айпи адреса
        $this->setting = json_decode($this->terminal['TTS_SETING'], true);
        register_shutdown_function("catchTimeoutTerminals");
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
