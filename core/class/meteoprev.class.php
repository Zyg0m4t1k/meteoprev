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
require_once __DIR__ . '/../../../../core/php/core.inc.php';

class meteoprev extends eqLogic {
	/*     * *************************Attributs****************************** */
	public static $_widgetPossibility = array( 'custom' => true );

	public static function cron() {
		foreach ( self::byType( 'meteoprev', true) as $station ) {
			$autorefresh = $station->getConfiguration( 'refreshCron' );
			if ( $autorefresh != '' ) {
				try {
					$c = new Cron\CronExpression( $autorefresh, new Cron\FieldFactory );
					if ( $c->isDue() ) {
						log::add( 'meteoprev', 'debug', 'is dure' );
						try {
							$station->setDataStation();
						} catch ( Exception $exc ) {
							log::add( 'meteoprev', 'error', __( 'Erreur pour ', __FILE__ ) . $station->getHumanName() . ' : ' . $exc->getMessage() );
						}
					}
				} catch ( Exception $exc ) {
					log::add( 'meteoprev', 'error', __( 'Expression cron non valide pour ', __FILE__ ) . $station->getHumanName() . ' : ' . $autorefresh );
				}
			}
		}
	}

	public function setDataStation() {
		$station = $this->getConfiguration( 'station' );
		$path = __DIR__ . '/../../data';
		$file = $path . '/' . $station . '.json';
		$lockFile = $path . '/' . $station . '.lock';
		$minInterval = 60;

		// Verrou anti-spam : cooldown minimum entre deux actualisations
		if ( !is_dir( $path ) ) {
			log::add( 'meteoprev', 'debug', 'Creation du dossier data' );
			mkdir( $path, 0775, true );
		}
		if ( file_exists( $lockFile ) ) {
			$lastRun = (int)file_get_contents( $lockFile );
			if ( ( time() - $lastRun ) < $minInterval ) {
				log::add( 'meteoprev', 'debug', 'Cooldown actif (' . $minInterval . 's), actualisation ignorée' );
				return;
			}
		}
		file_put_contents( $lockFile, time() );

		// Appel API unique avec timeout
		$ctx = stream_context_create( array( 'http' => array( 'timeout' => 10 ) ) );
		$homepage = @file_get_contents( 'https://www.prevision-meteo.ch/services/json/' . $station, false, $ctx );
		if ( $homepage === false ) {
			log::add( 'meteoprev', 'error', 'Impossible de récupérer les données depuis prevision-meteo.ch pour ' . $station );
			// Fallback : conserver les dernières données valides
			if ( file_exists( $file ) ) {
				log::add( 'meteoprev', 'warning', 'Utilisation des dernières données en cache pour ' . $station );
				$this->updateCommand();
			}
			return;
		}
		$datas = json_decode( $homepage, true );
		if ( $datas === null ) {
			log::add( 'meteoprev', 'error', 'Réponse JSON invalide depuis prevision-meteo.ch pour ' . $station );
			if ( file_exists( $file ) ) {
				log::add( 'meteoprev', 'warning', 'Utilisation des dernières données en cache pour ' . $station );
				$this->updateCommand();
			}
			return;
		}
		$this->normalizeWeatherData( $datas );

		// Cache des icônes localement
		$this->cacheIcons( $datas );

		// Comparaison avec le cache existant
		if ( file_exists( $file ) ) {
			$results = json_decode( file_get_contents( $file ), true );
			if ( $datas === $results ) {
				log::add( 'meteoprev', 'debug', 'Données identiques au cache, mise à jour des commandes uniquement' );
				$this->addCommand( $datas );
				$this->updateCommand();
				return;
			}
		}

		// Sauvegarder les nouvelles données
		file_put_contents( $file, json_encode( $datas ) );
		log::add( 'meteoprev', 'debug', 'Données sauvegardées : ' . $file );

		// Créer/mettre à jour les commandes à partir des données fraîches
		$this->addCommand( $datas );
		$this->updateCommand();

		// Construire le fichier _days.json pour les graphiques et prévisions
		$arrays = array( 'fcst_day_0', 'fcst_day_1', 'fcst_day_2', 'fcst_day_3', 'fcst_day_4' );
		$_infos = array();
		$i = 0;
		foreach ( $arrays as $array ) {
			if ( !isset( $datas[ $array ] ) ) {
				$i++;
				continue;
			}
			$dayData = $datas[ $array ];
			$rain = 0;
			foreach ( $dayData as $key => $value ) {
				if ( $key == 'date' ) {
					$date = $value;
					$_infos[ $array ][ 'date' ] = $date;
				} elseif ( $key != 'hourly_data' ) {
					$_infos[ $array ][ $key ] = $value;
				} else {
					$_datas = array();
					foreach ( $value as $keys => $values ) {
						$dtime = DateTime::createFromFormat( "d.m.Y G:i", $date . ' ' . str_replace( 'H', ':', $keys ) );
						if ( !is_object( $dtime ) ) {
							continue;
						}
						$timestamp = $dtime->getTimestamp();
						$_value = array();
						foreach ( $values as $key1 => $value2 ) {
							if ( $key1 == "WNDSPD10m" ) {
								$var = self::windName( $value2 );
								$_value[ 'Bf' ] = $var[ 0 ];
								$_value[ 'windname' ] = $var[ 1 ];
							}
							if ( $key1 == "APCPsfc" ) {
								$rain = $rain + (float)$value2;
							}
							$_value[ $key1 ] = $value2;
						}
						array_push( $_datas, array( 'timestamp' => $timestamp, 'value' => $_value ) );
					}
					$_infos[ $array ][ 'data' ] = $_datas;
				}
			}
			log::add( 'meteoprev', 'debug', 'rain ' . $i . '  ' . $rain );
			$this->checkAndUpdateCmd( 'rain_' . $i, $rain );
			$i++;
		}
		$daysFile = $path . '/' . $station . '_days.json';
		file_put_contents( $daysFile, json_encode( $_infos ) );
		$this->refreshWidget();
	}

	private function normalizeWeatherData( &$json ) {
		for ( $i = 0; $i <= 4; $i++ ) {
			$day = 'fcst_day_' . $i;
			if ( !isset( $json[ $day ][ 'hourly_data' ] ) || !is_array( $json[ $day ][ 'hourly_data' ] ) ) {
				continue;
			}
			foreach ( $json[ $day ][ 'hourly_data' ] as &$hour ) {
				$temperature = isset( $hour[ 'TMP2m' ] ) ? (float)$hour[ 'TMP2m' ] : null;
				$humidity = isset( $hour[ 'RH2m' ] ) ? (float)$hour[ 'RH2m' ] : null;
				$dewPoint = isset( $hour[ 'DPT2m' ] ) ? (float)$hour[ 'DPT2m' ] : null;
				if ( $temperature === null || $humidity === null || $humidity <= 0 || $humidity > 100 ) {
					continue;
				}
				if ( $dewPoint === null || $dewPoint < -100 || $dewPoint > $temperature ) {
					$a = 17.62;
					$b = 243.12;
					$gamma = log( $humidity / 100 ) + ( $a * $temperature ) / ( $b + $temperature );
					$hour[ 'DPT2m' ] = round( ( $b * $gamma ) / ( $a - $gamma ), 1 );
				}
			}
			unset( $hour );
		}
	}

	private function cacheIcons( &$json ) {
		$iconDir = __DIR__ . '/../../data/icons';
		if ( !is_dir( $iconDir ) ) {
			mkdir( $iconDir, 0775, true );
		}
		$baseUrl = 'https://www.prevision-meteo.ch/style/images/icon/';
		$localBase = 'plugins/meteoprev/data/icons/';

		// Collecter toutes les condition_key uniques
		$keys = array();
		if ( isset( $json['current_condition']['condition_key'] ) ) {
			$keys[] = $json['current_condition']['condition_key'];
		}
		for ( $i = 0; $i <= 4; $i++ ) {
			$day = 'fcst_day_' . $i;
			if ( isset( $json[ $day ]['condition_key'] ) ) {
				$keys[] = $json[ $day ]['condition_key'];
			}
			if ( isset( $json[ $day ]['hourly_data'] ) ) {
				foreach ( $json[ $day ]['hourly_data'] as $hour ) {
					if ( isset( $hour['CONDITION_KEY'] ) ) {
						$keys[] = $hour['CONDITION_KEY'];
					}
				}
			}
		}
		$keys = array_unique( $keys );

		// Télécharger chaque icône manquante (petit + big)
		$ctx = stream_context_create( array( 'http' => array( 'timeout' => 5 ) ) );
		foreach ( $keys as $key ) {
			foreach ( array( $key . '.png', $key . '-big.png' ) as $filename ) {
				$localPath = $iconDir . '/' . $filename;
				if ( !file_exists( $localPath ) ) {
					$iconData = @file_get_contents( $baseUrl . $filename, false, $ctx );
					if ( $iconData !== false ) {
						file_put_contents( $localPath, $iconData );
					}
				}
			}
		}

		// Remplacer les URLs distantes par des URLs locales dans le JSON
		if ( isset( $json['current_condition']['icon'] ) ) {
			$json['current_condition']['icon'] = str_replace( $baseUrl, $localBase, $json['current_condition']['icon'] );
		}
		if ( isset( $json['current_condition']['icon_big'] ) ) {
			$json['current_condition']['icon_big'] = str_replace( $baseUrl, $localBase, $json['current_condition']['icon_big'] );
		}
		for ( $i = 0; $i <= 4; $i++ ) {
			$day = 'fcst_day_' . $i;
			if ( isset( $json[ $day ]['icon'] ) ) {
				$json[ $day ]['icon'] = str_replace( $baseUrl, $localBase, $json[ $day ]['icon'] );
			}
			if ( isset( $json[ $day ]['icon_big'] ) ) {
				$json[ $day ]['icon_big'] = str_replace( $baseUrl, $localBase, $json[ $day ]['icon_big'] );
			}
			if ( isset( $json[ $day ]['hourly_data'] ) ) {
				foreach ( $json[ $day ]['hourly_data'] as $hourKey => &$hour ) {
					if ( isset( $hour['ICON'] ) ) {
						$hour['ICON'] = str_replace( $baseUrl, $localBase, $hour['ICON'] );
					}
				}
				unset( $hour );
			}
		}
	}

	public static function launchRefresh( $_options ) {
		log::add( 'meteoprev', 'debug', "launchRefresh(" . json_encode( $_options ) . ")" );
		$meteoprev = eqLogic::byId( $_options[ 'id' ] );
		$meteoprev->refreshWidget();
	}

	public static function windName( $vitesse ) {
		$windnames = array( 'Calme', 'Très légère brise', 'Légère brise', 'Petite brise', 'Jolie brise',
			'Bonne brise', 'Vent frais', 'Grand vent frais', 'Coup de vent', 'Fort coup de vent', 'Tempête',
			'Violente tempête', 'Ouragan' );
		if ( $vitesse < 1 ) {
			$Bf = 0;
			$windname = $windnames[ 0 ];
		} elseif ( $vitesse >= 1 && $vitesse <= 5 ) {
			$Bf = 1;
			$windname = $windnames[ 1 ];
		} elseif ( $vitesse >= 6 && $vitesse <= 11 ) {
			$Bf = 2;
			$windname = $windnames[ 2 ];
		} elseif ( $vitesse >= 12 && $vitesse <= 19 ) {
			$Bf = 3;
			$windname = $windnames[ 3 ];
		} elseif ( $vitesse >= 20 && $vitesse <= 28 ) {
			$Bf = 4;
			$windname = $windnames[ 4 ];
		} elseif ( $vitesse >= 29 && $vitesse <= 38 ) {
			$Bf = 5;
			$windname = $windnames[ 5 ];
		} elseif ( $vitesse >= 39 && $vitesse <= 49 ) {
			$Bf = 6;
			$windname = $windnames[ 6 ];
		} elseif ( $vitesse >= 50 && $vitesse <= 61 ) {
			$Bf = 7;
			$windname = $windnames[ 7 ];
		} elseif ( $vitesse >= 62 && $vitesse <= 74 ) {
			$Bf = 8;
			$windname = $windnames[ 8 ];
		} elseif ( $vitesse >= 75 && $vitesse <= 88 ) {
			$Bf = 9;
			$windname = $windnames[ 9 ];
		} elseif ( $vitesse >= 89 && $vitesse <= 102 ) {
			$Bf = 10;
			$windname = $windnames[ 10 ];
		} elseif ( $vitesse >= 103 && $vitesse <= 117 ) {
			$Bf = 11;
			$windname = $windnames[ 11 ];
		} elseif ( $vitesse > 117 ) {
			$Bf = 12;
			$windname = $windnames[ 12 ];
		}
		return array( $Bf, $windname );
	}

	public function updateCommand() {
		$now = new DateTime();
		$now = $now->format( 'H' );
		$file = __DIR__ . '/../../data/' . $this->getConfiguration( 'station' ) . '.json';
		$json = json_decode( file_get_contents( $file ), true );
		$datas = $json[ 'city_info' ];
		foreach ( $datas as $key => $value ) {
			$this->checkAndUpdateCmd( 'city_info_' . $key, $value );
		}
		$datas = $json[ 'current_condition' ];
		foreach ( $datas as $key => $value ) {
			$this->checkAndUpdateCmd( 'current_condition_' . $key, $value );
		}
		$arrays = array( 'fcst_day_0', 'fcst_day_1', 'fcst_day_2', 'fcst_day_3', 'fcst_day_4' );
		foreach ( $arrays as $array ) {
			$datas = $json[ $array ];
			foreach ( $datas as $key => $value ) {
				if ( $key != 'hourly_data' ) {
					$this->checkAndUpdateCmd( $array . '_' . $key, $value );
				} else {
					if ( $array == 'fcst_day_0' ) {
						$infos = $json[ 'fcst_day_0' ][ 'hourly_data' ];
						foreach ( $infos as $keys => $values ) {
							$var = explode( "H", $keys );
							if ( $var[ 0 ] == $now ) {
								$info_hour = $json[ $array ][ 'hourly_data' ][ $keys ];
								foreach ( $info_hour as $key1 => $value2 ) {
									$this->checkAndUpdateCmd( 'fcst_day_0_' . $key1, $value2 );
								}
							}
						}
					}
				}
			}
		}
		$logicals = array('graph','forecast');
		foreach ( $logicals as $logical ) {
			$this->checkAndUpdateCmd( $logical, strtotime("now") );	
		}
	}

	public function addCommand( $json = null ) {
		if ( $json === null ) {
			$file = __DIR__ . '/../../data/' . $this->getConfiguration( 'station' ) . '.json';
			if ( !file_exists( $file ) ) {
				return;
			}
			$json = json_decode( file_get_contents( $file ), true );
			if ( $json === null ) {
				return;
			}
		}
		$datas = $json[ 'city_info' ];
		foreach ( $datas as $key => $value ) {
			$meteoprevCmd = $this->getCmd( null, 'city_info_' . $key );
			if ( !is_object( $meteoprevCmd ) ) {
				$meteoprevCmd = new meteoprevCmd();
			}
			$meteoprevCmd->setName( __( 'city_info_' . $key, __FILE__ ) );
			$meteoprevCmd->setLogicalId( 'city_info_' . $key );
			$meteoprevCmd->setEqLogic_id( $this->getId() );
			$meteoprevCmd->setType( 'info' );
			$meteoprevCmd->setSubType( 'string' );
			$meteoprevCmd->save();
		}
		$datas = $json[ 'current_condition' ];
		foreach ( $datas as $key => $value ) {
			$meteoprevCmd = $this->getCmd( null, 'current_condition_' . $key );
			if ( !is_object( $meteoprevCmd ) ) {
				$meteoprevCmd = new meteoprevCmd();
			}
			$meteoprevCmd->setName( __( 'current_condition_' . $key, __FILE__ ) );
			$meteoprevCmd->setLogicalId( 'current_condition_' . $key );
			$meteoprevCmd->setEqLogic_id( $this->getId() );
			$meteoprevCmd->setType( 'info' );
			switch ( $key ) {
				case 'tmp':
					$meteoprevCmd->setUnite( '°C' );
					$meteoprevCmd->setSubType( 'numeric' );
					break;
				case 'wnd_spd':
					$meteoprevCmd->setUnite( 'km/h' );
					$meteoprevCmd->setSubType( 'numeric' );
					break;
				case 'wnd_gust':
					$meteoprevCmd->setUnite( 'km/h' );
					$meteoprevCmd->setSubType( 'numeric' );
					break;
				case 'pressure':
					$meteoprevCmd->setUnite( 'ppm ' );
					$meteoprevCmd->setSubType( 'numeric' );
					break;
				case 'humidity':
					$meteoprevCmd->setUnite( '%' );
					$meteoprevCmd->setSubType( 'numeric' );
					break;
				case 'icon':
					$meteoprevCmd->setSubType( 'string' );
					$meteoprevCmd->setConfiguration('type','widget');
					$meteoprevCmd->setTemplate('dashboard','meteoprev::condition');
					break;					
				default:
					$meteoprevCmd->setSubType( 'string' );
					break;
			}
			$meteoprevCmd->save();
		}
		$arrays = array( 'fcst_day_0', 'fcst_day_1', 'fcst_day_2', 'fcst_day_3', 'fcst_day_4' );
		foreach ( $arrays as $array ) {
			$datas = $json[ $array ];
			foreach ( $datas as $key => $value ) {
				if ( $key != 'hourly_data' ) {
					$meteoprevCmd = $this->getCmd( null, $array . '_' . $key );
					if ( !is_object( $meteoprevCmd ) ) {
						$meteoprevCmd = new meteoprevCmd();
					}
					$meteoprevCmd->setName( __( $array . '_' . $key, __FILE__ ) );
					$meteoprevCmd->setLogicalId( $array . '_' . $key );
					$meteoprevCmd->setEqLogic_id( $this->getId() );
					$meteoprevCmd->setType( 'info' );
					switch ( $key ) {
						case 'tmin':
						case 'tmax':
							$meteoprevCmd->setUnite( '°C' );
							$meteoprevCmd->setSubType( 'numeric' );
							break;
						default:
							$meteoprevCmd->setSubType( 'string' );
							break;
					}
					$meteoprevCmd->save();
				} else {
					if ( $array == 'fcst_day_0' ) {
						$datas = $json[ $array ][ 'hourly_data' ][ '0H00' ];
						foreach ( $datas as $keys => $values ) {
							$meteoprevCmd = $this->getCmd( null, $array . '_' . $keys );
							if ( !is_object( $meteoprevCmd ) ) {
								$meteoprevCmd = new meteoprevCmd();
							}
							$meteoprevCmd->setName( __( $array . '_' . $keys, __FILE__ ) );
							$meteoprevCmd->setLogicalId( $array . '_' . $keys );
							$meteoprevCmd->setEqLogic_id( $this->getId() );
							$meteoprevCmd->setType( 'info' );
							switch ( $keys ) {
								case 'TMP2m':
								case 'DPT2m':
								case 'WNDCHILL2m':
									$meteoprevCmd->setUnite( '°C' );
									$meteoprevCmd->setSubType( 'numeric' );
									break;
								case 'RH2m':
									$meteoprevCmd->setUnite( '%' );
									$meteoprevCmd->setSubType( 'numeric' );
									break;
								case 'PRMSL':
									$meteoprevCmd->setUnite( 'Hpa' );
									$meteoprevCmd->setSubType( 'numeric' );
									break;
								case 'APCPsfc':
									$meteoprevCmd->setUnite( 'mm' );
									$meteoprevCmd->setSubType( 'numeric' );
									break;
								case 'WNDSPD10m':
								case 'WNDGUST10m':
									$meteoprevCmd->setUnite( 'Km/h' );
									$meteoprevCmd->setSubType( 'numeric' );
									break;
								case 'WNDDIR10m':
									$meteoprevCmd->setUnite( '°' );
									$meteoprevCmd->setSubType( 'numeric' );
									break;
								case 'HGT0C':
									$meteoprevCmd->setUnite( 'm' );
									$meteoprevCmd->setSubType( 'numeric' );
									break;
								case 'HCDC':
								case 'MCDC':
								case 'LCDC':
								case 'KINDEX':
								case 'CAPE180_0':
								case 'CIN180_0':
								case 'ISSNOW':
									$meteoprevCmd->setSubType( 'numeric' );
									break;
								default:
									$meteoprevCmd->setSubType( 'string' );
									break;
							}
							$meteoprevCmd->save();
						}
					}
				}
			}
		}
	}

	public function preSave() {
		if ( $this->getConfiguration( 'refreshCron' ) == '' ) {
			$this->setConfiguration( 'refreshCron', rand( 1, 9 ) . ' * * * *' );
		}
	}

	public function postUpdate() {
		$refresh = $this->getCmd( null, 'refresh' );
		if ( !is_object( $refresh ) ) {
			$refresh = new meteoprevCmd();
			$refresh->setEqLogic_id( $this->getId() );
			$refresh->setName( 'refresh' );
			$refresh->setLogicalId( 'refresh' );
			$refresh->setType( 'action' );
			$refresh->setSubType( 'other' );
			$refresh->save();
		}
		$forecast = $this->getCmd( null, 'forecast' );
		if ( !is_object( $forecast ) ) {
			$forecast = new meteoprevCmd();
			$forecast->setEqLogic_id( $this->getId() );
			$forecast->setName( 'Forecast' );
		}
		$forecast->setLogicalId( 'forecast' );
		$forecast->setType( 'info' );
		$forecast->setSubType( 'string' );	
		$forecast->setConfiguration('type','widget');
		$forecast->setTemplate('dashboard','meteoprev::forecast');
		$forecast->save();
		
		$graph = $this->getCmd( null, 'graph' );
		if ( !is_object( $graph ) ) {
			$graph = new meteoprevCmd();
			$graph->setEqLogic_id( $this->getId() );
			$graph->setName( 'Graphique' );
		}
		$graph->setLogicalId( 'graph' );
		$graph->setType( 'info' );
		$graph->setSubType( 'string' );	
		$graph->setConfiguration('type','widget');
		$graph->setTemplate('dashboard','meteoprev::graph');
		$graph->save();
		
		$listener = listener::byClassAndFunction( 'meteoprev', 'launchRefresh', array( 'id' => intval( $this->getId() ) ) );
		if ( $this->getIsEnable() == 1 ) {
			$arrays = array( 0, 1, 2, 3, 4 );
			foreach ( $arrays as $array ) {
				$meteoprevCmd = $this->getCmd( null, 'rain_' . $array );
				if ( !is_object( $meteoprevCmd ) ) {
					$meteoprevCmd = new meteoprevCmd();
				}
				$meteoprevCmd->setName( __( 'rain_' . $array, __FILE__ ) );
				$meteoprevCmd->setLogicalId( 'rain_' . $array );
				$meteoprevCmd->setEqLogic_id( $this->getId() );
				$meteoprevCmd->setType( 'info' );
				$meteoprevCmd->setSubType( 'numeric' );
				$meteoprevCmd->setUnite( 'mm' );
				$meteoprevCmd->save();
			}
			if ( !is_object( $listener ) ) {
				$listener = new listener();
			}
			$listener->setClass( 'meteoprev' );
			$listener->setFunction( 'launchRefresh' );
			$listener->setOption( array( 'id' => intval( $this->getId() ) ) );
			$listener->emptyEvent();
			$cmds = array( "temperature", "humidite", "pression", "vent", "pluieInst", "pluieTot" );
			foreach ( $cmds as $cmd ) {
				if ( $this->getConfiguration( $cmd ) != "" ) {
					$cmd_id = str_replace( '#', '', $this->getConfiguration( $cmd ) );
					if ( $cmd_id ) {
						$listener->addEvent( intval( $cmd_id ) );
					}
				}
			}
			$listener->save();
			$this->setDataStation();
		} else {
			if ( is_object( $listener ) ) {
				$listener->remove();
			}
		}
	}

	public function preRemove() {
		$listener = listener::byClassAndFunction( 'meteoprev', 'launchRefresh', array( 'id' => intval( $this->getId() ) ) );
		if ( is_object( $listener ) ) {
			$listener->remove();
		}

	}

	public function toHtml( $_version = 'dashboard' ) {
		$renderStart = microtime( true );
		$defaultWidget = (int)$this->getConfiguration( 'defaultWidget', 0 );
		$customWidget = (int)$this->getConfiguration( 'widgetCustom', 0 );
		$widgetType = $defaultWidget === 1 ? 'jeedom' : ( $customWidget === 1 ? 'custom' : 'basic' );
		log::add( 'meteoprev', 'debug', 'toHtml start - équipement=' . $this->getHumanName()
			. ', version=' . $_version . ', widget=' . $widgetType );
		$replace = $this->preToHtml( $_version );
		if ( !is_array( $replace ) ) {
			log::add( 'meteoprev', 'debug', 'toHtml skipped - équipement=' . $this->getHumanName()
				. ', retour preToHtml non générable' );
			return $replace;
		}
		$version = jeedom::versionAlias( $_version );
		$replace[ '#station#' ] = $this->getConfiguration( "station" );
		if ( $defaultWidget === 1 ) {
			log::add( 'meteoprev', 'debug', 'toHtml end - équipement=' . $this->getHumanName()
				. ', widget=jeedom, durée=' . round( ( microtime( true ) - $renderStart ) * 1000, 1 ) . ' ms' );
			return parent::toHtml( $_version );
		}
		// Le chargeur HTML de Jeedom attend l'événement onload des scripts src.
		// Si cet événement n'arrive pas lors de l'injection dynamique du widget,
		// DOMloading reste positif et le spinner ne disparaît jamais. Un script
		// inline est exécuté immédiatement par le même chargeur.
		static $dashboardScriptIncluded = false;
		if ( !$dashboardScriptIncluded ) {
			$dashboardJs = file_get_contents( __DIR__ . '/../../desktop/js/dashboard.js' );
			$replace[ '#dashboard_script#' ] = '<script>' . str_replace( '</script', '<\/script', $dashboardJs ) . '</script>';
			$dashboardScriptIncluded = true;
		} else {
			$replace[ '#dashboard_script#' ] = '';
		}
		if ( $customWidget === 0 ) {
			$cmds = $this->getCmd();
			log::add( 'meteoprev', 'debug', 'toHtml render - équipement=' . $this->getHumanName()
				. ', widget=basic, commandes=' . count( $cmds ) );
			$replace[ '#condition#' ] = "";
			foreach ( $cmds as $cmd ) {
				// Ne jamais exécuter une commande d'action pendant le rendu du
				// widget. La commande refresh déclenche setDataStation() et faisait
				// attendre le dashboard à chaque appel de toHtml().
				if ( $cmd->getType() != 'info' ) {
					continue;
				}
				if ( $cmd->getLogicalId() == "current_condition_tmp" ) {
					$currentTemperature = $cmd->execCmd();
					$replace[ '#' . $cmd->getLogicalId() . '#' ] = $currentTemperature;
					$humidex = $this->getCmd( null, 'fcst_day_0_WNDCHILL2m' );
					if ( !is_object( $humidex ) ) {
						continue;
					}
					$value = $humidex->execCmd();

					if ( $currentTemperature > 20 ) {
						$replace[ '#condition#' ] = '<div class="meteo-content" style="margin-top:5px;"><span>Humidex : ' . $value . '</span></div>';
					} elseif ( $currentTemperature <= 10 ) {
						$replace[ '#condition#' ] = '<div class="meteo-content" style="margin-top:5px;"><span><i class="fas fa-thermometer-three-quarters"></i> ressentie ' . $value . ' °C</span></div>';
					}

				} else {
					$replace[ '#' . $cmd->getLogicalId() . '#' ] = $cmd->execCmd();
				}
			}
			$html = template_replace( $replace, getTemplate( 'core', $version, 'eqLogic', 'meteoprev' ) );
			log::add( 'meteoprev', 'debug', 'toHtml end - équipement=' . $this->getHumanName()
				. ', widget=basic, durée=' . round( ( microtime( true ) - $renderStart ) * 1000, 1 ) . ' ms' );
			return $html;
		} else {
			$cmds = $this->getCmd();
			log::add( 'meteoprev', 'debug', 'toHtml render - équipement=' . $this->getHumanName()
				. ', widget=custom, commandes=' . count( $cmds ) );
			foreach ( $cmds as $cmd ) {
				if ( $cmd->getType() != 'info' ) {
					continue;
				}
				$replace[ '#' . $cmd->getLogicalId() . '#' ] = $cmd->execCmd();
			}
			$infos = array( "temperature" => "fcst_day_0_TMP2m", "humidite" => "fcst_day_0_RH2m", "pression" => "fcst_day_0_PRMSL", "vent" => "fcst_day_0_WNDSPD10m", "pluieInst" => "fcst_day_0_APCPsfc", "pluieTot" => "rain_0" );
			foreach ( $infos as $key => $value ) {
				log::add( 'meteoprev', 'debug', 'key ' . $key . ' value ' . $value );
				if ( $this->getConfiguration( $key ) != "" ) {
					log::add( 'meteoprev', 'debug', 'custom ' );
					$cmdId = str_replace( '#', '', $this->getConfiguration( $key ) );
					$cmd = cmd::byId( $cmdId );
					if ( is_object( $cmd ) ) {
						log::add( 'meteoprev', 'debug', 'cmd ' . $value );
						if ( $key == "vent" ) {
							log::add( 'meteoprev', 'debug', 'vent ' . $cmd->execCmd() );

							$return = meteoprev::windName( $cmd->execCmd() );
							$replace[ '#nameWind#' ] = $return[ 1 ];

						}
						$replace[ '#' . $value . '#' ] = $cmd->execCmd();
					}
				} else {
					log::add( 'meteoprev', 'debug', 'no custom ' );
					$meteoprevCmd = $this->getCmd( null, $value );
					if ( is_object( $meteoprevCmd ) ) {
						if ( $key == "vent" ) {
							log::add( 'meteoprev', 'debug', 'vent ' . $meteoprevCmd->execCmd() );

							$return = meteoprev::windName( $meteoprevCmd->execCmd() );
							$replace[ '#nameWind#' ] = $return[ 1 ];

						}
						$replace[ '#' . $value . '#' ] = $meteoprevCmd->execCmd();
					}
				}
			}
			$html = template_replace( $replace, getTemplate( 'core', $version, 'custom', 'meteoprev' ) );
			log::add( 'meteoprev', 'debug', 'toHtml end - équipement=' . $this->getHumanName()
				. ', widget=custom, durée=' . round( ( microtime( true ) - $renderStart ) * 1000, 1 ) . ' ms' );
			return $html;
		}
	}
}

class meteoprevCmd extends cmd {
	/*
	* Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS

	*/
	public function dontRemoveCmd() {
		return true;
	}

	public function execute( $_options = array() ) {
		if ( $this->getType() == 'info' ) {
			return;
		}
		$station = $this->getEqlogic();
		switch ( $this->getLogicalId() ) {
			case 'refresh':
				$station->setDataStation();
				break;
		}
	}
}


