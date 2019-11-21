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
		DebMes ($this->devicenumber);
		DebMes ($this->devicename);
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
}
