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
        parent::__construct($terminal);
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
    
    // Stop
    function stop()
    {
        curl_setopt($this->curl, CURLOPT_URL, $this->address . '/rc/?command=vlc_close');
        if ($result = curl_exec($this->curl)) {
            if ($result == 'OK') {
                $this->success = TRUE;
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
    
    // Get terminal status
    function terminal_status()
    {
        // Defaults
        $listening_keyphrase = -1;
        $volume_media        = -1;
        $volume_ring         = -1;
        $volume_alarm        = -1;
        $volume_notification = -1;
        $brightness_auto     = -1;
        $recognition         = -1;
        $fullscreen          = -1;
        $brightness          = -1;
        $display_state       = -1;
        $battery             = -1;
        
        $out_data = array(
            'listening_keyphrase' => (string) strtolower($listening_keyphrase), // ключевое слово терминал для  начала распознавания (-1 - не поддерживается терминалом)
            'volume_media' => (int) $volume_media, // громкость медиа на терминале (-1 - не поддерживается терминалом)
            'volume_ring' => (int) $volume_ring, // громкость звонка к пользователям на терминале (-1 - не поддерживается терминалом)
            'volume_alarm' => (int) $volume_alarm, // громкость аварийных сообщений на терминале (-1 - не поддерживается терминалом)
            'volume_notification' => (int) $volume_notification, // громкость простых сообщений на терминале (-1 - не поддерживается терминалом)
            'brightness_auto' => (int) $brightness_auto, // автояркость включена или выключена 1 или 0 (-1 - не поддерживается терминалом)
            'recognition' => (int) $recognition, // распознавание на терминале включена или выключена 1 или 0 (-1 - не поддерживается терминалом)
            'fullscreen' => (int) $recognition, // полноекранный режим на терминале включена или выключена 1 или 0 (-1 - не поддерживается терминалом)
            'brightness' => (int) $brightness, // яркость екрана (-1 - не поддерживается терминалом)
            'battery' => (int) $battery, // заряд акумулятора терминала в процентах (-1 - не поддерживается терминалом)
            'display_state' => (int) $display_state // 1, 0  - состояние дисплея (-1 - не поддерживается терминалом)
        );
        
        // удаляем из массива пустые данные
        foreach ($out_data as $key => $value) {
            if ($value == '-1')
                unset($out_data[$key]);
            ;
        }
        return $out_data;
    }
    
}

?>
