$(function () {
  $('.act_check').click(function () {
    var development_id = $(this).find('.hidded-nid').text();
    $.ajax({
      url: 'atlas-homepage-developing-update',
      data: {
        'development_id': development_id
      },
      async: false,
      success: function (data) {
        if (data != 'no') {
//          alert("Development plan updated successfully")
          location.reload();
        } else {
          alert('Something went wrong');
        }
      },
    });
  });
  
  $('.inprogress-btn input').click(function () {
    $(this).removeClass('inactive');
    $('.completed-btn input').addClass('inactive');
    $('.dev_plan_cont .row_plan_block.plan_in_pregress').css("display","table");
    $('.dev_plan_cont .row_plan_block.plan_completed').hide();
  });
  $('.completed-btn input').click(function () {
    $(this).removeClass('inactive');
    $('.inprogress-btn input').addClass('inactive');
    $('.dev_plan_cont .row_plan_block.plan_completed').css("display","table");
    $('.dev_plan_cont .row_plan_block.plan_in_pregress').hide();
  });
}
);