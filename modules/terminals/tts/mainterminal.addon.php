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
        parent::__construct($terminal);
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
                sleep ($message['MESSAGE_DURATION']+1);
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
