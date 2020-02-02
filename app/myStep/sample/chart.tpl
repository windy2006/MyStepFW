<style>
    canvas {
        -moz-user-select: none;
        -webkit-user-select: none;
        -ms-user-select: none;
        margin-bottom: 10px;
        border:1px gray solid;
        border-radius: 10px;
        width:100%;
        height:100%;
    }
</style>
<div class="row">
    <div class="col-xl-6 col-12">
        <canvas id="canvas1"></canvas>
    </div>
    <div class="col-xl-6 col-12">
        <canvas id="canvas2"></canvas>
    </div>
    <div class="col-xl-6 col-12">
        <canvas id="canvas3"></canvas>
    </div>
    <div class="col-xl-6 col-12">
        <canvas id="canvas4"></canvas>
    </div>
    <div class="col-xl-6 col-12">
        <canvas id="canvas5"></canvas>
    </div>
    <div class="col-xl-6 col-12">
        <canvas id="canvas6"></canvas>
    </div>
    <div class="col-xl-6 col-12">
        <canvas id="canvas7"></canvas>
    </div>
    <div class="col-xl-6 col-12">
        <canvas id="canvas8"></canvas>
    </div>
    <div class="col-xl-6 col-12">
        <canvas id="canvas9"></canvas>
    </div>
</div>
<script language="JavaScript">
jQuery.vendor('chart', {
    add_css:true,
    name_fix:'.bundle.min',
    callback:function(){
        jQuery.getScript('vendor/Chart/utils.js', function(){
            let getData = function(m=2, pie=false) {
                let chartColors = window.chartColors;
                let color = Chart.helpers.color;
                let data = {
                    labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July'],
                    datasets: []
                };
                let colorlist = ['red','blue', 'orange', 'purple', 'green', 'yellow', 'grey'];
                if(m<1) m = 1;
                if(m>=colorlist.length) m = colorlist.length - 1;
                if(pie) {
                    data.datasets.push({data:[], backgroundColor:[], lable:'My dataset'});
                    data.labels = data.labels.slice(0, m);
                }
                for(let i=0; i<m; i++) {
                    if(pie) {
                        data.datasets[0].data.push(randomScalingFactor());
                        data.datasets[0].backgroundColor.push(color(chartColors[colorlist[i]]).alpha(0.5).rgbString());
                    } else {
                        data.datasets.push({
                            label: 'Dataset ' + (i+1),
                            backgroundColor: color(chartColors[colorlist[i]]).alpha(0.5).rgbString(),
                            borderColor: chartColors[colorlist[i]],
                            borderWidth: 1,
                            data: [
                                randomScalingFactor(),
                                randomScalingFactor(),
                                randomScalingFactor(),
                                randomScalingFactor(),
                                randomScalingFactor(),
                                randomScalingFactor(),
                                randomScalingFactor()
                            ]
                        });
                    }
                }
                return data;
            };

            let config1 = {
                type: 'bar',
                options: {
                    responsive: true,
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Bar Chart'
                    },
                    tooltips: {
                        position: 'average',
                        mode: 'index',
                        intersect: false,
                    }
                },
                data: getData()
            };
            new Chart($id('canvas1').getContext('2d'), config1);

            let config2 = {
                type: 'line',
                options: {
                    responsive: true,
                    legend: {
                        position: 'bottom',
                    },
                    title: {
                        display: true,
                        text: 'Line Chart'
                    },
                    tooltips: {
                        position: 'nearest',
                        mode: 'index',
                        intersect: false,
                    },
                    hover: {
                        mode: 'nearest',
                        intersect: true
                    },
                    scales: {
                        xAxes: [{
                            display: true,
                            scaleLabel: {
                                display: true,
                                labelString: 'Month'
                            }
                        }],
                        yAxes: [{
                            display: true,
                            scaleLabel: {
                                display: true,
                                labelString: 'Value'
                            }
                        }]
                    }
                },
                data: getData(3)
            };
            new Chart($id('canvas2').getContext('2d'), config2);

            let config3 = {
                type: 'bar',
                options: {
                    title: {
                        display: true,
                        text: 'Bar Chart - Stacked'
                    },
                    tooltips: {
                        mode: 'index',
                        intersect: false
                    },
                    responsive: true,
                    scales: {
                        xAxes: [{
                            stacked: true,
                        }],
                        yAxes: [{
                            stacked: true
                        }]
                    }
                },
                data: getData(4)
            };
            new Chart($id('canvas3').getContext('2d'), config3);

            let config4 = {
                type: 'line',
                options: {
                    responsive: true,
                    hoverMode: 'index',
                    stacked: false,
                    title: {
                        display: true,
                        text: 'Line Chart - Multi Axis'
                    },
                    scales: {
                        yAxes: [{
                            type: 'linear', // only linear but allow scale type registration. This allows extensions to exist solely for log scale for instance
                            display: true,
                            position: 'left',
                            id: 'y-axis-1',
                        }, {
                            type: 'linear', // only linear but allow scale type registration. This allows extensions to exist solely for log scale for instance
                            display: true,
                            position: 'right',
                            id: 'y-axis-2',

                            // grid line settings
                            gridLines: {
                                drawOnChartArea: false, // only want the grid lines for one axis to show up
                            },
                        }],
                    }
                },
                data: getData()
            };
            config4.data.datasets[0].fill = false;
            config4.data.datasets[0].yAxisID = 'y-axis-1';
            config4.data.datasets[1].fill = false;
            config4.data.datasets[1].yAxisID = 'y-axis-2';
            config4.data.datasets[1].borderDash = [3,3];
            new Chart($id('canvas4').getContext('2d'), config4);

            let config5 = {
                type: 'radar',
                options: {
                    title: {
                        display: true,
                        text: 'Radar Chart'
                    },
                    legend: {
                        position: 'right',
                    },
                    maintainAspectRatio: true,
                    spanGaps: false,
                    elements: {
                        line: {
                            tension: 0.000001
                        }
                    },
                    plugins: {
                        filler: {
                            propagate: false
                        },
                        'samples-filler-analyser': {
                            target: 'chart-analyser'
                        }
                    }
                },
                data: getData(4)
            };
            config5.data.datasets[0].fill = 'start';
            config5.data.datasets[1].fill = 'start';
            new Chart($id('canvas5').getContext('2d'), config5);

            let config6 = {
                type: 'bar',
                data: getData(),
                options: {
                    responsive: true,
                    title: {
                        display: true,
                        text: 'Combo Bar Line Chart'
                    },
                    tooltips: {
                        mode: 'index',
                        intersect: true
                    },
                    spanGaps: false,
                    elements: {
                        line: {
                            tension: 0.000001
                        }
                    },
                }
            };
            config6.data.datasets[0].type = 'line';
            config6.data.datasets[0].fill = false;
            config6.data.datasets[1].type = 'bar';
            config6.data.datasets[1].fill = false;
            new Chart($id('canvas6').getContext('2d'), config6);

            let config7 = {
                type: 'pie',
                data: getData(5, true),
                options: {
                    responsive: true,
                    title: {
                        display: true,
                        text: 'Pie Chart'
                    },
                }
            };
            console.log(config7.data);
            new Chart($id('canvas7').getContext('2d'), config7);

            let config8 = {
                type: 'doughnut',
                data: getData(6, true),
                options: {
                    responsive: true,
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Doughnut Chart'
                    },
                    animation: {
                        animateScale: true,
                        animateRotate: true
                    }
                }
            };
            new Chart($id('canvas8').getContext('2d'), config8);

            let config9 = {
                data: getData(7, true),
                options: {
                    responsive: true,
                    legend: {
                        position: 'right',
                    },
                    title: {
                        display: true,
                        text: 'Polar Area Chart'
                    },
                    scale: {
                        ticks: {
                            beginAtZero: true
                        },
                        reverse: false
                    },
                    animation: {
                        animateRotate: false,
                        animateScale: true
                    }
                }
            };
            Chart.PolarArea($id('canvas9').getContext('2d'), config9);
        });
    }
});
</script>