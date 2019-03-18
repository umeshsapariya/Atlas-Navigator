$(function () {
  $('.act_check').click(function () {
    var nid = $(this).find('.hidded-nid').text();
    var development_id = 44;
    $.ajax({
      url: 'atlas-homepage-developing-update',
      data: {
        'development_id': development_id
      },
      async: false,
      success: function (data) {
        if (data == 'yes') {
          alert("development plan updated successfully")
        } else {
          alert('something went wrong');
        }
      },
    });
  });
  $('.hidded-nid').hide();
  $('.row_plan_block.plan_completed').hide();
  $('.inprogress-btn input').click(function () {
    $(this).removeClass('inactive');
     $('.completed-btn input').addClass('inactive');
    $('.dev_plan_cont .row_plan_block.plan_in_pregress').show();
    $('.dev_plan_cont .row_plan_block.plan_completed').hide();
  });
  $('.completed-btn input').click(function () {
    $(this).removeClass('inactive');
    $('.inprogress-btn input').addClass('inactive');
    $('.dev_plan_cont .row_plan_block.plan_completed').show();
    $('.dev_plan_cont .row_plan_block.plan_in_pregress').hide();
  });
}
);