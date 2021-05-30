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

try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');


    ajax::init();

	switch (init('action')) {
//		case 'getDatas':
//			$eqlogic = meteoprev::byId(init('id'));
//			$file =  dirname(__FILE__) . '/../../data/' . $eqlogic->getConfiguration('station') .'_days.json';
//			ajax::success(@file_get_contents($file));
//			break;
		case 'getForecastHtml':
			$id = init('id');
			$eq = eqLogic::byId($id);
			if(!is_object($eq)) {
				ajax::success(array('state' => 'nok'));
			}
			$file = __DIR__ . '/../config/forecast.html';
			$html = file_get_contents($file);
			$file = __DIR__ . '/../../data/' . $eq->getConfiguration('station') . '_days.json';
			$datas = json_decode(file_get_contents($file),true);
			$replace = array();
			foreach ($datas as $key => $values) {
				foreach($values as $k => $v) {
					if(!is_array($v)) {
						$replace[ '#' . $key . '_' . $k . '#' ] = $v;
					}
				}
			}
			ajax::success(array('state' => 'ok' , 'html' => template_replace($replace, $html) ));
			break;
		case 'getStation':
			$id = init('id');
			$eq = eqLogic::byId($id);			
			if(!is_object($eq)) {
				ajax::success(array('state' => 'nok'));
			}
			$file = __DIR__ . '/../config/graph.html';
			$html = file_get_contents($file);
			$file = __DIR__ . '/../../data/' . $eq->getConfiguration('station') . '_days.json';
			$datas = json_decode(file_get_contents($file),true);
			$replace = array();
			foreach ($datas as $key => $values) {
				foreach($values as $k => $v) {
					if(!is_array($v)) {
						$replace[ '#' . $key . '_' . $k . '#' ] = $v;
					}
				}
			}			
			ajax::success(array('state' => 'ok' , 'station' => $eq->getConfiguration('station'), 'html' => template_replace($replace, $html)  ));
			break;			
	}

    throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
    /*     * *********Catch exeption*************** */
} catch (Exception $e) {
    ajax::error(displayExeption($e), $e->getCode());
}
?>
