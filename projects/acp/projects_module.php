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

class projects_module
{
  /** @var string */
  public $u_action;

  /** @var \ttpham\projects\services\projects_helper */
  private $projects_helper;

  public function __construct()
  {
    global $phpbb_container;
    $this->projects_helper = $phpbb_container->get('ttpham.projects.projects_helper');
  }

  function main($id, $mode)
  {
    global $user;

    $user->add_lang('common');

    switch ($mode)
    {
      case 'manage_projects':
        $this->tpl_name = 'acp_manage_projects';
        $this->page_title = $user->lang('ACP_MANAGE_PROJECTS');
        $this->manage_projects();
        break;
      case 'settings':
      // no break
      default:
        $this->tpl_name = 'acp_projects';
        $this->page_title = $user->lang('ACP_PROJECTS_SETTINGS');
        $this->handle_settings();
    }
  }

  private function manage_projects()
  {
    global $config, $request, $template, $user;

    $form_name = 'acp_manage_projects';
    add_form_key($form_name);

    $statuses_in_use = $this->projects_helper->get_statuses_in_use();

    if ($request->is_set_post('submit'))
    {
      if (!check_form_key($form_name))
      {
        trigger_error($user->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
      }

      /**
      * Handle status actions.
      */

      // Change existing statuses.
      $primary_statuses   = $request->variable('prj_primary_status_ids', array(''));
      $statuses_to_delete = $request->variable('prj_delete_status_ids', array(''));

      if (sizeof($primary_statuses) !== 0)
        $this->projects_helper->set_primary_statuses($primary_statuses);

      foreach ($statuses_to_delete as $status_id)
      {
        if (!in_array($status_id, $statuses_in_use))
          $this->projects_helper->delete_status($status_id);
      }

      // Add new status.
      $new_statuses = $request->variable('prj_new_status_names', array(''));
      $primary_ary  = $request->variable('prj_new_statuses_primary', array(''));
      for ($i = 0; $i < sizeof($new_statuses); ++$i)
      {
        $new_status = $new_statuses[$i];
        $is_primary = (in_array($i, $primary_ary));

        if ($new_status !== '')
          $this->projects_helper->add_new_status($new_status, $is_primary);
      }

      /**
      * Handle release code actions.
      */

      // Change existing release codes.
      $code_indexes    = $request->variable('prj_acp_code_indexes', array(''));
      $codes_to_delete = $request->variable('prj_delete_release_code_ids', array(''));

      $release_codes = $this->projects_helper->get_release_codes();
      for ($i = 0; $i < sizeof($release_codes); ++$i)
      {
        $release_code = $release_codes[$i]['release_code_id'];
        $code_index   = $code_indexes[$i];
        if ($release_codes[$i]['release_code_index'] !== $code_index)
          $this->projects_helper->modify_release_code_index($release_code, $code_index);
      }
      
      foreach ($codes_to_delete as $code_id)
        $this->projects_helper->delete_release_code($code_id);

      // Add new release codes.
      $new_release_codes       = $request->variable('prj_new_release_codes', array(''));
      $indexes                 = $request->variable('prj_new_code_indexes', array(''));
      for ($i = 0; $i < sizeof($new_release_codes); ++$i)
      {
        $new_release_code = $new_release_codes[$i];
        $index            = $indexes[$i];

        if ($new_release_code !== '')
          $this->projects_helper->add_new_release_code($new_release_code, $index);
      }

      trigger_error($user->lang('ACP_MANAGE_PROJECTS_SAVED') . adm_back_link($this->u_action));
    }

    // Show statuses.
    $tpl_loopname = 'prj_acp_statuses';
    $statuses     = $this->projects_helper->get_statuses();
    foreach ($statuses as $status)
    {
      $tpl_ary = array(
        'STATUS_ID'      => $status['status_id'],
        'STATUS_NAME'    => $status['status_name'],
        'PRIMARY_STATUS' => $status['primary_status'],
        'IN_USE'         => in_array($status['status_id'], $statuses_in_use)
      );
      $template->assign_block_vars($tpl_loopname, $tpl_ary);
    }

    // Show release codes.
    $tpl_loopname  = 'prj_acp_release_codes';
    $release_codes = $this->projects_helper->get_release_codes();
    foreach ($release_codes as $release_code)
    {
      $tpl_ary = array(
        'CODE_ID'    => $release_code['release_code_id'],
        'FORUM_IDS'  => $release_code['forum_ids'],
        'CODE_NAME'  => $release_code['release_code_name'],
        'CODE_INDEX' => $release_code['release_code_index']
      );
      $template->assign_block_vars($tpl_loopname, $tpl_ary);
    }

    // Include dependencies.
    $template->assign_vars(array(
      'PRJ_INCLUDE_ACP_DEPENDENCIES' => true)
    );
  }

  private function handle_settings()
  {
    global $config, $request, $template, $user;

    $form_name = 'acp_projects';
    add_form_key($form_name);

    if ($request->is_set_post('submit'))
    {
      if (!check_form_key($form_name))
        trigger_error($user->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);

      $default_table_size   = $request->variable('prj_default_table_size', 0);
      $releases_id          = $request->variable('prj_releases_forum_id', 0);
      $num_releases         = $request->variable('prj_number_of_releases_to_display', 0);
      $move_after_release   = $request->variable('prj_move_after_release', false);
      $move_lock_topics     = $request->variable('prj_move_lock_topics', false);
      $move_rules           = $request->variable('prj_move_rules', '');
      $project_ids          = $request->variable('prj_project_forum_ids', '');
      $num_projects         = $request->variable('prj_number_of_projects_to_display', 0);
      $days_threshold       = $request->variable('prj_days_threshold', 0);
      $prune_releases_gc    = $request->variable('prj_prune_releases_gc', 7);
      $releases_table_ratio = $request->variable('prj_releases_table_ratio', 2.0);
      $prune_projects_gc    = $request->variable('prj_prune_projects_gc', 7);

      $config->set('prj_default_table_size', $default_table_size);
      $config->set('prj_releases_forum_id', $releases_id);
      $config->set('prj_number_of_releases_to_display', $num_releases);
      $config->set('prj_move_after_release', $move_after_release);
      $config->set('prj_move_lock_topics', $move_lock_topics);
      $config->set('prj_move_rules', $move_rules);
      $config->set('prj_project_forum_ids', $project_ids);
      $config->set('prj_number_of_projects_to_display', $num_projects);
      $config->set('prj_days_threshold', $days_threshold);
      $config->set('prj_prune_releases_gc', ($prune_releases_gc * 24 * 60 * 60));
      $config->set('prj_releases_table_ratio', $releases_table_ratio);
      $config->set('prj_prune_projects_gc', ($prune_projects_gc * 24 * 60 * 60));

      if ($request->variable('prj_reset_database', false))
        $this->reset_database();
      if ($request->variable('prj_prune_database', false))
        $this->prune_database();

      trigger_error($user->lang('ACP_PROJECTS_SETTINGS_SAVED') . adm_back_link($this->u_action));
    }

    $template->assign_vars(array(
      'U_ACTION' => $this->u_action,

      'PRJ_DEFAULT_TABLE_SIZE' => (isset($config['prj_default_table_size'])) ? $config['prj_default_table_size'] : 0,

      'PRJ_RELEASES_FORUM_ID'             => (isset($config['prj_releases_forum_id'])) ? $config['prj_releases_forum_id'] : 0,
      'PRJ_NUMBER_OF_RELEASES_TO_DISPLAY' => (isset($config['prj_number_of_releases_to_display'])) ? $config['prj_number_of_releases_to_display'] : 0,
      'PRJ_MOVE_AFTER_RELEASE'            => (isset($config['prj_move_after_release'])) ? $config['prj_move_after_release'] : false,
      'PRJ_MOVE_LOCK_TOPICS'              => (isset($config['prj_move_lock_topics'])) ? $config['prj_move_lock_topics'] : false,
      'PRJ_MOVE_RULES'                    => (isset($config['prj_move_rules'])) ? $config['prj_move_rules'] : '',
      
      'PRJ_PROJECT_FORUM_IDS'             => (isset($config['prj_project_forum_ids'])) ? $config['prj_project_forum_ids'] : '',
      'PRJ_NUMBER_OF_PROJECTS_TO_DISPLAY' => (isset($config['prj_number_of_projects_to_display'])) ? $config['prj_number_of_projects_to_display'] : 0,
      'PRJ_DAYS_THRESHOLD'                => (isset($config['prj_days_threshold'])) ? $config['prj_days_threshold'] : 0,

      'PRJ_PRUNE_RELEASES_GC'    => (isset($config['prj_prune_releases_gc'])) ? ($config['prj_prune_releases_gc'] / (60 * 60 * 24)) : 7,
      'PRJ_RELEASES_TABLE_RATIO' => (isset($config['prj_releases_table_ratio'])) ? $config['prj_releases_table_ratio'] : 2.0,
      'PRJ_PRUNE_PROJECTS_GC'    => (isset($config['prj_prune_projects_gc'])) ? ($config['prj_prune_projects_gc'] / (60 * 60 * 24)) : 7,

      'PRJ_RESET_DATABASE' => false
    ));
  }

  /**
  * Resets the releases table so that it is filled with all releases
  * in the reset forums.
  */
  private function reset_database()
  {
    global $phpbb_container;
    $projects_helper = $phpbb_container->get('ttpham.projects.projects_helper');
    $projects_helper->prune_releases(true);
  }

  /**
  * Prunes the projects, stages, and releases table.
  */
  private function prune_database()
  {
    global $phpbb_container;
    $projects_helper = $phpbb_container->get('ttpham.projects.projects_helper');
    $projects_helper->prune_releases();
    $projects_helper->prune_projects();
  }
}
