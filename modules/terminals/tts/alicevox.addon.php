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
        $this->curl    = curl_init();
        $this->address = 'http://192.18.1.51:8080';
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, 5);
        curl_setopt($this->curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($this->curl, CURLOPT_USERPWD, 'xbmc:xbmc');
        
        parent::__construct($terminal);
    }
    
    
    // Say
    public function say_message($message, $terminal) //SETTINGS_SITE_LANGUAGE_CODE=код языка
    {
        if ($message['CACHED_FILENAME']) {
            if (file_exists($message['CACHED_FILENAME'])) {
                if (preg_match('/\/cms\/cached.+/', $message['CACHED_FILENAME'], $m)) {
                    $message['CACHED_FILENAME'] = 'http://' . getLocalIp() . $m[0];
                    $json                       = array(
                        'jsonrpc' => '2.0',
                        'method' => 'Addons.ExecuteAddon',
                        'params' => array(
                            'addonid' => 'script.alicevox.master',
                            'params' => array(
                                $message['CACHED_FILENAME']
                            )
                        ),
                        'id' => (int) $message['ID']
                    );
                    $request                    = json_encode($json);
                    curl_setopt($this->curl, CURLOPT_URL, $this->address . '/jsonrpc?request=' . urlencode($request));
                    
                    if ($result = curl_exec($this->curl)) {
                        $code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
                    }
                    if ($code == 200) {
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
