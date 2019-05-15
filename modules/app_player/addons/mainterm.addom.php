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
        $this->description = 'Умная колонка от Google.';
        
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
        // E:\xampp\htdocs/cms/cached/voice/sapi_608333adc72f545078ede3aad71bfe74.mp3, http://192.168.1.30/cms/cached/voice/sapi_608333adc72f545078ede3aad71bfe74.mp3, 3, привет, SAY, ua, uk_UA
        // $filename, $ipfilename, $level, $message, $event, $langcode, $langfullcode
	$this->reset_properties();
	$out = explode(',', $param);
	$filename = $out[0];
	$level = $out[1];
	$message = $out[2];
	$event = $out[3];
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
