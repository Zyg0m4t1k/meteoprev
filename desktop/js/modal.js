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
	
	
	
/*
  Highcharts JS v6.0.7 (2018-02-16)
 Wind barb series module

 (c) 2010-2017 Torstein Honsi

 License: www.highcharts.com/license
*/
(function(g){"object"===typeof module&&module.exports?module.exports=g:g(Highcharts)})(function(g){var x=function(f){var g=f.each,p=f.seriesTypes,r=f.stableSort;return{getPlotBox:function(){return f.Series.prototype.getPlotBox.call(this.options.onSeries&&this.chart.get(this.options.onSeries)||this)},translate:function(){p.column.prototype.translate.apply(this);var c=this.options,e=this.chart,d=this.points,a=d.length-1,b,f,q=c.onSeries;b=q&&e.get(q);var c=c.onKey||"y",q=b&&b.options.step,l=b&&b.points,
k=l&&l.length,m=this.xAxis,w=this.yAxis,u=0,h,v,n,t;if(b&&b.visible&&k)for(u=(b.pointXOffset||0)+(b.barW||0)/2,b=b.currentDataGrouping,v=l[k-1].x+(b?b.totalRange:0),r(d,function(a,b){return a.x-b.x}),c="plot"+c[0].toUpperCase()+c.substr(1);k--&&d[a]&&!(h=l[k],b=d[a],b.y=h.y,h.x<=b.x&&void 0!==h[c]&&(b.x<=v&&(b.plotY=h[c],h.x<b.x&&!q&&(n=l[k+1])&&void 0!==n[c]&&(t=(b.x-h.x)/(n.x-h.x),b.plotY+=t*(n[c]-h[c]),b.y+=t*(n.y-h.y))),a--,k++,0>a)););g(d,function(a,b){var c;a.plotX+=u;void 0===a.plotY&&(0<=
a.plotX&&a.plotX<=m.len?a.plotY=e.chartHeight-m.bottom-(m.opposite?m.height:0)+m.offset-w.top:a.shapeArgs={});(f=d[b-1])&&f.plotX===a.plotX&&(void 0===f.stackIndex&&(f.stackIndex=0),c=f.stackIndex+1);a.stackIndex=c})}}}(g);(function(f,g){var p=f.each,r=f.seriesType;r("windbarb","column",{lineWidth:2,onSeries:null,states:{hover:{lineWidthPlus:0}},tooltip:{pointFormat:'\x3cspan style\x3d"color:{point.color}"\x3e\u25cf\x3c/span\x3e {series.name}: \x3cb\x3e{point.value}\x3c/b\x3e ({point.beaufort})\x3cbr/\x3e'},
vectorLength:20,yOffset:-20},{pointArrayMap:["value","direction"],parallelArrays:["x","value","direction"],beaufortName:"Calm;Light air;Light breeze;Gentle breeze;Moderate breeze;Fresh breeze;Strong breeze;Near gale;Gale;Strong gale;Storm;Violent storm;Hurricane".split(";"),beaufortFloor:[0,.3,1.6,3.4,5.5,8,10.8,13.9,17.2,20.8,24.5,28.5,32.7],trackerGroups:["markerGroup"],pointAttribs:function(c,e){var d=this.options;c=c.color||this.color;var a=this.options.lineWidth;e&&(c=d.states[e].color||c,a=
(d.states[e].lineWidth||a)+(d.states[e].lineWidthPlus||0));return{stroke:c,"stroke-width":a}},markerAttribs:function(){},getPlotBox:g.getPlotBox,windArrow:function(c){var e=1.943844*c.value,d,a=this.options.vectorLength/20,b=-10;if(c.isNull)return[];if(0===c.beaufortLevel)return this.chart.renderer.symbols.circle(-10*a,-10*a,20*a,20*a);c=["M",0,7*a,"L",-1.5*a,7*a,0,10*a,1.5*a,7*a,0,7*a,0,-10*a];d=(e-e%50)/50;if(0<d)for(;d--;)c.push(-10===b?"L":"M",0,b*a,"L",5*a,b*a+2,"L",0,b*a+4),e-=50,b+=7;d=(e-
e%10)/10;if(0<d)for(;d--;)c.push(-10===b?"L":"M",0,b*a,"L",7*a,b*a),e-=10,b+=3;d=(e-e%5)/5;if(0<d)for(;d--;)c.push(-10===b?"L":"M",0,b*a,"L",4*a,b*a),e-=5,b+=3;return c},translate:function(){var c=this.beaufortFloor,e=this.beaufortName;g.translate.call(this);p(this.points,function(d){for(var a=0;a<c.length&&!(c[a]>d.value);a++);d.beaufortLevel=a-1;d.beaufort=e[a-1]})},drawPoints:function(){var c=this.chart,e=this.yAxis;p(this.points,function(d){var a=d.plotX,b=d.plotY;c.isInsidePlot(a,0,c.inverted)?
(d.graphic||(d.graphic=this.chart.renderer.path().add(this.markerGroup)),d.graphic.attr({d:this.windArrow(d),translateX:a,translateY:b+this.options.yOffset,rotation:d.direction}).attr(this.pointAttribs(d))):d.graphic&&(d.graphic=d.graphic.destroy());d.tooltipPos=c.inverted?[e.len+e.pos-c.plotLeft-b,this.xAxis.len-a]:[a,b+e.pos-c.plotTop+this.options.yOffset-this.options.vectorLength/2]},this)},animate:function(c){c?this.markerGroup.attr({opacity:.01}):(this.markerGroup.animate({opacity:1},f.animObject(this.options.animation)),
this.animate=null)}},{isValid:function(){return f.isNumber(this.value)&&0<=this.value}})})(g,x)});	
