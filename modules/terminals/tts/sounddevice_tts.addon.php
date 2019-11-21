<?php

class sounddevice_tts extends tts_addon
{
    function __construct($terminal)
    {
        $this->terminal = $terminal;
        $this->title    = "Звуковые карты";
        $this->description .= '<b>Работает:</b>&nbsp; только на Виндовс;.<br>';
        $this->description .= '<b>Поддерживаемые возможности:</b>&nbsp;say(), sayTo(), sayReply().';
        $this->setting = json_decode($this->terminal['TTS_SETING'], true);
        $this->devicenumber = substr($this->setting['TTS_SOUND_DEVICE'], 0, strpos($this->setting['TTS_SOUND_DEVICE'], '^'));
        $this->devicename = substr($this->setting['TTS_SOUND_DEVICE'], strpos($this->setting['TTS_SOUND_DEVICE'], '^')+1);
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
                    safe_exec(DOC_ROOT . '/rc/smallplayer.exe -play ' . $filename . ' ' . $this->devicenumber);
                } else {
                    // linux
                }
                sleep($message['MESSAGE_DURATION']);
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
	
	 // Set volume
    function set_volume($level)
    {
        if (IsWindowsOS()) {
			exec(DOC_ROOT . '/rc/setvol.exe report ' . $level . ' device ' . $this->devicename, $out ); 
        } else {
            // linux
        }
        if ($out) {
			return TRUE;
		}
	    return FALSE;
    }
	
	// Get player status
    function status()
    {
        // Defaults
        $track_id = -1;
        $length   = 0;
        $time     = 0;
        $name     = 'unknow';
        $state    = 'unknown';
	$file     = '';
        $volume   = 0;
        $muted    = FALSE;
        $random   = FALSE;
        $loop     = FALSE;
        $repeat   = FALSE;
        
	// get volume
	if (IsWindowsOS()) {
	    exec(DOC_ROOT . '/rc/setvol.exe report device ' . $this->devicename, $volum ); 
	    $volume = str_replace("Master volume level =", "", $volum[0]);
        } else {
            // linux
        }
        $this->data    = array(
                'track_id' => (int) $track_id, //ID of currently playing track (in playlist). Integer. If unknown (playback stopped or playlist is empty) = -1.
                'name' => (string) $name, //Current speed for playing media. float.
                'file' => (string) $file, //Current link for media in device. String.
                'length' => (int) $length, //Track length in seconds. Integer. If unknown = 0. 
                'time' => (int) $time, //Current playback progress (in seconds). If unknown = 0. 
                'state' => (string) strtolower($state), //Playback status. String: stopped/playing/paused/unknown 
                'volume' => intval($volume), // Volume level in percent. Integer. Some players may have values greater than 100.
                'muted' => (int) $muted, // Volume level in percent. Integer. Some players may have values greater than 100.
                'random' => (boolean) $random, // Random mode. Boolean. 
                'loop' => (boolean) $loop, // Loop mode. Boolean.
                'repeat' => (string) $repeat //Repeat mode. Boolean.
            );
        return $this->data;
    }
}
