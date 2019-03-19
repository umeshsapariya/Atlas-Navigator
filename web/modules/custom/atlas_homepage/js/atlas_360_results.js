(function($, Drupal, drupalSettings) {
    Drupal.behaviors.chartresults = {
        attach: function(context, settings) {
            $(document).ready(function() {
                google.charts.load('current', {
                    'packages': ['corechart']
                });
                var cat_data = drupalSettings.cat_data;
                var invite_id = $('input[name=invite_id]').val();
                google.load("visualization", "1", {
                    packages: ["corechart"]
                });
                //google.setOnLoadCallback(drawVisualization);
                google.setOnLoadCallback(load_page_data);

                function addLink(data, id) {
                    var n, p, info = [],
                        ns = 'http://www.w3.org/1999/xlink';

                    // make an array for label and link.
                    info = [];
                    n = data.getNumberOfRows();
                    for (i = 0; i < n; ++i) {
                        info.push({
                            label: data.getValue(i, 0),
                            link: data.getValue(i, 5)
                        });
                    }
                    return info;
                }


                function load_page_data() {
                    $.ajax({
                        url: 'atlas_homepage_category-getdata',
                        data: {
                            'invite_id': invite_id
                        },
                        async: false,
                        success: function(data) {
                            if (!$.isEmptyObject(data)) {

                                $('.noresult').css("display", "none");
                                $('#chart_div').css("display", "block");
                                if (data) {
                                    var temp = ['Category 0', {
                                        label: 'Avg Rating',
                                        type: 'number'
                                    }, {
                                        role: 'annotation'
                                    }, {
                                        label: 'Self Rating',
                                        type: 'number'
                                    }, {
                                        role: 'annotation'
                                    }, {
                                        role: 'link'
                                    }];
                                    data.splice(0, 0, temp);
                                    $.each(data, function(index, value) {
                                        if (value[3] == 0) {
                                            value[3] = null;
                                        } else if (value[1] == 0) {
                                            value[1] = null;
                                        } else {}
                                    });
                                    drawVisualization(data);

                                }
                            } else {
                                $('.noresult').css("display", "block");
                                $('#chart_div').css("display", "none");
                            }

                        },
                    });
                }

                $(window).on("throttledresize", function(event) {
                    //drawVisualization(cat_data);
                });

                function drawVisualization(cat_data) {
                    //var data = google.visualization.arrayToDataTable(temp);
                    var data = new google.visualization.arrayToDataTable(cat_data);
                    var cat_count = cat_data.length - 1;
                    var extra = 0;
                    if (cat_count < 5) {
                        var extra = 70;
                    }
                    var height = cat_count * 65 + extra;
                    var options = {
                        title: 'Category',
                        width: '50%',
                        height: height,
                        axisTitlesPosition: 'out',
                        'isStacked': true,
                        pieSliceText: 'percentage',
                        orientation: 'vertical',
                        colors: ['#0598d8', '#D5D5D5'],
                        chartArea: {
                            height: "90%",
                            width: "60%",
                            bottom: '80',
                            left: '20%',
                        },
                        'tooltip': {
                            trigger: 'none'
                        },
                        //explorer: {axis: 'vertical', keepInBounds: true},
                        titleTextStyle: {
                            color: '#FFFFFF'
                        },
                        legend: {
                            position: 'none',
                            alignment: 'center'
                        },
                        hAxis: {
                            maxValue: 5,
                            textStyle: {
                                color: '#416e7f'
                            },
                            viewWindow: {
                                max: 5,
                                min: 0
                            },
                            ticks: [0, 1, 2, 3, 4, 5],
                        },
                        vAxis: {
                            textStyle: {
                                color: '#416e7f',
                                fontSize: 13
                            }
                        },
                        seriesType: 'bars',
                        bar: {
                            groupWidth: "20"
                        },
                        annotations: {
                            textStyle: {
                                fontName: 'Times-Roman',
                                fontSize: 6,
                                color: '#000',
                                auraColor: 'none',
                            },
                            stem: {
                                length: 1,
                            },
                            style: 'point',
                        },
                        axisTitlesPosition: 'out',
                        series: {
                            1: {
                                annotations: {
                                    stem: {
                                        length: -8
                                    }
                                },
                                type: 'line',
                                pointSize: 30,
                                lineWidth: 0,
                                pointShape: 'square',
                            }
                        },
                    };
                    var chartDiv = document.getElementById('chart_div');
                    var chart = new google.visualization.ComboChart(document.getElementById('chart_div'));
                    chart.draw(data, options);
                    var info = addLink(data, 'chart_div');
                    google.visualization.events.addListener(chart, 'select', function(e) {
                        var selection = chart.getSelection();
                        if (selection.length) {
                            var row = selection[0].row;
                            let link = data.getValue(row, 5);
                            location.href = link;
                        }

                    });
                    google.visualization.events.addListener(chart, 'ready', function() {
                        /* add a link to each label */

                        $("text[text-anchor=end]").click(function() {
                            var i = $(this).index("text[text-anchor=end]");
                            window.location.href = info[i]['link'];
                        });
                        // Change annotation text color
                        Array.prototype.forEach.call(chartDiv.getElementsByTagName('text'), function(text, index) {
                            if (text.getAttribute('fill') == '#ffffff') {
                                text.setAttribute('fill', '#00000');
                            }
                            if (text.getAttribute('text-anchor') == 'end' || text.getAttribute('text-anchor') == 'start') {
                                text.setAttribute('dominant-baseline', 'middle');
                            }
                            if (text.getAttribute('text-anchor') == 'end' && text.getAttribute('font-size')  == '6' ) {
                                text.setAttribute('x', '24%');
                            }
                            if ($(window).width() < 450) {
                if (text.getAttribute('text-anchor') == 'end' && text.getAttribute('font-size')  == '6' ) {
                                text.setAttribute('x', '27%');
                            }
            }
                        });


                    });
                    google.visualization.events.addListener(chart, 'onmouseover', function() {
                        // Change annotation text color
                        Array.prototype.forEach.call(chartDiv.getElementsByTagName('text'), function(text, index) {
                            if (text.getAttribute('fill') == '#ffffff') {
                                text.setAttribute('fill', '#00000');
                            }
                            if (text.getAttribute('text-anchor') == 'end' || text.getAttribute('text-anchor') == 'start') {
                                text.setAttribute('dominant-baseline', 'middle');
                            }
                            if (text.getAttribute('text-anchor') == 'end' && text.getAttribute('font-size')  == '6' ) {
                                text.setAttribute('x', '24%');
                            }
                            if ($(window).width() < 450) {
                if (text.getAttribute('text-anchor') == 'end' && text.getAttribute('font-size')  == '6' ) {
                                text.setAttribute('x', '27%');
                            }
            }
                        });
                    });
                    google.visualization.events.addListener(chart, 'click', function() {
                        // Change annotation text color
                        Array.prototype.forEach.call(chartDiv.getElementsByTagName('text'), function(text, index) {
                            if (text.getAttribute('fill') == '#ffffff') {
                                text.setAttribute('fill', '#00000');
                            }
                            if (text.getAttribute('text-anchor') == 'end' || text.getAttribute('text-anchor') == 'start') {
                                text.setAttribute('dominant-baseline', 'middle');
                            }
                            if (text.getAttribute('text-anchor') == 'end' && text.getAttribute('font-size')  == '6' ) {
                                text.setAttribute('x', '24%');
                            }
                            if ($(window).width() < 450) {
                if (text.getAttribute('text-anchor') == 'end' && text.getAttribute('font-size')  == '6' ) {
                                text.setAttribute('x', '27%');
                            }
            }
                        });
                    });
                    google.visualization.events.addListener(chart, 'select', function() {
                        // Change annotation text color
                        Array.prototype.forEach.call(chartDiv.getElementsByTagName('text'), function(text, index) {
                            if (text.getAttribute('fill') == '#ffffff') {
                                text.setAttribute('fill', '#00000');
                            }
                            if (text.getAttribute('text-anchor') == 'end' || text.getAttribute('text-anchor') == 'start') {
                                text.setAttribute('dominant-baseline', 'middle');
                            }
                            if (text.getAttribute('text-anchor') == 'end' && text.getAttribute('font-size')  == '6' ) {
                                text.setAttribute('x', '24%');
                            }
                            if ($(window).width() < 450) {
                if (text.getAttribute('text-anchor') == 'end' && text.getAttribute('font-size')  == '6' ) {
                                text.setAttribute('x', '27%');
                            }
            }
                        });
                    });
                    google.visualization.events.addListener(chart, 'onmouseout', function() {
                        // Change annotation text color
                        Array.prototype.forEach.call(chartDiv.getElementsByTagName('text'), function(text, index) {
                            if (text.getAttribute('fill') == '#ffffff') {
                                text.setAttribute('fill', '#00000');
                            }
                            if (text.getAttribute('text-anchor') == 'end' || text.getAttribute('text-anchor') == 'start') {
                                text.setAttribute('dominant-baseline', 'middle');
                            }
                            if (text.getAttribute('text-anchor') == 'end' && text.getAttribute('font-size')  == '6' ) {
                                text.setAttribute('x', '24%');
                            }
                            if ($(window).width() < 450) {
                if (text.getAttribute('text-anchor') == 'end' && text.getAttribute('font-size')  == '6' ) {
                                text.setAttribute('x', '27%');
                            }
            }
                        });
                    });

                    $('.chart_wrap').append('<div class="home_legends"><span class="blue-box box-legend">Avg Rating</span><span class="grey-box box-legend">Self Rating</span></div>');
                    $(".home_legends").not(':last-child').remove();
                }
            });
        }
    }
})(jQuery, Drupal, drupalSettings);