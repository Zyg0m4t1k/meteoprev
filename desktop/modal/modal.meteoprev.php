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
$.getJSON('/plugins/meteoprev/data/' + filename + '_days.json', function(json) {
    var	temperatures = [],
		precipitations = [],
		conv_precipitations = [],
		pressures = [],
		dtemperatures = [],
		windDirections = [],
		windDirectionNames = [],
		windSpeeds = [],
		windSpeedNames = [],
		pointStart;
		
    $.each(json, function (j, datas) {

		$.each(datas.datas, function(i, value) {	
			temperatures.push([i*1000,value.TMP2m]);
			precipitations.push([i*1000,value.APCPsfc]);
			//conv_precipitations.push([i*1000,value.ACPCP_0]);
			pressures.push([i*1000,value.PRMSL]);
			dtemperatures.push([i*1000,value.WNDCHILL2m]);

//			windDirections.push(value.wd);
//			windDirectionNames.push(value.wdn);
//			windSpeeds.push(value.ws);
//			windSpeedNames.push(value.wsn);
		})
    });
	console.log(temperatures)
	
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

	var options = {
        chart: {
            renderTo: 'container',
            height: 500
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
            useHTML: true
        },
        xAxis: [{ // Bottom X axis
            type: 'datetime',
            tickInterval: 4 * 36e5, // two hours
            minorTickInterval: 2 * 36e5, // one hour
            tickLength: 0,
            gridLineWidth: 1,
            gridLineColor: (Highcharts.theme && Highcharts.theme.background2) || '#F0F0F0',
            showLastLabel: true,
            labels: {
                format: '{value:%Hh}'
            }
        }, { // Top X axis
            linkedTo: 0,
            type: 'datetime',
            tickInterval: 24 * 3600 * 1000,
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
            gridLineColor: (Highcharts.theme && Highcharts.theme.background2) || '#F0F0F0'

        },

		{ // hpa axis
            title: {
                text: null,
            },
            labels: {
				format: '{value} hPa',
                enabled: true,
            },
            gridLineWidth: 0,
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
            gridLineWidth: 0,
            tickLength: 0,
            opposite: true,
            min:0

        }],

        legend: {
            enabled: false
        },

        plotOptions: {
            series: {
                pointPlacement: 'between'
            },
			column: {
                grouping: false,
                shadow: false,
                borderWidth: 0
            }
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
                enabled: false,
                formatter: function () {
                    if (this.y > 0) {
                        return this.y;
                    }
                },
                style: {
                    fontSize: '8px'
                }
            },
            tooltip: {
                valueSuffix: ' mm'
            }
        }
		]	
	}
	new Highcharts.Chart(options)		
	
	
});	
	
</script>



