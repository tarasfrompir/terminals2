<?php
/*
Addon iobroker.paw http for app_player
*/
class airplay_tts extends tts_addon
{
    
    function __construct($terminal)
    {
        $this->title       = "Airplay";
        $this->description = '<b>Поддерживаемые возможности:</b>say(),sayTo()<br>';
        $this->terminal      = $terminal;
        $this->setting       = json_decode($this->terminal['TTS_SETING'], true);
        $this->port          = empty($this->setting['TTS_PORT']) ? 7000 : $this->setting['TTS_PORT'];
        include_once(DIR_MODULES . 'app_player/libs/Airplay/airplay.php');
    }

    // Say
    function say_media_message($message, $terminal) //SETTINGS_SITE_LANGUAGE_CODE=код языка
    {
        if ($message['CACHED_FILENAME']) {
            $fileinfo = pathinfo($message['CACHED_FILENAME']);
            $filename = $fileinfo[dirname] . '/' . $fileinfo[filename] . '.avi';
            if (!file_exists($filename)) {
                if (!defined('PATH_TO_FFMPEG')) {
                    if (IsWindowsOS()) {
                        define("PATH_TO_FFMPEG", SERVER_ROOT . '/apps/ffmpeg/ffmpeg.exe');
                    } else {
                        define("PATH_TO_FFMPEG", 'ffmpeg');
                    }
                }
                shell_exec(PATH_TO_FFMPEG . " -loop 1 -y -i " .DOC_ROOT. "/img/logo.png -i " . $message['CACHED_FILENAME'] . " -shortest -acodec copy -vcodec mjpeg " . $filename);
            }
			$remote = new AirPlay($this->terminal['HOST'], $this->port);
			$response = $remote->sendvideo($filename);
			if ($response) {
				$this->success = TRUE;
			} else {
				$this->success = FALSE;
			}
			sleep($message['MESSAGE_DURATION']);
			return $this->success;
		}
    }
}
?>
