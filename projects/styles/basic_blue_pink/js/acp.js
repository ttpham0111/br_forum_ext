$(document).ready(function(){
  var i = 0;
  $('#prj-add-status').click(function()
  {
    var new_status = document.createElement('li');
    new_status.className = 'prj-acp-statuses';
    new_status.innerHTML = '<dl>' +
                             '<dt>' +
                               '<div>' +
                                 '<input type="text" name="prj_new_status_names[]" size="20" maxlength="120" tabindex="2" />' +
                               '</div>' +
                             '</dt>' +
                             '<dd>' +
                               '<div></div>' +
                               '\n<div>' +
                                 '<input type="checkbox" name="prj_new_statuses_primary[]" value="'+i+'" />' +
                               '</div>' +
                             '</dd>' +
                           '</dl>';
    $('.prj-acp-status-list').append(new_status);
    ++i;
  });

  $('#prj-add-release-code').click(function()
  {
    var new_code = document.createElement('li');
    new_code.className = 'prj-acp-statuses';
    new_code.innerHTML = '<dl>' +
                           '<dt>' +
                             '<div>' +
                               '<input type="text" name="prj_new_release_codes[]" size="15" maxlength="10" tabindex="2" />' +
                             '</div>' +
                           '</dt>' +
                           '<dd>' +
                             '<div>' +
                               '<input type="number" name="prj_new_code_indexes[]" size="3" min="0" max="999" value="0" />' +
                             '</div>' +
                           '</dd>' +
                         '</dl>';
    $('.prj-acp-release-code-list').append(new_code);
  });
});