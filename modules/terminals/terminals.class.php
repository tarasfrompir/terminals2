<?php
/**
 * Terminals
 *
 * Terminals
 *
 * @package MajorDoMo
 * @author Serge Dzheigalo <jey@tut.by> http://smartliving.ru/
 * @version 0.3
 */
//
//
class terminals extends module
{
    /**
     * terminals
     *
     * Module class constructor
     *
     * @access private
     */
    function __construct()
    {
        $this->name = "terminals";
        $this->title = "<#LANG_MODULE_TERMINALS#>";
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
    function saveParams($data = 1)
    {
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
    function getParams($data = 1)
    {
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
    function run()
    {
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
        $out['MODE'] = $this->mode;
        $out['ACTION'] = $this->action;
        $out['TAB'] = $this->tab;
        if ($this->single_rec) {
            $out['SINGLE_REC'] = 1;
        }
        $this->data = $out;
        $p = new parser(DIR_TEMPLATES . $this->name . "/" . $this->name . ".html", $this->data, $this);
        $this->result = $p->result;
    }

    /**
     * BackEnd
     *
     * Module backend
     *
     * @access public
     */
    function admin(&$out)
    {
        $this->getConfig();
		$out['LOG_ENABLED'] = $this->config['LOG_ENABLED'];

        if ($this->config['TERMINALS_TIMEOUT']) {
			$out['TERMINALS_TIMEOUT'] = $this->config['TERMINALS_TIMEOUT'];
		} else {
			$out['TERMINALS_TIMEOUT'] = 10;
		}
        if ($this->config['TERMINALS_PING']) {
			$out['TERMINALS_PING'] = $this->config['TERMINALS_PING'];
		} else {
			$out['TERMINALS_PING'] = 27;
		}
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
		if ($this->view_mode == 'update_settings') {
            global $log_enabled;
            $this->config['LOG_ENABLED'] = $log_enabled;
            global $terminals_timeout;           
            $this->config['TERMINALS_TIMEOUT'] = trim($terminals_timeout);
            global $terminals_ping;           
            $this->config['TERMINALS_PING'] = trim($terminals_ping);
            $this->saveConfig();
            
            $this->redirect("?ok=1");
        }
    }

    /**
     * FrontEnd
     *
     * Module frontend
     *
     * @access public
     */
    function usual(&$out)
    {
        $this->admin($out);
    }

    /**
     * terminals search
     *
     * @access public
     */
    function search_terminals(&$out)
    {
        require(DIR_MODULES . $this->name . '/terminals_search.inc.php');
    }

    /**
     * terminals edit/add
     *
     * @access public
     */
    function edit_terminals(&$out, $id)
    {
        require(DIR_MODULES . $this->name . '/terminals_edit.inc.php');
    }

    /**
     * terminals delete record
     *
     * @access public
     */
    function delete_terminals($id)
    {
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
     function processSubscription($event, $details = '')
    {
        // если происходит событие SAY_CACHED_READY то запускаемся
        if ($event == 'SAY_CACHED_READY' ) {
            $this->getConfig();
            if ($this->config['LOG_ENABLED']) DebMes("Processing $event: " . json_encode($details, JSON_UNESCAPED_UNICODE), 'terminals');
            // ждем файл сообщения
            while (!file_exists($details['CACHED_FILENAME']) OR $count < 50 ) {
                usleep(1000);
                $count++;
            }
            // берем длинну сообщения
            if (get_media_info($details['CACHED_FILENAME'])['duration'] < 2) {
                $duration = 2;
            } else {
                $duration = get_media_info($details['CACHED_FILENAME'])['duration'];
            }
            if ($this->config['LOG_ENABLED']) DebMes("FINISH Processing $event:  - " . $details['CACHED_FILENAME'], 'terminals');
            $rec['MESSAGE_DURATION'] = $duration + 1;
            $rec['ID'] = $details['ID'];
            $rec['CACHED_FILENAME'] = $details['CACHED_FILENAME'];
            SQLUpdate('shouts', $rec);
        } else if ($event == 'ASK') {
            $this->getConfig();
            $terminal = $details['destination'];
            try {
                include_once DIR_MODULES . 'terminals/tts_addon.class.php';
                $addon_file = DIR_MODULES . 'terminals/tts/' . $terminal['TTS_TYPE'] . '.addon.php';
                if (file_exists($addon_file) AND $terminal['TTS_TYPE']=='majordroid_tts') {
                    include_once($addon_file);
                    $tts = new $terminal['TTS_TYPE']($terminal);
                    if (method_exists($tts,'ask')) {
                        $tts->ask($details['message']);
                        if ($this->config['LOG_ENABLED']) DebMes("Sending Message - " . $details['message'] . "to : " . $terminal['NAME'], 'terminals');
                    } 
                } else {
                    sleep (1);
                    if ($this->config['LOG_ENABLED']) DebMes("Terminal not right configured - " . $terminal['NAME'] , 'terminals');
                }
            } catch(Exception $e) {
                if ($this->config['LOG_ENABLED']) DebMes("Terminal terminated, not work addon - " . $terminal['NAME'] , 'terminals');
            }
        } else  if ($event == 'HOURLY') {
            // check terminals
            $terminals = SQLSelect("SELECT * FROM terminals WHERE IS_ONLINE=0 AND HOST!=''");
            foreach ($terminals as $terminal) {
                pingTerminalSafe($terminal['NAME'], $terminal);
            }
            SQLExec('UPDATE terminals SET IS_ONLINE=0 WHERE LATEST_ACTIVITY < (NOW() - INTERVAL 150 MINUTE)');
        }
    }

    /**
     * Install
     *
     * Module installation routine
     *
     * @access private
     */
    function install($parent_name = "")
    {
        // add class and properties
        addClass('Terminals', 'SDevices');
        addClassProperty('Terminals', 'name');
        addClassProperty('Terminals', 'basy');
        addClassProperty('Terminals', 'playerdata');
	    
	// update main terminal
        $terminal = getMainTerminal();
        $terminal['TTS_TYPE'] = 'mainterminal';
        SQLUpdate('terminals', $terminal);
	    
        //добавляем связанній обьект для всех терминалов необходимо для передачи сообщений
        $terminals = SQLSelect("SELECT * FROM terminals WHERE LINKED_OBJECT=''");
        foreach ($terminals as $terminal) {
            addClassObject('Terminals', $terminal['NAME']);
            $terminal['LINKED_OBJECT'] = $terminal['NAME'];
            SQLUpdate('terminals', $terminal);
        }
        
        unsubscribeFromEvent($this->name, 'SAY');
        unsubscribeFromEvent($this->name, 'SAYTO');
        unsubscribeFromEvent($this->name, 'ASK');
        unsubscribeFromEvent($this->name, 'SAYREPLY');
        
        subscribeToEvent($this->name, 'SAY_CACHED_READY', 0);
        subscribeToEvent($this->name, 'ASK', 0);
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
    function uninstall()
    {
        SQLDropTable('terminals');
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
    function dbInstall($data)
    {
        /*
        terminals - Terminals
        */
        $data = <<<EOD
 terminals: ID int(10) unsigned NOT NULL auto_increment
 terminals: NAME varchar(255) NOT NULL DEFAULT ''
 terminals: HOST varchar(255) NOT NULL DEFAULT ''
 terminals: TITLE varchar(255) NOT NULL DEFAULT ''
 terminals: CANPLAY int(1) NOT NULL DEFAULT '0'
 terminals: CANTTS int(1) NOT NULL DEFAULT '0'
 terminals: MIN_MSG_LEVEL varchar(255) NOT NULL DEFAULT ''
 terminals: TTS_TYPE char(20) NOT NULL DEFAULT '' 
 terminals: TTS_PORT char(5) NOT NULL DEFAULT '' 
 terminals: PLAYER_TYPE char(20) NOT NULL DEFAULT ''
 terminals: PLAYER_PORT varchar(255) NOT NULL DEFAULT ''
 terminals: PLAYER_USERNAME varchar(255) NOT NULL DEFAULT ''
 terminals: PLAYER_PASSWORD varchar(255) NOT NULL DEFAULT ''
 terminals: PLAYER_CONTROL_ADDRESS varchar(255) NOT NULL DEFAULT ''
 terminals: IS_ONLINE int(1) NOT NULL DEFAULT '0'
 terminals: MAJORDROID_API int(3) NOT NULL DEFAULT '0'
 terminals: LATEST_REQUEST varchar(255) NOT NULL DEFAULT ''
 terminals: LATEST_REQUEST_TIME datetime
 terminals: LATEST_ACTIVITY datetime
 terminals: USE_SYSTEM_MML int(1) NOT NULL DEFAULT '1'
 terminals: MESSAGE_VOLUME_LEVEL int(3) NOT NULL DEFAULT '100' 
 terminals: TERMINAL_VOLUME_LEVEL int(3) NOT NULL DEFAULT '100' 
EOD;
        parent::dbInstall($data);

    }
// --------------------------------------------------------------------
}

/*
*
* TW9kdWxlIGNyZWF0ZWQgTWFyIDI3LCAyMDA5IHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
?>
