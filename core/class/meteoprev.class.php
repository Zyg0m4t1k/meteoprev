<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class meteoprev extends eqLogic {
    /*     * *************************Attributs****************************** */
	public static $_widgetPossibility = array('custom' => true);


    /*     * ***********************Methode static*************************** */

	public static function cron() {
		foreach (self::byType('meteoprev') as $station) {
			$autorefresh = $station->getConfiguration('refreshCron');
			if ($station->getIsEnable() && $autorefresh != '' ) {
				try {
					$c = new Cron\CronExpression($autorefresh, new Cron\FieldFactory);
					if ($c->isDue()) {
						log::add('meteoprev', 'debug','is dure');
						try {
							$station->setDataStation();
						} catch (Exception $exc) {
							log::add('meteoprev', 'error', __('Erreur pour ', __FILE__) . $station->getHumanName() . ' : ' . $exc->getMessage());
						}
					}
				} catch (Exception $exc) {
					log::add('meteoprev', 'error', __('Expression cron non valide pour ', __FILE__) . $station->getHumanName() . ' : ' . $autorefresh);
				}
			}
		}
	}	


    /*
     * Fonction exécutée automatiquement toutes les heures par Jeedom
      public static function cronHourly() {

      }
     */

    /*
     * Fonction exécutée automatiquement tous les jours par Jeedom
      public static function cronDayly() {

      }
     */
	public function setDataStation() {
		$homepage = @file_get_contents('https://www.prevision-meteo.ch/services/json/' . $this->getConfiguration('station'));
		if($homepage === FALSE) {
			log::add('meteoprev','error','Impossible de récupérer le fichier https://www.prevision-meteo.ch/services/json/' . $this>getConfiguration('station'));
			return;
		}
		$path = dirname(__FILE__) . '/../../data';
		if (!is_dir($path)) {
				log::add('meteoprev','debug','Creation du dossier data');
				com_shell::execute(system::getCmdSudo() . 'mkdir ' . dirname(__FILE__) . '/../../data' . ' > /dev/null 2>&1;');
				com_shell::execute(system::getCmdSudo() . 'chmod 777 -R ' . dirname(__FILE__) . '/../../data' . ' > /dev/null 2>&1;');
				log::add('meteoprev','debug','Droits sudo dossier data');
		} else {
			com_shell::execute(system::getCmdSudo() . 'chmod 777 -R ' . dirname(__FILE__) . '/../../data' . ' > /dev/null 2>&1;');
			log::add('meteoprev','debug','Droits sudo dossier data');
		}
		$file = dirname(__FILE__) . '/../../data/' . $this->getConfiguration('station') .'.json';	
		if (!file_exists($file)) {
			log::add('meteoprev','debug','fichier existe pas');
			file_put_contents($file, $homepage);
		} else {
			$results = json_decode(file_get_contents($file), true);
			$datas = json_decode($homepage, true);
			if($datas === $results) {
				log::add('meteoprev','debug','datas = json continue');
				$this->updateCommand();
				return;
			} else {
				log::add('meteoprev','debug','datas!= json');
				file_put_contents($file, $homepage,true);
			}
		}
		log::add('meteoprev','debug','Json file : ' . dirname(__FILE__) . '/../../data/' . $this->getConfiguration('station') .'.json');
		log::add('meteoprev','debug','End function savedata');
		$this->updateCommand();

		$json = json_decode(file_get_contents($file),true);
		$arrays =array('fcst_day_0','fcst_day_1','fcst_day_2','fcst_day_3','fcst_day_4');
		$_infos = array();
		$i=0;
		foreach ($arrays as $array) {
			$datas = $json[$array];
			$rain = 0;
			foreach ($datas as $key => $value) {
				if ($key == 'date') {

					$date = $value;
					$_infos[$array]['date'] = $date;
				} elseif (($key != 'hourly_data')) {
					$_infos[$array][$key] = $value;
				} else {
					$infos = $json[$array]['hourly_data'];
					$_datas = array();
					foreach ($infos as $keys => $values) {
						$dtime = DateTime::createFromFormat("d.m.Y G:i", $date . ' '  . str_replace('H', ':', $keys));
						$timestamp = $dtime->getTimestamp();
						$_value = array();
						$info_hour = $json[$array]['hourly_data'][$keys];
						foreach ($info_hour as $key1 => $value2) {
							if ($key1 == "WNDSPD10m") {
								$var = self::windName($value2);
								$_value['Bf'] = $var[0];
								$_value['windname'] = $var[1];
							}
							if ($key1 == "APCPsfc") {
								$rain = $rain + (float)$value2;
							}
							$_value[$key1] = $value2;
						}								
						array_push($_datas,array('timestamp' => $timestamp,'value' => $_value));
					}
					$_infos[$array]['data'] = $_datas;
				}
			}
			log::add('meteoprev','debug','rain ' . $i . '  ' . $rain);
			$this->checkAndUpdateCmd('rain_' .$i, $rain);					
			$i++;
		}
		$file =  dirname(__FILE__) . '/../../data/' . $this->getConfiguration('station') .'_days.json';
		file_put_contents($file, json_encode($_infos));	
		$this->refreshWidget();
	}
	
	static function launchRefresh($_options) {
		log::add('meteoprev', 'debug', "launchRefresh(" . json_encode($_options) . ")");
		$meteoprev = eqLogic::byId($_options['id']);
		$meteoprev->refreshWidget();
	}
	
	public static function windName($vitesse) {
		
		$windnames = array('Calme', 'Très légère brise', 'Légère brise', 'Petite brise', 'Jolie brise',
				'Bonne brise', 'Vent frais', 'Grand vent frais', 'Coup de vent', 'Fort coup de vent', 'Tempête',
				'Violente tempête', 'Ouragan');	
					
		if ($vitesse < 1) {
			$Bf = 0;
			$windname = $windnames[0];
		} elseif ($vitesse >= 1 && $vitesse <= 5) {
			$Bf = 1;
			$windname = $windnames[1];
		} elseif ($vitesse >= 6 && $vitesse <= 11) {
			$Bf = 2;
			$windname = $windnames[2];
		} elseif ($vitesse >= 12 && $vitesse <= 19) {
			$Bf = 3;
			$windname = $windnames[3];
		} elseif ($vitesse >= 20 && $vitesse <= 28) {
			$Bf = 4;
			$windname = $windnames[4];
		} elseif ($vitesse >= 29 && $vitesse <= 38) {
			$Bf = 5;
			$windname = $windnames[5];
		} elseif ($vitesse >= 39 && $vitesse <= 49) {
			$Bf = 6;
			$windname = $windnames[6];
		} elseif ($vitesse >= 50 && $vitesse <= 61) {
			$Bf = 7;
			$windname = $windnames[7];
		} elseif ($vitesse >= 62 && $vitesse <= 74) {
			$Bf = 8;
			$windname = $windnames[8];
		} elseif ($vitesse >= 75 && $vitesse <= 88) {
			$Bf = 9;
			$windname = $windnames[9];
		} elseif ($vitesse >= 89 && $vitesse <= 102) {
			$Bf = 10;
			$windname = $windnames[10];
		} elseif ($vitesse >= 103 && $vitesse <= 117) {
			$Bf = 11;
			$windname = $windnames[11];
		} elseif ($vitesse > 117 ) {
			$Bf = 12;
			$windname = $windnames[12];
		}
		return array($Bf,$windname);	
	}
	
	public function updateCommand() {
		$now = new DateTime();
		$now = $now->format('H');
		$file = dirname(__FILE__) . '/../../data/' . $this->getConfiguration('station') .'.json';
		$json = json_decode(file_get_contents($file),true);
		$datas = $json['city_info'];
		foreach ($datas as $key => $value) {
				$this->checkAndUpdateCmd('city_info_' .$key, $value);
		}
		$datas = $json['current_condition'];
		foreach ($datas as $key => $value) {
			$this->checkAndUpdateCmd('current_condition_' .$key, $value);
		}
		$arrays =array('fcst_day_0','fcst_day_1','fcst_day_2','fcst_day_3','fcst_day_4');
		foreach ($arrays as $array) {
			$datas = $json[$array];
			foreach ($datas as $key => $value) {
				if ($key != 'hourly_data') {
					$this->checkAndUpdateCmd($array . '_' .$key, $value);
				} else {
					if($array == 'fcst_day_0') {
						$infos = $json['fcst_day_0']['hourly_data'];
						foreach ($infos as $keys => $values) {
							$var = explode("H", $keys);
							if ( $var[0] == $now) {
								$info_hour = $json[$array]['hourly_data'][$keys];
								foreach ($info_hour as $key1 => $value2) {
									$this->checkAndUpdateCmd('fcst_day_0_' .$key1, $value2);
								}
							}
						}						
					}
				}
			}
		}	
	}
	
	
	public function addCommand() {	
		
		$file = 'https://www.prevision-meteo.ch/services/json/' . $this->getConfiguration('station');
			
		$json = json_decode(file_get_contents($file),true);
		$datas = $json['city_info'];
		foreach ($datas as $key => $value) {
			$meteoprevCmd = $this->getCmd(null, 'city_info_' .$key);
			if (!is_object($meteoprevCmd)) {
				$meteoprevCmd = new meteoprevCmd();
			}
			$meteoprevCmd->setName(__('city_info_' .$key , __FILE__));
			$meteoprevCmd->setLogicalId('city_info_' .$key);
			$meteoprevCmd->setEqLogic_id($this->getId());
			$meteoprevCmd->setType('info');
			$meteoprevCmd->setSubType('string');
			$meteoprevCmd->save();				
		}
		$datas = $json['current_condition'];
		foreach ($datas as $key => $value) {
			$meteoprevCmd = $this->getCmd(null, 'current_condition_' .$key);
			if (!is_object($meteoprevCmd)) {
				$meteoprevCmd = new meteoprevCmd();
			}
			$meteoprevCmd->setName(__('current_condition_' .$key , __FILE__));
			$meteoprevCmd->setLogicalId('current_condition_' .$key);
			$meteoprevCmd->setEqLogic_id($this->getId());
			$meteoprevCmd->setType('info');
			switch ($key) {
				case 'tmp': $meteoprevCmd->setUnite('°C');$meteoprevCmd->setSubType('numeric'); break;
				case 'wnd_spd': $meteoprevCmd->setUnite('km/h');$meteoprevCmd->setSubType('numeric');break;
				case 'wnd_gust': $meteoprevCmd->setUnite('km/h');$meteoprevCmd->setSubType('numeric');break;
				case 'pressure': $meteoprevCmd->setUnite('ppm ');$meteoprevCmd->setSubType('numeric');break;
				case 'humidity': $meteoprevCmd->setUnite('%');$meteoprevCmd->setSubType('numeric');break;
				default: $meteoprevCmd->setSubType('string'); break;
			}
			$meteoprevCmd->save();				
		}		
		
		
		$arrays =array('fcst_day_0','fcst_day_1','fcst_day_2','fcst_day_3','fcst_day_4');
		foreach ($arrays as $array) {
			$datas = $json[$array];
			foreach ($datas as $key => $value) {
				if ($key != 'hourly_data') {
					$meteoprevCmd = $this->getCmd(null, $array . '_' .$key);
					if (!is_object($meteoprevCmd)) {
						$meteoprevCmd = new meteoprevCmd();
					}
					$meteoprevCmd->setName(__( $array . '_' .$key , __FILE__));
					$meteoprevCmd->setLogicalId( $array . '_' .$key);
					$meteoprevCmd->setEqLogic_id($this->getId());
					$meteoprevCmd->setType('info');
					switch ($key) {
						case 'tmin':
						case 'tmax': 
							$meteoprevCmd->setUnite('°C');$meteoprevCmd->setSubType('numeric');break;
						default: $meteoprevCmd->setSubType('string'); break;
					}					
					$meteoprevCmd->save();
				} else {
					if($array == 'fcst_day_0') {
						$datas = $json[$array]['hourly_data']['0H00'];
						foreach ($datas as $keys => $values) {
							
								$meteoprevCmd = $this->getCmd(null,  $array . '_' .$keys);
								if (!is_object($meteoprevCmd)) {
									$meteoprevCmd = new meteoprevCmd();
								}
								$meteoprevCmd->setName(__( $array . '_' .$keys , __FILE__));
								$meteoprevCmd->setLogicalId( $array . '_' .$keys);
								$meteoprevCmd->setEqLogic_id($this->getId());
								$meteoprevCmd->setType('info');
								switch ($keys) {
									case 'TMP2m':
									case 'DPT2m': 
									case 'WNDCHILL2m':
										$meteoprevCmd->setUnite('°C');$meteoprevCmd->setSubType('numeric');break;
									case 'RH2m': $meteoprevCmd->setUnite('%');$meteoprevCmd->setSubType('numeric');break;
									case 'PRMSL': $meteoprevCmd->setUnite('Hpa');$meteoprevCmd->setSubType('numeric');break; 
									case 'APCPsfc': $meteoprevCmd->setUnite('mm');$meteoprevCmd->setSubType('numeric');break; 
									case 'WNDSPD10m':
									case 'WNDGUST10m': 
										$meteoprevCmd->setUnite('Km/h');$meteoprevCmd->setSubType('numeric');break; 
									case 'WNDDIR10m': $meteoprevCmd->setUnite('°');$meteoprevCmd->setSubType('numeric');break; 
									case 'HGT0C': $meteoprevCmd->setUnite('m');$meteoprevCmd->setSubType('numeric');break;  
									case 'HCDC':
									case 'MCDC':
									case 'LCDC':
									case 'KINDEX':
									case 'CAPE180_0':
									case 'CIN180_0':
									case 'ISSNOW':
										$meteoprevCmd->setSubType('numeric');break;
									default: $meteoprevCmd->setSubType('string'); break;
								}						
								
								$meteoprevCmd->save();
						}
					}				
				}
				
			}
		}
	}


    /*     * *********************Méthodes d'instance************************* */

    public function preInsert() {
        
    }

    public function postInsert() {
        
    }

    public function preSave() {
		if($this->getConfiguration('refreshCron') == '') {
			$this->setConfiguration('refreshCron',rand(1,9) . ' * * * *');
		}
    }

    public function postSave() {
        
    }

    public function preUpdate() {
        
    }

    public function postUpdate() {
		$listener = listener::byClassAndFunction('meteoprev', 'launchRefresh', array('id' => intval($this->getId())));
		if($this->getIsEnable() == 1 ) {
			$this->addCommand();
			$arrays = array(0,1,2,3,4);
			foreach ($arrays as $array) {
				$meteoprevCmd = $this->getCmd(null,  'rain_' .$array);
				if (!is_object($meteoprevCmd)) {
					$meteoprevCmd = new meteoprevCmd();
				}
				$meteoprevCmd->setName(__( 'rain_' .$array , __FILE__));
				$meteoprevCmd->setLogicalId('rain_' .$array);
				$meteoprevCmd->setEqLogic_id($this->getId());
				$meteoprevCmd->setType('info');	
				$meteoprevCmd->setSubType('numeric');
				$meteoprevCmd->setUnite('mm');
				$meteoprevCmd->save();
						
				
			}
			if (!is_object($listener)) {
				$listener = new listener();
			}
			$listener->setClass('meteoprev');
			$listener->setFunction('launchRefresh');
			$listener->setOption(array('id' => intval($this->getId())));
			$listener->emptyEvent();
			$cmds = array("temperature","humidite","pression","vent","pluieInst","pluieTot");
			foreach ($cmds as $cmd) {
				if($this->getConfiguration($cmd) != "") {
					$cmd_id = str_replace('#', '', $this->getConfiguration($cmd));
					if ($cmd_id) {
						$listener->addEvent(intval($cmd_id));
					}					
				}
			}

			$listener->save();			
			$this->setDataStation();
						
		} else {
			if (is_object($listener)) {
				$listener->remove();
			}
		}
    }

    public function preRemove() {
		$listener = listener::byClassAndFunction('meteoprev', 'launchRefresh', array('id' => intval($this->getId())));
		if (is_object($listener)) {
			$listener->remove();
		}		
        
    }

    public function postRemove() {
        
    }

    /*
     * Non obligatoire mais permet de modifier l'affichage du widget si vous en avez besoin*/
	 
      public function toHtml($_version = 'dashboard') {
		  log::add('meteoprev','debug',' To html' );
			$replace = $this->preToHtml($_version);
			if (!is_array($replace)) {
				return $replace;
			}
			$version = jeedom::versionAlias($_version);
					  
		  if ($this->getConfiguration("widgetCustom") == 0) {
			$cmds = $this->getCmd();
			$replace['#condition#'] = "";
			foreach ($cmds as $cmd) {
				if( $cmd->getLogicalId() == "current_condition_tmp"){
					$replace['#' . $cmd->getLogicalId() . '#'] = $cmd->execCmd();
					$humidex = $this->getCmd(null,  'fcst_day_0_WNDCHILL2m');
					if (!is_object($humidex)) {
						continue;
					}
					$value = $humidex->execCmd();
					
					if($cmd->execCmd() > 20) {
						$replace['#condition#'] = '<div class="meteo-content" style="margin-top:5px;"><span>Humidex : ' . $value .'</span></div>';
					} elseif ($cmd->execCmd() <= 10) {
						$replace['#condition#'] = '<div class="meteo-content" style="margin-top:5px;"><span><i class="fas fa-thermometer-three-quarters"></i> ressentie ' . $value . ' °C</span></div>';						
					}
					
				} else {
					$replace['#' . $cmd->getLogicalId() . '#'] = $cmd->execCmd();
				}
			}
			return template_replace($replace, getTemplate('core', $version, 'eqLogic', 'meteoprev'));
		  }
		 else {
			 
			$cmds = $this->getCmd();
			foreach ($cmds as $cmd) {
				$replace['#' . $cmd->getLogicalId() . '#'] = $cmd->execCmd();
			}
			 $infos = array ("temperature" => "fcst_day_0_TMP2m","humidite" => "fcst_day_0_RH2m","pression" => "fcst_day_0_PRMSL","vent" => "fcst_day_0_WNDSPD10m","pluieInst" => "fcst_day_0_APCPsfc","pluieTot" => "rain_0");
			 
			 foreach ($infos as $key => $value) {
				log::add('meteoprev','debug','key ' . $key . ' value ' .$value );
				if($this->getConfiguration($key) != "") {
					log::add('meteoprev','debug','custom ');
					$cmdId = str_replace('#', '', $this->getConfiguration($key));
					$cmd = cmd::byId($cmdId);
					if (is_object($cmd)) {
						log::add('meteoprev','debug','cmd ' . $value);
						if($key == "vent") {
							 log::add('meteoprev','debug','vent ' . $cmd->execCmd());
							 
							 $return = meteoprev::windName($cmd->execCmd());
							 $replace['#nameWind#'] = $return[1];
							
						}
						$replace['#' . $value . '#'] = $cmd->execCmd();
					}			
				} else {
					log::add('meteoprev','debug','no custom ');
					$meteoprevCmd = $this->getCmd(null,  $value);
					if (is_object($meteoprevCmd)) {
						if($key == "vent") {
							 log::add('meteoprev','debug','vent ' . $meteoprevCmd->execCmd());
							 
							 $return = meteoprev::windName($meteoprevCmd->execCmd());
							 $replace['#nameWind#'] = $return[1];
							
						}						
						$replace['#' . $value . '#'] = $meteoprevCmd->execCmd();
					}
				}
			 }
			 
			return template_replace($replace, getTemplate('core', $version, 'custom', 'meteoprev'));
				
		 }
      }
     

    /*
     * Non obligatoire mais ca permet de déclancher une action après modification de variable de configuration
    public static function postConfig_<Variable>() {
    }
     */

    /*
     * Non obligatoire mais ca permet de déclancher une action avant modification de variable de configuration
    public static function preConfig_<Variable>() {
    }
     */

    /*     * **********************Getteur Setteur*************************** */
}

class meteoprevCmd extends cmd {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*
     * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS

     */
	public function dontRemoveCmd() {
		return true;
	}
	
	public function execute($_options = array()) {
	  
	}

    /*     * **********************Getteur Setteur*************************** */
}


