<?php

class mainterminal extends tts_addon
{
    function __construct($terminal)
    {
        $this->title = "Основной терминал системы";
        parent::__construct($terminal);
    }

    // Say
    public function say_message($message, $terminal) //SETTINGS_SITE_LANGUAGE_CODE=код языка
    {
        if($message['CACHED_FILENAME']) {
            if(file_exists($message['CACHED_FILENAME'])) {
                if (IsWindowsOS()){
                    safe_exec(DOC_ROOT . '/rc/madplay.exe ' . $message['CACHED_FILENAME']);
                } else {
                    safe_exec('mplayer ' . $message['CACHED_FILENAME'] . " >/dev/null 2>&1");
                }
        		sleep ($message['MESSAGE_DURATION']);
                $this->success = TRUE;
                $this->message = 'OK';
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