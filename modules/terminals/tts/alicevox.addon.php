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
        $this->description = 'Работает на медиацентрах KODI  с установленным плагином  Аливокс. Ссылка на плагин - https://github.com/SergMicar/script.alicevox.master Ссылка на тему форума - https://mjdm.ru/forum/viewtopic.php?f=4&t=3765&p=88392&hilit=alicevox#p88392' ;
        $this->address = 'http://xbmc:xbmc@'.$this->terminal['HOST'].':'.(empty($this->terminal['TTS_PORT'])?8080:$this->terminal['TTS_PORT']);
        parent::__construct($terminal);
    }
    
    
    // Say
    public function say_message($message, $terminal) //SETTINGS_SITE_LANGUAGE_CODE=код языка
    {
        if ($message['CACHED_FILENAME']) {
            if (file_exists($message['CACHED_FILENAME'])) {
                if (preg_match('/\/cms\/cached.+/', $message['CACHED_FILENAME'], $m)) {
                    $message['CACHED_FILENAME'] = 'http://' . getLocalIp() . $m[0];
                    $url = $this->address."/jsonrpc?request={\"jsonrpc\":\"2.0\",\"method\":\"Addons.ExecuteAddon\",\"params\":{\"addonid\":\"script.alicevox.master\",\"params\":[\"".$message['CACHED_FILENAME']."\"]},\"id\":1}";
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
