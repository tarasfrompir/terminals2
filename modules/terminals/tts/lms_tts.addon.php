<?php

/*
	Addon Logitech Media Server for app_player
	http://tutoriels.domotique-store.fr/content/54/95/fr/api-logitech-squeezebox-server-_-player-http.html
	http://localhost:9000/html/docs/cli-api.html
*/

class lms_tts extends tts_addon {
	
	// Constructor
	function __construct($terminal) {
		$this->title = 'Logitech Media Server';
		$this->description = 'Logitech Media Server - это потоковый аудиосервер,разработанный, в частности, для поддержки цифровых аудиоприемников Squeezebox.<br>';
		$this->description .= 'В поле <i>Имя пользователя</i> необходимо указать IP или MAC адрес плеера.';
		
		$this->terminal = $terminal;
        $this->setting = json_decode($this->terminal['TTS_SETING'], true);		
		// Curl
		$this->curl = curl_init();
		$this->address = 'http://'.$this->terminal['HOST'].':'.(empty($this->setting['TTS_PORT'])?9000:$this->setting['TTS_PORT']);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, TRUE);
        parent::__construct($terminal);
	}
	
	// Destructor
	function destroy() {
		curl_close($this->curl);
	}
	
	// Private: LMS JSON-RPC request
	private function lms_jsonrpc_request($data) {
		$jsonrpc = array(
			'id'		=> 1,
			'method'	=> 'slim.request',
			'params'	=> array(
				$this->terminal['PLAYER_USERNAME'],
				$data,
			)
		);
		$this->reset_properties();
		curl_setopt($this->curl, CURLOPT_POST, TRUE);
		curl_setopt($this->curl, CURLOPT_URL, $this->address.'/jsonrpc.js');
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($jsonrpc));
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		if($result = curl_exec($this->curl)) {
			if($json = json_decode($result)) {
				$this->success = TRUE;
				$this->message = 'OK';
				$this->data = ($json->result?$json->result:NULL);
			} else {
				$this->success = FALSE;
				$this->message = 'JSON decode: '.json_last_error_msg().'!';
			}
		} else {
			$this->success = FALSE;
			$this->message = 'LMS JSON-RPC interface: '.curl_error($this->curl).'!';
		}
		return $this->success;
	}
	
	// Play
	function play($input) {
		if(strlen($input)) {
			$input = preg_replace('/\\\\$/is', '', $input);
			if($this->lms_jsonrpc_request(array('playlist', 'play', $input))) {
				if($this->status()) {
					$track_id = $this->data['track_id'];
				} else {
					$track_id = -1;
				}
				$this->reset_properties(array('success'=>TRUE, 'message'=>'OK'));
				$this->data = (int)$track_id;
			}
		} else {
			$this->success = FALSE;
			$this->message = 'Input is missing!';
		}
		return $this->success;
	}
	
	// Stop
	function stop() {
		if($this->lms_jsonrpc_request(array('stop'))) {
			$this->reset_properties(array('success'=>TRUE, 'message'=>'OK'));
		}
		return $this->success;
	}
	
	// Set volume
	function set_volume($level) {
		if(strlen($level)) {
			if($this->lms_jsonrpc_request(array('mixer', 'volume', (int)$level))) {
				$this->reset_properties(array('success'=>TRUE, 'message'=>'OK'));
			}
		} else {
			$this->success = FALSE;
			$this->message = 'Level is missing!';
		}
		return $this->success;
	}
	
	// Playlist: Get
	function pl_get() {
		if($this->lms_jsonrpc_request(array('status', 0, PHP_INT_MAX, 'tags:u'))) {
			$playlist = $this->data->playlist_loop;
			$this->reset_properties(array('success'=>TRUE, 'message'=>'OK', 'data'=>array()));
			if($playlist) {
				foreach($playlist as $track) {
					$this->data[] = array(
						'id'	=> (int)$track->id,
						'name'	=> (string)(empty($track->title)?'Unknown':$track->title),
						'file'	=> (string)trim($track->url, 'file:///'),
					);
				}
			}
		}
		return $this->success;
	}

	// Default command
	function command($command, $parameter) {
		$data = array($command, $parameter);
		return $this->lms_jsonrpc_request($data);
	}
	
    function say_media_message($message, $terminal) //SETTINGS_SITE_LANGUAGE_CODE=код языка
        {
		if(file_exists($message['CACHED_FILENAME'])) {
			$message['CACHED_FILENAME'] = preg_replace('/\\\\$/is', '', $message['CACHED_FILENAME']);
			if($this->lms_jsonrpc_request(array('playlist', 'play', $message['CACHED_FILENAME']))) {
				if($this->status()) {
					$track_id = $this->data['track_id'];
				} else {
					$track_id = -1;
				}
				$this->reset_properties(array('success'=>TRUE, 'message'=>'OK'));
				$this->data = (int)$track_id;
			}
		} else {
			$this->success = FALSE;
			$this->message = 'Input is missing!';
		}
		return $this->success;
	}
}

?>
