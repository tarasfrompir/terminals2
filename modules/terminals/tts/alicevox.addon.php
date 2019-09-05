<?php
/*
Addon Kodi (XBMC) for app_player
*/
class kodialt extends app_player_addon
{
    
    // Private properties
    private $curl;
    private $address;
    
    // Constructor
    function __construct($terminal)
    {
        $this->title       = 'KODI (XBMC) alicevox';
        $this->description = 'Описание: Бесплатный кроссплатформенный медиаплеер и программное обеспечение для организации HTPC с открытым исходным кодом.';
        $this->description .= 'Поддерживаемые возможности say, sayto, sayreply, ask через alicevox плагин для KODI (https://mjdm.ru/forum/viewtopic.php?f=5&t=2893&start=120).';
        
        $this->terminal = $terminal;
        $this->reset_properties();
        
        // Curl
        $this->curl    = curl_init();
        $this->address = 'http://' . $this->terminal['HOST'] . ':' . (empty($this->terminal['PLAYER_PORT']) ? 8080 : $this->terminal['PLAYER_PORT']);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, 5);
        if ($this->terminal['PLAYER_USERNAME'] || $this->terminal['PLAYER_PASSWORD']) {
            curl_setopt($this->curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($this->curl, CURLOPT_USERPWD, $this->terminal['PLAYER_USERNAME'] . ':' . $this->terminal['PLAYER_PASSWORD']);
        }
    }
    
    // Say
    function sayToMedia($message_link, $time_message) //SETTINGS_SITE_LANGUAGE_CODE=код языка
    {
        // преобразовываем файл в вав формат
        $path_parts = pathinfo($message_link);
        if ($path_parts['extension'] != 'wav') {
            if (!defined('PATH_TO_FFMPEG')) {
                if (IsWindowsOS()) {
                    define("PATH_TO_FFMPEG", SERVER_ROOT . '/apps/ffmpeg/ffmpeg.exe');
                } else {
                    define("PATH_TO_FFMPEG", 'ffmpeg');
                }
                $outlink = str_ireplace("." . $path_parts['extension'], ".wav", $message_link);
                $out     = shell_exec(PATH_TO_FFMPEG . " -i " . $message_link . " -acodec pcm_s16le -ar 44100 " . $outlink . " 2>&1");
            }
        }
        
        // берем ссылку http
        if (preg_match('/\/cms\/cached.+/', $outlink, $m)) {
            $server_ip = getLocalIp();
            if (!$server_ip) {
                DebMes("Server IP not found", 'terminals');
                return false;
            } else {
                $outlink = 'http://' . $server_ip . $m[0];
            }
        }
        
        //  в некоторых системах есть по несколько серверов, поэтому если файл отсутствует, то берем путь из BASE_URL
        if (!remote_file_exists($outlink)) {
            $outlink = BASE_URL . $m[0];
        }
        
        if (strlen($outlink)) {
            if ($this->kodi_request('Addons.ExecuteAddon', array(
                'addonid' => 'script.alicevox.master',
                'params' => array(
                    $outlink
                )
            ))) {
                $this->success = TRUE;
                $this->message = 'OK';
            }
        } else {
            $this->success = FALSE;
            $this->message = 'Input is missing!';
        }
        return $this->success;
    }

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
    
}
?>
