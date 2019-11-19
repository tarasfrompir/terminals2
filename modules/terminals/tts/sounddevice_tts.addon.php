<?php

class sounddevice_tts extends tts_addon
{
    function __construct($terminal)
    {
        $this->title = "Звуковые карты";
	$this->description .= '<b>Работает:</b>&nbsp; только на Виндовс;.<br>';
	$this->description .= '<b>Поддерживаемые возможности:</b>&nbsp;say(), sayTo(), sayReply().';    
        parent::__construct($terminal);
    }

    // Say
    public function say_media_message($message, $terminal) //SETTINGS_SITE_LANGUAGE_CODE=код языка
    {
        if($message['CACHED_FILENAME']) {
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
				shell_exec(PATH_TO_FFMPEG . " -i " . $message['CACHED_FILENAME'] . " -acodec pcm_u8 -ar 22050 " . $fileinfo[dirname] . '/' . $fileinfo[filename] . '.wav');
				DebMes($filename);
			}
            if(file_exists($message['CACHED_FILENAME'])) {
                if (IsWindowsOS()){
                    safe_exec(DOC_ROOT . '/rc/smallplayer.exe -play ' . $filename. ' '.$terminal['TTS_SOUND_DEVICE']);
                } 
                sleep ($message['MESSAGE_DURATION']);
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
