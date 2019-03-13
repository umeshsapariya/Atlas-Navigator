(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.overallCharts = {
    attach: function (context, settings) {
      $(document).ready(function () {
    google.charts.load('current', {'packages': ['corechart']});
    google.charts.setOnLoadCallback(drawVisualization);
    var rel_data = drupalSettings.rel_data;
    var cat_count = rel_data.length
    google.load("visualization", "1", {
      packages: ["corechart"]
    });
    google.setOnLoadCallback(drawVisualization);
    $(window).on("throttledresize", function (event) {
      drawVisualization();
    });
    function drawVisualization() {
      var temp = [
        ['Category 0', {label: 'Self Rating', type: 'number'}, { role: 'style' }, {role: 'annotation'}, {label:  'Target Proficiency', type: 'number'}],
        ];
      var i;
      for (i = 0; i < rel_data.length; ++i) {
        temp.push(rel_data[i]);
      }
      $.each(temp, function( index, value ) {
        if (value[4] == 0){
          value[4] = null;
        } 
        else if (value[1] == 0 ){
          value[1] = null;
        } 
        else {}
      });
      var data = google.visualization.arrayToDataTable(temp);
     
    var options = {
            title: 'Category',
            width: '70%',
            height: cat_count*55 + 20,
            axisTitlesPosition: 'out',
            'isStacked': true,
            pieSliceText: 'percentage',
            orientation: 'vertical',
            colors: ['#0598d8', '#D5D5D5'],
            chartArea: {
              height: "90%",
              width: "70%",
              bottom: '50',
              left: '20%',
            },
            'tooltip' : {
              trigger: 'none'
            },
            //explorer: {axis: 'vertical', keepInBounds: true},
             titleTextStyle: {color: '#FFFFFF'},
              legend: {position: 'none', alignment: 'center'},
              hAxis: {maxValue: 5, textStyle: {color: '#416e7f'},viewWindow: {
                max: 5,
                min: 0
              }, ticks: [0, 1, 2, 3, 4, 5],},
              vAxis: {textStyle: {color: '#416e7f', fontSize: 13}},
            //  width: $('.outer_wrapper').width()*0.85,
            // height: $('.outer_wrapper').height()*0.75,
            // chartArea:{width:'60%',height:'auto'},
            seriesType: 'bars',
            bar: {groupWidth: "12"},
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
            series: {1: {type: 'line', lineWidth :5, color:'red'}},
      };
      var chartDiv = document.getElementById('skill_details_chart');
      var chart = new google.visualization.ComboChart(chartDiv);
      google.visualization.events.addListener(chart, 'ready', function () {  
        // reference line
        Array.prototype.forEach.call(chartDiv.getElementsByTagName('path'), function(path, index) {
     
            if (path.getAttribute('stroke') == '#ff0000' && path.getAttribute('fill') == 'none') {
                // change line coords
                var refCoords = path.getAttribute('d').split(',');
                console.log(refCoords+"before");
                var len = refCoords.length;
                refCoords[len-1] = parseFloat(refCoords[len-1]) + 40;
                var refWidth = refCoords[1].split('L');
                refWidth[0] = parseFloat(refWidth[0]) - 40;
                refCoords[1] = refWidth.join('L');
                console.log(refCoords+"after");
                path.setAttribute('d', refCoords.join(','));
            }

        });
        // Change annotation text color
        Array.prototype.forEach.call(chartDiv.getElementsByTagName('text'), function(text, index) {
            if (text.getAttribute('fill') == '#ffffff') {
                text.setAttribute('fill', '#00000');
            }
            if (text.getAttribute('text-anchor') == 'end') {
                text.setAttribute('dominant-baseline', 'middle');
            }
            // if (text.getAttribute('text-anchor') == 'start' && text.textContent == 0) {
            //       text.textContent = '';
            //       text.setAttribute('opacity', '0');
            //       text.setAttribute('fill', '#ffffff');
            // }
        });
        // $("rect[fill-opacity=0]").remove();
      });
      google.visualization.events.addListener(chart, 'onmouseover', function () { 
          // Change annotation text color
        Array.prototype.forEach.call(chartDiv.getElementsByTagName('text'), function(text, index) {
            if (text.getAttribute('fill') == '#ffffff') {
                text.setAttribute('fill', '#00000');
            }
            if (text.getAttribute('text-anchor') == 'end') {
                text.setAttribute('dominant-baseline', 'middle');
            }
        });
      });
      google.visualization.events.addListener(chart, 'click', function (event) { 
          // Change annotation text color
        Array.prototype.forEach.call(chartDiv.getElementsByTagName('text'), function(text, index) {
            if (text.getAttribute('fill') == '#ffffff') {
                text.setAttribute('fill', '#00000');
            }
            if (text.getAttribute('text-anchor') == 'end') {
                text.setAttribute('dominant-baseline', 'middle');
            }
        });
      }); 
       google.visualization.events.addListener(chart, 'select', function (event) { 
          // Change annotation text color
        Array.prototype.forEach.call(chartDiv.getElementsByTagName('text'), function(text, index) {
            if (text.getAttribute('fill') == '#ffffff') {
                text.setAttribute('fill', '#00000');
            }
            if (text.getAttribute('text-anchor') == 'end') {
                text.setAttribute('dominant-baseline', 'middle');
            }
        });
      });
       google.visualization.events.addListener(chart, 'onmouseout', function (event) { 
          // Change annotation text color
        Array.prototype.forEach.call(chartDiv.getElementsByTagName('text'), function(text, index) {
            if (text.getAttribute('fill') == '#ffffff') {
                text.setAttribute('fill', '#00000');
            }
            if (text.getAttribute('text-anchor') == 'end') {
                text.setAttribute('dominant-baseline', 'middle');
            }
        });
      });   
      chart.draw(data, options);
       $('.skill_chart_wrp').append('<div class="home_legends"><span class="blue-box box-legend">Avg Rating</span><span class="red-box box-legend">Target Proficiency</span></div>');
       $(".home_legends").not(':last-child').remove();
    }


  });
 }
  }
})(jQuery, Drupal, drupalSettings);
