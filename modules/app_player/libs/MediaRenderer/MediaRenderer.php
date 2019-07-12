<?php
/** AVTransport UPnP Class
 * Used for controlling renderers
 *
 * @author jalder
 */

class MediaRenderer {
    public function __construct($server) {
        // получаем айпи и порт устройства
        $url = parse_url($server);
        $this->ip = $url['host'];
        $this->port = $url['port'];
        // получаем XML
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $server);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $content = curl_exec($ch);
        curl_close($ch);

        // загружаем xml
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content);
        // получаем адрес управления устройством
        foreach($xml->device->serviceList->service as $service) {
            if ($service->serviceId == 'urn:upnp-org:serviceId:AVTransport') {
                $chek_url = (substr($service->controlURL, 0, 1));
                $this->service_type = ($service->serviceType);
                if ($chek_url == '/') {
                    $this->ctrlurl = ($url['scheme'] . '://' . $this->ip . ':' . $this->port . $service->controlURL);
                } else {
                    $this->ctrlurl = ($url['scheme'] . '://' . $this->ip . ':' . $this->port . '/' . $service->controlURL);
                }
            }
        }
    }

    private function instanceOnly ($command, $id = 0) {
        $args = array('InstanceID' => $id);
        return $this->sendRequestToDevice($command, $args);
    }

    private function sendRequestToDevice ($command, $arguments) {
        $body = '<?xml version="1.0" encoding="utf-8" standalone="yes"?>'."\r\n";
        $body.= '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">';
        $body.= '<s:Body>';
        $body.= '<u:' . $command . ' xmlns:u="' . $this->service_type . '">';
        foreach($arguments as $arg => $value) {
            $body.= '<' . $arg . '>' . $value . '</' . $arg . '>';
        }

        $body.= '</u:' . $command . '>';
        $body.= '</s:Body>';
        $body.= '</s:Envelope>';
        $header = array(
            'Host: ' . $this->ip . ':' . $this->port,
            'User-Agent: Majordomo/ver-x.x UDAP/2.0 Win/7', //fudge the user agent to get desired video format
            'Content-Length: ' . strlen($body) ,
            'Connection: close',
            'Content-Type: text/xml; charset="utf-8"',
            'SOAPAction: "' . $this->service_type . '#' . $command . '"',
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_URL, $this->ctrlurl);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    public function play($url = "") {

		if ($url === "") {
            return $this->sendRequestToDevice('Play', $args = array('InstanceID' => 0,'Speed' => 1));
        }
 
        // neobhodimo ostanovit vosproizvedenie
        $this->instanceOnly('Stop');

        // proverem est li rashirenie
        $path_info = pathinfo($url);
        if ($path_info['extension']) {
            $urimetadata = $this->get_extfile(trim ($path_info['extension']));
        } else {
            $content_type = get_headers($url, 1)["Content-Type"];
            var_dump($content_type);
            // poluchaem zagolovki dlyz protokola i classa contenta massiv 'iteam' i 'httphead'
            $urimetadata = $this->get_urihead($content_type);
        }
        //var_dump($urimetadata);
        $MetaData ='&lt;?xml version=&quot;1.0&quot; encoding=&quot;UTF-8&quot;?&gt;';
        $MetaData.='&lt;DIDL-Lite xmlns=&quot;urn:schemas-upnp-org:metadata-1-0/DIDL-Lite/&quot; xmlns:dc=&quot;http://purl.org/dc/elements/1.1/&quot; xmlns:sec=&quot;http://www.sec.co.kr/&quot; xmlns:upnp=&quot;urn:schemas-upnp-org:metadata-1-0/upnp/&quot;&gt;';
        $MetaData.='&lt;item id=&quot;0&quot; parentID=&quot;-1&quot; restricted=&quot;0&quot;&gt;';
        $MetaData.='&lt;upnp:class&gt;'.$urimetadata['item'].'&lt;/upnp:class&gt;';
        $MetaData.='&lt;dc:title&gt;Majordomo mesage&lt;/dc:title&gt;';
        $MetaData.='&lt;dc:creator&gt;Majordomoterminal&lt;/dc:creator&gt;';
        $MetaData.='&lt;res protocolInfo=&quot;'.$urimetadata['httphead'].'&quot;&gt;' . $url . '&lt;/res&gt;';
        $MetaData.='&lt;/item&gt;';
        $MetaData.='&lt;/DIDL-Lite&gt;';
        
        $args = array('InstanceID' => 0, 'CurrentURI' => '<![CDATA[' . $url . ']]>', 'CurrentURIMetaData' => '');
        $response = $this->sendRequestToDevice('SetAVTransportURI', $args);
        // создаем хмл документ
        $doc = new \DOMDocument();
        $doc->loadXML($response);
        //DebMes($response);
        if(!$doc->getElementsByTagName('SetAVTransportURIResponse')) {
            $args = array('InstanceID' => 0, 'CurrentURI' => '<![CDATA[' . $url . ']]>', 'CurrentURIMetaData' => $MetaData);
            $response = $this->sendRequestToDevice('SetAVTransportURI', $args);
        }
        $args = array( 'InstanceID' => 0, 'Speed' => 1);
        $response = $this->sendRequestToDevice('Play', $args);
		$doc->loadXML($response);
        if ($doc->getElementsByTagName('PlayResponse ')) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function setNext($url) {
        $tags = get_meta_tags($url);
        $args = array(
            'InstanceID' => 0,
            'NextURI' => '<![CDATA[' . $url . ']]>',
            'NextURIMetaData' => ''
        );
        return $this->sendRequestToDevice('SetNextAVTransportURI', $args);
    }

    public function getState() {
        return $this->instanceOnly('GetTransportInfo');
    }

    public function getPosition() {
        return $this->instanceOnly('getPositionInfo');
    }

    public function getMedia() {
        return $this->instanceOnly('GetMediaInfo');
    }

    public function stop() {
        $response = $this->instanceOnly('Stop');
		// создаем хмл документ
        $doc = new \DOMDocument();
		$doc->loadXML($response);
        //DebMes($response);
        if ($doc->getElementsByTagName('StopResponse ')) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function pause() {
		$response = $this->getState();
		// создаем хмл документ
        $doc = new \DOMDocument();
		$doc->loadXML($response);
        if ($doc->getElementsByTagName('CurrentTransportState')->item(0)->nodeValue == 'PLAYING') {
            $response = $this->instanceOnly('Pause');
        } else {
			$response = $this->sendRequestToDevice('Play', array('InstanceID' => 0,'Speed' => 1));
		}
		$doc->loadXML($response);
        //DebMes($response);
        if ($doc->getElementsByTagName('PauseResponse ') OR $doc->getElementsByTagName('PlayResponse ')) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function next() {
        $response = $this->instanceOnly('Next');
		// создаем хмл документ
        $doc = new \DOMDocument();
		$doc->loadXML($response);
        //DebMes($response);
        if ($doc->getElementsByTagName('NextResponse ')) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function previous() {
        $response = $this->instanceOnly('Previous');
		// создаем хмл документ
        $doc = new \DOMDocument();
		$doc->loadXML($response);
        //DebMes($response);
        if ($doc->getElementsByTagName('PreviousResponse ')) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function seek($target = 0) {
        $response = $this->sendRequestToDevice('Seek', array('InstanceID' => 0,'Unit' => 'REL_TIME','Target' => $target));
		// создаем хмл документ
        $doc = new \DOMDocument();
		$doc->loadXML($response);
        //DebMes($response);
        if ($doc->getElementsByTagName('SeekResponse ')) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
	
// dorabativaem
private function get_urihead($uri_head){
    $avmetadatauri = array(
    'video/avi'=>            array('item'=>'object.item.videoItem', 'httphead'=>'http-get:*:video/avi:DLNA.ORG_PN=PV_DIVX_DX50;DLNA.ORG_OP=11;DLNA.ORG_CI=0;DLNA.ORG_FLAGS=01700000000000000000000000000000'), 
    'video/x-ms-asf' =>        array('item'=>'object.item.videoItem', 'httphead'=>'http-get:*:video/x-ms-asf:DLNA.ORG_PN=MPEG4_P2_ASF_SP_G726;DLNA.ORG_OP=00;DLNA.ORG_CI=0;DLNA.ORG_FLAGS=01700000000000000000000000000000'),
    'video/x-ms-wmv' =>        array('item'=>'object.item.videoItem', 'httphead'=>'http-get:*:video/x-ms-wmv:DLNA.ORG_PN=WMVMED_FULL;DLNA.ORG_OP=00;DLNA.ORG_CI=0;DLNA.ORG_FLAGS=01700000000000000000000000000000'),
    'video/mp4'=>            array('item'=>'object.item.videoItem', 'httphead'=>'http-get:*:video/mp4:*'),
    'video/mpeg' =>            array('item'=>'object.item.videoItem', 'httphead'=>'http-get:*:video/mpeg:DLNA.ORG_PN=MPEG_TS_SD_NA_ISO;DLNA.ORG_OP=00;DLNA.ORG_CI=0;DLNA.ORG_FLAGS=01700000000000000000000000000000'), 
    'video/mpeg2'=>            array('item'=>'object.item.videoItem', 'httphead'=>'http-get:*:video/mpeg2:DLNA.ORG_PN=MPEG_PS_PAL;DLNA.ORG_OP=00;DLNA.ORG_CI=0;DLNA.ORG_FLAGS=01700000000000000000000000000000'),
    'video/mp2t' =>            array('item'=>'object.item.videoItem', 'httphead'=>'http-get:*:video/mp2t:DLNA.ORG_PN=MPEG_TS_HD_NA;DLNA.ORG_OP=00;DLNA.ORG_CI=0;DLNA.ORG_FLAGS=01700000000000000000000000000000'),
    'video/mp2p' =>            array('item'=>'object.item.videoItem', 'httphead'=>'http-get:*:video/mp2t:DLNA.ORG_PN=MPEG_TS_HD_NA;DLNA.ORG_OP=00;DLNA.ORG_CI=0;DLNA.ORG_FLAGS=01700000000000000000000000000000'),
    'video/quicktime'=>        array('item'=>'object.item.videoItem', 'httphead'=>'http-get:*:video/quicktime:*'),
    'video/x-mkv'=>            array('item'=>'object.item.videoItem', 'httphead'=>'http-get:*:video/x-matroska:*'), 
    'video/3gpp' =>            array('item'=>'object.item.videoItem', 'httphead'=>'http-get:*:video/3gpp:*'),
    'video/x-flv'=>            array('item'=>'object.item.videoItem', 'httphead'=>'http-get:*:video/x-flv:*'),
    'audio/x-aac'=>            array('item'=>'object.item.audioItem.musicTrack', 'httphead'=>'http-get:*:audio/x-aac:*'),
    'audio/x-ac3'=>            array('item'=>'object.item.audioItem.musicTrack', 'httphead'=>'http-get:*:audio/x-ac3:DLNA.ORG_PN=AC3;DLNA.ORG_OP=00;DLNA.ORG_CI=0;DLNA.ORG_FLAGS=01700000000000000000000000000000'),
    'audio/mpeg' =>            array('item'=>'object.item.audioItem.musicTrack', 'httphead'=>'http-get:*:audio/mpeg:DLNA.ORG_PN=MP3;DLNA.ORG_OP=11;DLNA.ORG_CI=0;DLNA.ORG_FLAGS=01700000000000000000000000000000'),
    'application/ogg'=>        array('item'=>'object.item.audioItem.audioBroadcast', 'httphead'=>'http-get:*:audio/x-ogg:*'),
    'audio/x-ms-wma' =>        array('item'=>'object.item.audioItem.audioBroadcast', 'httphead'=>'http-get:*:audio/x-ms-wma:DLNA.ORG_PN=WMAFULL;DLNA.ORG_OP=00;DLNA.ORG_CI=0;DLNA.ORG_FLAGS=01700000000000000000000000000000'),
    'application/octet-stream' =>    array('item'=>'object.item.audioItem.audioBroadcast', 'httphead'=>'http-get:*:audio/mpeg:DLNA.ORG_PN=MP3;DLNA.ORG_OP=11;DLNA.ORG_CI=0;DLNA.ORG_FLAGS=01700000000000000000000000000000'),
    /// provereno
    'audio/aacp'=>            array('item'=>'object.item.audioItem.audioBroadcast', 'httphead'=>'http-get:*:video/x-flv:*'),
    );
    
    return $avmetadatauri[$uri_head];
    }
// get headers with extends files
private function get_extfile($ext){
    $extmetadatauri = array(
    'avi'=>     array('item'=>'object.item.videoItem',         'httphead'=>'http-get:*:video/avi:DLNA.ORG_PN=PV_DIVX_DX50;DLNA.ORG_OP=11;DLNA.ORG_CI=0;DLNA.ORG_FLAGS=01700000000000000000000000000000'), 
    'asf'=>     array('item'=>'object.item.videoItem',         'httphead'=>'http-get:*:video/x-ms-asf:DLNA.ORG_PN=MPEG4_P2_ASF_SP_G726;DLNA.ORG_OP=00;DLNA.ORG_CI=0;DLNA.ORG_FLAGS=01700000000000000000000000000000 '), 
    'wmv'=>     array('item'=>'object.item.videoItem',         'httphead'=>'http-get:*:video/x-ms-wmv:DLNA.ORG_PN=WMVMED_FULL;DLNA.ORG_OP=00;DLNA.ORG_CI=0;DLNA.ORG_FLAGS=01700000000000000000000000000000'), 
    'mp4'=>     array('item'=>'object.item.videoItem',        'httphead'=>'http-get:*:video/mp4:*'),
    'mpeg'=>     array('item'=>'object.item.videoItem',         'httphead'=>'http-get:*:video/mpeg:DLNA.ORG_PN=MPEG_PS_PAL;DLNA.ORG_OP=00;DLNA.ORG_CI=0;DLNA.ORG_FLAGS=01700000000000000000000000000000'), 
    'mpeg_ts' => array('item'=>'object.item.videoItem',        'httphead'=>'http-get:*:video/mpeg:DLNA.ORG_PN=MPEG_TS_SD_NA_ISO;DLNA.ORG_OP=00;DLNA.ORG_CI=0;DLNA.ORG_FLAGS=01700000000000000000000000000000 '),
    'mpeg1' =>     array('item'=>'object.item.videoItem',         'httphead'=>'http-get:*:video/mpeg:DLNA.ORG_PN=MPEG1;DLNA.ORG_OP=00;DLNA.ORG_CI=0;DLNA.ORG_FLAGS=01700000000000000000000000000000'),
    'mpeg2' =>     array('item'=>'object.item.videoItem',         'httphead'=>'http-get:*:video/mpeg2:DLNA.ORG_PN=MPEG_PS_PAL;DLNA.ORG_OP=00;DLNA.ORG_CI=0;DLNA.ORG_FLAGS=01700000000000000000000000000000'), 
    'ts'  =>     array('item'=>'object.item.videoItem',         'httphead'=>'http-get:*:video/mp2t:DLNA.ORG_PN=MPEG_TS_HD_NA;DLNA.ORG_OP=00;DLNA.ORG_CI=0;DLNA.ORG_FLAGS=01700000000000000000000000000000'), 
    'mp2t' =>     array('item'=>'object.item.videoItem',         'httphead'=>'http-get:*:video/mp2t:DLNA.ORG_PN=MPEG_TS_HD_NA;DLNA.ORG_OP=00;DLNA.ORG_CI=0;DLNA.ORG_FLAGS=01700000000000000000000000000000'), 
    'mp2p' =>     array('item'=>'object.item.videoItem',         'httphead'=>'http-get:*:video/mp2t:DLNA.ORG_PN=MPEG_TS_HD_NA;DLNA.ORG_OP=00;DLNA.ORG_CI=0;DLNA.ORG_FLAGS=01700000000000000000000000000000'), 
    'mov'=>     array('item'=>'object.item.videoItem',         'httphead'=>'http-get:*:video/quicktime:*'),
    'mkv'=>     array('item'=>'object.item.videoItem',         'httphead'=>'http-get:*:video/x-matroska:*'),
    '3gp'=>     array('item'=>'object.item.videoItem',         'httphead'=>'http-get:*:video/3gpp:*'), 
    'flv'=>     array('item'=>'object.item.videoItem',         'httphead'=>'http-get:*:video/x-flv:*'),
    'aac'=>     array('item'=>'object.item.audioItem.audioBroadcast', 'httphead'=>'http-get:*:audio/x-aac:*'),
    'ac3'=>     array('item'=>'object.item.audioItem.audioBroadcast', 'httphead'=>'http-get:*:audio/x-ac3:DLNA.ORG_PN=AC3;DLNA.ORG_OP=00;DLNA.ORG_CI=0;DLNA.ORG_FLAGS=01700000000000000000000000000000'),
    'ogg'=>     array('item'=>'object.item.audioItem.audioBroadcast', 'httphead'=>'http-get:*:audio/x-ogg:*'),
    'wma'=>     array('item'=>'object.item.audioItem.audioBroadcast', 'httphead'=>'http-get:*:audio/x-ms-wma:DLNA.ORG_PN=WMAFULL;DLNA.ORG_OP=00;DLNA.ORG_CI=0;DLNA.ORG_FLAGS=01700000000000000000000000000000'),
    // provereno
    'mp3'=>     array('item'=>'object.item.audioItem.audioBroadcast', 'httphead'=>'http-get:*:audio/mpeg:DLNA.ORG_PN=MP3;DLNA.ORG_OP=11;DLNA.ORG_CI=0;DLNA.ORG_FLAGS=01700000000000000000000000000000'),
    'm3u'=>     array('item'=>'object.item.audioItem.audioBroadcast', 'httphead'=>'http-get:*:audio/m3u:*'),
    );
    return $extmetadatauri[$ext];
    }
}
