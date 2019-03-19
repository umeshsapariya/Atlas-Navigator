(function($) {
  $.fn.cloneData = function(data) {
    if (data) {
      var cat_no = parseInt(data['cat_no']);
      var skill_no = parseInt(data['skill_no']);
      var level_name = 'field_category['+ cat_no +'][subform][field_skills]['+ skill_no +'][subform][field_number_of_levels]';
      var level_value = data['skill_levels'];
      var prof_value = data['skill_target_prof'];
      var include_na = data['skill_include_na'];
      $('[name="field_category['+ cat_no +'][subform][field_skills]['+ skill_no +'][subform][field_skill][0][value]"]').val(data['skill_name']);
      $("input[name='"+ level_name +"'][value='"+ level_value +"']").prop('checked', true);
      if (include_na) {
        $('input[name="field_category['+ cat_no +'][subform][field_skills]['+ skill_no +'][subform][field_include_na][value]"]').prop('checked', include_na);
      }
      $('input[name="field_category['+ cat_no +'][subform][field_skills]['+ skill_no +'][subform][field_target_proficiency]"][value="'+ prof_value +'"]').prop('checked', true);
      if (data['skill_info']) {
        var obj = data['skill_info'];
        //console.log(obj)
        Object.keys(obj).forEach(function(key) {
          textarea_id = '';
          $('input[name="field_category['+ cat_no +'][subform][field_skills]['+ skill_no +'][subform][field_skill_level_information]['+ key +'][subform][field_level_header][0][value]"]').val(obj[key][0]);
          textarea_id = $("textarea[name='field_category["+ cat_no +"][subform][field_skills]["+ skill_no +"][subform][field_skill_level_information]["+ key +"][subform][field_level_description][0][value]']").attr('id');
          if (textarea_id) {
            textarea_id = textarea_id.replace('#', '');
            CKEDITOR.instances[textarea_id].setData(obj[key][1]);
          }
        });
      }
      $('.field--name-field-number-of-levels').trigger("change");
    }
  };
})(jQuery);
