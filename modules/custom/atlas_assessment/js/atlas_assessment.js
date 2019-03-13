Drupal.behaviors.atlas_assessmentform = {
  attach: function (context, settings) {
    var numItems = jQuery('.paragraph-type--skill').length;
    var numcats = jQuery('.paragraph-type--category').length;
    var numSkills = jQuery('.paragraph-type--skill-level-information').length;
    var i;
    var j;
    var k;
    jQuery('.field--name-field-number-of-levels').change(function () {
      for (i = 0; i <= numcats; i++) {
        for (j = 0; j <= numItems; j++) {
          var selected_value = jQuery("input[name='field_category["+ i +"][subform][field_skills]["+ j +"][subform][field_number_of_levels]']:checked").val();
          if (selected_value) {
            jQuery("input[name='field_category["+ i +"][subform][field_skills]["+ j +"][subform][field_target_proficiency]']").each(function() {
              var prof_value = jQuery(this).val();
              jQuery(this).parent().show();
              if (parseInt(prof_value) > parseInt(selected_value)) {
                 jQuery(this).parent().hide();
              }
            });
            for (k = 0; k <= numSkills; k++) {
              jQuery("input[name='field_category["+ i +"][subform][field_skills]["+ j +"][subform][field_skill_level_information]["+ k +"][subform][field_level_header][0][value]']").each(function() {
                jQuery(this).closest('.paragraph-type--skill-level-information').show();
                if (parseInt(k) >= parseInt(selected_value)) {
                   jQuery(this).closest('.paragraph-type--skill-level-information').hide();
                }
              }); 
            }
          }
          else {
            for (k = 0; k <= numSkills; k++) {
              jQuery("input[name='field_category["+ i +"][subform][field_skills]["+ j +"][subform][field_skill_level_information]["+ k +"][subform][field_level_header][0][value]']").closest('.paragraph-type--skill-level-information').hide();
            }
          }
        }
      }
    });
    jQuery('.field--name-field-number-of-levels').each(function () {
      //jQuery('.field--name-field-number-of-levels').trigger("change");
      for (i = 0; i <= numcats; i++) {
        for (j = 0; j <= numItems; j++) {
          var selected_value = jQuery("input[name='field_category["+ i +"][subform][field_skills]["+ j +"][subform][field_number_of_levels]']:checked").val();
          if (selected_value) {
            jQuery("input[name='field_category["+ i +"][subform][field_skills]["+ j +"][subform][field_target_proficiency]']").each(function() {
              var prof_value = jQuery(this).val();
              jQuery(this).parent().show();
              if (parseInt(prof_value) > parseInt(selected_value)) {
                 jQuery(this).parent().hide();
              }
            });
            for (k = 0; k <= numSkills; k++) {
              jQuery("input[name='field_category["+ i +"][subform][field_skills]["+ j +"][subform][field_skill_level_information]["+ k +"][subform][field_level_header][0][value]']").each(function() {
                jQuery(this).closest('.paragraph-type--skill-level-information').show();
                if (parseInt(k) >= parseInt(selected_value)) {
                   jQuery(this).closest('.paragraph-type--skill-level-information').hide();
                }
              });
            }
          }
        }
      }
    });
    jQuery('.paragraph-type-top .paragraphs-dropbutton-wrapper input[type=submit]').val('X'); 
  }
};
