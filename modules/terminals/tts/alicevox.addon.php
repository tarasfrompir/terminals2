<?php
/*
Addon Kodi (XBMC) for app_player
*/
class alicevox extends tts_addon
{
    function __construct($terminal)
    {
        $this->title   = "Alicevox";
        $this->description = '<b>Описание:</b>&nbsp; Работает на медиацентрах KODI с установленным плагином &nbsp;<a href="https://github.com/SergMicar/script.alicevox.master">Alicevox</a>.<br>Ссылка на &nbsp;<a href="https://mjdm.ru/forum/viewtopic.php?f=5&t=2893">тему форума</a>.<br>';
        $this->description .= '<b>Проверка доступности:</b>&nbsp;service_ping ("пингование" устройства проводится проверкой состояния сервиса).<br>';
        $this->description .= '<b>Настройка:</b>&nbsp; Не забудьте активировать управление по HTTP в настройках KODI (Настройки -> Сервисные настройки -> Управление -> Разрешить удаленное управление по HTTP) и установить "порт", "имя пользователя" и "пароль".<br>';
        $this->description .= '<b>Поддерживаемые возможности:</b>&nbsp;say(), sayTo(), sayReply().';

        $this->terminal = $terminal;
        if (!$this->terminal['HOST']) return false;
	    
        // содержит в себе все настройки терминала кроме айпи адреса
        $this->setting = json_decode($this->terminal['TTS_SETING'], true);
   
        $this->address = 'http://'.$this->setting['TTS_USERNAME'].':'.$this->setting['TTS_PASSWORD'].'@'.$this->terminal['HOST'].':'.(empty($this->setting['TTS_PORT'])?8080:$this->setting['TTS_PORT']);
        register_shutdown_function("catchTimeoutTerminals");
    }
    
    
    // Say
    public function say_media_message($message, $terminal) //SETTINGS_SITE_LANGUAGE_CODE=код языка
    {
        if ($message['CACHED_FILENAME']) {
            $fileinfo = pathinfo($message['CACHED_FILENAME']);
            $filename = $fileinfo[dirname] . '/' . $fileinfo[filename] . '.wav';
            if (!file_exists($filename)) {
                if (!defined('PATH_TO_FFMPEG')) {
                    if (IsWindowsOS()) {
                        define("PATH_TO_FFMPEG", SERVER_ROOT . '/apps/ffmpeg/ffmpeg.exe');
                    } else {
                        define("PATH_TO_FFMPEG", 'ffmpeg');
                    }
                }
                shell_exec(PATH_TO_FFMPEG . " -i " . $message['CACHED_FILENAME'] . " -acodec pcm_s16le -ac 1 -ar 44100 " . $filename);
            }
            if (file_exists($filename)) {
                if (preg_match('/\/cms\/cached.+/', $filename, $m)) {
                    $filename = 'http://' . getLocalIp() . $m[0];
                    $url = $this->address."/jsonrpc?request={\"jsonrpc\":\"2.0\",\"method\":\"Addons.ExecuteAddon\",\"params\":{\"addonid\":\"script.alicevox.master\",\"params\":[\"".$filename."\"]},\"id\":1}";
                    $result = json_decode(getURL($url, 0), true);
                    if ($result['result']=='OK') {
                        sleep($message['MESSAGE_DURATION']);
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
        } else {
            $this->success = FALSE;
        }
        return $this->success;
    }

    // ping terminal
    function ping()
    {
        // proverka na otvet
        $url = $this->address."/jsonrpc?request={\"jsonrpc\":\"2.0\",\"method\":\"Addons.ExecuteAddon\",\"params\":{\"addonid\":\"script.alicevox.master\",\"params\":[\"ping\"]},\"id\":1}";
        $result = json_decode(getURL($url, 0), true);
        if ($result['error']) {
            $this->success = FALSE;
        } else {
            $this->success = TRUE;
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
                'listening_keyphrase' =>(string) strtolower($listening_keyphrase), // ключевое слово терминал для  начала распознавания (-1 - не поддерживается терминалом)
				'volume_media' => (int)$volume_media, // громкость медиа на терминале (-1 - не поддерживается терминалом)
                'volume_ring' => (int)$volume_ring, // громкость звонка к пользователям на терминале (-1 - не поддерживается терминалом)
                'volume_alarm' => (int)$volume_alarm, // громкость аварийных сообщений на терминале (-1 - не поддерживается терминалом)
                'volume_notification' => (int)$volume_notification, // громкость простых сообщений на терминале (-1 - не поддерживается терминалом)
                'brightness_auto' => (int) $brightness_auto, // автояркость включена или выключена 1 или 0 (-1 - не поддерживается терминалом)
                'recognition' => (int) $recognition, // распознавание на терминале включена или выключена 1 или 0 (-1 - не поддерживается терминалом)
                'fullscreen' => (int) $recognition, // полноекранный режим на терминале включена или выключена 1 или 0 (-1 - не поддерживается терминалом)
				'brightness' => (int) $brightness, // яркость екрана (-1 - не поддерживается терминалом)
				'battery' => (int) $battery, // заряд акумулятора терминала в процентах (-1 - не поддерживается терминалом)
                'display_state'=> (int) $display_state, // 1, 0  - состояние дисплея (-1 - не поддерживается терминалом)
            );
		
		// удаляем из массива пустые данные
		foreach ($out_data as $key => $value) {
			if ($value == '-1') unset($out_data[$key]); ;
		}
        return $out_data;
    }
}
