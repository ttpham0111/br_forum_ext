<?php
/**
 *
 * @package Cover Projects Extenion
 * @copyright (c) 2015 Tuan Pham (https://github.com/tuan91187dsf)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 * Extension to integrate the creation/display of cover projects.
 * Specifically designed for breaking-records.com
 *
 */

namespace ttpham\projects\acp;

use ttpham\projects\prefixes;

class projects_module
{
  public $u_action;

  function main($id, $mode)
  {
    global $config, $request, $template, $user;

    $user->add_lang('common');
    $this->tpl_name = 'acp_projects_settings';
    $this->page_title = $user->lang('ACP_PROJECTS_SETTINGS');

    $form_name = 'acp_projects_settings';
    add_form_key($form_name);

    $template->assign_vars(array(
      'PRJ_CONFIG' => prefixes::CONFIG
    ));

    if ($request->is_set_post('submit'))
    {
      if (!check_form_key($form_name))
      {
        trigger_error($user->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
      }

      $config->set(prefixes::CONFIG . 'display_above_forumbody', $request->variable(prefixes::CONFIG . 'display_above_forumbody', true));

      $config->set(prefixes::CONFIG . 'releases_forum_id', $request->variable(prefixes::CONFIG . 'releases_forum_id', 0));
      $config->set(prefixes::CONFIG . 'number_of_releases_to_display', $request->variable(prefixes::CONFIG . 'number_of_releases_to_display', 5));

      $config->set(prefixes::CONFIG . 'project_forum_ids', $request->variable(prefixes::CONFIG . 'project_forum_ids', ''));
      $config->set(prefixes::CONFIG . 'number_of_projects_to_display', $request->variable(prefixes::CONFIG . 'number_of_projects_to_display', 0));
    }

    $template->assign_vars(array(
      'U_ACTION' => $this->u_action,
      'PRJ_DISPLAY_ABOVE_FORUMBODY' => isset($config[prefixes::CONFIG . 'display_above_forumbody']) ? $config[prefixes::CONFIG . 'display_above_forumbody'] : true,

      'PRJ_RELEASES_FORUM_ID' => isset($config[prefixes::CONFIG . 'releases_forum_id']) ? $config[prefixes::CONFIG . 'releases_forum_id'] : 0,
      'PRJ_NUMBER_OF_RELEASES_TO_DISPLAY' => isset($config[prefixes::CONFIG . 'number_of_releases_to_display']) ? $config[prefixes::CONFIG . 'number_of_releases_to_display'] : 5,

      'PRJ_PROJECT_FORUM_IDS' => isset($config[prefixes::CONFIG . 'project_forum_ids']) ? $config[prefixes::CONFIG . 'project_forum_ids'] : '',
      'PRJ_NUMBER_OF_PROJECTS_TO_DISPLAY' => isset($config[prefixes::CONFIG . 'number_of_projects_to_display']) ? $config[prefixes::CONFIG . 'number_of_projects_to_display'] : 0
    ));
  }
}
