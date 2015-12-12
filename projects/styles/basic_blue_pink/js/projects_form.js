$(document).ready(function(){
  $('#prj-add-deadline').click(function()
  {
    var new_deadline = document.createElement('div');
    new_deadline.className = 'prj-form-group';
    new_deadline.innerHTML = '<div>' +
                               '<div class="prj-form-label"><label>Stage Name:</label></div>' +
                               '<input type="text" name="prj_stage_names[]" size="20" maxlength="120" tabindex="2" value" class="inputbox autowidth">' +
                             '</div>' +
                             '<div>' +
                               '<div class="prj-form-label"><label>Deadline Date:</label></div>' +
                               '<input type="text" name="" size="20" maxlength="120" tabindex="2" value="" class="inputbox autowidth prj-date" readonly="readonly">' +
                               '<input type="text" class="prj-date-hidden" name="prj_dates[]" hidden>' +
                             '</div>';
    $('.prj-form-inner').append(new_deadline);
  });

  $('.prj-form-inner').on('focus', '.prj-date', function()
  {
    $(this).datepicker({
      numberOfMonths : 3,
      showButtonPanel: true,
      altFormat      : '@',
      altField       : $(this).next(),
      dateFormat     : 'M dd, yy'
    });
  });
});