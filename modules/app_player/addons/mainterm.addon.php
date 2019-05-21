<?php

/*
    Addon Main terminal for app_player
*/

class mainterm extends app_player_addon {

    // Private properties
    private $address;
    
    // Constructor
    function __construct($terminal) {
        $this->title = 'Основной терминал Мажордомо.';
        $this->description = 'Основной терминал Мажордомо.';
        
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
    function say($param) {
        //$terminal, $message, $event, $member, $level, $filename, $linkfile, $lang, $langfull
        $this->reset_properties();
        $out = explode(',', $param);
        $filename = $out[5];
        $message = $out[1];
        if(strlen($message)) {
            if(file_exists($filename)) {
                if (IsWindowsOS()){
                    safe_exec(DOC_ROOT . '/rc/madplay.exe ' . $filename);
                } else {
                    safe_exec('mplayer ' . $filename . " >/dev/null 2>&1");
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
        return $this->success;
    }
}
?>
