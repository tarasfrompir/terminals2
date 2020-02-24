<?php

class tts_addon {
    // Addon info
    private $title = NULL;
    public $terminal = NULL;

    function __construct($terminal) {
        $this->terminal = $terminal;
    }

    public function ask($phrase, $level) {
        return $this->say($phrase);
    }

    public function set_volume_notification($volume) {
        return false;
    }

    public function set_volume_alarm($volume) {
        return false;
    }
	
    public function set_volume_ring($volume) {
        return false;
    }

    public function set_volume($volume) {
        return false;
    }

    function ping()
    {
        return false;
    }

    function say_media_message($message, $terminal)
    {
        return false;
    }

    function say_message($message, $terminal)
    {
        return false;
    }

    function set_brightness_display($brightness)
    {
        return false;
    }

    function turn_on_display($time)
    {
        return false;
    }

    function turn_off_display($time)
    {
        return false;
    }

    function stop()
    {
        return false;
    }
	
	function terminal_status()
    {
        // Defaults
        $listening_keyphrase = -1;
		$volume_media        = -1;
        $volume_ring         = -1;
        $volume_alarm        = -1;
        $volume_notification = -1;
        $brightness_auto     = -1;
        $recognition         = -1;
        $fullscreen          = -1;
        $brightness          = -1;
        $display_state       = -1;
        $battery             = -1;
		
        $out_data = array(
                'listening_keyphrase' =>(string) strtolower($listening_keyphrase), // ключевое слово терминал для  начала распознавания (-1 - не поддерживается терминалом)
                'volume_media' => (int)$volume_media, // громкость медиа на терминале (-1 - не поддерживается терминалом)
                'volume_ring' => (int)$volume_ring, // громкость звонка к пользователям на терминале (-1 - не поддерживается терминалом)
                'volume_alarm' => (int)$volume_alarm, // громкость аварийных сообщений на терминале (-1 - не поддерживается терминалом)
                'volume_notification' => (int)$volume_notification, // громкость простых сообщений на терминале (-1 - не поддерживается терминалом)
                'brightness_auto' => (int) $brightness_auto, // автояркость включена или выключена 1 или 0 (-1 - не поддерживается терминалом)
                'recognition' => (int) $recognition, // распознавание на терминале включена или выключена 1 или 0 (-1 - не поддерживается терминалом)
                'fullscreen' => (int) $recognition, // полноекранный режим на терминале включена или выключена 1 или 0 (-1 - не поддерживается терминалом)
                'brightness' => (int) $brightness, // яркость екрана (-1 - не поддерживается терминалом)
                'battery' => (int) $battery, // заряд акумулятора терминала в процентах (-1 - не поддерживается терминалом)
                'display_state'=> (int) $display_state, // 1, 0  - состояние дисплея (-1 - не поддерживается терминалом)
            );
		
        // удаляем из массива пустые данные
        foreach ($out_data as $key => $value) {
            if ($value == '-1') unset($out_data[$key]); ;
        }
        return $out_data;
    }
}