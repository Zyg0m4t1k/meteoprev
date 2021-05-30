var hour = new Date().getHours() + '' +   new Date().getDay();


function meteoprevGraph(_station, _count,_container, _height) {
	//window.prv_meteogram = new prv_Meteogram(json, 'mpv_container');
	$.getJSON('plugins/meteoprev/data/' + _station + '_days.json', function(json) {
		window.prv_meteogram = new prv_Meteogram(json, _container , _count, _height);
	});
}

function prevCheckTimestamp(_time , _count, _height) {
	var ts = _time
	var day = Math.floor(new Date().setHours(0, 0, 0, 0) + _count * 24 * 3600 * 1000  );
	var checkDay = new Date(ts).setHours(0, 0, 0, 0);
	if(day === checkDay){
		return true;
	}
	return false;
}

function prv_Meteogram(json, container , _count , _height) {
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
	this.count = _count;
	this.arrowHeight = _height;
	// Run
	this.parseYrData();
}

prv_Meteogram.prototype.parseYrData = function () {
	var prv_meteogram = this,
		json = this.json,
		_count = this.count,
		pointStart;
	$.each(json, function (j, datas) {
		$.each(datas.data, function(i, value) {	
			if ((new Date(value.timestamp*1000).getHours() + '' +   new Date(value.timestamp*1000).getDay() )  == hour) {
				prv_meteogram.time_stamp = value.timestamp*1000;
			}	
			var from, to;
			if (prevCheckTimestamp(value.timestamp*1000, _count) === false) {
				return;
			}			
			from = value.timestamp*1000;
			to = value.timestamp*1000 +  4 * 36e5;
			if (to > pointStart + 4 * 24 * 36e5) {
				return;
			}
			if (i === 0) {
				prv_meteogram.resolution = to - from;
			}					
			prv_meteogram.temperatures.push({
				//x: value.timestamp*1000,
				x: from,
				y: value.value.TMP2m,
				condition: value.value.CONDITION,
				symbolName: value.value.windname + '  ( ' + value.value.WNDDIRCARD10 + ' )'
			})
			prv_meteogram.precipitations.push({
				x: from,
				y: value.value.APCPsfc
			})
			prv_meteogram.pressures.push({
				x: from,
				y: value.value.PRMSL
			})	
			prv_meteogram.dtemperatures.push({
				x: from,
				y: value.value.WNDCHILL2m
			})	
			prv_meteogram.winds.push({
				x: from,
				value: parseFloat(value.value.WNDSPD10m),
				direction: parseFloat(value.value.WNDDIR10m)
			});
			 prv_meteogram.icon.push(value.value.ICON);
			 prv_meteogram.windDirections.push(value.value.WNDDIR10m);
			 prv_meteogram.windDirectionNames.push(value.value.WNDDIRCARD10);
			 prv_meteogram.windSpeeds.push(value.value.WNDSPD10m);
			 prv_meteogram.windSpeedNames.push(value.value.windname);
			 prv_meteogram.conditions.push(value.value.CONDITION);
			if (i == 0) {
				pointStart = (from + to) / 2;
			}	
		})
	});
   this.smoothLine(this.temperatures);
	// Create the chart when the data is loaded
	this.createChart();
};

prv_Meteogram.prototype.smoothLine = function (data) {
	var i = data.length,
		sum,
		value;
	while (i--) {
		data[i].value = value = data[i].y;
		sum = (data[i - 1] || data[i]).y + value + (data[i + 1] || data[i]).y;
		data[i].y = Math.max(value - 0.5, Math.min(sum / 3, value + 0.5));
	}
};

prv_Meteogram.prototype.createChart = function () {
	var prv_meteogram = this;
	this.chart = new Highcharts.Chart(this.getChartOptions(), function (chart) {
		prv_meteogram.onChartLoad(chart);
	});
};

prv_Meteogram.prototype.getChartOptions = function () {
	var prv_meteogram = this;
	return {
		chart: {
			renderTo: this.container,
			backgroundColor: 'rgba(0,0,0,0)'
		},
		title: {
			text: null,
		},
		exporting: {
			enabled: false
		},		
		credits: {
			//enabled: false
            text: '<a href="https://www.prevision-meteo.ch">prevision-meteo.ch</a>',
			align: 'right',
            position: {
                y: - 250
            }
		},
		tooltip: {
			shared: true,
			useHTML: true,
			formatter: function () {
				return prv_meteogram.tooltipFormatter(this);
			}
		},
		xAxis: [{ // Bottom X axis
			type: 'datetime',
			tickInterval: 3 * 36e5, // two hours
			labels: {
				format: '{value:%Hh}'
			},
			plotLines: [{
				value: Date.now(),
				width: 1,
				color: '#996428',
				dashStyle: 'LongDash',
			}]			
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
			gridLineWidth: 1
		}],
		yAxis: [{ // temperature axis
			labels: {
				format: '{value}°C',
				style: {
					color: Highcharts.getOptions().colors[2]
				}
			},
			title: {
				text: null,
				style: {
					color: Highcharts.getOptions().colors[2]
				}
			},

		},
//
//		{ // hpa axis
//            title: {
//                text: null,
//            },
//            labels: {
//				format: '{value} hPa',
//                enabled: true,
//            },
//            gridLineWidth: 0,
//            tickLength: 0,
//            opposite: true
//
//        },
		{ // precipitation axis
			title: {
				text: null,
			},
			labels: {
				format: '{value} mm',
				enabled: true,
			},
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
			yAxis: 0,
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
			name: 'Précipitations',
			data: this.precipitations,
			pointPadding: 0,
			pointPlacement: -0.1,
			type: 'column',
			color: 'rgba(104,207,232,0.8)',
			yAxis: 1,
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

prv_Meteogram.prototype.onChartLoad = function (chart) {
   this.drawWeatherSymbols(chart);
   this.drawWindArrows(chart);
   this.drawBlocksForWindArrows(chart);
};

prv_Meteogram.prototype.drawBlocksForWindArrows = function (chart) {
	var xAxis = chart.xAxis[0],
		x,
		pos,
		max,
		isLong,
		isLast,
		i;
	for (pos = xAxis.min, max = xAxis.max, i = 0; pos <= max + 36e5; pos += 36e5, i += 1) {
		isLast = pos === max + 36e5;
		x = Math.round(xAxis.toPixels(pos)) + (isLast ? 0.5 : -0.5);
		if (this.resolution > 36e5) {
			isLong = pos % this.resolution === 0;
		} else {
			isLong = i % 2 === 0;
		}
		chart.renderer.path(['M', x, chart.plotTop + chart.plotHeight + (isLong ? 0 : 28),
			'L', x, chart.plotTop + chart.plotHeight + 32, 'Z'])
			.attr({
				stroke: chart.options.chart.plotBorderColor,
				'stroke-width': 1
			})
			.add();
	}
};	

prv_Meteogram.prototype.windArrow = function (name) {
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

prv_Meteogram.prototype.drawWindArrows = function (chart) {
	var prv_meteogram = this;
	$.each(chart.series[0].data, function (i, point) {
		var sprite, arrow, x, y;
		if (prv_meteogram.resolution > 36e5 || i % 3 === 0) {
			// Draw the wind arrows
			x = point.plotX + chart.plotLeft + 8/(prv_meteogram.resolution/36e5);
			y = prv_meteogram.arrowHeight; //200;
			if (prv_meteogram.windSpeedNames[i] === '0') {
				arrow = chart.renderer.circle(x, y, 10).attr({
					fill: 'none'
				});
			} else {
				arrow = chart.renderer.path(
					prv_meteogram.windArrow(prv_meteogram.windSpeedNames[i])
				).attr({
					rotation: parseInt(prv_meteogram.windDirections[i], 10),
					translateX: x, // rotation center
					translateY: y // rotation center
				});
			}
			arrow.attr({
				stroke: (Highcharts.theme && Highcharts.theme.contrastTextColor) || '#3A5A55',
				'stroke-width': 1.5,
				zIndex: 5
			})
			.add();
		}
	});
};

prv_Meteogram.prototype.drawWeatherSymbols = function (chart) {
	var prv_meteogram = this;
	$.each(chart.series[0].data, function (i, point) {
		if (i % 2 === 0) {
			chart.renderer
				.image(
					prv_meteogram.icon[i],
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

prv_Meteogram.prototype.tooltipFormatter = function (tooltip) {
	// Create the header with reference to the time interval
	var index = tooltip.points[0].point.index,
		x = this.x,
		ret = '<small>' + Highcharts.dateFormat('%A %e %b %H:%M', tooltip.x) + ' </small><br>';
		ret += '<b>' + this.conditions[index] + ' </b><br>';
	// Add all series
	Highcharts.each(tooltip.points, function (point) {
		var series = point.series;
		ret += '<span style="color:' + series.color + '">\u25CF</span> ' + series.name +
			':' + Highcharts.pick(point.point.value, point.y) +
			series.options.tooltip.valueSuffix + '<br/>' ;
	});
	// Add wind
	ret += '\u25CF Vent :' + this.windDirectionNames[index] +
		'<br>\u25B7 ' + this.windSpeedNames[index] + ' (' +
		Highcharts.numberFormat(this.windSpeeds[index], 1) + ' km/h)';
	return ret;
};