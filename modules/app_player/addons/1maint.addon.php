<?php

/*
    Addon Main terminal for app_player
*/

class 1maint extends app_player_addon {

    // Private properties
    private $address;
    
    // Constructor
    function __construct($terminal) {
        $this->title = 'Основной терминал Мажордомо';
        $this->description = 'Описание: Тип терминала для воспроизведения сообщений на локальном сервере.';
        
        $this->terminal = $terminal;
        $this->reset_properties();
        
    }

    // Get player status
    function status() {
        $this->reset_properties();
        // Defaults
        $track_id = -1;
        $length   = 0;
        $time     = 0;
        $state    = 'unknown';
        $volume   = 0;
        $random   = FALSE;
        $loop     = FALSE;
        $repeat   = FALSE;
 
        return $this->success;
    }
    
    // Playlist: Get
    function pl_get() {
        $this->success = FALSE;
        $this->message = 'Command execution error!';
        $track_id      = -1;
        $name          = 'unknow';
        $curren_url    = '';
     
        return $this->success;
    }
	
    // Say
    function sayToMedia($message_link, $time_message) { //SETTINGS_SITE_LANGUAGE_CODE=код языка
        $this->reset_properties();
        if($time_message) {
            if(file_exists($message_link)) {
                if (IsWindowsOS()){
                    safe_exec(DOC_ROOT . '/rc/madplay.exe ' . $message_link);
                } else {
                    safe_exec('mplayer ' . $message_link . " >/dev/null 2>&1");
                }
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
        return $this->message;
    }
}
?>
