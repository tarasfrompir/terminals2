<?php
class terminals extends module {
    function __construct() {
        $this->name            = "terminals";
        $this->title           = "<#LANG_MODULE_TERMINALS#>";
        $this->module_category = "<#LANG_SECTION_SETTINGS#>";
        $this->checkInstalled();
        $this->serverip = getLocalIp();
    }
    
    /**
     * saveParams
     *
     * Saving module parameters
     *
     * @access public
     */
    function saveParams($data = 1) {
        $data = array();
        if (IsSet($this->id)) {
            $data["id"] = $this->id;
        }
        if (IsSet($this->view_mode)) {
            $data["view_mode"] = $this->view_mode;
        }
        if (IsSet($this->edit_mode)) {
            $data["edit_mode"] = $this->edit_mode;
        }
        if (IsSet($this->tab)) {
            $data["tab"] = $this->tab;
        }
        return parent::saveParams($data);
    }
    
    /**
     * getParams
     *
     * Getting module parameters from query string
     *
     * @access public
     */
    function getParams($data = 1) {
        global $id;
        global $mode;
        global $view_mode;
        global $edit_mode;
        global $tab;
        if (isset($id)) {
            $this->id = $id;
        }
        if (isset($mode)) {
            $this->mode = $mode;
        }
        if (isset($view_mode)) {
            $this->view_mode = $view_mode;
        }
        if (isset($edit_mode)) {
            $this->edit_mode = $edit_mode;
        }
        if (isset($tab)) {
            $this->tab = $tab;
        }
    }
    
    /**
     * Run
     *
     * Description
     *
     * @access public
     */
    function run() {
        global $session;
        $out = array();
        if ($this->action == 'admin') {
            $this->admin($out);
        } else {
            $this->usual($out);
        }
        if (IsSet($this->owner->action)) {
            $out['PARENT_ACTION'] = $this->owner->action;
        }
        if (IsSet($this->owner->name)) {
            $out['PARENT_NAME'] = $this->owner->name;
        }
        $out['VIEW_MODE'] = $this->view_mode;
        $out['EDIT_MODE'] = $this->edit_mode;
        $out['MODE']      = $this->mode;
        $out['ACTION']    = $this->action;
        $out['TAB']       = $this->tab;
        if ($this->single_rec) {
            $out['SINGLE_REC'] = 1;
        }
        $this->data   = $out;
        $p            = new parser(DIR_TEMPLATES . $this->name . "/" . $this->name . ".html", $this->data, $this);
        $this->result = $p->result;
    }
    
    /**
     * BackEnd
     *
     * Module backend
     *
     * @access public
     */
    function admin(&$out) {
        if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
            $out['SET_DATASOURCE'] = 1;
        }
        if ($this->data_source == 'terminals' || $this->data_source == '') {
            if ($this->view_mode == '' || $this->view_mode == 'search_terminals') {
                $this->search_terminals($out);
            }
            if ($this->view_mode == 'edit_terminals') {
                $this->edit_terminals($out, $this->id);
            }
            if ($this->view_mode == 'delete_terminals') {
                $this->delete_terminals($this->id);
                $this->redirect("?");
            }
        }
    }
    
    /**
     * FrontEnd
     *
     * Module frontend
     *
     * @access public
     */
    function usual(&$out) {
        $this->admin($out);
    }
    
    /**
     * terminals search
     *
     * @access public
     */
    function search_terminals(&$out) {
        require(DIR_MODULES . $this->name . '/terminals_search.inc.php');
    }
    
    /**
     * terminals edit/add
     *
     * @access public
     */
    function edit_terminals(&$out, $id) {
        require(DIR_MODULES . $this->name . '/terminals_edit.inc.php');
    }
    
    /**
     * terminals delete record
     *
     * @access public
     */
    function delete_terminals($id) {
        if ($rec = getTerminalByID($id)) {
			deleteObject($rec['LINKED_OBJECT']);
            SQLExec('DELETE FROM `terminals` WHERE `ID` = ' . $rec['ID']);
        }
    }
    /**
     * terminals subscription events
     *
     * @access public
     */
    function processSubscription($event, $details = '') {
		// если происходит событие SAY_CACHED_READY то запускаемся
        if ($event == 'SAY_CACHED_READY' AND $details['level'] >= (int) getGlobal('minMsgLevel')) {

            // берем длинну сообщения
            if (!getMediaDurationSeconds($details['filename'])){
                $details['time_shift'] = 1;
            } else {
				$details['time_shift'] = getMediaDurationSeconds($details['filename']);
			}

			// берем ссылку http
            if (preg_match('/\/cms\/cached.+/', $details['filename'], $m)) {
                $server_ip = getLocalIp();
                if (!$server_ip) {
                    DebMes("Server IP not found", 'terminals');
                    return false;
                } else {
                    $details['linkfile'] = 'http://' . $server_ip . $m[0];
                }
		    }

			// добавляем язык в разных форматах
			$details['lang'] = SETTINGS_SITE_LANGUAGE;
			$details['langfull'] = LANG_SETTINGS_SITE_LANGUAGE_CODE;

            if (!$details['event']) {
                $details['event'] = 'SAY';
            }
            if ($details['event']) {
                $terminals = array();
                if ($details['destination']) {
                    if (!$terminals = getTerminalsByName($details['destination'], 1)) {
                        $terminals = getTerminalsByHost($details['destination'], 1);
                    }
                } else {
                    $terminals = getTerminalsByCANTTS();
                }
                $this->terminalSayByCacheQueue($terminals, $details);
            }
        } elseif ($event == 'HOURLY') {
            // check terminals
            $terminals = SQLSelect("SELECT * FROM terminals WHERE IS_ONLINE=0 AND HOST!=''");
            foreach ($terminals as $terminal) {
                if (ping($terminal['HOST'])) {
                    $terminal['LATEST_ACTIVITY'] = date('Y-m-d H:i:s');
                    $terminal['IS_ONLINE']       = 1;
                    SQLUpdate('terminals', $terminal);
                }
            }
            SQLExec('UPDATE terminals SET IS_ONLINE=0 WHERE LATEST_ACTIVITY < (NOW() - INTERVAL 90 MINUTE)'); //
        }
    }

    /**
     * очередь сообщений 
     *
     * @access public
     */
    function terminalSayByCacheQueue($terminals, $details) {
		foreach ($terminals as $terminal) {	
			$online_terminal = ping($terminal['HOST']);
			if (!$terminal['MIN_MSG_LEVEL']) {
				$terminal['MIN_MSG_LEVEL'] = 0;
			}
			if (!$terminal['ID'] OR !$terminal['CANPLAY'] OR !$terminal['CANTTS'] OR $terminal['MIN_MSG_LEVEL'] > $details['level']) {
				continue;
			}
			if ($details['event'] == 'ASK') {
				$details['level'] = 9999;
			}
			
			// berem vse soobsheniya iz shoots dlya poiska soobsheniya s takoy frazoy
			$messages = SQLSelect("SELECT * FROM shouts ORDER BY ID DESC LIMIT 0 , 100");
			foreach ($messages as $message) {
				if ($details['message'] == $message['MESSAGE']) {
					$number_message = $message['ID'];
					break;
				}
			}
			// получаем данные оплеере для восстановления проигрываемого контента
			$chek_restore = SQLSelectOne("SELECT * FROM jobs WHERE TITLE LIKE'" . 'target-' . $terminal['NAME'] . '-number-' . "99999999999'");
			if (!$chek_restore) {
				$played = getPlayerStatus($terminal['NAME']);
				if (($played['state'] == 'playing') and (stristr($played['file'], 'cms/cached/voice') === FALSE)) {
					addScheduledJob('target-' . $terminal['NAME'] . '-number-99999999998', "playMedia('" . $played['file'] . "', '" . $terminal['NAME'] . "',1);", time() + 100, 4);
					addScheduledJob('target-' . $terminal['NAME'] . '-number-99999999999', "seekPlayerPosition('" . $terminal['NAME'] . "'," . $played['time'] . ");", time() + 110, 4);
				}
			}
		
			addScheduledJob('target-' . $terminal['NAME'] . '-number-' . $number_message, "send_message_to_terminal('" . $terminal['NAME'] . "','" . $details['message']. "','" . $details['event']. "','" . $details['member']. "','" . $details['level']. "','" . $details['filename']. "','" . $details['linkfile']. "','" . $details['lang']. "','" . $details['langfull']. "');", time() + 1, $details['time_shift']);

			// vibiraem vse soobsheniya dla terminala s sortirovkoy po nazvaniyu
			$all_messages = SQLSelect("SELECT * FROM jobs WHERE TITLE LIKE'" . 'target-' . $terminal['NAME'] . '-number-' . "%' ORDER BY `TITLE` ASC");
			$first_fields = reset($all_messages);
			$runtime      = (strtotime($first_fields['RUNTIME']));
			foreach ($all_messages as $message) {
				$expire          = (strtotime($message['EXPIRE'])) - (strtotime($message['RUNTIME']));
				$rec['ID']       = $message['ID'];
				$rec['TITLE']    = $message['TITLE'];
				$rec['COMMANDS'] = $message['COMMANDS'];
				$rec['RUNTIME']  = date('Y-m-d H:i:s', $runtime);
				$rec['EXPIRE']   = date('Y-m-d H:i:s', $runtime + $expire);
				// proverka i udaleniye odinakovih soobsheniy
				if ($prev_message['TITLE'] == $message['TITLE']) {
					SQLExec("DELETE FROM jobs WHERE ID='" . $rec['ID'] . "'");
				} else {
					SQLUpdate('jobs', $rec);
				}
				$runtime      = $runtime + $expire;
				$prev_message = $message;
			}
		}
	}
    
    /**
     * Install
     *
     * Module installation routine
     *
     * @access private
     */
    function install($parent_name = "") {
        // updates database
        // update main terminal
        $terminal                = getMainTerminal();
        $terminal['PLAYER_TYPE'] = 'mainterm';
        SQLUpdate('terminals', $terminal);
        
        unsubscribeFromEvent($this->name, 'SAY');
        unsubscribeFromEvent($this->name, 'SAYTO');
        unsubscribeFromEvent($this->name, 'ASK');
        unsubscribeFromEvent($this->name, 'SAYREPLY');
        //subscribeToEvent($this->name, 'SAY', '', 0);
        //subscribeToEvent($this->name, 'SAYREPLY', '', 0);
        //subscribeToEvent($this->name, 'SAYTO', '', 0);
        //subscribeToEvent($this->name, 'ASK', '', 0);
        subscribeToEvent($this->name, 'SAY_CACHED_READY', '', 0);
        subscribeToEvent($this->name, 'HOURLY');
      
        parent::install($parent_name);
        
    }
    
    /**
     * Uninstall
     *
     * Module uninstall routine
     *
     * @access public
     */
    function uninstall() {
        //SQLDropTable('terminals');
        unsubscribeFromEvent($this->name, 'SAY');
        unsubscribeFromEvent($this->name, 'SAYTO');
        unsubscribeFromEvent($this->name, 'ASK');
        unsubscribeFromEvent($this->name, 'SAYREPLY');
        unsubscribeFromEvent($this->name, 'SAY_CACHED_READY');
        unsubscribeFromEvent($this->name, 'HOURLY');
       
        parent::uninstall();
    }
    
    /**
     * dbInstall
     *
     * Database installation routine
     *
     * @access private
     */
    function dbInstall($data) {
        /*
        terminals - Terminals
        */
        $data = <<<EOD
        terminals: ID int(10) unsigned NOT NULL auto_increment
        terminals: NAME varchar(255) NOT NULL DEFAULT ''
 terminals: HOST varchar(255) NOT NULL DEFAULT ''
 terminals: TITLE varchar(255) NOT NULL DEFAULT ''
 terminals: CANPLAY int(3) NOT NULL DEFAULT '1'
 terminals: CANTTS int(3) NOT NULL DEFAULT '0'
 terminals: MIN_MSG_LEVEL varchar(255) NOT NULL DEFAULT ''
 terminals: TTS_TYPE char(20) NOT NULL DEFAULT '' 
 terminals: PLAYER_TYPE char(20) NOT NULL DEFAULT ''
 terminals: PLAYER_PORT varchar(255) NOT NULL DEFAULT ''
 terminals: PLAYER_USERNAME varchar(255) NOT NULL DEFAULT ''
 terminals: PLAYER_PASSWORD varchar(255) NOT NULL DEFAULT ''
 terminals: PLAYER_CONTROL_ADDRESS varchar(255) NOT NULL DEFAULT ''
 terminals: IS_ONLINE int(3) NOT NULL DEFAULT '0'
 terminals: MAJORDROID_API int(3) NOT NULL DEFAULT '0'
 terminals: LATEST_REQUEST varchar(255) NOT NULL DEFAULT ''
 terminals: LATEST_REQUEST_TIME datetime
 terminals: LATEST_ACTIVITY datetime
 terminals: LINKED_OBJECT varchar(255) NOT NULL DEFAULT ''
 terminals: LEVEL_LINKED_PROPERTY varchar(255) NOT NULL DEFAULT ''
EOD;
        parent::dbInstall($data);
  
    }
}
?>
