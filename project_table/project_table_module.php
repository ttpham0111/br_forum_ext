<?php
/**
 *
 * @package Project Tables Extension
 * @copyright //
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Create projects table specifically for breaking-records.com.
 */

namespace ttpham\project_table\acp;

class project_table_module
{
  public $u_action;

  function main($id, $mode)
  {
    global $config, $phpbb_extension_manager, $request, $template, $user;

    $user->add_lang('acp/common');
    $this->tpl_name = 'acp_project_table';
    $this->page_title = $user->lang('PROJECT_TABLE');

    $form_key = 'acp_project_table';
    add_form_key($form_key);

    if ($request->is_set_post('submit'))
    {
      if (!check_form_key($form_key))
      {
        trigger_error($user->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
      }

      $pt_number = $request->variable('pt_number', 5);
      $config->set('pt_number', $pt_number);

      trigger_error($user->lang('CONFIG_UPDATED') . adm_back_link($this->u_action));
    }

    $template->assign_vars(array(
      'PT_NUMBER' => isset($config['pt_number']) ? $config['pt_number'] : '',

      'U_ACTION'  => $this->u_action
    ));
  }
}