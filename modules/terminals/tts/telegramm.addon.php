<?php

class telegramm extends tts_addon {

    function __construct($terminal) {
        $this->title="Telegramm module";
        $this->description = '<b>Описание:</b>&nbsp;Работает с помощью &nbsp;<a href="https://mjdm.ru/forum/viewtopic.php?f=5&t=2768&sid=89e1057b5d8345f7983111f006d41154">модуля Телеграм</a>. Без этого модуля ничего работать не будет.<br>';
	$this->description .= '<b>Поддерживаемые возможности:</b>&nbsp;say(), sayTo(), sayReply(), ask().<br>';
	$this->description .= '<b>Проверка доступности:</b>&nbsp;service_ping (пингование устройства проводится проверкой состояния сервиса).';
	unsubscribeFromEvent('telegram', 'SAY');
        unsubscribeFromEvent('telegram', 'SAYTO');
        unsubscribeFromEvent('telegram', 'ASK');
        unsubscribeFromEvent('telegram', 'SAYREPLY');
        parent::__construct($terminal);
    }

    // Say
    function say_message($message, $terminal) //SETTINGS_SITE_LANGUAGE_CODE=код языка
    {
        if (file_exists(DIR_MODULES . 'telegram/telegram.class.php')) {
            $users   = SQLSelect("SELECT * FROM tlg_user ");
            $c_users = count($users);
            if ($message['MESSAGE'] AND $c_users) {
                for ($j = 0; $j < $c_users; $j++) {
                    $user_id = $users[$j]['USER_ID'];
                    if ($user_id === '0') {
                        $user_id = $users[$j]['NAME'];
                    }
                    $url = BASE_URL . "/ajax/telegram.html?sendMessage=1&user=" . $user_id . "&text=" . urlencode($message['MESSAGE']);
                    getURLBackground($url,0);
                    usleep(100000);
	                $this->success = TRUE;
                    $this->message = 'OK';
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

    function ask($phrase, $level=0) //SETTINGS_SITE_LANGUAGE_CODE=код языка
    {
        if (file_exists(DIR_MODULES . 'telegram/telegram.class.php')) {
            $users   = SQLSelect("SELECT * FROM tlg_user ");
            $c_users = count($users);
            if ($phrase AND $c_users) {
                for ($j = 0; $j < $c_users; $j++) {
                    $user_id = $users[$j]['USER_ID'];
                    if ($user_id === '0') {
                        $user_id = $users[$j]['NAME'];
                    }
                    $url = BASE_URL . "/ajax/telegram.html?sendMessage=1&user=" . $user_id . "&text=" . urlencode($phrase);
                    getURLBackground($url,0);
                    usleep(100000);
	                $this->success = TRUE;
                    $this->message = 'OK';
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

?>
