(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.overallCharts = {
    attach: function (context, settings) {
      $(document).ready(function () {
    google.charts.load('current', {'packages': ['corechart']});
    google.charts.setOnLoadCallback(drawVisualization);
    var cat_data = drupalSettings.cat_data;
    var cat_count = cat_data.length;
    google.load("visualization", "1", {
      packages: ["corechart"]
    });
    google.setOnLoadCallback(drawVisualization);
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
                    console.log(info)
                }

    $(window).on("throttledresize", function (event) {
      drawVisualization();
    });
    function drawVisualization() {
      var temp = [
        ['Category 0', {label: 'Avg Rating', type: 'number'}, {role: 'annotation'}, {label: 'Self Rating', type: 'number'}, {role: 'annotation'}, {role: 'link'}],
        ];
      var i;
      for (i = 0; i < cat_data.length; ++i) {
        temp.push(cat_data[i]);
      }
      $.each(temp, function( index, value ) {
        if (value[3] == 0){
          value[3] = null;
        } 
        else if (value[1] == 0 ){
          value[1] = null;
        } 
        else {}
      });
      var data = google.visualization.arrayToDataTable(temp);
      var formatNumer = new google.visualization.NumberFormat({pattern: '0.0'});
      formatNumer.format(data, 2);
      formatNumer.format(data, 4);
        var options = {
        title: 'Category',
        width: '70%',
        height: cat_count*65 + 20,
        axisTitlesPosition: 'out',
        'isStacked': true,
        pieSliceText: 'percentage',
        orientation: 'vertical',
        colors: ['#008ec3', '#D5D5D5'],
        chartArea: {
          height: "90%",
          width: "70%",
          bottom: '50',
          left: '20%',
        },
        'tooltip' : {
          trigger: 'none'
        },
        titleTextStyle: {color: '#FFFFFF', fontSize: '10'},
        legend: {position: 'none', alignment: 'center'},
        hAxis: {maxValue: 5, textStyle: {color: '#416e7f'}, viewWindow: {
                max: 5,
                min: 0
              }, ticks: [0, 1, 2, 3, 4, 5],},
        vAxis: {textStyle: {color: '#416e7f', fontSize: 13}},
        //  width: $('.outer_wrapper').width()*0.85,
        // height: $('.outer_wrapper').height()*0.75,
        // chartArea:{width:'60%',height:'auto'},
        seriesType: 'bars',
        bar: {groupWidth: "20"},
        annotations: {
          textStyle: {
            fontName: 'Times-Roman',
            fontSize: 2,
            color: '#000',
            auraColor: 'none',
          },
          stem: {
            length: 1,
          },
          style: 'point',
        },
        axisTitlesPosition: 'out',
        series: {1: {annotations: {stem: {length: -10}}, type: 'line', pointSize: 40, lineWidth: 0, pointShape: 'square', }},
      };
      var chartDiv = document.getElementById('category_details_chart');
      var chart = new google.visualization.ComboChart(document.getElementById('category_details_chart'));
      chart.draw(data, options);
      var info = addLink(data, 'category_details_chart');
      $('.category_chart_wrp').append('<div class="home_legends"><span class="blue-box box-legend">Avg Rating</span><span class="grey-box box-legend">Self Rating</span></div>');
       $(".home_legends").not(':last-child').remove();
      google.visualization.events.addListener(chart, 'select', function (e) {
          var selection = chart.getSelection(); 
          if (selection.length) { 
              var row = selection[0].row; 
              let link = data.getValue(row, 5); 
              location.href = link; } 
      });
      google.visualization.events.addListener(chart, 'ready', function () { 
        $("text[text-anchor=end]").click(function() {
          var i = $(this).index("text[text-anchor=end]");
          window.location.href = info[i]['link'];
        });
          // Change annotation text color
        Array.prototype.forEach.call(chartDiv.getElementsByTagName('text'), function(text, index) {
            if (text.getAttribute('fill') == '#ffffff') {
                text.setAttribute('fill', '#00000');
                text.setAttribute('dominant-baseline', 'middle');
            }
            if (text.getAttribute('text-anchor') == 'end' || text.getAttribute('text-anchor') == 'start') {
                text.setAttribute('dominant-baseline', 'middle');
            }
            if (text.getAttribute('text-anchor') == 'end' && text.getAttribute('font-size')  == '2' ) {
                                text.setAttribute('x', '24%');
                            }
            if ($(window).width() < 450) {
                if (text.getAttribute('text-anchor') == 'end' && text.getAttribute('font-size')  == '2' ) {
                                text.setAttribute('x', '27%');
                            }
            }
        });
      });
      google.visualization.events.addListener(chart, 'onmouseover', function () { 
          // Change annotation text color
        Array.prototype.forEach.call(chartDiv.getElementsByTagName('text'), function(text, index) {
            if (text.getAttribute('fill') == '#ffffff') {
                text.setAttribute('fill', '#00000');
                text.setAttribute('dominant-baseline', 'middle');
            }
            if (text.getAttribute('text-anchor') == 'end' || text.getAttribute('text-anchor') == 'start') {
                text.setAttribute('dominant-baseline', 'middle');
            }
            if (text.getAttribute('text-anchor') == 'end' && text.getAttribute('font-size')  == '2' ) {
                                text.setAttribute('x', '24%');
                            }
                            if ($(window).width() < 450) {
                if (text.getAttribute('text-anchor') == 'end' && text.getAttribute('font-size')  == '2' ) {
                                text.setAttribute('x', '27%');
                            }
            }
        });
      });
      google.visualization.events.addListener(chart, 'click', function () { 
          // Change annotation text color
        Array.prototype.forEach.call(chartDiv.getElementsByTagName('text'), function(text, index) {
            if (text.getAttribute('fill') == '#ffffff') {
                text.setAttribute('fill', '#00000');
                text.setAttribute('dominant-baseline', 'middle');
            }
            if (text.getAttribute('text-anchor') == 'end' || text.getAttribute('text-anchor') == 'start') {
                text.setAttribute('dominant-baseline', 'middle');
            }
            if (text.getAttribute('text-anchor') == 'end' && text.getAttribute('font-size')  == '2' ) {
                                text.setAttribute('x', '24%');
                            }
                            if ($(window).width() < 450) {
                if (text.getAttribute('text-anchor') == 'end' && text.getAttribute('font-size')  == '2' ) {
                                text.setAttribute('x', '27%');
                            }
            }
        });
      });
      google.visualization.events.addListener(chart, 'select', function () { 
          // Change annotation text color
        Array.prototype.forEach.call(chartDiv.getElementsByTagName('text'), function(text, index) {
            if (text.getAttribute('fill') == '#ffffff') {
                text.setAttribute('fill', '#00000');
                text.setAttribute('dominant-baseline', 'middle');
            }
            if (text.getAttribute('text-anchor') == 'end' || text.getAttribute('text-anchor') == 'start') {
                text.setAttribute('dominant-baseline', 'middle');
            }
            if (text.getAttribute('text-anchor') == 'end' && text.getAttribute('font-size')  == '2' ) {
                                text.setAttribute('x', '24%');
                            }
                            if ($(window).width() < 450) {
                if (text.getAttribute('text-anchor') == 'end' && text.getAttribute('font-size')  == '2' ) {
                                text.setAttribute('x', '27%');
                            }
            }
        });
      });
      google.visualization.events.addListener(chart, 'onmouseout', function () { 
          // Change annotation text color
        Array.prototype.forEach.call(chartDiv.getElementsByTagName('text'), function(text, index) {
            if (text.getAttribute('fill') == '#ffffff') {
                text.setAttribute('fill', '#00000');
                text.setAttribute('dominant-baseline', 'middle');
            }
            if (text.getAttribute('text-anchor') == 'end' || text.getAttribute('text-anchor') == 'start') {
                text.setAttribute('dominant-baseline', 'middle');
            }
            if (text.getAttribute('text-anchor') == 'end' && text.getAttribute('font-size')  == '2' ) {
                                text.setAttribute('x', '24%');
                            }
                            if ($(window).width() < 450) {
                if (text.getAttribute('text-anchor') == 'end' && text.getAttribute('font-size')  == '2' ) {
                                text.setAttribute('x', '27%');
                            }
            }
        });
      });
    }
  });
 }
  }
})(jQuery, Drupal, drupalSettings);
