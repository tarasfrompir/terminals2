<?php

class mainterm extends app_player_addon {
	
	// Private properties
	private $curl;
	private $address;
	
	// Constructor
	function __construct($terminal) {
		$this->title = 'Основной терминал системы';
		$this->description = 'Системный терминал';
		
		$this->terminal = $terminal;
		$this->reset_properties();
		
	}
	
	// Say
    function say($params) {
		// E:\xampp\htdocs/cms/cached/voice/sapi_608333adc72f545078ede3aad71bfe74.mp3, http://192.168.1.30/cms/cached/voice/sapi_608333adc72f545078ede3aad71bfe74.mp3, 3, привет, SAY, ua, uk_UA
		// $filename, $ipfilename, $level, $message, $event, $langcode, $langfullcode

        $this->reset_properties();
	    $out = explode(',', $params);
        $filename = $out[0];
		$message = $out[4];
		//DebMes('params - '.ROOT . 'rc/madplay.exe '.$filename);
        if($message) {
            if(file_exists($filename)) {
                if (IsWindowsOS())
                    safe_exec(ROOT . 'rc/madplay.exe ' . $filename, 1);
                else
                    safe_exec('mplayer ' . $filename . " >/dev/null 2>&1", $exclusive, $priority);
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
	
}

?>
