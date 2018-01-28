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

if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}

if (init('id') == '') {
    throw new Exception('{{L\'id de l\'opération ne peut etre vide : }}' . init('op_id'));
}

$eqLogic = eqLogic::byId(init('id'));
if (!is_object($eqLogic)) { 
 throw new Exception('{{Aucun équipement associé à l\'id : }}' . init(init('id')));
 }
 
$filename =  $eqLogic->getConfiguration('station');

sendVarToJS('filename',  $filename);
sendVarToJS('name',  $eqLogic->getName());

?>
<div id="container"></div>
<script>
// http://jsfiddle.net/gh/get/library/pure/highcharts/highcharts/tree/master/samples/highcharts/demo/combo-meteogram/

var hour = new Date().getHours() + '' +   new Date().getDay() ,
 	wHeight = $(window).height(),
	dHeight = wHeight * 0.7
	
//	$.ajax({// fonction permettant de faire de l'ajax
//		type: "POST", // methode de transmission des données au fichier php
//		url: "plugins/meteoprev/core/ajax/meteoprev.ajax.php", // url du fichier php
//		data: {
//			action: "getDatas",
//			id: _id
//		},
//		dataType: 'json',
//		error: function(request, status, error) {
//			handleAjaxError(request, status, error);
//		},
//		success: function(data) { // si l'appel a bien fonctionné
//			if (data.state != 'ok') {
//				$('#div_alert').showAlert({message:  data.result,level: 'danger'});
//				return;
//			}
//			createChart(data.result)
//		}
//	});	
	
$.getJSON('/plugins/meteoprev/data/' + filename + '_days.json', function(json) {
    var	temperatures = [],
		precipitations = [],
		conv_precipitations = [],
		icon = [];
		pressures = [],
		dtemperatures = [],
		windDirections = [],
		windDirectionNames = [],
		windSpeeds = [],
		windSpeedNames = [],
		time_stamp = [],
		conditions = [];
		
		
    $.each(json, function (j, datas) {

		$.each(datas.data, function(i, value) {	
			if ((new Date(value.timestamp*1000).getHours() + '' +   new Date(value.timestamp*1000).getDay() )  == hour) {
				time_stamp = value.timestamp*1000;
				
			}		
			temperatures.push({
				x: value.timestamp*1000,
				y: value.value.TMP2m
			})
			
			precipitations.push([value.timestamp*1000,value.value.APCPsfc]);
			//conv_precipitations.push([value.timestamp*1000,value.value.ACPCP_0]);
			pressures.push([value.timestamp*1000,value.value.PRMSL]);
			dtemperatures.push([value.timestamp*1000,value.value.WNDCHILL2m]);
			icon.push(value.value.ICON);

			windDirections.push(value.value.WNDDIR10m);
			windDirectionNames.push(value.value.WNDDIRCARD10);
			windSpeeds.push(value.value.WNDSPD10m);
			windSpeedNames.push(value.value.windname);
			conditions.push(value.value.CONDITION);
		})
    });
	
	if(typeof Highcharts !== 'undefined'){
		Highcharts.setOptions({
			lang: {
				months: ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',  'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'],
				shortMonths: ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juill.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'],
				weekdays: ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']
			},
			global : {
				useUTC : false
			}			
		});
	}	
	

	function tooltipFormatter (tooltip) {
		var index = tooltip.points[0].point.index,
			ret = '<small>' + Highcharts.dateFormat('%A %e %b %H:%M', tooltip.x) + '</small><br>';
		// Symbol text
		ret += '<b>' + this.conditions[index] + '</b>';
		ret += '<table>';
		
		// Add all series
		Highcharts.each(tooltip.points, function (point) {
			var series = point.series;
			ret += '<tr><td><span style="color:' + series.color + '">\u25CF</span> ' + series.name +
				': </td><td style="white-space:nowrap">' + Highcharts.pick(point.point.value, point.y) +
				series.options.tooltip.valueSuffix + '</td></tr>';
		});
		
		// Add wind
		ret += '<tr><td style="vertical-align: top">\u25CF Vent</td><td style="white-space:nowrap">' + this.windDirectionNames[index] +
			'<br>' + this.windSpeedNames[index] + ' (' +
			Highcharts.numberFormat(this.windSpeeds[index], 1) + ' km/h)</td></tr>';
		
		// Close
		ret += '</table>';
		
		
		return ret;	
	}
	
	var options = {
        chart: {
            renderTo: 'container',
            height: dHeight
        },
        title: {
            //text: '<strong>Prévisions pour '+city+', '+country+', '+elevation+' mètres</strong>',
			text: name,
			useHTML: true,
            align: 'center'
        },
        credits: {
            text: '<a href="https://www.prevision-meteo.ch">prevision-meteo.ch</a>',
			align: 'right',
            position: {
                x: -10
            }
        },
        tooltip: {
            shared: true,
            useHTML: true,
            formatter: function () {
                return tooltipFormatter(this);
            }			
			
        },
        xAxis: [{ // Bottom X axis
            type: 'datetime',
            tickInterval: 4 * 36e5, // two hours
            minorTickInterval: 2 * 36e5, // one hour
           // tickLength: 0,
           gridLineWidth: 0,
           minorGridLineWidth: 0,		   

           // gridLineColor: (Highcharts.theme && Highcharts.theme.background2) || '#F0F0F0',
            showLastLabel: true,
            labels: {
                format: '{value:%Hh}'
            },
			plotLines: [{
				color: 'blue',
				width: 2,
				value: time_stamp
			}]			
			
        }, { // Top X axis
            linkedTo: 0,
            type: 'datetime',
            tickInterval: 24 * 3600 * 1000 - 3600000,
            labels: {
                format: '{value:<span style="font-size: 12px; font-weight: bold">%a</span> %e %b}',
                align: 'left',
                x: 3,
                y: -5
            },
            opposite: true,
            tickLength: 20,
            gridLineWidth: 1
        }],

        yAxis: [{ // temperature axis
            title: {
                text: null
            },
            labels: {
                format: '{value} °C',
                style: {
                    fontSize: '10px'
                },
                x: -3
            },
            plotLines: [{ // zero plane
                value: 0,
                color: '#BBBBBB',
                width: 1,
                zIndex: 2
            }],
            // Custom positioner to provide even temperature ticks from top down
            tickPositioner: function () {
                var max = Math.ceil(this.max) + 1,
                    pos = max - 12, // start
                    ret;

                if (pos < this.min) {
                    ret = [];
                    while (pos <= max) {
                        ret.push(pos += 1);
                    }
                } // else return undefined and go auto

                return ret;

            },
            maxPadding: 0.3,
            tickInterval: 1,
            //gridLineColor: (Highcharts.theme && Highcharts.theme.background2) || '#F0F0F0'

        },

		{ // hpa axis
            title: {
                text: null,
            },
            labels: {
				format: '{value} hPa',
                enabled: true,
            },
            //gridLineWidth: 0,
            tickLength: 0,
            opposite: true

        },
		{ // precipitation axis
            title: {
                text: null,
            },
            labels: {
				format: '{value} mm',
                enabled: true,
            },
            //gridLineWidth: 0,
            tickLength: 0,
            opposite: true,
            min:0

        }],

        legend: {
            enabled: false
        },

        plotOptions: {
        },


     series: [{
            name: 'Temperature',
            data: temperatures,
            type: 'spline',
            marker: {
                enabled: false,
                states: {
                    hover: {
                        enabled: true
                    }
                }
            },
            tooltip: {
                valueSuffix: ' °C'
            },
            zIndex: 1,
            color: '#FF3333',
            negativeColor: '#48AFE8'
        },{
            name: 'T. ressentie au vent',
            data: dtemperatures,
            type: 'spline',
            marker: {
                enabled: false,
                states: {
                    hover: {
                        enabled: true
                    }
                }
            },
            tooltip: {
                valueSuffix: ' °C'
            },
            zIndex: 1,
            lineWidth: 0.7,
            dashStyle: 'Dash',
            color: '#FF3333',
            negativeColor: '#48AFE8'
        },
		{
            name: 'Pression (QFF)',
            data: pressures,
            type: 'spline',
			yAxis: 1,
            marker: {
                enabled: false,
                states: {
                    hover: {
                        enabled: true
                    }
                }
            },
            tooltip: {
                valueSuffix: ' hPa'
            },
            zIndex: 1,
            lineWidth: 0.8,
			color: {
				linearGradient: { x1: 0, x2: 0, y1: 0, y2: 1 },
				stops: [
					[0, '#dcdcdc'],
					[1, '#dcdcdc']
				]
			}
        },
		{
            name: 'Précipitations',
            data: precipitations,
			pointPadding: 0,
            pointPlacement: -0.1,
            type: 'column',
            color: 'rgba(104,207,232,1)',
            yAxis: 2,
            groupPadding: 0,
            borderWidth: 0,
            shadow: false,
            dataLabels: {
                enabled: true,
                formatter: function () {
                    if (this.y > 0) {
                        return this.y;
                    }
                },
                style: {
                    fontSize: '6px'
                }
            },
            tooltip: {
                valueSuffix: ' mm'
            }
        }
		]

	}
	
function windArrow (name) {
	var level, 
		path;

	// The stem and the arrow head
	path = [
		'M', 0, 7, // base of arrow
		'L', -1.5, 7,
		0, 10,
		1.5, 7,
		0, 7,
		0, -10 // top
	];

	level = $.inArray(name, ['Calme', 'Très légère brise', 'Légère brise', 'Petite brise', 'Jolie brise',
				'Bonne brise', 'Vent frais', 'Grand vent frais', 'Coup de vent', 'Fort coup de vent', 'Tempête',
				'Violente tempête', 'Ouragan']);

	if (level === 0) {
		path = []; // TODO: circle
	}

	if (level == 2) {
		path.push('M', 0, -8, 'L', 4, -8); // short line
	} else if (level >= 3) {
		path.push(0, -10, 7, -10); // long line
	}

	if (level == 4) {
		path.push('M', 0, -7, 'L', 4, -7);
	} else if (level >= 5) {
		path.push('M', 0, -7, 'L', 7, -7);
	}

	if (level == 5) {
		path.push('M', 0, -4, 'L', 4, -4);
	} else if (level >= 6) {
		path.push('M', 0, -4, 'L', 7, -4);
	}

	if (level == 7) {
		path.push('M', 0, -1, 'L', 4, -1);
	} else if (level >= 8) {
		path.push('M', 0, -1, 'L', 7, -1);
	}

	return path;
}	
	
	
	new Highcharts.Chart(options, function(chart) {
		 var max_value =  chart.yAxis[0].max;
        $.each(chart.series[0].data, function(i, point) {
				var arrow, x, y;
				
				if(i % 2 !== 0) {
					group = chart.renderer.g()
						.attr({
							translateX: point.plotX + chart.plotLeft - 15,
							translateY: point.plotY + chart.plotTop - 30,
							zIndex: 5
						})
						.clip(chart.renderer.clipRect(0, 0, 25, 25))
						.add();
	
					// Position the image inside it at the sprite position
					chart.renderer.image(
						icon[i],
						0,
						0,
						25,
						25
					)
						.add(group);
						
				}
				x = point.plotX + chart.plotLeft ;
				y = max_value + chart.plotTop - 10  ;
				if (windSpeedNames[i] === 'calme') {
					arrow = chart.renderer.circle(x, y, 10).attr({
						fill: 'none'
					});
				} else {
					arrow = chart.renderer.path(
						windArrow(windSpeedNames[i])
					).attr({
						rotation: parseInt(windDirections[i], 10),
						translateX: x, // rotation center
						translateY: y + 10 // rotation center
					});
				}
				arrow.attr({
					stroke: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black',
					'stroke-width': 2,
					zIndex: 5
				})
				.add();				
				
			
				
        });
    });
	
	
});	
	
</script>



