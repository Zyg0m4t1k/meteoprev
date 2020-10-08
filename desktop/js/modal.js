// http://jsfiddle.net/gh/get/library/pure/highcharts/highcharts/tree/master/samples/highcharts/demo/combo-meteogram/

var hour = new Date().getHours() + '' +   new Date().getDay() ,
 	wHeight = $(window).height(),
	dHeight = wHeight * 0.7;

console.log('modal')
$.getJSON('/plugins/meteoprev/data/' + filename + '_days.json', function(json) {
	window.meteogram = new Meteogram(json, 'container');
});

function Meteogram(json, container) {
    // Parallel arrays for the chart data, these are populated as the XML/JSON file
    // is loaded
    this.icon = [];
    this.precipitations = [];
    this.precipitationsError = []; // Only for some data sets
    this.winds = [];
    this.dtemperatures = [];
	this.temperatures = [];
    this.pressures = [];
	this.winds = [];
	this.windDirections = [];
	this.windDirectionNames = [];
	this.windSpeeds = [];	
	this.windSpeedNames = [];
	this.conditions = [];
	this.time_stamp = "";
	this.resoluton = 4 ;
	


    // Initialize
    this.json = json;
    this.container = container;

    // Run
    this.parseYrData();
}

Meteogram.prototype.parseYrData = function () {

    var meteogram = this,
        json = this.json,
        pointStart;
		
    $.each(json, function (j, datas) {
		
		$.each(datas.data, function(i, value) {	
			if ((new Date(value.timestamp*1000).getHours() + '' +   new Date(value.timestamp*1000).getDay() )  == hour) {
				meteogram.time_stamp = value.timestamp*1000;
				
			}	
			var from, to;
			
			from = value.timestamp*1000;
			to = value.timestamp*1000 +  4 * 36e5;
			
			if (to > pointStart + 4 * 24 * 36e5) {
				return;
			}
            if (i === 0) {
                meteogram.resolution = to - from;
            }					
				
			 meteogram.temperatures.push({
				//x: value.timestamp*1000,
				x: from,
				y: value.value.TMP2m,
				condition: value.value.CONDITION,
				symbolName: value.value.windname + '  ( ' + value.value.WNDDIRCARD10 + ' )'
			})
			
			 meteogram.precipitations.push({
				x: from,
				y: value.value.APCPsfc
			})
			
			 meteogram.pressures.push({
				x: from,
				y: value.value.PRMSL
			})	
			
			 meteogram.dtemperatures.push({
				x: from,
				y: value.value.WNDCHILL2m
			})	
			
			// meteogram.winds.push([value.timestamp*1000,[value.value.WNDSPD10m,value.value.WNDDIR10m]]);
                meteogram.winds.push({
                    x: from,
                    value: parseFloat(value.value.WNDSPD10m),
                    direction: parseFloat(value.value.WNDDIR10m)
                });

			 meteogram.icon.push(value.value.ICON);
			 meteogram.windDirections.push(value.value.WNDDIR10m);
			 meteogram.windDirectionNames.push(value.value.WNDDIRCARD10);
			 meteogram.windSpeeds.push(value.value.WNDSPD10m);
			 meteogram.windSpeedNames.push(value.value.windname);
			 meteogram.conditions.push(value.value.CONDITION);
			if (i == 0) {
				pointStart = (from + to) / 2;
			}	
		

		})
    });


    // The returned xml variable is a JavaScript representation of the provided
    // XML, generated on the server by running PHP simple_load_xml and
    // converting it to JavaScript by json_encode.

    // Smooth the line
   this.smoothLine(this.temperatures);

    // Create the chart when the data is loaded
    this.createChart();
};

Meteogram.prototype.smoothLine = function (data) {
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

Meteogram.prototype.createChart = function () {
    var meteogram = this;
    this.chart = new Highcharts.Chart(this.getChartOptions(), function (chart) {
        meteogram.onChartLoad(chart);
    });
};


Meteogram.prototype.getChartOptions = function () {
    var meteogram = this;

    return {
        chart: {
            renderTo: this.container,
            marginBottom: 70,
            marginRight: 130,
            marginTop: 80,
            plotBorderWidth: 1,
            height: 500
        },
        title: {
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
                return meteogram.tooltipFormatter(this);
            }
        },
        xAxis: [{ // Bottom X axis
            type: 'datetime',
            tickInterval: 4 * 36e5, // two hours
            minorTickInterval: 2 * 36e5, // one hour
            tickLength: 0,
            gridLineWidth: 1,
            gridLineColor: (Highcharts.theme && Highcharts.theme.background2) || '#F0F0F0',
            startOnTick: false,
            endOnTick: false,
            minPadding: 0,
            maxPadding: 0,
            offset: 30,
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
            data: this.temperatures,
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
            data: this.dtemperatures,
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
            data: this.pressures,
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
            data: this.precipitations,
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
                    fontSize: '8px'
                }
            },
            tooltip: {
                valueSuffix: ' mm'
            }
        }]
    }
};

Meteogram.prototype.onChartLoad = function (chart) {

   this.drawWeatherSymbols(chart);
   this.drawWindArrows(chart);
  // this.drawBlocksForWindArrows(chart);

};

Meteogram.prototype.windArrow = function (name) {
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

    //level = $.inArray(name, [0,1,2,3,4,5,6,7,8,9,10,11,12]);
	level = $.inArray(name, ['Calme', 'Très légère brise', 'Légère brise', 'Petite brise', 'Jolie brise',
        'Bonne brise', 'Vent frais', 'Grand frais', 'Coup de vent', 'Fort coup de vent', 'Tempête',
        'Violente tempête', 'Ouragan']);

    if (level === 0) {
        path = [];
    }

    if (level === 2) {
        path.push('M', 0, -8, 'L', 4, -8); // short line
    } else if (level >= 3) {
        path.push(0, -10, 7, -10); // long line
    }

    if (level === 4) {
        path.push('M', 0, -7, 'L', 4, -7);
    } else if (level >= 5) {
        path.push('M', 0, -7, 'L', 7, -7);
    }

    if (level === 5) {
        path.push('M', 0, -4, 'L', 4, -4);
    } else if (level >= 6) {
        path.push('M', 0, -4, 'L', 7, -4);
    }

    if (level === 7) {
        path.push('M', 0, -1, 'L', 4, -1);
    } else if (level >= 8) {
        path.push('M', 0, -1, 'L', 7, -1);
    }

    return path;
};

Meteogram.prototype.drawWindArrows = function (chart) {
    var meteogram = this;

    $.each(chart.series[0].data, function (i, point) {
        var sprite, arrow, x, y;

        if (meteogram.resolution > 36e5 || i % 3 === 0) {

            // Draw the wind arrows
            x = point.plotX + chart.plotLeft + 8/(meteogram.resolution/36e5);
            y = 445;
            if (meteogram.windSpeedNames[i] === '0') {
                arrow = chart.renderer.circle(x, y, 10).attr({
                    fill: 'none'
                });
            } else {
                arrow = chart.renderer.path(
                    meteogram.windArrow(meteogram.windSpeedNames[i])
                ).attr({
                    rotation: parseInt(meteogram.windDirections[i], 10),
                    translateX: x, // rotation center
                    translateY: y // rotation center
                });
            }
            arrow.attr({
                stroke: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black',
                'stroke-width': 1.5,
                zIndex: 5
            })
            .add();

        }
    });
};

Meteogram.prototype.drawBlocksForWindArrows = function (chart) {
    var xAxis = chart.xAxis[0],
        x,
        pos,
        max,
        isLong,
        isLast,
        i;

    for (pos = xAxis.min, max = xAxis.max, i = 0; pos <= max + 36e5; pos += 36e5, i += 1) {

        // Get the X position
        isLast = pos === max + 36e5;
        x = Math.round(xAxis.toPixels(pos)) + (isLast ? 0.5 : -0.5);

        // Draw the vertical dividers and ticks
        if (this.resolution > 36e5) {
            isLong = pos % this.resolution === 0;
        } else {
            isLong = i % 2 === 0;
        }
        chart.renderer.path(['M', x, chart.plotTop + chart.plotHeight + (isLong ? 0 : 28),
            'L', x, chart.plotTop + chart.plotHeight + 32, 'Z'])
            .attr({
                'stroke': chart.options.chart.plotBorderColor,
                'stroke-width': 1
            })
            .add();
    }

      // Center items in block
    chart.get('windbarbs').markerGroup.attr({
        translateX: chart.get('windbarbs').markerGroup.translateX + 8
    });

};

Meteogram.prototype.drawWeatherSymbols = function (chart) {
    var meteogram = this;

    $.each(chart.series[0].data, function (i, point) {
        if (i % 2 === 0) {

            chart.renderer
                .image(

                    meteogram.icon[i],
                    point.plotX + chart.plotLeft - 8,
                    point.plotY + chart.plotTop - 30,
                    30,
                    30
                )
                .attr({
                    zIndex: 5
                })
                .add();
        }
    });
};

	
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

Meteogram.prototype.tooltipFormatter = function (tooltip) {

    // Create the header with reference to the time interval
    var index = tooltip.points[0].point.index,
	    x = this.x,
        ret = '<small>' + Highcharts.dateFormat('%A %e %b %H:%M', tooltip.x) + ' </small><br>';
		ret += '<b>' + this.conditions[index] + ' </b><br>';
    // Symbol text
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
};
	
	
