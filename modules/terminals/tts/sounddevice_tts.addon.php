<?php

class sounddevice_tts extends tts_addon
{
    function __construct($terminal)
    {
        $this->terminal = $terminal;
        $this->title    = "Звуковые карты";
        $this->description .= '<b>Работает:</b>&nbsp; только на Виндовс;.<br>';
        $this->description .= '<b>Поддерживаемые возможности:</b>&nbsp;say(), sayTo(), sayReply().';
        $this->setting      = json_decode($this->terminal['TTS_SETING'], true);
        $this->devicenumber = substr($this->setting['TTS_SOUND_DEVICE'], 0, strpos($this->setting['TTS_SOUND_DEVICE'], '^'));
        $this->devicename   = substr($this->setting['TTS_SOUND_DEVICE'], strpos($this->setting['TTS_SOUND_DEVICE'], '^') + 1);
        parent::__construct($terminal);
    }
    
    // Say
    public function say_media_message($message, $terminal) //SETTINGS_SITE_LANGUAGE_CODE=код языка
    {
        if ($message['CACHED_FILENAME']) {
            $fileinfo = pathinfo($message['CACHED_FILENAME']);
            $filename = $fileinfo[dirname] . '/' . $fileinfo[filename] . '.wav';
            if (!file_exists($filename)) {
                if (!defined('PATH_TO_FFMPEG')) {
                    if (IsWindowsOS()) {
                        define("PATH_TO_FFMPEG", SERVER_ROOT . '/apps/ffmpeg/ffmpeg.exe');
                    } else {
                        define("PATH_TO_FFMPEG", 'ffmpeg');
                    }
                }
                shell_exec(PATH_TO_FFMPEG . " -i " . $message['CACHED_FILENAME'] . " -acodec pcm_s16le -ac 1 -ar 44100 " . $filename);
            }
            if (file_exists($filename)) {
                if (IsWindowsOS()) {
                    exec(DOC_ROOT . '/rc/smallplay.exe -play ' . $filename . ' ' . $this->devicenumber );
                } else {
                    // linux
                }
                sleep($message['MESSAGE_DURATION']);
                $this->success = TRUE;
            } else {
                $this->success = FALSE;
            }
        } else {
            $this->success = FALSE;
            $this->message = 'Input is missing!';
        }
        return $this->success;
    }
    
    // Set volume
    function set_volume($level)
    {
        if (IsWindowsOS()) {
            exec(DOC_ROOT . '/rc/smallplay.exe -setvolume ' . $level/100 . ' ' . $this->devicename, $volum);
        } else {
            // linux
        }
        if ($volum) {
            return TRUE;
        } else {
            return FALSE;
	}
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

	    
	    
        // get volume
        if (IsWindowsOS()) {
            exec(DOC_ROOT . '/rc/smallplay.exe -getvolume ' . $this->devicename . ' 2>&1', $volum);
        } else {
            // linux
        }
        if ($volum) {
            $volume = intval($volum[0]*100);
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
	
	
	
	// Get terminal status
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
	
	    // get volume
        if (IsWindowsOS()) {
            exec(DOC_ROOT . '/rc/smallplay.exe -getvolume ' . $this->devicename . ' 2>&1', $volume);
        } else {
            // linux
        }
        if ($volume) {
            $volume_media = intval($volum[0]*100);
	    }
	
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
