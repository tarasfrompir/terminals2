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
   // function sayToMedia($message_link, $time_message) //SETTINGS_SITE_LANGUAGE_CODE=код языка
//    {
  //      // преобразовываем файл в вав формат
//        $path_parts = pathinfo($message_link);
////        if ($path_parts['extension'] != 'wav') {
//            if (!defined('PATH_TO_FFMPEG')) {
//                if (IsWindowsOS()) {
//                    define("PATH_TO_FFMPEG", SERVER_ROOT . '/apps/ffmpeg/ffmpeg.exe');
//                } else {
//                    define("PATH_TO_FFMPEG", 'ffmpeg');
//                }
//                $outlink = str_ireplace("." . $path_parts['extension'], ".wav", $message_link);
//                $out     = shell_exec(PATH_TO_FFMPEG . " -i " . $message_link . " -acodec pcm_s16le -ar 44100 " . $outlink . " 2>&1");
//            }
//        }
        

        

//        } else {
//            $this->success = FALSE;
//            $this->message = 'Input is missing!';
//        }
//        return $this->success;
//    }

    // Default command
    function command($command, $parameter)
    {
        if (!$json = json_decode($parameter)) {
            $json = array();
        }
        if ($this->kodi_request($command, $json)) {
            $this->success = TRUE;
            $this->message = 'OK';
        }
        return $this->success;
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
