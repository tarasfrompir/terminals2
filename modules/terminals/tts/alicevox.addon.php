<?php
/*
Addon Kodi (XBMC) for app_player
*/
class alicevox extends tts_addon
{
    function __construct($terminal)
    {
		$this->terminal = $terminal;
        $this->title   = "Alicevox";
		$this->description = 'Работает на ХВМС устройствах с установленным скриптом  Аливокс.';
        $this->address = '192.168.1.51:8080';

        parent::__construct($terminal);
    }
    
    
    // Say
    public function say_message($message, $terminal) //SETTINGS_SITE_LANGUAGE_CODE=код языка
    {
        if ($message['CACHED_FILENAME']) {
            if (file_exists($message['CACHED_FILENAME'])) {
                if (preg_match('/\/cms\/cached.+/', $message['CACHED_FILENAME'], $m)) {
                    $message['CACHED_FILENAME'] = 'http://' . getLocalIp() . $m[0];
                    $url="http://xbmc:xbmc@".$this->address."/jsonrpc?request={\"jsonrpc\":\"2.0\",\"method\":\"Addons.ExecuteAddon\",\"params\":{\"addonid\":\"script.alicevox.master\",\"params\":[\"".$message['CACHED_FILENAME']."\"]},\"id\":1}";


                    $result = getURL($url, 0);
 
                    if ($result) {
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
