(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.RolePageDashbord = {
    attach: function (context, settings) {

      //$(document).ready(function () {

        $('.role-tab-container thead tr th:last-child').append(' <i class="icon-sort"></i>');
       // table sorting
       $('.role-tab-container thead tr th:last-child').once('RolePageDashbord').click(function () {
         $('.role-tab-container thead tr th').removeClass('active');
         $(this).toggleClass('active');
         if($(this).hasClass("asc")) {
             $(this).removeClass('asc').addClass('desc');
         } else {
           $(this).removeClass('desc').addClass('asc');
         }
       });
        // table sorting
        $('#role-dashboard-form thead tr th:nth-child(2)').once('RolePageDashbord1').click(function () {

          $('#role-dashboard-form thead tr th').removeClass('active');
          $(this).toggleClass('active');
          var table = $(this).parents('table').eq(0)
          var rows = table.find('tr:gt(0)').toArray().sort(comparer($(this).index()))
          this.asc = !this.asc

          if (!this.asc) {
            rows = rows.reverse()
          }
          for (var i = 0; i < rows.length; i++) {
            table.find('.mCSB_container').append(rows[i])
          }
        })
        function comparer(index) {
          return function (a, b) {
            var valA = getCellValue(a, index), valB = getCellValue(b, index)
            return $.isNumeric(valA) && $.isNumeric(valB) ? valA - valB : valA.toString().localeCompare(valB);
          }
        }
        
        function getCellValue(row, index) {
            console.log($(row).children('td').eq(index).text());
          return $(row).children('td').eq(index).text();
        }
      //});
      $( document ).ajaxComplete(function( event, request, settings ) {
        $(".role-container .role-tab-container tbody").addClass('scroll_div_content');
        $(".scroll_div_content").mCustomScrollbar({
        });
        var path_role_page = $('.path-role-page #left-outer-section').height() - $('.path-role-page #left-outer-section #filter-container').height() - 85;
        $(".path-role-page #left-outer-section tbody").css({"height": path_role_page});
      });

    }
  }
})(jQuery, Drupal, drupalSettings);
