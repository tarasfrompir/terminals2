<?php

class mainterm extends tts_addon
{
    function __construct($terminal)
    {
        $this->title = "MediaPlayer";
        parent::__construct($terminal);
    }

    // Say
    function sayCached($phrase, $level = 0, $cached_file = '') //SETTINGS_SITE_LANGUAGE_CODE=код языка
    {
        $this->reset_properties();
        if($message['FILE_LINK']) {
            if(file_exists($message['FILE_LINK'])) {
                if (IsWindowsOS()){
                    safe_exec(DOC_ROOT . '/rc/madplay.exe ' . $message['FILE_LINK']);
                } else {
                    safe_exec('mplayer ' . $message['FILE_LINK'] . " >/dev/null 2>&1");
                }
                sleep ($message['TIME_MESSAGE']);
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