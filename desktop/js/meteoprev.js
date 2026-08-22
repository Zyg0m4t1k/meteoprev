
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

document.getElementById('bt_cronGenerator').addEventListener('click', function() {
	jeedom.getCronSelectModal({}, function(result) {
		document.querySelector('.eqLogicAttr[data-l1key="configuration"][data-l2key="refreshCron"]').jeeValue(result.value);
	});
});

document.getElementById('div_pageContainer').addEventListener('click', function(event) {
	var _target = null;
	if (_target = event.target.closest('.listCmdInfo')) {
		var el = document.querySelector('.' + _target.dataset.input + ' input');
		if (el) el.value = '';
		jeedom.cmd.getSelectModal({cmd: {type: 'info'}}, function(result) {
			if (el) el.jeeValue(result.human);
		});
		return;
	}
	if (_target = event.target.closest('.checkbox-widget')) {
		if (_target.dataset.l2key == 'widgetCustom' && _target.checked) {
			document.getElementById('custom').seen();
		} else {
			document.getElementById('custom').unseen();
		}
		document.querySelectorAll('.checkbox-widget').forEach(function(cb) {
			if (cb !== _target) cb.checked = false;
		});
		return;
	}
});

function printEqLogic(_eqLogic) {
	if (isset(_eqLogic.configuration)) {
		if (_eqLogic.configuration.widgetCustom == 1) {
			document.getElementById('custom').seen();
		} else {
			document.getElementById('custom').unseen();
		}
	}
}

function addCmdToTable(_cmd) {
	if (!isset(_cmd)) {
		var _cmd = {configuration: {}};
	}
	if (!isset(_cmd.configuration)) {
		_cmd.configuration = {};
	}
	var type = '';
	if (isset(_cmd.configuration.type)) {
		type = _cmd.configuration.type;
	}
	var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
	tr += '<td class="hidden-xs">';
	tr += '<span class="cmdAttr" data-l1key="id"></span>';
	tr += '</td>';
	tr += '<td>';
	tr += '<div class="input-group">';
	tr += '<input class="cmdAttr form-control input-sm roundedLeft" data-l1key="name" placeholder="{{Nom de la commande}}">';
	tr += '<span class="input-group-btn"><a class="cmdAction btn btn-sm btn-default" data-l1key="chooseIcon" title="{{Choisir une icône}}"><i class="fas fa-icons"></i></a></span>';
	tr += '<span class="cmdAttr input-group-addon roundedRight" data-l1key="display" data-l2key="icon" style="font-size:19px;padding:0 5px 0 0!important;"></span>';
	tr += '</div>';
	tr += '<select class="cmdAttr form-control input-sm" data-l1key="value" style="display:none;margin-top:5px;" title="{{Commande info liée}}">';
	tr += '<option value="">{{Aucune}}</option>';
	tr += '</select>';
	tr += '</td>';
	tr += '<td>';
	tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
	tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
	tr += '</td>';
	if (type == '') {
		tr += '<td>';
		tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label> ';
		tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" checked/>{{Historiser}}</label> ';
		tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label> ';
		tr += '<div style="margin-top:7px;">';
		tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">';
		tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">';
		tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="unite" placeholder="Unité" title="{{Unité}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">';
		tr += '</div>';
		tr += '</td>';
		tr += '<td>';
		tr += '<span class="cmdAttr" data-l1key="htmlstate"></span>';
		tr += '</td>';
	}
	tr += '<td>';
	if (is_numeric(_cmd.id)) {
		tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> ';
		tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> {{Tester}}</a>';
	}
	tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove" title="{{Supprimer la commande}}"></i>';
	tr += '</td>';
	tr += '</tr>';
	var tableId = '#table_cmd' + type;
	document.querySelector(tableId + ' tbody').insertAdjacentHTML('beforeend', tr);
	var newTr = document.querySelector(tableId + ' tbody tr:last-child');
	jeedom.eqLogic.buildSelectCmd({
		id: document.querySelector('.eqLogicAttr[data-l1key="id"]').jeeValue(),
		filter: {type: 'info'},
		error: function(error) {
			jeedomUtils.showAlert({message: error.message, level: 'danger'});
		},
		success: function(result) {
			var select = newTr.querySelector('.cmdAttr[data-l1key="value"]');
			if (select) select.insertAdjacentHTML('beforeend', result);
			newTr.setJeeValues(_cmd, '.cmdAttr');
			jeedom.cmd.changeType(newTr, init(_cmd.subType));
		}
	});
}
