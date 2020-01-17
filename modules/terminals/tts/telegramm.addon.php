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
        set_time_limit(5);
        register_shutdown_function("catchTimeoutTerminals");
    }
    
    // Say
    function say_message($message, $terminal) //SETTINGS_SITE_LANGUAGE_CODE=код языка
    {
        if (file_exists(DIR_MODULES . 'telegram/telegram.class.php')) {
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
                    $url    = BASE_URL . "/ajax/telegram.html?sendMessage=1&user=" . $user_id . "&text=" . urlencode($message['MESSAGE']);
                    $result = getURL($url, 0);
                    //getURLBackground($url, 0);
                    if ($result) {
                        usleep(100000);
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
        return $this->success;
    }
    
    function ask($phrase, $level = 0) //SETTINGS_SITE_LANGUAGE_CODE=код языка
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
                    $url    = BASE_URL . "/ajax/telegram.html?sendMessage=1&user=" . $user_id . "&text=" . urlencode($phrase);
                    $result = getURL($url, 0);
                    //getURLBackground($url, 0);
                    if ($result) {
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
        return $this->success;
    }
}

?>
