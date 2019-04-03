(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.OpchartsInner = {
    attach: function (context, settings) {

      $(document).ready(function () {
          $('#overall-proficiency-right thead tr th:nth-child(3), #overall-proficiency-right thead tr th:nth-child(4)').append(' <i class="icon-sort"></i>');
        // table sorting
        $('#overall-proficiency-right thead tr th:nth-child(3), #overall-proficiency-right thead tr th:nth-child(4)').click(function () {
          $('#overall-proficiency-right thead tr th').removeClass('active');
          $(this).toggleClass('active');
          if($(this).hasClass("asc")) {
              $(this).removeClass('asc').addClass('desc');
          } else {
            $(this).removeClass('desc').addClass('asc');
          }
          var table = $(this).parents('table').eq(0)
          var rows = table.find('tr:gt(0)').toArray().sort(comparer($(this).index()))
          this.asc = !this.asc
          if (!this.asc) {
            rows = rows.reverse()
          }
          for (var i = 0; i < rows.length; i++) {
            table.find('#mCSB_2_container').append(rows[i])
          }
        })
        function comparer(index) {
          return function (a, b) {
            var valA = getCellValue(a, index), valB = getCellValue(b, index)
            return $.isNumeric(valA) && $.isNumeric(valB) ? valA - valB : valA.toString().localeCompare(valB);
          }
        }
        function getCellValue(row, index) {
          return $(row).children('td').eq(index).text();
        }


        google.charts.load('current', {'packages': ['corechart']});
        google.charts.setOnLoadCallback(drawChart);
        var strengths_percentage = drupalSettings.my_strengths;
        var opportunities_percentage = 100 - strengths_percentage;
        function drawChart() {

          var data = google.visualization.arrayToDataTable([
            ['Effort', 'Amount given'],
            ['Proficient skills', strengths_percentage],
            ['Skills gap', opportunities_percentage],
          ]);

          var options = {
            // width: $("#overall-proficiency-left").width(),
            // height: $("#overall-proficiency-left").height(),
            width: 240,
            height: 240,
            pieHole: 0.6,
            pieSliceTextStyle: {
              color: 'black',
            },
            colors: ['#008ec3', '#FF2F2F'],
            pieSliceText: 'none',
            legend: {position: 'none', pointSize: 1},
            chartArea: {width: '90%', height: '90%'},
            tooltip: {trigger: 'none'},
          };

          var chart = new google.visualization.PieChart(document.getElementById('donut_single'));
          chart.draw(data, options);
        }
      });
    }
  }
})(jQuery, Drupal, drupalSettings);
