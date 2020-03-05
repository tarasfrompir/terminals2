<?php

class mainterminal extends tts_addon
{
    function __construct($terminal)
    {
        $this->title = "Основной терминал системы";
	$this->description = '<b>Описание:</b>&nbsp; Использует системный звуковой плеер, работает на встроенной звуковой карте сервера, без каких либо настроек.<br>';
	$this->description .= '<b>Проверка доступности:</b>&nbsp; ip_ping.<br>';
	$this->description .= '<b>Настройка:</b>&nbsp; Пользователи OS Linux могут указать предпочитаемый плеер, см. /path/to/majordomo/config.php, опция Define(\'AUDIO_PLAYER\',\'player_name\');.<br>';
	$this->description .= '<b>Поддерживаемые возможности:</b>&nbsp;say(), sayTo(), sayReply(), ask().';    

        $this->terminal = $terminal;
        if (!$this->terminal['HOST']) return false;
	    
	    register_shutdown_function("catchTimeoutTerminals");
    }

    // Say
    public function say_media_message($message, $terminal) //SETTINGS_SITE_LANGUAGE_CODE=код языка
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
            } else {
                $this->success = FALSE;
            }
        } else {
            $this->success = FALSE;
        }
        return $this->success;
    }
}
