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
        $this->address       = 'http://' . $this->terminal['HOST'] . ':' . $this->port;
		include_once(DIR_MODULES . 'app_player/libs/Airplay/airplay.php');
    }

    // Say
    function say_media_message($message, $terminal) //SETTINGS_SITE_LANGUAGE_CODE=код языка
    {
        $outlink = $message['CACHED_FILENAME'];
        // берем ссылку http
        if (preg_match('/\/cms\/cached.+/', $outlink, $m)) {
            $server_ip = getLocalIp();
            if (!$server_ip) {
                DebMes("Server IP not found", 'terminals');
                return false;
            } else {
                $message_link = 'http://' . $server_ip . $m[0];
            }
        }
        //DebMes("Url to file " . $message_link);
        // конец блока получения ссылки на файл 

        $remote = new AirPlay($this->terminal['HOST'], $this->port);
        $response = $remote->sendvideo($message_link);
        if ($response) {
            $this->success = TRUE;
        } else {
            $this->success = FALSE;
        }
        sleep($message['MESSAGE_DURATION']);
        return $this->success;
    }
}
?>
