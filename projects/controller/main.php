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

namespace ttpham\projects\controller;

class main
{
  /** @var \phpbb\auth\auth */
  private $auth;

  /** @var \phpbb\config\config */
  private $config;

  /** @var \phpbb\db\driver\driver_interface */
  private $db;

  /** @var \phpbb\template\template */
  private $template;

  /** @var \phpbb\user */
  private $user;

  /** @var \ttpham\projects\services\projects_helper */
  private $projects_helper;

  /** @var string PHP extension */
  private $phpEx;

  /** @var string phpBB root path */
  private $root_path;

  /** @var string */
  private $table_prefix;

  public function __construct(
    \phpbb\auth\auth                          $auth,
    \phpbb\config\config                      $config,
    \phpbb\db\driver\driver_interface         $db,
    \phpbb\template\template                  $template,
    \phpbb\user                               $user,
    \ttpham\projects\services\projects_helper $projects_helper,
                                              $phpEx,
                                              $root_path,
                                              $table_prefix
  )
  {
    $this->auth            = $auth;
    $this->config          = $config;
    $this->db              = $db;
    $this->template        = $template;
    $this->user            = $user;
    $this->projects_helper = $projects_helper;
    $this->root_path       = $root_path;
    $this->phpEx           = $phpEx;
    $this->table_prefix    = $table_prefix;
  }

  public function display_project_tables()
  {
    // If bot, don't waste time querying database.
    if ($this->user->data['is_bot'])
      return;

    $this->user->add_lang_ext('ttpham/projects', 'projects');

    $num_projects = $this->display_projects_table();
    $this->display_releases_table($num_projects);
  }

  private function display_releases_table($num_projects, $tpl_loopname = 'prj_releases_table')
  {
    // Grab config variables.
    $num_releases = $this->config['prj_number_of_releases_to_display'];
    if ($num_releases == 0)
      $num_releases = $num_projects;

    // Grab topics in releases table.
    $sql = 'SELECT t.topic_id, t.topic_title, t.forum_id
            FROM ' . $this->table_prefix . 'prj_releases r
            LEFT JOIN ' . TOPICS_TABLE . ' t
              ON t.topic_id = r.topic_id
            WHERE t.topic_id IS NOT NULL
              AND t.topic_visibility = 1
            ORDER BY t.topic_time DESC';
    $result = $this->db->sql_query_limit($sql, $num_releases);

    $rowset = array();
    while ($row = $this->db->sql_fetchrow($result))
      $rowset[] = $row;
    $this->db->sql_freeresult($results);

    // No topics in releases table.
    if (!sizeof($rowset))
      return;

    foreach ($rowset as $row)
    {
      $project_name = $row['topic_title'];
      $topic_id     = $row['topic_id'];
      $forum_id     = $row['forum_id'];
      
      $view_topic_url = append_sid("{$this->root_path}viewtopic.$this->phpEx", 'f=' . $forum_id . '&amp;t=' . $topic_id);

      $tpl_ary = array(
        'PROJECT_NAME'   => $project_name,
        'U_VIEW_TOPIC'   => $view_topic_url
      );

      $this->template->assign_block_vars($tpl_loopname, $tpl_ary);
    }

    $this->template->assign_vars(array(
      strtoupper($tpl_loopname) . '_DISPLAY' => true
    ));
  }

  private function display_projects_table()
  {
    $tpl_loopname_new     = 'prj_new_projects_table';
    $tpl_loopname_ongoing = 'prj_ongoing_projects_table';

    // Grab config variables.
    $num_projects   = $this->config['prj_number_of_projects_to_display'];
    $days_threshold = $this->config['prj_days_threshold'];
    $project_forums = explode(',', $this->config['prj_project_forum_ids']);

    // Used to keep track for balancing the table.
    $default_size = $this->config['prj_default_table_size'];
    $tpl_new_projects_size = 0;
    $tpl_ongoing_projects_size = 0;

    // Will only show projects that are visible in the topics table.
    $rowset = $this->projects_helper->get_project_data(
                array(),
                array('SELECT'            => array('t.topic_id',
                                                   't.forum_id',
                                                   't.topic_time',
                                                   'p.project_id',
                                                   'p.project_title',
                                                   'p.current_stage_id',
                                                   'stg.project_deadline',
                                                   'sts.status_name'
                                             ),
                      'JOIN_TOPICS_TABLE' => true,
                      'WHERE'             => '(p.current_stage_id = stg.stage_id OR p.current_stage_id = 0)',
                      'ORDER_BY'          => 'stg.project_deadline ASC'
                )
    );

    if ($num_projects == 0)
      $limit = -1;
    else
      $limit = $num_projects;

    // No topics in projects table.
    if (!$rowset)
      return $default_size;

    foreach ($rowset as $project)
    {
      // Only show requested number of projects.
      if ($limit == 0)
        break;
      --$limit;

      // Set URL of project.
      $view_topic_url = append_sid("{$this->root_path}viewtopic.$this->phpEx",'f=' . $project['forum_id'] . '&amp;t=' . $project['topic_id']);

      $project_deadline = $project['project_deadline'];
      $project_status   = $project['status_name'];
      $now = time();

      if (!$project_deadline || !is_string(getType($project_deadline)))
        $project_deadline = '0';

      // If project has been open for more than the threshold
      // consider it an ongoing project.
      $ongoing_project = false;
      if (strtotime('+' . $days_threshold . ' days', $project['topic_time']) < $now)
        $ongoing_project = true;

      // Set project icon.
      $project_icon = 'prj-' . str_replace(' ', '-', strtolower($project_status));

      // Overwrite status if late.
      $project_late = false;
      if ($project_deadline && $now > $project_deadline)
      {
        $project_status = 'Late';
        $project_late   = true;
        if ($project_icon !== '')
          $project_icon .= '-late';
      }

      $project_name = $this->projects_helper->build_topic_title($project_status,
                                                                $project['project_title'],
                                                                $project_deadline);
      $tpl_ary = array(
        'PROJECT_NAME' => $project_name,
        'PROJECT_LATE' => $project_late,
        'PROJECT_ICON' => $project_icon,
        'U_VIEW_TOPIC' => $view_topic_url
      );

      if ($ongoing_project)
      {
        ++$tpl_ongoing_projects_size;
        $this->template->assign_block_vars($tpl_loopname_ongoing, $tpl_ary);
      }
      else
      {
        ++$tpl_new_projects_size;
        $this->template->assign_block_vars($tpl_loopname_new, $tpl_ary);
      }
    }

    // Balance the projects table.
    $diff = $tpl_new_projects_size - $tpl_ongoing_projects_size;
    for ($i = 0; $i < $diff; ++$i)
      $this->template->assign_block_vars($tpl_loopname_ongoing, array());
    for ($j = 0; $j > $diff; --$j)
      $this->template->assign_block_vars($tpl_loopname_new, array());

    // Fill till default size.
    if ($diff > 0)
      $balance_factor = $default_size - $tpl_new_projects_size;
    else
      $balance_factor = $default_size - $tpl_ongoing_projects_size;

    for ($k = 0; $k < $balance_factor; ++$k)
    {
      $this->template->assign_block_vars($tpl_loopname_new, array());
      $this->template->assign_block_vars($tpl_loopname_ongoing, array());
    }

    $this->template->assign_vars(array(
      strtoupper($tpl_loopname_new) . '_DISPLAY' => true,
      strtoupper($tpl_loopname_ongoing) . '_DISPLAY' => true,
    ));

    return ($balance_factor > 0) ? $default_size : (($diff > 0) ? $tpl_new_projects_size : $tpl_ongoing_projects_size);
  }

  public function stageup($topic_id)
  {
    $rowset = $this->projects_helper->get_project_data(
                array('topic_id'          => $topic_id),
                array('SELECT'            => array('t.topic_poster',
                                                   't.forum_id',
                                                   'p.project_id',
                                                   'p.project_title',
                                                   'p.current_stage_id'
                                             ),
                      'JOIN_TOPICS_TABLE' => true
                )
    );

    if (!$rowset)
      return;
    else
      $row = $rowset[0];

    $forum_id = $row['forum_id'];

    $project_data = array();
    $project_data['project_id']       = $row['project_id'];
    $project_data['topic_id']         = $topic_id;
    $project_data['current_stage_id'] = $row['current_stage_id'];
    $project_data['project_title']    = $row['project_title'];

    // No project current stage means user entered from typing
    // in URL and this topic isn't actually a project.
    if (!$project_data['current_stage_id'])
    {
      redirect(append_sid("{$this->phpbb_root_path}viewtopic.$this->phpEx", "f=$forum_id&amp;t=$topic_id"));
      return;
    }

    // Check if user has permission to change the stage of the project.
    if ($this->user->data['user_id'] != $$row['topic_poster'] &&
        !$this->auth->acl_get('m_prj_stageup'))
    {
      redirect(append_sid("{$this->phpbb_root_path}viewtopic.$this->phpEx", "f=$forum_id&amp;t=$topic_id"));
      return;
    }

    // Move up the stage of the project.
    $next_stage = $this->projects_helper->get_next_stage(
                                            $project_data['project_id'],
                                            $project_data['current_stage_id']
                                          );
    $project_data['current_stage_id'] = $next_stage['stage_id'];
    $this->projects_helper->edit_project('update_stage', $project_data);

    $project_status   = $next_stage['status_name'];
    $project_title    = $project_data['project_title'];
    $project_deadline = $next_stage['project_deadline'];

    // Updates the topic title and first post title.
    $topic_title = $this->projects_helper->build_topic_title($project_status,
                                                             $project_title,
                                                             $project_deadline);
    $this->projects_helper->sync_topic_title($topic_id, $topic_title);
    redirect(append_sid("{$this->phpbb_root_path}viewtopic.$this->phpEx", "f=$forum_id&amp;t=$topic_id"));
  }
}