<?php

/*
Addon VLC GUI for app_player
*/

class vlc_tts extends tts_addon
{
    
    // Constructor
    function __construct($terminal)
    {
        $this->title       = 'Системные сообщения с помощью VLC (VideoLAN)';
        $this->description = 'Управление VLC через GUI интерфейс. ';
        $this->description .= 'В настоящее время доступно только для Windows. ';
        $this->description .= 'Поддерживает ограниченный набор команд. ';

        $this->terminal = $terminal;
        if (!$this->terminal['HOST'])
            return false;
        
        $this->setting = json_decode($this->terminal['TTS_SETING'], true);
        
        // Curl
        $this->curl    = curl_init();
        $this->address = 'http://' . $this->terminal['HOST'] . ':' . (empty($this->setting['TTS_PORT']) ? 80 : $this->setting['TTS_PORT']);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
        if ($this->setting['TTS_USERNAME'] || $this->setting['TTS_PASSWORD']) {
            curl_setopt($this->curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($this->curl, CURLOPT_USERPWD, $this->setting['TTS_USERNAME'] . ':' . $this->setting['TTS_PASSWORD']);
        }
        register_shutdown_function("catchTimeoutTerminals");
    }
    
    // Destructor
    private function destroy()
    {
        curl_close($this->curl);
    }
    
    // Say
    function say_media_message($message, $terminal) //SETTINGS_SITE_LANGUAGE_CODE=код языка
    {
        if (strlen($message['MESSAGE'])) {
            $message['CACHED_FILENAME'] = preg_replace('/\\\\$/is', '', $message['CACHED_FILENAME']);
            $message['CACHED_FILENAME'] = preg_replace('/\/$/is', '', $message['CACHED_FILENAME']);
            if (!preg_match('/^http/', $message['CACHED_FILENAME'])) {
                $message['CACHED_FILENAME'] = str_replace('/', "\\", $message['CACHED_FILENAME']);
            }
            $this->stop();
            
            curl_setopt($this->curl, CURLOPT_URL, $this->address . '/rc/?command=vlc_play&param=' . urlencode("'" . $message['CACHED_FILENAME'] . "'"));
            //DebMes( $this->address.'/rc/?command=vlc_play&param='.urlencode("'".$message['CACHED_FILENAME']."'"));
            if ($result = curl_exec($this->curl)) {
                if ($result == 'OK') {
                    $this->success = TRUE;
                } else {
                    $this->success = FALSE;
                }
            } else {
                $this->success = FALSE;
            }
        } else {
            $this->success = FALSE;
        }
        return $this->success;
    }
 
    // Default command
    private function command($command, $parameter)
    {
        $this->reset_properties();
        curl_setopt($this->curl, CURLOPT_URL, $this->address . '/rc/?command=' . urlencode($command) . (strlen($parameter) ? '&param=' . urlencode($parameter) : ''));
        if ($result = curl_exec($this->curl)) {
            if ($result == 'OK') {
                $json['success'] = TRUE;
            } else {
                $json['success'] = FALSE;
            }
        } else {
            $this->success = FALSE;
        }
        return $this->success;
    }
}

?>
