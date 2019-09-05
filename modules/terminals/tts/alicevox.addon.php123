<?php
/*
Addon Kodi (XBMC) for app_player
*/
class alicevox extends tts_addon
{
    function __construct($terminal)
    {
        $this->title       = 'KODI (XBMC) alicevox';
        $this->description = 'Описание: Бесплатный кроссплатформенный медиаплеер и программное обеспечение для организации HTPC с открытым исходным кодом.';
        $this->description .= 'Поддерживаемые возможности say, sayto, sayreply, ask через alicevox плагин для KODI (https://mjdm.ru/forum/viewtopic.php?f=5&t=2893&start=120).';
        parent::__construct($terminal);
    }
    
    // Say
    public function say_message($message, $terminal) //SETTINGS_SITE_LANGUAGE_CODE=код языка
    {
        if($message['CACHED_FILENAME']) {
            if(file_exists($message['CACHED_FILENAME'])) {
                // берем ссылку http
                if (preg_match('/\/cms\/cached.+/', $message['CACHED_FILENAME'], $m)) {
                    $server_ip = getLocalIp();
                    if (!$server_ip) {
                       DebMes("Server IP not found", 'terminals');
                       return false;
                } else {
                    $outlink = 'http://' . $server_ip . $m[0];
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
?>
