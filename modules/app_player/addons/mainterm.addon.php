<?php

/*
    Addon Main terminal for app_player
*/

class mainterm extends app_player_addon {

    // Private properties
    private $address;
    
    // Constructor
    function __construct($terminal) {
        $this->title = 'Main terminal for Majordomo';
        $this->description = 'Основной терминал Мажордомо.';
        
        $this->terminal = $terminal;
        $this->reset_properties();
        
    }

    // Play
    function play($input) {
        $this->reset_properties();
        if(strlen($input)) {
            if(file_exists($input)) {
                if (IsWindowsOS()){
                   safe_exec(DOC_ROOT . '/rc/madplay.exe ' . $input);
                } else {
                   safe_exec('mplayer ' . $input . " >/dev/null 2>&1");
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
