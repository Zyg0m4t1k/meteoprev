// http://jsfiddle.net/gh/get/library/pure/highcharts/highcharts/tree/master/samples/highcharts/demo/combo-meteogram/

var hour = new Date().getHours() + '' +   new Date().getDay() ,
 	wHeight = $(window).height(),
	dHeight = wHeight * 0.7
	

function smoothLine(data) {
    var i = data.length,
        sum,
        value;

    while (i--) {
        data[i].value = value = data[i].y; // preserve value for tooltip

        // Set the smoothed value to the average of the closest points, but don't allow
        // it to differ more than 0.5 degrees from the given value
        sum = (data[i - 1] || data[i]).y + value + (data[i + 1] || data[i]).y;
        data[i].y = Math.max(value - 0.5, Math.min(sum / 3, value + 0.5));
    }
};
	
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
		 
		
	var pointStart,
		resolution;
		
		
		var k=0;
    $.each(json, function (j, datas) {
		
		$.each(datas.data, function(i, value) {	
			if ((new Date(value.timestamp*1000).getHours() + '' +   new Date(value.timestamp*1000).getDay() )  == hour) {
				time_stamp = value.timestamp*1000;
				
			}	
			var from, to;
			
			from = value.timestamp*1000;
			to = value.timestamp*1000 + 4 * 36e5;
			
			if (to > pointStart + 4 * 24 * 36e5) {
				return;
			}
            if (k === 0) {
                var resolution = to - from;
            }					
				
			temperatures.push({
				//x: value.timestamp*1000,
				x: from,
				y: value.value.TMP2m,
				to: to,
				symbolName: value.value.ICON
			})
			
			precipitations.push({
				x: from,
				y: value.value.APCPsfc
			})
			
			pressures.push({
				x: from,
				y: value.value.PRMSL
			})	
			
			dtemperatures.push({
				x: from,
				y: value.value.WNDCHILL2m
			})	
			
		//	winds.push([value.timestamp*1000,[value.value.WNDSPD10m,value.value.WNDDIR10m]]);
//            if (i % 2 === 0) {
//                winds.push({
//                    x: from,
//                    value: parseFloat(value.value.WNDSPD10m),
//                    direction: parseFloat(value.value.WNDDIR10m)
//                });
//            }

			icon.push(value.value.ICON);
			
			windDirections.push(value.value.WNDDIR10m);
			windDirectionNames.push(value.value.WNDDIRCARD10);
			windSpeeds.push(value.value.WNDSPD10m);
			windSpeedNames.push(value.value.windname);
			conditions.push(value.value.CONDITION);
			if (k == 0) {
				pointStart = (from + to) / 2;
			}	
		
			k++;
		})
    });
	smoothLine(temperatures)
	
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
            tickLength: 0,
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