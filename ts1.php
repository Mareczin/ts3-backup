<?php
class poke_admins {
    
    private static $eventName = 'poke_admins';
    private static $config;
    private static $simpleConfig = array('onClientAreOnChannel' => array(0), 'groups_poke' => array(1,2), 'ignored_channel' => array());
    private static $cacheNames = array('help_clients' => 'messaged_client');
    private static $clientsNeedHelp = array();
    
    private static function loadConfig() {
        global $lang;
    $cfg = getEventConfigValue(self::$eventName);
        if ($cfg != false) {
            self::$config = $cfg;
        } else {
            self::$config = self::$simpleConfig;
            echo ": > [".self::$eventName."]: ".$lang->getConsoleLanguage('SIMPLE_CONFIGURATION')."\n";
        }
        return true;
    }
    
    public static function onRegister() {
        global $cache;
        self::loadConfig();
        $cache->setCache(self::$cacheNames['help_clients'],array());
        return true;
    }
	
    private static function checkGroups($invokerid, $cid) {
		if (isset(self::$config['groups_poke'][$cid])) {
			$clientGroups = explode(',',$invokerid['client_servergroups']);
			foreach ($clientGroups as $clientGroup) {
				if (in_array($clientGroup,self::$config['groups_poke'][$cid]) == true) {
					return true;
				}
			}
		}
        return false;
    }
	
	private static function isClientInGroup($group,$clientGroups) {
        foreach ($clientGroups as $checkGroup) {
            if ($group == $checkGroup) {
                return $group;
            }            
        }
        return false;
    }
	
	
    public static function adminsData($config){
		global $ts, $whoami; 
		$i = 0;
		$admins = array();
		
        $servergroups = $ts->getElement($ts->getServerGroupList(),'data');		
		foreach ($ts->getElement($ts->getClientList('-groups -uid -away -voice -times'),'data') as $client) {
            if ($client['clid'] != $whoami['client_id']) {
                $clientGroups = explode(',',$client['client_servergroups']);
                foreach ($config as $checkThisGroup) {
                    $group = self::isClientInGroup($checkThisGroup,$clientGroups);
                    if (is_numeric($group) == true && in_array($group,$config) == true) {
                        $admins[$client['client_nickname']] = array('group' => $group, 'clid' => $client['clid'], 'unique_id' => $client['client_unique_identifier'], 'away' => $client['client_away'], 'mute' => $client['client_output_muted']);
							
					}
                }
            }
        }
		
		$adminsCount = array();
        foreach ($servergroups as $group) {
			if (in_array($group['sgid'], $config) == true) {
				foreach ($admins as $nickname => $values) {
					if ($values['group'] == $group['sgid']) {							
							$adminsCount[] = '[URL=client://' . $values['clid'] . '/' . $values['unique_id'] . '~'.urlencode($nickname).']' . $nickname . '[/URL]';
							$i++;
						}
					}
			}
		}
		
		return $adminsCount;
	}
	
    private static function isAdminOnChannel($clientsData) {
        foreach ($clientsData as $user) {
            if (self::checkGroups($user, $user['cid']) == true && in_array($user['cid'],self::$config['onClientAreOnChannel']) == true) {
                return true;
            }
        }
        return false;
    }
	
    public static function onClient($invokerid = null) {
        global $cache;
		if (array_key_exists((string) $invokerid['client_unique_identifier'],self::$clientsNeedHelp) == true) {
			if (self::$clientsNeedHelp[(string) $invokerid['client_unique_identifier']] == true && in_array($invokerid['cid'],self::$config['onClientAreOnChannel']) == false) {
				self::$clientsNeedHelp[(string) $invokerid['client_unique_identifier']] = false;
			}
		}
		return true;
    }
	
	public static function onClientAreOnChannel($clid = null,$cid = null,$invokerid = null,$clientsData = null) {
		global $ts, $kernel, $lang, $cache;
		$client = $ts->getElement($ts->getClientInfo($clid),'data');
		
		if (self::checkGroups($client, $client['cid']) == false && $client['client_is_talker'] == 0 && self::isAdminOnChannel($clientsData) == false) {
			if ($client['client_away'] != 0 or $client['client_input_muted'] != 0 or $client['client_output_muted'] != 0 or $client['client_output_hardware'] != 1) {
				$ts->sendMessage(1,$clid,$lang->getLanguage('PA_KICK_MSG'));
				$ts->kickClient($clid,$lang->getLanguage('PA_KICK_MSG'),'channel');
				return true;
			}
			
			$pokes = 0;
			foreach ($clientsData as $aclient) {
				if (self::checkGroups($aclient, $client['cid']) == true && $aclient['client_away'] == 0 && $aclient['client_database_id'] != 94) {
					if (!in_array($aclient['cid'], self::$config['ignored_channel'])) {
						if(self::$config['type'] == 'poke'){
							$ts->pokeClient($aclient['clid'],$lang->langReplace('[USER_NAME]',$client['client_nickname'],'PA_POKE_MSG'));
							$pokes++;
						}else if(self::$config['type'] == 'pw'){
							$ts->sendMessage(1,$aclient['clid'],$lang->langReplace('[USER_NAME]',$client['client_nickname'],'PA_POKE_MSG'));
							$pokes++;
						}
					}
				}
			}
			
			$admins = implode(",",self::adminsData(self::$config['groups_poke'][$cid]));
			$adminCount = count(self::adminsData(self::$config['groups_poke'][$cid]));
			$channelInfo = $ts->getElement($ts->getChannelInfo($client['cid']), 'data');
			
			if (array_key_exists((string) $client['client_unique_identifier'],self::$clientsNeedHelp) == true) {
				if (self::$clientsNeedHelp[(string) $client['client_unique_identifier']] == false) {
					self::$clientsNeedHelp[(string) $client['client_unique_identifier']] = true;
					if ($pokes > 0) {
						$ts->sendMessage(1,$clid, $lang->langReplace(array("[COUNT]", "[ADMINS]","[CH_ID]","[CH_NAME]"), array($adminCount, $admins, $cid, $channelInfo['channel_name']),'PA_USER_MSG'));
					} else {
						$ts->sendMessage(1,$clid,$lang->getLanguage('PA_USER_MSG_NOADMINS'));
					}
				}
				
			} else {
				self::$clientsNeedHelp[(string) $client['client_unique_identifier']] = true;
				if ($pokes > 0) {
					$ts->sendMessage(1,$clid, $lang->langReplace(array("[COUNT]", "[ADMINS]","[CH_ID]","[CH_NAME]"), array($adminCount, $admins, $cid, $channelInfo['channel_name']),'PA_USER_MSG'));
				} else {
					$ts->sendMessage(1,$clid,$lang->getLanguage('PA_USER_MSG_NOADMINS'));
				}
			}
		}
		return true;
	}
    
}
?>
