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

    if ($request->is_set_post('submit'))
    {
      if (!check_form_key($form_name))
      {
        trigger_error($user->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
      }
    }

    $tpl_loopname = 'prj_statuses';
    $statuses = $this->projects_helper->get_statuses();
    foreach ($statuses as $status)
    {
      $tpl_ary = array(
        'STATUS_ID'   => $status['status_id'],
        'STATUS_NAME' => $status['status_name']
      );

      $template->assign_block_vars($tpl_loopname, $tpl_ary);
    }
  }

  private function handle_settings()
  {
    global $config, $request, $template, $user;

    $form_name = 'acp_projects';
    add_form_key($form_name);

    if ($request->is_set_post('submit'))
    {
      if (!check_form_key($form_name))
      {
        trigger_error($user->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
      }

      $default_table_size = $request->variable('prj_default_table_size', 0);
      $config->set('prj_default_table_size', $default_table_size);

      $releases_id = $request->variable('prj_releases_forum_id', 0);
      $config->set('prj_releases_forum_id', $releases_id);
      $num_releases = $request->variable('prj_number_of_releases_to_display', 0);
      $config->set('prj_number_of_releases_to_display', $num_releases);

      $project_ids = $request->variable('prj_project_forum_ids', '');
      $config->set('prj_project_forum_ids', $project_ids);
      $num_projects = $request->variable('prj_number_of_projects_to_display', 0);
      $config->set('prj_number_of_projects_to_display', $num_projects);
      $days_threshold = $request->variable('prj_days_threshold', 0);
      $config->set('prj_days_threshold', $days_threshold);
      $allow_projects_form = $request->variable('prj_allow_projects_form', false);
      $config->set('prj_allow_projects_form', $allow_projects_form);


      if ($request->variable('prj_reset_database', false))
      {
        $this->reset_database($releases_id, $num_releases, $project_ids, $num_projects);
      }
      trigger_error($user->lang('ACP_PROJECTS_SETTINGS_SAVED') . adm_back_link($this->u_action));
    }

    $template->assign_vars(array(
      'U_ACTION' => $this->u_action,

      'PRJ_DEFAULT_TABLE_SIZE' => $config['prj_default_table_size'],

      'PRJ_RELEASES_FORUM_ID' => $config['prj_releases_forum_id'],
      'PRJ_NUMBER_OF_RELEASES_TO_DISPLAY' => (isset($config['prj_number_of_releases_to_display'])) ? $config['prj_number_of_releases_to_display'] : 0,

      'PRJ_PROJECT_FORUM_IDS' => (isset($config['prj_project_forum_ids'])) ? $config['prj_project_forum_ids'] : '',
      'PRJ_NUMBER_OF_PROJECTS_TO_DISPLAY' => (isset($config['prj_number_of_projects_to_display'])) ? $config['prj_number_of_projects_to_display'] : 0,
      'PRJ_DAYS_THRESHOLD' => (isset($config['prj_days_threshold'])) ? $config['prj_days_threshold'] : 0,
      'PRJ_ALLOW_PROJECTS_FORM' => (isset($config['prj_allow_projects_form'])) ? $config['prj_allow_projects_form'] : false,

      'PRJ_RESET_DATABASE' => (isset($config['prj_reset_database'])) ? $config['prj_reset_database'] : false,
    ));
  }

  private function reset_database($releases_id, $num_releases, $project_ids, $num_projects)
  {
    global $db, $table_prefix;

    // Drop all rows from projects and releases table.
    $sql = 'TRUNCATE TABLE ' . $table_prefix . 'prj_releases';
    $db->sql_query($sql);
    // $sql = 'TRUNCATE TABLE ' . $table_prefix . 'prj_projects';
    // $db->sql_query($sql);

    // Add data to projects table.
    // $project_ids = explode(',', $project_ids);

    // $sql = 'SELECT topic_id, topic_title
    //   FROM ' . TOPICS_TABLE . '
    //   WHERE ' . $db->sql_in_set('forum_id', $project_ids) . '
    //     AND topic_type = 0
    //   ORDER BY topic_time DESC';

    // $result = $db->sql_query($sql);

    // $topic_ids = array();
    // while ($row = $db->sql_fetchrow($result))
    //   $rowset[] = $row;
    // $db->sql_freeresult($result);

    // if(sizeof($rowset))
    // {
    //   $topics = array();
    //   foreach($rowset as $row)
    //   {
    //     $topic_id      = $row['topic_id'];
    //     $project_title = $row['topic_title'];

    //     // $pattern = '/^\[(.*?)\]\s*?(.*)\s*?[\[\(](.*?)[\]\)]$/';
    //     // $topic_title = trim(preg_replace($pattern, '$2', $row['topic_title']));
    //     // $topic_status = trim(preg_replace($pattern, '$1', $row['topic_title']));
    //     // $topic_date = trim(preg_replace($pattern, '$3', $row['topic_title']));

    //     $topics[] = array(
    //       'topic_id' => $topic_id,
    //       'project_title' => $project_title,
    //     );
    //   }
    //   $db->sql_multi_insert($table_prefix . 'prj_projects', $topics);
    // }

    // Add data to releases table.
    $sql = 'SELECT topic_id
      FROM ' . TOPICS_TABLE . '
      WHERE forum_id = ' . $releases_id . '
      ORDER BY topic_time DESC';

    $result = $db->sql_query($sql);
    $topic_ids = array();
    while ($row = $db->sql_fetchrow($result))
      $topic_ids[] = array('topic_id' => $row['topic_id']);
    $db->sql_freeresult($result);

    if(sizeof($topic_ids))
      $db->sql_multi_insert($table_prefix . 'prj_releases', $topic_ids);
  }
}
