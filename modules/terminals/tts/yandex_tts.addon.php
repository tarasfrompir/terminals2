<?php
class yandex_tts extends tts_addon
{

    function __construct($terminal)
    {

        $this->title = "Yandex module";
        $this->description = '<b>Описание:</b>&nbsp;Для работы использует&nbsp;<a href="https://connect.smartliving.ru/addons/category1/211.html">модуль YaDevices</a>. Без этого модуля ничего работать не будет.<br>';
        $this->description .= '<b>Поддерживаемые возможности:</b>&nbsp;say(), sayTo(), sayReply().';

        $this->terminal = $terminal;

        if (!$this->terminal['HOST']) return false;

        unsubscribeFromEvent('yadevices', 'SAY');
        unsubscribeFromEvent('yadevices', 'SAYTO');
        unsubscribeFromEvent('yadevices', 'ASK');
        unsubscribeFromEvent('yadevices', 'SAYREPLY');
    }

    // Say
    public function say_message($message, $terminal) //SETTINGS_SITE_LANGUAGE_CODE=код языка
    
    {
        if (file_exists(DIR_MODULES . 'yadevices/yadevices.class.php'))
        {
            include_once (DIR_MODULES . 'yadevices/yadevices.class.php');
            $yadevice = new yadevices();
            $station = SQLSelectOne("SELECT * FROM yastations WHERE IP='" . $terminal['HOST'] . "'");
            $yadevice->sendCommandToStation((int)$station['ID'], 'повтори за мной ' . $message['MESSAGE']);
            $this->success = true;
        
        } else {
            $this->success = false;
        }
        usleep(100000);
        return $this->success;
    }

    // Set volume
    function set_volume($level)
    {
        if (file_exists(DIR_MODULES . 'yadevices/yadevices.class.php'))
        {
            include_once (DIR_MODULES . 'yadevices/yadevices.class.php');
            $yadevice = new yadevices();
            $station = SQLSelectOne("SELECT * FROM yastations WHERE IP='" . $this->terminal['HOST'] . "'");
            //if (($params['command'] == 'setVolume') && $params['volume']) {
            //        return $this->sendCommandToStation((int)$params['station'], $params['command'], $params['volume']);
            $yadevice->sendCommandToStation((int)$station['ID'], 'setVolume', $level / 100);
            $this->success = true;
        } else {
            $this->success = false;
        }
        usleep(100000);
        return $this->success;
    }

    // ping terminal
    public function ping_ttsservice($host)
    {
        $this->success = true;
        return $this->success;
    }

}

?>
