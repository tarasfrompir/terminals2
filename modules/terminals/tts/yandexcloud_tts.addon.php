<?php

class yandexcloud_tts extends tts_addon
{
    
    function __construct($terminal)
    {
        
        $this->title       = "Yandex smart device cloud";
        $this->description = '<b>Описание:</b>&nbsp;Для работы использует &nbsp;<a href="https://mjdm.ru/forum/viewtopic.php?f=5&t=6922">модуль Яндекс девайс</a>. Без этого модуля ничего работать не будет.<br>';
        //$this->description .= '<b>Проверка доступности:</b>&nbsp;service_ping (пингование проводится проверкой состояния сервиса).<br>';
        $this->description .= '<b>Поддерживаемые возможности:</b>&nbsp;say(), sayTo(), sayReply().';
        
        $this->terminal = $terminal;
        if (!$this->terminal['HOST']) return false;
        
        unsubscribeFromEvent('yadevices', 'SAY');
        unsubscribeFromEvent('yadevices', 'SAYTO');
        unsubscribeFromEvent('yadevices', 'ASK');
        unsubscribeFromEvent('yadevices', 'SAYREPLY');
    }
    
    // Say
    function say_message($message, $terminal) {
        if (file_exists(DIR_MODULES . 'yadevices/yadevices.class.php')) {
            $station = SQLSelectOne("SELECT * FROM yastations WHERE IP='".$this->terminal['HOST']."'");
            //include(DIR_MODULES . 'yadevices/yadevices.class.php');
            //$yandex_cloud = new yadevices();
            //$yandex_cloud->sendCloudTTS($station['IOT_ID'],$message['MESSAGE']);
            callAPI('/api/module/yadevices','GET',array('station'=>$station['ID'],'say'=>$message['MESSAGE']));
        }
        $this->success = TRUE;
        usleep(500000);
        return $this->success;
    }
 }

?>
