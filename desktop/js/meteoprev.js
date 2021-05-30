
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


$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
/*
 * Fonction pour l'ajout de commande, appellé automatiquement par plugin.template
 */
 
$('#bt_cronGenerator').on('click',function(){
    jeedom.getCronSelectModal({},function (result) {
        $('.eqLogicAttr[data-l1key=configuration][data-l2key=refreshCron]').value(result.value);
    });
});

 $(".form-group").delegate(".listCmdInfo", 'click', function () {
    let el = $('.' + $(this).attr('data-input')).find('input');
	el.empty();
    jeedom.cmd.getSelectModal({cmd: {type: 'info'}}, function (result) {
        el.atCaret('insert', result.human);
    });
});


$(".checkbox-widget").on('click ', function () {
	( $(this).attr('data-l2key') == 'widgetCustom' && $(this).value() == 1 ) ? $('#custom').show() : $('#custom').hide();
	$('.checkbox-widget').not(this).each(function(){
		$( this ).prop("checked", false);
	});
});

function printEqLogic(_eqLogic) {
	if (isset(_eqLogic.configuration)) {
		_eqLogic.configuration.widgetCustom == 1 ? $('#custom').show() : $('#custom').hide();	
	}	
}


function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        let _cmd = {configuration: {}};
    }
    if (!isset(_cmd.configuration)) {
        _cmd.configuration = {};
    }
	let type = '';
    if (isset(_cmd.configuration.type)) {
       type = _cmd.configuration.type;
    }	
    let tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    tr += '<td>';
    tr += '<span class="cmdAttr" data-l1key="id" style="display:none;"></span>';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" style="width : 200px;" placeholder="{{Nom}}">';
    tr += '</td>';
    tr += '<td>';
    tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
    tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
    tr += '</td>';
	if(type == '') {
		tr += '<td>';
		if (isset(_cmd.unite) && _cmd.unite != '') {
			tr += '<input class="cmdAttr form-control input-sm" data-l1key="unite" placeholder="Unité" title="{{Unité}}" style="width:10%;display:inline-block;margin-left:2px;margin-right:5px;">';
		}
		tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" checked/>{{Afficher}}</label></span> ';
		tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized" checked/>{{Historiser}}</label></span> ';
		tr += '</td>';		
	}
	tr += '<td>';
    if (is_numeric(_cmd.id)) {
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> ';
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> {{Tester}}</a>';
    }
    tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i>';
    tr += '</td>';
    tr += '</tr>';
    $('#table_cmd' + type + ' tbody').append(tr);
    $('#table_cmd' + type + ' tbody tr:last').setValues(_cmd, '.cmdAttr');
    if (isset(_cmd.type)) {
        $('#table_cmd' + type + ' tbody tr:last .cmdAttr[data-l1key=type]').value(init(_cmd.type));
    }
    jeedom.cmd.changeType($('#table_cmd' + type + ' tbody tr:last'), init(_cmd.subType));
}
