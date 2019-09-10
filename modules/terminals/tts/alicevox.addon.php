<?php
/*
Addon Kodi (XBMC) for app_player
*/
class alicevox extends tts_addon
{
    function __construct($terminal)
    {
        $this->title = "Alicevox";
        parent::__construct($terminal);
    }
    
    
    // Say
    public function say_message($message, $terminal) //SETTINGS_SITE_LANGUAGE_CODE=код языка
    {
        if ($message['CACHED_FILENAME']) {
            if (file_exists($message['CACHED_FILENAME'])) {
                if (preg_match('/\/cms\/cached.+/', $message['CACHED_FILENAME'], $m)) {
					include_once(DIR_MODULES . "app_player/addons/kodi.addon.php");
                    $kodi = new kodi();
                    $message['CACHED_FILENAME'] = 'http://' . getLocalIp() . $m[0];
                    $kodi->kodi_request('Addons.ExecuteAddon', array('addonid' => 'script.alicevox.master','params' => array($message['CACHED_FILENAME'])));
                    sleep($message['MESSAGE_DURATION']);
                    $this->success = TRUE;
                    $this->message = 'OK';
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