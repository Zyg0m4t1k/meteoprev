// http://jsfiddle.net/gh/get/library/pure/highcharts/highcharts/tree/master/samples/highcharts/demo/combo-meteogram/

var hour = new Date().getHours() + '' +   new Date().getDay() ,
 	wHeight = $(window).height(),
	dHeight = wHeight * 0.7;

$.getJSON('/plugins/meteoprev/data/' + filename + '_days.json', function(json) {
	console.log(json);
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
	


    // Initialize
    this.json = json;
    this.container = container;

    // Run
    this.parseYrData();
}

Meteogram.prototype.parseYrData = function () {

    var meteogram = this,
        json = this.json,
        pointStart,
		resolution;
	var k=0;
    $.each(json, function (j, datas) {
		
		$.each(datas.data, function(i, value) {	
			if ((new Date(value.timestamp*1000).getHours() + '' +   new Date(value.timestamp*1000).getDay() )  == hour) {
				meteogram.time_stamp = value.timestamp*1000;
				
			}	
			var from, to;
			
			from = value.timestamp*1000;
			to = value.timestamp*1000 + 4 * 36e5;
			
//			if (to > pointStart + 4 * 24 * 36e5) {
//				return;
//			}
            if (k === 0) {
                var resolution = to - from;
            }					
				
			 meteogram.temperatures.push({
				//x: value.timestamp*1000,
				x: from,
				y: value.value.TMP2m,
				to: to,
				symbolName: value.value.CONDITION
			})
			
			 meteogram.precipitations.push({
				x: from,
				y: value.value.APCPsfc,
				symbolName: value.value.windname + '  ( ' + value.value.WNDDIRCARD10 + ' )'
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
           // if (i % 2 === 0) {
                meteogram.winds.push({
                    x: from,
                    value: parseFloat(value.value.WNDSPD10m),
                    direction: parseFloat(value.value.WNDDIR10m)
                });
           // }

			 meteogram.icon.push(value.value.ICON);
			 meteogram.windDirections.push(value.value.WNDDIR10m);
			 meteogram.windDirectionNames.push(value.value.WNDDIRCARD10);
			 meteogram.windSpeeds.push(value.value.WNDSPD10m);
			 meteogram.windSpeedNames.push(value.value.windname);
			 meteogram.conditions.push(value.value.CONDITION);
			if (k == 0) {
				pointStart = (from + to) / 2;
			}	
		
			k++;
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
            marginRight: 40,
            marginTop: 50,
            plotBorderWidth: 1,
            height: dHeight,
            alignTicks: false
        },

        title: {
            text: name,
            align: 'left'
        },

        credits: {
           text: '<a href="https://www.prevision-meteo.ch">prevision-meteo.ch</a>',
            position: {
                x: -40
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
            gridLineWidth: 0,
           // gridLineColor: (Highcharts.theme && Highcharts.theme.background2) || '#F0F0F0',
		   lineWidth: 0,
		   minorGridLineWidth: 0,
		   lineColor: 'transparent',
		   minorTickLength: 0,
		   tickLength: 0,		   
		   
            minPadding: 0,
            maxPadding: 0,
            offset: 30,
            showLastLabel: true,
            labels: {
                format: '{value:%H}'
            },
			plotLines: [{
				color: 'blue',
				width: 2,
				value: this.time_stamp
			}],			
			
            crosshair: true
        }, { // Top X axis
            linkedTo: 0,
            type: 'datetime',
            tickInterval: 24 * 3600 * 1000,
            labels: {
                format: '{value:<span style="font-size: 12px; font-weight: bold">%a</span> %b %e}',
                align: 'left',
                x: 3,
                y: -5
            },
            opposite: true,
            tickLength: 0,
            gridLineWidth: 0
        }],

        yAxis: [{ // temperature axis
            title: {
                text: null
            },
            labels: {
                format: '{value}°',
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
            maxPadding: 0.3,
            minRange: 8,
            tickInterval: 1,
            //gridLineColor: (Highcharts.theme && Highcharts.theme.background2) || '#F0F0F0'

        }, { // precipitation axis
            title: {
                text: null
            },
            labels: {
                enabled: false
            },
            gridLineWidth: 0,
            tickLength: 0,
            minRange: 10,
            min: 0

        }, { // Air pressure
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
        }],

        legend: {
            enabled: false
        },

        plotOptions: {
            series: {
                pointPlacement: 'between'
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
        }, {
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
                    fontSize: '6px'
                }
            },
            tooltip: {
                valueSuffix: ' mm'
            }
        },{
            name: 'T. ressentie au vent',
            data: this.dtemperatures,
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
        },{
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
        }, {
            name: 'Vent',
            type: 'windbarb',
            id: 'windbarbs',
            color: Highcharts.getOptions().colors[1],
            lineWidth: 1.5,
            data: this.winds,
            vectorLength: 18,
            yOffset: -15,
            tooltip: {
                valueSuffix: ' km/h'
            }
        }
		]
    };
};

Meteogram.prototype.onChartLoad = function (chart) {

   this.drawWeatherSymbols(chart);
  // this.drawBlocksForWindArrows(chart);

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
        if (meteogram.resolution > 36e5 || i % 2 === 0) {

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

	function tooltipFormatter (tooltip) {
		console.log(tooltip);
		//var index = tooltip.points[0].point.symbolName,
		var ret = '<small>' + Highcharts.dateFormat('%A %e %b %H:%M', tooltip.x) + '</small><br>';
//		// Symbol text
		ret += '<b>' +tooltip.points[0].point.symbolName + '</b>';
		ret += '<table>';
		
		// Add all series
		Highcharts.each(tooltip.points, function (point) {
			var series = point.series;
			ret += '<tr><td><span style="color:' + series.color + '">\u25CF</span> ' + series.name +
				': </td><td style="white-space:nowrap">' + Highcharts.pick(point.point.value, point.y) +
				series.options.tooltip.valueSuffix + '</td></tr>';
		});
		ret += '<tr><td style="text-align:right"><b>' +tooltip.points[1].point.symbolName + '</b></td></tr>';
		ret += '</table>';
		//ret += '<b>' +tooltip.points[1].point.symbolName + '</b>';
		
			
		return ret;	
	}
	
	
