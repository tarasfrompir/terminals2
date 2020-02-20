<?php

/*
	Addon MPD for app_player
*/

class mpd extends app_player_addon {

	// Private properties
	private $mpd;

	// Constructor
	function __construct($terminal) {
		$this->title = 'Music Player Daemon (MPD)';
		$this->description = '<b>Описание:</b>&nbsp; Воспроизведение звука через кроссплатформенный музыкальный проигрыватель, который имеет клиент-серверную архитектуру.<br>';
		$this->description .= '<b>Восстановление воспроизведения после TTS:</b>&nbsp; Нет (если ТТС такого же типа, что и плеер). Если же тип ТТС и тип плеера для терминала различны, то плейлист плеера при ТТС не потеряется при любых обстоятельствах).<br>';
		$this->description .= '<b>Проверка доступности:</b>&nbsp;?????.<br>';
		$this->description .= '<b>Настройка:</b>&nbsp; Порт доступа по умолчанию 6600 (если по умолчанию, можно не указывать).';
		
		$this->terminal = $terminal;
        if (!$this->terminal['HOST'])
            return false;
		$this->reset_properties();
		
		// Network
		$this->port = (empty($this->terminal['PLAYER_PORT'])?6600:$this->terminal['PLAYER_PORT']);
		$this->password = (empty($this->terminal['PLAYER_PASSWORD'])?NULL:$this->terminal['PLAYER_PASSWORD']);
		
        $this->title       = 'Music Player Daemon (MPD)';
        $this->description = '<b>Описание:</b>&nbsp; Воспроизведение звука через кроссплатформенный музыкальный проигрыватель, который имеет клиент-серверную архитектуру.<br>';
        $this->terminal    = $terminal;
        if (!$this->terminal['HOST']) return false;
        // MPD
        include (DIR_MODULES . 'app_player/libs/mpd/mpd.class.php');
        $this->mpd = new mpd_player($this->terminal['HOST'], $this->port, $this->password);   
        $this->mpd->Disconnect();
	}

    // Set volume
    function set_volume($level=0) {
		$this->reset_properties();
        if (!$this->mpd->mpd_sock OR !$this->mpd->connected) $this->mpd->Connect();
        if ($this->mpd->connected) {
            try {
                if ($this->mpd->SetVolume($level)) {
					$this->message = 'OK';
                    $this->success = TRUE;
                } else {
                    $this->success = FALSE;
					$this->message = 'Missing volume';
                }
            }
            catch (Exception $e) {
                $this->success = FALSE;
				$this->message = 'Error ' . $e;
            }
        } else {
            $this->success = FALSE;
			$this->message = 'Missing volume';
        }
        if ($this->mpd->mpd_sock AND $this->mpd->connected) $this->mpd->Disconnect();
        return $this->success;
    }
    
    // Set Repeat
    function set_repeat($repeat=0) {
		$this->reset_properties();
        if (!$this->mpd->mpd_sock OR !$this->mpd->connected) $this->mpd->Connect();
        if ($this->mpd->connected) {
            try {
                if ($this->mpd->SetRepeat($repeat)) {
					$this->message = 'OK';
                    $this->success = TRUE;
                } else {
                    $this->success = FALSE;
					$this->message = 'Missing repeat';
                }
            }
            catch (Exception $e) {
                $this->success = FALSE;
				$this->message = 'Error ' . $e;
            }
        } else {
            $this->success = FALSE;
			$this->message = 'Missing repeat';
        }
        if ($this->mpd->mpd_sock AND $this->mpd->connected) $this->mpd->Disconnect();
        return $this->success;
    }

    // Set random
    function set_random($random=0) {
		$this->reset_properties();
        if (!$this->mpd->mpd_sock OR !$this->mpd->connected) $this->mpd->Connect();
        if ($this->mpd->connected) {
            try {
                if ($this->mpd->SetRandom($random)) {
					$this->message = 'OK';
                    $this->success = TRUE;
                } else {
                    $this->success = FALSE;
					$this->message = 'Missing random';
                }
            }
            catch (Exception $e) {
                $this->success = FALSE;
				$this->message = 'Error ' . $e;
            }
        } else {
            $this->success = FALSE;
			$this->message = 'Missing random';
        }
        if ($this->mpd->mpd_sock AND $this->mpd->connected) $this->mpd->Disconnect();
        return $this->success;
    }
    
    // Set crossfade
    function set_crossfade($crossfade=0) {
		$this->reset_properties();
        if (!$this->mpd->mpd_sock OR !$this->mpd->connected) $this->mpd->Connect();
        if ($this->mpd->connected) {
            try {
                if ($this->mpd->SetCrossfade($crossfade)) {
					$this->message = 'OK';
                    $this->success = TRUE;
                } else {
                    $this->success = FALSE;
					$this->message = 'Missing crosfade';
                }
            }
            catch (Exception $e) {
                $this->success = FALSE;
				$this->message = 'Error ' . $e;
            }
        } else {
            $this->success = FALSE;
			$this->message = 'Missing crosfade';
        }
        if ($this->mpd->mpd_sock AND $this->mpd->connected) $this->mpd->Disconnect();
        return $this->success;
    }
 	
	// Play
	function play($input) {
		$this->reset_properties();
        if (!$this->mpd->mpd_sock OR !$this->mpd->connected) $this->mpd->Connect();
		    if ($input && $this->mpd->connected) {
            try {
				$this->mpd->PLClear();
                if ($this->mpd->PLAddFile($input)) {
					$this->message = 'OK';
                    $this->success = TRUE;
                } else {
                    $this->success = FALSE;
					$this->message = 'Missing play';
                }
            }
            catch (Exception $e) {
                $this->success = FALSE;
				$this->message = 'Error ' . $e;
            }
        } else {
            $this->success = FALSE;
			$this->message = 'Missing play';
        }
        if ($this->mpd->mpd_sock AND $this->mpd->connected) $this->mpd->Disconnect();
        return $this->success;
	}

	// Pause
	function pause() {
		$this->reset_properties();
        if (!$this->mpd->mpd_sock OR !$this->mpd->connected) $this->mpd->Connect();
		    if ($this->mpd->connected) {
            try {
                if ($this->mpd->Pause()) {
					$this->message = 'OK';
                    $this->success = TRUE;
                } else {
                    $this->success = FALSE;
					$this->message = 'Missing play';
                }
            }
            catch (Exception $e) {
                $this->success = FALSE;
				$this->message = 'Error ' . $e;
            }
        } else {
            $this->success = FALSE;
			$this->message = 'Missing play';
        }
        if ($this->mpd->mpd_sock AND $this->mpd->connected) $this->mpd->Disconnect();
        return $this->success;
	}

	// Stop
	function stop() {
		$this->reset_properties();
        if (!$this->mpd->mpd_sock OR !$this->mpd->connected) $this->mpd->Connect();
		    if ($this->mpd->connected) {
            try {
                if ($this->mpd->Stop()) {
					$this->message = 'OK';
                    $this->success = TRUE;
                } else {
                    $this->success = FALSE;
					$this->message = 'Missing stop';
                }
            }
            catch (Exception $e) {
                $this->success = FALSE;
				$this->message = 'Error ' . $e;
            }
        } else {
            $this->success = FALSE;
			$this->message = 'Missing stop';
        }
        if ($this->mpd->mpd_sock AND $this->mpd->connected) $this->mpd->Disconnect();
        return $this->success;
	}
	
	// Next
	function next() {
		$this->reset_properties();
        if (!$this->mpd->mpd_sock OR !$this->mpd->connected) $this->mpd->Connect();
		    if ($this->mpd->connected) {
            try {
                if ($this->mpd->Next()) {
					$this->message = 'OK';
                    $this->success = TRUE;
                } else {
                    $this->success = FALSE;
					$this->message = 'Missing next';
                }
            }
            catch (Exception $e) {
                $this->success = FALSE;
				$this->message = 'Error ' . $e;
            }
        } else {
            $this->success = FALSE;
			$this->message = 'Missing next';
        }
        if ($this->mpd->mpd_sock AND $this->mpd->connected) $this->mpd->Disconnect();
        return $this->success;
	}
	
	// Previous
	function previous() {
		$this->reset_properties();
        if (!$this->mpd->mpd_sock OR !$this->mpd->connected) $this->mpd->Connect();
		    if ($this->mpd->connected) {
            try {
                if ($this->mpd->Previous()) {
					$this->message = 'OK';
                    $this->success = TRUE;
                } else {
                    $this->success = FALSE;
					$this->message = 'Missing Previous';
                }
            }
            catch (Exception $e) {
                $this->success = FALSE;
				$this->message = 'Error ' . $e;
            }
        } else {
            $this->success = FALSE;
			$this->message = 'Missing Previous';
        }
        if ($this->mpd->mpd_sock AND $this->mpd->connected) $this->mpd->Disconnect();
        return $this->success;
	}

    // restore playlist
    function restore_playlist($playlist_id=0, $playlist_content = array(), $track_id=0, $time=0) {
        if (!$this->mpd->mpd_sock OR !$this->mpd->connected) $this->mpd->Connect();
        if ($this->mpd->connected) {
            try {
                // create new playlist
                $this->mpd->PLClear();
                // add files to playlist
                foreach ($playlist_content as $song) {
                    $this->mpd->PLAddFileWithPosition($song['file'], $song['Pos']);
                }
                // change played file
                $this->mpd->PLSeek($track_id, $time);
                // play seeked file
                if ($this->mpd->Play()) {
                    $this->success = TRUE;
					$this->message = 'OK';
                } else {
                    $this->success = FALSE;
					$this->message = 'Missing restore playlist';
                }
            }
            catch (Exception $e) {
                $this->success = FALSE;
				$this->message = 'Error ' . $e;
            }
        } else {
            $this->success = FALSE;
			$this->message = 'Missing restore playlist';
        }
        if ($this->mpd->mpd_sock AND $this->mpd->connected) $this->mpd->Disconnect();
        return $this->success;
    }

	// Get player status
    function status() {
        $this->reset_properties();
        if (!$this->mpd->mpd_sock OR !$this->mpd->connected) $this->mpd->Connect();
        // Defaults
		$playlist_id = -1;
		$playlist_content = array();
        $track_id = -1;
		$name     = -1;
		$file     = -1;
        $length   = -1;
        $time     = -1;
        $state    = -1;
        $volume   = -1;
		$muted    = -1;
        $random   = -1;
        $loop     = -1;
        $repeat   = -1;
        $crossfade= -1;
		$speed = -1;
		
        if ($this->mpd->connected) {
            $result = $this->mpd->GetStatus();
        }
        // получаем плейлист - возможно он не сохранен поэтому получаем его полностью
        if ($this->mpd->connected) {
            $playlist_content = $this->mpd->GetPlaylistinfo ();
        }
		
        $this->data = array(
                'playlist_id' => (int)$result['playlist'], // номер или имя плейлиста 
                'playlist_content' => json_encode($playlist_content), 	// содержимое плейлиста должен быть ВСЕГДА МАССИВ 
																		// обязательно $playlist_content[$i]['pos'] - номер трека
																		// обязательно $playlist_content[$i]['file'] - адрес трека
																		// возможно $playlist_content[$i]['Artist'] - артист
																		// возможно $playlist_content[$i]['Title'] - название трека
				'track_id' => (int) $result['song'], //ID of currently playing track (in playlist). Integer. If unknown (playback stopped or playlist is empty) = -1.
			    'name' => (string) $name, //Current speed for playing media. float.
				'file' => (string) $file, //Current link for media in device. String.
                'length' => (int) $result['duration'], //Track length in seconds. Integer. If unknown = 0. 
                'time' => (int) $result['time'], //Current playback progress (in seconds). If unknown = 0. 
                'state' => (string) strtolower($result['state']), //Playback status. String: stopped/playing/paused/unknown 
                'volume' => (int)$result['volume'], // Volume level in percent. Integer. Some players may have values greater than 100.
                'muted' => (int) $result['muted'], // Volume level in percent. Integer. Some players may have values greater than 100.
                'random' => (int) $result['random'], // Random mode. Boolean. 
                'loop' => (int) $result['loop'], // Loop mode. Boolean.
                'repeat' => (int) $result['repeat'], //Repeat mode. Boolean.
                'crossfade' => (int) $result['xfade'], // crossfade
                'speed' => (int) $speed, // crossfade
            );
 		// удаляем из массива пустые данные
		foreach ($this->data as $key => $value) {
			if ($value == '-1' or !$value) unset($this->data[$key]);
		}
				        
        $this->success = TRUE;
        $this->message = 'OK';
        return $this->success;
    }

// unknow 
		// Seek
	function seek($position) {
		$this->reset_properties();
		if(strlen($position)) {
			if($this->mpd_connect()) {
				$this->mpd->SeekTo((int)$position);
				$this->mpd->Disconnect();
				$this->reset_properties();
				$this->success = TRUE;
				$this->message = 'OK';
			}
		} else {
			$this->success = FALSE;
			$this->message = 'Position is missing!';
		}
		return $this->success;
	}
	// Get volume
	public function get_volume() {
		if($this->mpd_connect()) {
			$this->reset_properties();
			if(!is_null($volume = $this->mpd->GetVolume())) {
				$this->success = TRUE;
				$this->message = 'OK';
				if($volume == -1) {
					if(strtolower($this->terminal['HOST']) == 'localhost' || $this->terminal['HOST'] == '127.0.0.1') {
						$this->data = (int)getGlobal('ThisComputer.volumeMediaLevel');
					} else {
						$this->success = FALSE;
						$this->message = 'The volume level is unknown!';
					}
				} else {
					$this->data = (int)$volume;
				}
			} else {
				$this->success = FALSE;
				if(is_null($this->mpd->errStr)) {
					$this->message = 'Error getting volume level!';
				} else {
					$this->message = $this->mpd->errStr;
				}
			}
			$this->mpd->Disconnect();
		}
		return $this->success;
	}
	
	// Playlist: Get
	function pl_get() {
		if($this->mpd_connect()) {
			$this->reset_properties();
			if(!is_null($playlist = $this->mpd->GetPlaylist())) {
				$this->success = TRUE;
				$this->message = 'OK';
				foreach($playlist['files'] as $file) {
					$this->data[] = array(
						'id'	=> (int)$file['Id'],
						'name'	=> (string)$file['Title'],
						'file'	=> (string)$file['file'],
					);
				}
			} else {
				$this->success = FALSE;
				if(is_null($this->mpd->errStr)) {
					$this->message = 'Error getting playlist!';
				} else {
					$this->message = $this->mpd->errStr;
				}
			}
			$this->mpd->Disconnect();
		}
		return $this->success;
	}

	// Playlist: Add
	function pl_add($input) {
		$this->reset_properties();
		if(strlen($input)) {
			if($this->mpd_connect()) {
				$this->mpd->PLAdd($input);
				$this->mpd->Disconnect();
				$this->reset_properties();
				$this->success = TRUE;
				$this->message = 'OK';
			}
		} else {
			$this->success = FALSE;
			$this->message = 'Input is missing!';
		}
		return $this->success;
	}
	
	// Playlist: Delete
	function pl_delete($id) {
		$this->reset_properties();
		if(strlen($id)) {
			if($this->mpd_connect()) {
				$this->mpd->PLRemoveId($id);
				$this->mpd->Disconnect();
				$this->reset_properties();
				$this->success = TRUE;
				$this->message = 'OK';
			}
		} else {
			$this->success = FALSE;
			$this->message = 'Id is missing!';
		}
		return $this->success;
	}
	
	// Playlist: Empty
	function pl_empty() {
		if($this->mpd_connect()) {
			$this->mpd->PLClear();
			//$this->mpd->DBRefresh();
			$this->mpd->Disconnect();
			$this->reset_properties();
			$this->success = TRUE;
			$this->message = 'OK';
		}
		return $this->success;
	}
	
	// Playlist: Play
	function pl_play($id) {
		$this->reset_properties();
		if(strlen($id)) {
			if($this->mpd_connect()) {
				$this->mpd->PlayId((int)$id);
				$this->mpd->Disconnect();
				$this->reset_properties();
				$this->success = TRUE;
				$this->message = 'OK';
			}
		} else {
			$this->success = FALSE;
			$this->message = 'Id is missing!';
		}
		return $this->success;
	}

	// Playlist: Random on/off
	function pl_random() {
		if($this->mpd_connect()) {
			$this->reset_properties();
			if(!is_null($status = $this->mpd->GetStatus())) {
				$this->mpd->SetRandom((int)!$status['random']);
				$this->success = TRUE;
				$this->message = 'OK';
			} else {
				$this->success = FALSE;
				if(is_null($this->mpd->errStr)) {
					$this->message = 'Error getting player status!';
				} else {
					$this->message = $this->mpd->errStr;
				}
			}
			$this->mpd->Disconnect();
		}
		return $this->success;
	}

	// Playlist: Loop on/off
	function pl_loop() {
		if($this->mpd_connect()) {
			$this->reset_properties();
			if(!is_null($status = $this->mpd->GetStatus())) {
				$loop = (($status['single'] == '0') && ($status['repeat'] == '1'));
				$this->mpd->SetSingle(0);
				$this->mpd->SetRepeat((int)!$loop);
				$this->success = TRUE;
				$this->message = 'OK';
			} else {
				$this->success = FALSE;
				if(is_null($this->mpd->errStr)) {
					$this->message = 'Error getting player status!';
				} else {
					$this->message = $this->mpd->errStr;
				}
			}
			$this->mpd->Disconnect();
		}
		return $this->success;
	}

	// Playlist: Repeat on/off
	function pl_repeat() {
		if($this->mpd_connect()) {
			$this->reset_properties();
			if(!is_null($status = $this->mpd->GetStatus())) {
				$repeat = (($status['single'] == '1') && ($status['repeat'] == '1'));
				$this->mpd->SetSingle((int)!$repeat);
				$this->mpd->SetRepeat((int)!$repeat);
				$this->success = TRUE;
				$this->message = 'OK';
			} else {
				$this->success = FALSE;
				if(is_null($this->mpd->errStr)) {
					$this->message = 'Error getting player status!';
				} else {
					$this->message = $this->mpd->errStr;
				}
			}
			$this->mpd->Disconnect();
		}
		return $this->success;
	}

	// Default command
	function command($command, $parameter) {
		if($this->mpd_connect()) {
			$result = $this->mpd->SendCommand($command, $parameter);
			$this->mpd->Disconnect();
			$this->reset_properties();
			$this->success = TRUE;
			$this->message = 'OK';
			if(!is_null($result)) {
				$this->data = $result;
			}
		}
		return $this->success;
	}
	

	
}

?>
