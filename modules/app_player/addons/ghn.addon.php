<?php

/*
	Addon Google Home Notifier for app_player
*/

class ghn extends app_player_addon {

	// Private properties
	private $address;
	
	// Constructor
	function __construct($terminal) {
		$this->title = 'Google Home Notifier';
		$this->description = '<b>Описание:</b>&nbsp; Воспроизведение звука на google home устройствах через запущенный сервис &nbsp;<a href="https://github.com/noelportugal/google-home-notifier">google-home</a>. Передает текстовые сообщения с параметром языка, выбранного Вами в мажордомо.<br>Ссылка на &nbsp;<a href="https://connect.smartliving.ru/profile/1502/blog38.html">how-to</a>.<br>Ссылка на &nbsp;<a href="https://mjdm.ru/forum/viewtopic.php?f=23&t=5042&hilit=google+home">тему форума</a>.<br>';
		$this->description .= '<b>Восстановление воспроизведения после TTS:</b>&nbsp;Нет.<br>';
		$this->description .= '<b>Проверка доступности:</b>&nbsp;??? нужно разбираться ???.<br>';
		$this->description .= '<b>Настройка:</b>&nbsp; Порт доступа по умолчанию 8091 (если по умолчанию, можно не указывать).';
		
		$this->terminal = $terminal;
		$this->reset_properties();
		
		// Network
		$this->terminal['PLAYER_PORT'] = (empty($this->terminal['PLAYER_PORT'])?8091:$this->terminal['PLAYER_PORT']);
		$this->address = 'http://'.$this->terminal['HOST'].':'.$this->terminal['PLAYER_PORT'];
	}

	// Play
	function play($input) {
		$this->reset_properties();
		if(strlen($input)) {
			if(getURL($this->address.'/google-home-notifier?text='.urlencode($input), 0)) {
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
		return $this->success;
	}

	// Stop
	function stop() {
		$this->reset_properties();
		if(getURL($this->address.'/google-home-notifier?text='.urlencode('http://somefakeurl.stream/'), 0)) {
			$this->success = TRUE;
			$this->message = 'OK';
		} else {
			$this->success = FALSE;
			$this->message = 'Command execution error!';
		}
		return $this->success;
	}

}

?>
