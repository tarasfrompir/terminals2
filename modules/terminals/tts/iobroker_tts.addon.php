<?php

/*
	Addon iobroker.paw http for app_player
*/

class iobroker_tts extends tts_addon {
	

    function __construct($terminal) {
	$this->title="ioBroker.paw";  
	$this->description = '<b>Поддерживаемые возможности:</b>say()<br>';
	$this->description .= '<b>Описание:</b>&nbsp;Для работы использует &nbsp;<a href="https://play.google.com/store/apps/details?id=ru.codedevice.iobrokerpawii">ioBroker.paw</a>';
	
	$this->terminal = $terminal;
        $this->setting = json_decode($this->terminal['TTS_SETING'], true);  
        $this->port = empty($this->setting['TTS_PORT']) ? 8080 : $this->setting['TTS_PORT'];
	$this->curl = curl_init();
	$this->address = 'http://' . $this->terminal['HOST'] . ':' . $this->port;
	parent::__construct($terminal);
    }

    // Say
    function say_message($message, $terminal) //SETTINGS_SITE_LANGUAGE_CODE=код языка
    {
	getURLBackground($this->address . "/api/set.json?ringtone=content%3A%2F%2Fmedia%2Finternal%2Faudio%2Fmedia%2F36",0);
	usleep(500000);
	getURLBackground($this->address . "/api/set.json?ringtone=false",0);
	sleep(1);
	$url = $this->address . "/api/set.json?tts=" . urlencode($message['MESSAGE']);
	getURLBackground($url,0);
    	usleep(500000);
    }
}

?>
