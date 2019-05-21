$ = jQuery;

// Ready function starts
$(document).ready(function () {
  $('input[type="date"]').change(function () {
    $(this).parent().parent().find('.dev-date').html(this.value);
  });

  $('.atlas-skill-developing-plan-form select').on('change', function (event) {
    selectvalue();
    $('.atlas-skill-developing-plan-form input[type="checkbox"]').prop('checked', false);
  });

  const selectvalue = function () {

    var option_value = $('.atlas-skill-developing-plan-form select').val();
    $('.dev_row_cont').hide();
    $('.dev_row_cont.level_' + option_value).show();
  }



  const dev_default_val = function () {
    var dev_option_value = $('.right_rating_section .tyg-table td:nth-child(2)').text();
    var level;
    if (dev_option_value <= 1.6) {
      level = 1;
    } else if (dev_option_value >= 1.7 && dev_option_value <= 2.6) {
      level = 2;
    } else if (dev_option_value >= 2.7 && dev_option_value <= 3.6) {
      level = 3;
    } else if (dev_option_value >= 3.7 && dev_option_value <= 4.6) {
      level = 4;
    } else {
      level = 5;
    }
    $(".atlas-skill-developing-plan-form select").val(level);
    selectvalue();
  }
  dev_default_val();
  /* To add Mcustom Scroll - GLobal */

  /* End add Mcustom Scroll */

  /* To add Scroll height as per page size - GLobal */

  const scrollHeight = function () {
    var heightWindow = $(window).height();

    // var height_top_strength = $('.top-strengths').outerHeight() - $('.top-strengths table caption').outerHeight() + 20;


    var left_outer_section = $('.left-outer-section').height();
    $(".admin_custom_theme .content").css({"height": heightWindow});
    $(".admin_custom_theme .content .atlasnavigator_home").css({"height": heightWindow - 120});
    $(".admin_custom_theme .content .category_details_graph").css({"min-height": heightWindow - 150});
    $(".admin_custom_theme .content .skill_details_outer .inner_wrapper").css({"height": heightWindow - 150});
    $(".admin_custom_theme .content .overall-proficiency-wrapper").css({"height": heightWindow - 200});
    $(".admin_custom_theme .content .role-container").css({"height": heightWindow - 150});
    var path_assessment_form = $('.path-assessment-form .content').height() - $('.path-assessment-form .heading_titles').outerHeight() - $('.path-assessment-form .bottom_content').outerHeight() - $('.path-assessment-form .no_of_levels').outerHeight() - $('.path-assessment-form .rating_heading').outerHeight()- $('.path-assessment-form .content-header').outerHeight();
    $(".path-assessment-form .rating_main_wrapper .content_wrapper").css({"height": path_assessment_form - 40});


    var page_prof_content = $('.path-overall-proficiency-details .content').height();
    var scroll_prof_box = page_prof_content - 250;
    $("#overall-proficiency-right tbody").css({"height": scroll_prof_box});
    var path_role_page = $('.path-role-page #left-outer-section').height() - $('.path-role-page #left-outer-section #filter-container').height() - 85;
    $(".path-role-page #left-outer-section tbody").css({"height": path_role_page});
    var skill_dev_box = $('.skill_dev_plan').height() - 85;
    $(".path-category-details .dev-plan-cont").css({"height": skill_dev_box});
    var home_content = $('.atlas-home .content').height();
    var left_box_size = home_content - home_content * 5 / 100;
    var scroll_ht = left_box_size - 200;
    $(".atlas-home  .chart_container").css({"height": scroll_ht});
    var divide_size = left_box_size / 2;
    var dev_plan_scroll = divide_size - $('.box_title.blue_title').height() - $('.status-btn').height() - 30;
    $(".atlas-home .home_dev_block .dev_plan_cont").css({"height": dev_plan_scroll});
    $(".path-development-plan-details #output-results").css({"min-height": heightWindow - 130});
  }
  /* End croll height as per page size - GLobal*/

  /*Add Class - Homepage*/
  $('.atlasnavigator_home').parents('.content').addClass('atlasnavigator_home_content');
  /*End add Class - Homepage*/

  /* To Fade in % value   - GLobal*/
  $(".centerLabel").delay(500).fadeIn();
  /*End To Fade in % value   - GLobal */

  /*if admin login then - Global*/
  if ($('.toolbar-lining')[0]) {
    $('.nav-tabs-custom').css("padding-bottom", "40px");
    $('.nav-tabs-custom').css("margin-bottom", "10px");
    $('.tab-content').css("padding-bottom", "25px");
  }
  /*end if admin login then - Global*/
  // if($('.path-assessment-form .messages.messages--status')[0]) {
  //   var error_ht = $('.path-assessment-form .content_wrapper').height();
  //   $(".path-assessment-form .content_wrapper").css({"max-height": error_ht - 50});
  // }

  /* Outside popup close functionality  - Global*/
  $(document.body).on("click", ".ui-widget-overlay", function ()
  {
    $.each($(".ui-dialog"), function ()
    {
      var $dialog;
      $dialog = $(this).children(".ui-dialog-content");
      if ($dialog.dialog("option", "modal"))
      {
        $dialog.dialog("close");
      }
    });
  });
  /* End outside popup close functionality  - Global*/

  /* To add Star rating  - Overall Proficiency Page*/
  $('tr .ratings').each(function () {
    var star_width = $(this).find('.star-percentage').text();
    star_width = star_width * 0.98;
    $(this).find('.full-stars').css('width', star_width + '%');
  });
  /* End Star rating - Overall Proficiency Page*/

  /* To toggle Strengths &  Opportunities  - Overall Proficiency Page*/
  $('#overall-proficiency-right input').click(function () {
    $('#overall-proficiency-right input').removeClass('active');
    $(this).addClass('active');
    var active_val = $(this).val();
    if (active_val == 'Strengths') {
      $('#overall-proficiency-right thead tr th:nth-child(3)').click();
      if ($('#overall-proficiency-right thead tr th:nth-child(3)').hasClass("asc")) {
        $('#overall-proficiency-right thead tr th:nth-child(3)').click();
      }
      $('tr.Opportunities').hide();
      $('tr.Strengths').show();
    } else if (active_val == 'Opportunities') {
      $('#overall-proficiency-right thead tr th:nth-child(4)').click();
      if ($('#overall-proficiency-right thead tr th:nth-child(3)').hasClass("desc")) {
        $('#overall-proficiency-right thead tr th:nth-child(3)').click();
      }
      $('tr.Opportunities').show();
      $('tr.Strengths').hide();
    } else {
      $('tr.Opportunities').show();
      $('tr.Strengths').show();
    }
  });
  /* End toggle Strengths &  Opportunities  - Overall Proficiency Page*/

  // $(window).resize(function () {
  //   scrollHeight();
  // });
  $(".scroll_div_content").mCustomScrollbar({
  });

  if ($(window).width() > 767) {
    $(".admin_custom_theme .content").addClass('scroll_div_content');
    $("#overall-proficiency-right tbody").addClass('scroll_div_content');
    $("#overall-proficiency-right tbody").addClass('scroll_div_content');
    $(".role-container .role-tab-container tbody, .role-container .top-strengths tbody, .role-container .top-opportunities tbody").addClass('scroll_div_content');
    $(".scroll_div_content").mCustomScrollbar({
    });
    scrollHeight();

  }

  if ($(window).width() < 768) {
    $(".admin_custom_theme .content .atlasnavigator_home, .admin_custom_theme .content .overall-proficiency-wrapper").css({"height": "auto"});
    $(".admin_custom_theme .content .category_details_graph").css({"height": "auto", "padding-bottom": "30px"});
    $(".admin_custom_theme .content .skill_details_outer .inner_wrapper").css({"height": "auto", "padding-bottom": "30px"});
  }

  if ($(window).width() < 900) {
    var height_top_strength_mob = $('.top-strengths').height() / 2;
    $(".admin_custom_theme .content .role-container").css({"height": "auto"});

    $(".role-container .top-strengths tbody, .role-container .top-opportunities tbody").css({"height": height_top_strength_mob + 90});
  }
  /* Progress Bar  - Multistep Assessment Page*/
  var $total_progress = $('.progress_bar .total_pages').text();
  var $current_progress = $('.progress_bar .current_page').text();
  var $progress = $current_progress / $total_progress * 100;
  $('.progress_bar .current_page').css('width', $progress + '%');
  /* End Progress Bar  - Multistep Assessment Page*/


  /* Display form field on value present  - Multistep Assessment Page*/
  $('#node-assessment-form-form #edit-field-category-wrapper').hide();
  $('#node-assessment-form-form #edit-actions').hide();
  $('#node-assessment-form-form .clearfix').hide();
  $('#node-assessment-form-form #edit-field-verbatim-comments-wrapper').hide();

  $('#node-assessment-form-form #edit-title-0-value').on('keyup mouseenter', function () {
    if (($(this).val().length) >= 1) {
      $('#node-assessment-form-form #edit-field-category-wrapper').show();
    }
  });

  $('#node-assessment-form-form #edit-field-category-0-subform-field-skills-wrapper').hide();
  $('#node-assessment-form-form #edit-field-category-0-subform-field-new-category-0-value').on('keyup mouseenter', function () {
    if (($(this).val().length) >= 1) {
      $('#node-assessment-form-form .clearfix').show();
      $('#node-assessment-form-form #edit-field-category-0-subform-field-skills-wrapper').show();
      $('#node-assessment-form-form #edit-field-verbatim-comments-wrapper').show();
      $('#node-assessment-form-form #edit-actions').show();
    }
  });

  $('#node-assessment-form-form #edit-field-category-0-subform-field-skills-0-subform-field-skill-0-value').on('keyup mouseenter', function () {
    if (($(this).val().length) >= 1) {
      $('#node-assessment-form-form .clearfix').show();
    }
  });
  /* End Display form field on value present - Multistep Assessment Page*/
});

 $( document ).ajaxComplete(function( event, request, settings ) {
  var heightWindow = $(window).height();
  $(".admin_custom_theme .content .overall-proficiency-wrapper").css({"height": heightWindow - 200});
  $("#overall-proficiency-right tbody").addClass('scroll_div_content');
  $(".scroll_div_content").mCustomScrollbar({
  });
    var page_prof_content = $('.path-overall-proficiency-details .content').height();
    var scroll_prof_box = page_prof_content - 250;
    $("#overall-proficiency-right tbody").css({"height": scroll_prof_box});
   $(".centerLabel").fadeIn(); 
    
      $('#overall-proficiency-right input').click(function (e) {
        e.preventDefault();
    $('#overall-proficiency-right input').removeClass('active');
    $(this).addClass('active');
    var active_val = $(this).val();
    if (active_val == 'Strengths') {
      $('#overall-proficiency-right thead tr th:nth-child(3)').click();
      if ($('#overall-proficiency-right thead tr th:nth-child(3)').hasClass("asc")) {
        $('#overall-proficiency-right thead tr th:nth-child(3)').click();
      }
      $('tr.Opportunities').hide();
      $('tr.Strengths').show();
    } else if (active_val == 'Opportunities') {
      $('#overall-proficiency-right thead tr th:nth-child(4)').click();
      if ($('#overall-proficiency-right thead tr th:nth-child(3)').hasClass("desc")) {
        $('#overall-proficiency-right thead tr th:nth-child(3)').click();
      }
      $('tr.Opportunities').show();
      $('tr.Strengths').hide();
    } else {
      $('tr.Opportunities').show();
      $('tr.Strengths').show();
    }
  });
      $('tr .ratings').each(function () {
    var star_width = $(this).find('.star-percentage').text();
    star_width = star_width * 0.98;
    $(this).find('.full-stars').css('width', star_width + '%');
  });
  });


$(window).on('load', function () {
  const scrollHeight1 = function () {
    var height_top_strength = $('.top-strengths').outerHeight() - $('.top-strengths table caption').outerHeight();
    $(".role-container .top-strengths tbody, .role-container .top-opportunities tbody").css({"height": height_top_strength - 10});
  }

  $(window).resize(function () {
    scrollHeight1();
  });

  scrollHeight1();
});