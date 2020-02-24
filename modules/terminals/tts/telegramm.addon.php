<?php

class telegramm extends tts_addon
{
    
    function __construct($terminal)
    {
        $this->title       = "Telegramm module";
        $this->description = '<b>Описание:</b>&nbsp;Для работы использует &nbsp;<a href="https://mjdm.ru/forum/viewtopic.php?f=5&t=2768&sid=89e1057b5d8345f7983111f006d41154">модуль Телеграм</a>. Без этого модуля ничего работать не будет.<br>';
        $this->description .= '<b>Проверка доступности:</b>&nbsp;service_ping (пингование устройства проводится проверкой состояния сервиса).<br>';
        $this->description .= '<b>Поддерживаемые возможности:</b>&nbsp;say(), sayTo(), sayReply(), ask().';
        
        unsubscribeFromEvent('telegram', 'SAY');
        unsubscribeFromEvent('telegram', 'SAYTO');
        unsubscribeFromEvent('telegram', 'ASK');
        unsubscribeFromEvent('telegram', 'SAYREPLY');
        register_shutdown_function("catchTimeoutTerminals");
        parent::__construct($terminal);
    }
    
    // Say
    function say_message($message, $terminal) //SETTINGS_SITE_LANGUAGE_CODE=код языка
    {
        if (file_exists(DIR_MODULES . 'telegram/telegram.class.php')) {
            include(DIR_MODULES . 'telegram/telegram.class.php');
            $telegram_module = new telegram();
            // если пользователь привязан к телеграмму
            if ($user = gg($terminal['LINKED_OBJECT'] . '.username')) {
                $MEMBER_ID = SQLSelectOne("SELECT ID FROM users WHERE USERNAME = '" . $user . "'");
                $users     = SQLSelect("SELECT * FROM tlg_user WHERE MEMBER_ID = '" . $MEMBER_ID['ID'] . "'");
                if (!$users) {
                    $users = SQLSelect("SELECT * FROM tlg_user ");
                }
            } else {
                // усли пользователя нет то отправляем на всех без исключения
                $users = SQLSelect("SELECT * FROM tlg_user ");
            }
            $c_users = count($users);
            if ($message['MESSAGE'] AND $c_users) {
                for ($j = 0; $j < $c_users; $j++) {
                    $user_id = $users[$j]['USER_ID'];
                    if ($user_id === '0') {
                        $user_id = $users[$j]['NAME'];
                    }
                    $result = $telegram_module->sendMessageToUser($user_id, $message['MESSAGE']);
                    if (is_array($result) AND $result["ok"] = true) {
                        $this->success = TRUE;
                    } else {
                        $this->success = FALSE;
                    }
                }
            } else {
                $this->success = FALSE;
            }
        } else {
            $this->success = FALSE;
        }
        sleep(1);
        return $this->success;
    }
    
    function ask($phrase, $level = 0) //SETTINGS_SITE_LANGUAGE_CODE=код языка
    {
        if (file_exists(DIR_MODULES . 'telegram/telegram.class.php')) {
            include(DIR_MODULES . 'telegram/telegram.class.php');
            $telegram_module = new telegram();
            $users   = SQLSelect("SELECT * FROM tlg_user ");
            $c_users = count($users);
            if ($phrase AND $c_users) {
                for ($j = 0; $j < $c_users; $j++) {
                    $user_id = $users[$j]['USER_ID'];
                    if ($user_id === '0') {
                        $user_id = $users[$j]['NAME'];
                    }
                    // new variant 
                    $result = $telegram_module->sendMessageToUser($user_id, $message['MESSAGE']);
                    if (is_array($result) AND $result["ok"] = true) {
                        $this->success = TRUE;
                    } else {
                        $this->success = FALSE;
                    }
                }
            } else {
                $this->success = FALSE;
            }
        } else {
            $this->success = FALSE;
        }
        sleep(1);
        return $this->success;
    }

    // ping terminal
    function ping()
    {
        if (file_exists(DIR_MODULES . 'telegram/telegram.class.php')) {
            include(DIR_MODULES . 'telegram/telegram.class.php');
            $telegram_module = new telegram();
            $result = $telegram_module->getMe();
            if (is_array($result) AND $result["ok"] = true) {
                $this->success = TRUE;
            } else {
                $this->success = FALSE;
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

?>
