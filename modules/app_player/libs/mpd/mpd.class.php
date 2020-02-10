<?php

/*
 * MPD command reference at http://www.musicpd.org/doc/protocol/index.html
 */

class mpd_player {

	function __construct($srv,$port,$pwd = NULL, $debug= FALSE ) {
		$this->host = $srv;
		$this->port = $port;
        	$this->password = $pwd;
        	$this->debugging = $debug;

		if (!$this->host) {
		        $this->connected = FALSE;
		}
		
		$this->mpd_sock = fsockopen($this->host,$this->port,$errNo,$errStr,3);
		if (!$this->mpd_sock) {
		        $this->connected = FALSE;
		} else {
			$counter=0;
			while(!feof($this->mpd_sock)) {
				$counter++;
				if ($counter > 10){
					$this->connected = FALSE;
					break ;
				}
				$response = fgets($this->mpd_sock, 1024);
				if (strncmp("OK", $response, strlen("OK")) == 0) {
					$this->connected = TRUE;
					break ;
				}
				if (strncmp("ACK",$response,strlen("ACK")) == 0) {
				    $this->connected = FALSE;
					break;
				}
			}
		}
		if (!$this->connected) {
			// close socket
			fclose($this->mpd_sock);
		}
        if ($this->password) {
            fputs($this->mpd_sock, 'password "' . $this->password . '"'."\n");
            while(!feof($this->mpd_sock)) {
                $response = fgets($this->mpd_sock,1024);
                if (strncmp("OK", $response, strlen("OK")) == 0) {
                    $this->connected = TRUE;
                    break ;
                }
				if (strncmp("ACK",$response,strlen("ACK")) == 0) {
					$this->connected = FALSE;
					// close socket
					fclose($this->mpd_sock);
					break ;
				}
            }
		} 
	}

	/* Disconnect() 
	 * 
	 * Closes the connection to the MPD server.
	 */
	function Disconnect() {
		fclose($this->mpd_sock);
		$this->connected = FALSE;
		unset($this->mpd_sock);
	}

	/* SendCommand()
	 * 
	 * Sends a generic command to the MPD server. Several command constants are pre-defined for 
	 * use (see MPD_CMD_* constant definitions above). 
	 */
	function SendCommand($cmdStr, $arg1 = "",$arg2 = "") {
		$respStr = "";
		if ($this->connected ) {
			if (strlen($arg1) > 0) $cmdStr .= ' "' . $arg1 . '"';
			if (strlen($arg2) > 0) $cmdStr .= ' "' . $arg2 . '"';
			fputs($this->mpd_sock, $cmdStr . "\n");
			while(!feof($this->mpd_sock)) {
				$response = fgets($this->mpd_sock, 1024);
				// Build the response string
				$respStr .= $response;
				// An OK signals the end of transmission -- we'll ignore it
				if (strncmp("OK",$response,strlen("OK")) == 0) {
				    break;
				}
				// An ERR signals the end of transmission with an error! Let's grab the single-line message.
				if (strncmp("ACK",$response,strlen("ACK")) == 0) {
					list ( $junk, $errTmp ) = strtok("ACK" . " ",$response );
					return $response;
				}
			}
		}
		return $respStr;
	}	

	/* GetStatus() 
	 * 
	 * Retrieves the 'status' variables from the server and tosses them into an array.
     *
	 * NOTE: This function really should not be used. Instead, use $this->[variable]. The function
	 *   will most likely be deprecated in future releases.
	 */
	function GetStatus() {
		$status = $this->SendCommand("status");
		if ( ! $status ) {
			return NULL;
		} else {
			$statusArray = array();
			$statusLine = strtok($status,"\n");
			while ( $statusLine ) {
				list ( $element, $value ) = explode(": ",$statusLine);
				$statusArray[$element] = $value;
				$statusLine = strtok("\n");
			}
		}
		return $statusArray;
	}

    function GetCommand() {
		$status = $this->SendCommand("commands");
		if ( ! $status ) {
			return NULL;
		} else {
			$statusArray = array();
			$statusLine = strtok($status,"\n");
			$i=0;
			while ( $statusLine ) {
				list ( $element, $value ) = explode(": ",$statusLine);
				$i++;
				$statusArray[$i] = $value;
				$statusLine = strtok("\n");
			}
		}
		return $statusArray;
	}



	function SetVolume($newVol) {
        // Forcibly prevent out of range errors
		if ( $newVol < 0 )   $newVol = 0;
		if ( $newVol > 100 ) $newVol = 100;
        $ret = $this->SendCommand("setvol", $newVol);
		return $ret;
	}


	function Play($time=0) {
		$rpt = $this->SendCommand("play", $time);
		return $rpt;
	}
	
	function Stop() {
		$rpt = $this->SendCommand("stop");
		return $rpt;
	}
	
	function Previous() {
		$rpt = $this->SendCommand("previous");
		return $rpt;
	}

	function Next() {
		$rpt = $this->SendCommand("next");
		return $rpt;
	}

   // dopisat proverku na pausu tekushuyu 
	function Pause() {
		$rpt = $this->SendCommand("pause");
		return $rpt;
	}

	function PLClear() {
		$rpt = $this->SendCommand("clear");
		return $rpt;
	}	
	
	function PLAdd($url) {
		$rpt = $this->SendCommand("add", $url);
		return $rpt;
	}	
	
	function Ping() {
		$rpt = $this->SendCommand("ping");
		return $rpt;
	}
	
	function SetRepeat($in) {
		$rpt = $this->SendCommand("repeat",$in);
		return $rpt;
	}
	
	function SetRandomOff($in) {
		$rpt = $this->SendCommand("random",$in);
		return $rpt;
	}
    
}

?>
