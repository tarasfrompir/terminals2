<?php

class telegramm extends app_player_addon
{
    
    // Private properties
    private $curl;
    private $address;
    
    // Constructor
    function __construct($terminal)
    {
        $this->title       = 'Терминал для модуля Телеграмм';
        $this->description = 'Описание: Терминал использующий прием-передачу сообщений через модуль Телеграмм.';
        $this->description .= ' Терминал имеет обратную связь с сайтом Телеграмм.(сообщения не теряются).';
        $this->terminal = $terminal;
        $this->reset_properties();
        //DebMes('Отписываем от событий терминал Телеграмма ' . microtime(true), 'terminals2');
        unsubscribeFromEvent('telegram', 'SAY');
        unsubscribeFromEvent('telegram', 'SAYTO');
        unsubscribeFromEvent('telegram', 'ASK');
        unsubscribeFromEvent('telegram', 'SAYREPLY');
        //DebMes('Отписались от событий терминал Телеграмма ' . microtime(true), 'terminals2');
    }
    
    // Say
    function say_message($message, $terminal) //SETTINGS_SITE_LANGUAGE_CODE=код языка
    {
		$time_message = $message['TIME_MESSAGE'];
		$outlink = $message['FILE_LINK'];
        $this->reset_properties();
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
                    //$out = getURL($url,0);
                    getURLBackground($url, 0);

                    $rec = SQLSelectOne("SELECT * FROM shouts WHERE ID = '".$message['ID']."'");
                    $rec['SOURCE'] = str_replace($terminal['ID'] . '^', '', $message['SOURCE']);
                    SQLUpdate('shouts', $rec);

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
        sg($terminal['LINKED_OBJECT'].'.BASY',0);
        return $this->success;
    }
}

?>
