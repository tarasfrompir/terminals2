<?php
/*
Addon iobroker.paw http for app_player
*/
class iobroker_tts extends tts_addon
{
    
    function __construct($terminal)
    {
        $this->title       = "ioBroker.paw";
        $this->description = '<b>Поддерживаемые возможности:</b>say(),sayTo()<br>';
        $this->description .= '<b>Описание:</b>&nbsp;Для работы использует &nbsp;<a href="https://play.google.com/store/apps/details?id=ru.codedevice.iobrokerpawii">ioBroker.paw</a>';
        $this->terminal      = $terminal;
        $this->setting       = json_decode($this->terminal['TTS_SETING'], true);
        $this->port          = empty($this->setting['TTS_PORT']) ? 8080 : $this->setting['TTS_PORT'];
        $this->curl          = curl_init();
        $this->address       = 'http://' . $this->terminal['HOST'] . ':' . $this->port;
        register_shutdown_function("catchTimeoutTerminals");
        parent::__construct($terminal);
    }
    // Say
    function say_message($message, $terminal) //SETTINGS_SITE_LANGUAGE_CODE=код языка
    {
        getURL($this->address . "/api/set.json?play=true", 0);
        sleep(1);
        getURL($this->address . "/api/set.json?ringtone=false", 0);
        usleep(500000);
        $url = $this->address . "/api/set.json?tts=" . urlencode($message['MESSAGE']);
        getURL($url, 0);
        sleep($message['MESSAGE_DURATION']);
        return true;
    }
    
    function turn_on_display($terminal, $time=0)
    {
        // включаем дисплей
        $url = $this->address . "/api/set.json?toWake=true";
        if ($time>0) {
            setTimeout($this->terminal['NAME'] . '_on_display',"getURL('$url', 0);", $time);
        } else {
            getURL($url, 0);
        }
        return true;
    }
    function turn_off_display($terminal, $time=0)
    {
        // выключаем дисплей
        $url = $this->address . "/api/set.json?toWake=false";
        if ($time>0) {
            setTimeout($this->terminal['NAME'] . '_off_display',"getURL('$url', 0);", $time);
        } else {
            getURL($url, 0);
        }
        return true;
    
    }
    
    function set_brightness_display($terminal, $brightness, $time=0)
    {
        // установим яркость дисплея
        $url = $this->address . "/api/set.json?brightness=" . $brightness;
        if ($time>0) {
            setTimeout($this->terminal['NAME'] . '_set_brightness',"getURL('$url', 0);", $time);
        } else {
            getURL($url, 0);
        }
        return true;
    }
    
    // Get player status
    function status()
    {
        // Defaults
        $track_id = -1;
        $length   = '';
        $time     = '';
        $state    = 'unknown';
        $volume   = '';
        $muted    = FALSE;
        $random   = FALSE;
        $loop     = FALSE;
        $repeat   = FALSE;
        $name     = 'unknow';
        $file     = '';
        $brightness = '';
        $display_state = 'unknown';
        
        $result = json_decode(getURL($this->address . "/api/get.json", 0), true);
        if ($result) {
            if ($result['display']['state']) {
                $display_state = 'On';
            } else if (!$result['display']['state']) {
                $display_state = 'Off' ;
            }
            $volume = intval($result['volume']['music'] * 100 / $result['volume']['music-max']);
            $brightness = intval($result['display']['brightness']);
        }
         $this->data = array(
                'track_id' => $track_id, //ID of currently playing track (in playlist). Integer. If unknown (playback stopped or playlist is empty) = -1.
                'name' => $name, //Current speed for playing media. float.
                'file' => $file, //Current link for media in device. String.
                'length' => $length, //Track length in seconds. Integer. If unknown = 0. 
                'time' => $time, //Current playback progress (in seconds). If unknown = 0. 
                'state' => strtolower($state), //Playback status. String: stopped/playing/paused/unknown 
                'volume' => $volume, // Volume level in percent. Integer. Some players may have values greater than 100.
                'muted' => $muted, // Muted mode. Boolean.
                'random' => $random, // Random mode. Boolean. 
                'loop' => $loop, // Loop mode. Boolean.
                'repeat' => $repeat, //Repeat mode. Boolean.
                'brightness'=> $brightness, // brightness display in %
                'display_state'=> $display_state, // unknow , On, Off  - display  state
                
            );
        return $this->data;
    }
}
?>
