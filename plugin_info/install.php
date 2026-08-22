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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

function meteoprev_install() {
    
}

function meteoprev_update() {
	foreach (eqLogic::byType('meteoprev', true) as $eqlogic) {
		try {
			$station = basename($eqlogic->getConfiguration('station'));
			$file = __DIR__ . '/../data/' . $station . '.json';
			if (is_file($file) && !unlink($file)) {
				$warning = 'Impossible de supprimer le fichier météo : ' . $file;
				log::add('meteoprev', 'warning', $warning);
				message::add('meteoprev', $warning, 'update', 'cache-delete-' . $eqlogic->getId());
			}
			$eqlogic->save();
		} catch (Exception $e) {
			log::add('meteoprev', 'error', 'Erreur mise à jour ' . $eqlogic->getHumanName() . ' : ' . $e->getMessage());
		}
	}
}


function meteoprev_remove() {
    
}

?>
