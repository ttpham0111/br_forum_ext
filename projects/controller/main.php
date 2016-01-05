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

  /** @var \phpbb\notification\manager */
  private $notification_manager;
  
  /** @var \phpbb\request\request */
  private $request;

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
    \phpbb\notification\manager               $notification_manager,
    \phpbb\request\request                    $request,
    \phpbb\template\template                  $template,
    \phpbb\user                               $user,
    \ttpham\projects\services\projects_helper $projects_helper,
                                              $phpEx,
                                              $root_path,
                                              $table_prefix
  )
  {
    $this->auth                 = $auth;
    $this->config               = $config;
    $this->db                   = $db;
    $this->notification_manager = $notification_manager;
    $this->request              = $request;
    $this->template             = $template;
    $this->user                 = $user;
    $this->projects_helper      = $projects_helper;
    $this->root_path            = $root_path;
    $this->phpEx                = $phpEx;
    $this->table_prefix         = $table_prefix;
  }

  /**
  * Event: core.index_modify_page_title.
  *
  * Displays the project tables above the forum body.
  */
  public function display_project_tables()
  {
    // If bot, don't waste time querying database.
    if ($this->user->data['is_bot'])
      return;

    $this->user->add_lang_ext('ttpham/projects', 'projects');

    $this->display_projects_table();
    $this->display_releases_table();
  }

  /**
  * Route: /projects/stageup/{topic_id}
  *
  * Handle moving the project to the next stage,
  * or releasing the project.
  *
  * @param   int    @topic_id          Topic id of the project.
  *          bool   @release_project   True if the project is to be released.
  */
  public function stageup($topic_id)
  {
    // Grab forum id for redirect.
    $forum_id = $this->projects_helper->get_forum_id($topic_id);
    $redirect = append_sid("{$this->phpbb_root_path}viewtopic.$this->phpEx", "f=$forum_id&amp;t=$topic_id");

    // If the user is not registered, we don't do anything.
    if (!$this->user->data['is_registered'])
      redirect($redirect);

    // Ask the user for confirmation.
    if (!confirm_box(true))
    {
      $this->user->add_lang_ext('ttpham/projects', 'projects');
      $release_project = (bool) $this->request->variable('release_project', false);
      $request_release = (bool) $this->request->variable('request_release', false);
      $next_stage      = $this->projects_helper->get_next_stage($topic_id);
      $confirm_text    = $this->user->lang['PRJ_CONFIRM'] . ' ';
      $confirm_append  = ($release_project)
                         ? (($request_release)
                           ? $this->user->lang['PRJ_REQUEST_RELEASE']
                           : $this->user->lang['PRJ_RELEASE_PROJECT'])
                         : $this->user->lang['PRJ_STAGEUP'] . ' ' . $next_stage['status_name'];
      confirm_box(false, $confirm_text . strtolower($confirm_append) . '?');
      redirect($redirect);
    }


    // If the project needs to be released, we don't handle that here.
    if ($this->projects_helper->is_last_stage($topic_id) ||
        (bool) $this->request->variable('release_project', false))
      return $this->release_project($topic_id);

    $rowset = $this->projects_helper->get_project_data(
                array('topic_id'          => $topic_id),
                array('SELECT'            => array('t.topic_poster',
                                                   'p.project_id',
                                                   'p.project_title',
                                                   'p.current_stage_id'
                                             ),
                      'JOIN_TOPICS_TABLE' => true
                )
    );

    // No project data returned means user is trying to stage up
    // a topic that isn't a project.
    if (!$rowset)
      redirect($redirect);
    else
      $row = $rowset[0];

    $project_data = array();
    $project_data['project_id']       = $row['project_id'];
    $project_data['topic_id']         = $topic_id;
    $project_data['current_stage_id'] = $row['current_stage_id'];
    $project_data['project_title']    = $row['project_title'];

    // No project current stage means user entered from typing
    // in URL and this topic isn't actually a project.
    if (!isset($project_data['current_stage_id']))
      redirect($redirect);

    // Check if user has permission to change the stage of the project.
    if ($this->user->data['user_id'] != $row['topic_poster'] &&
        !$this->auth->acl_get('m_prj_stageup'))
      redirect($redirect);

    // Move up the stage of the project.
    $next_stage = $this->projects_helper->get_next_stage($project_data['topic_id']);
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
    redirect($redirect);
  }

  /**
  * Set template variables for the projects and ongoing tables.
  */
  private function display_projects_table()
  {
    $tpl_loopname_new     = 'prj_new_projects_table';
    $tpl_loopname_ongoing = 'prj_ongoing_projects_table';

    // Grab config variables.
    $num_projects   = (int) $this->config['prj_number_of_projects_to_display'];
    $days_threshold = (int) $this->config['prj_days_threshold'];

    // Grab projects forum ids.
    $projects_forum_ids = $this->projects_helper->get_projects_forum_ids();

    // Used to keep track for balancing the table.
    $default_size = (int) $this->config['prj_default_table_size'];
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
                      'WHERE'             => '(p.current_stage_id = stg.stage_id OR p.current_stage_id = 0) AND ' . $this->db->sql_in_set('t.forum_id', $projects_forum_ids),
                      'ORDER_BY'          => 'stg.project_deadline ASC'
                )
    );

    if ($num_projects === 0)
      $limit = -1;
    else
      $limit = $num_projects;

    if ($rowset)
    {
      foreach ($rowset as $project)
      {
        // Only show requested number of projects.
        if ($limit === 0)
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

    $table_size = ($balance_factor > 0)
                  ? $default_size
                  : (($diff > 0)
                    ? $tpl_new_projects_size
                    : $tpl_ongoing_projects_size);
    if ((int) $this->config['prj_projects_table_size'] !== $table_size)
      $this->config->set('prj_projects_table_size', $table_size);
  }

  /**
  * Sets the template variables for the releases table.
  */
  private function display_releases_table($tpl_loopname = 'prj_releases_table')
  {
    // If num_releases is 0, display as many rows as the projects table.
    $num_releases = (int) $this->config['prj_number_of_releases_to_display'];
    if ($num_releases === 0)
      $num_releases = (int) $this->config['prj_projects_table_size'];

    // Grab releases forum ids.
    $releases_forum_ids = $this->projects_helper->get_releases_forum_ids();
    
    $display_empty = false;
    if (!sizeof($releases_forum_ids))
      $display_empty = true;

    // Grab topics in releases table.
    $sql = 'SELECT t.topic_id, t.topic_title, t.forum_id
            FROM ' . $this->table_prefix . 'prj_releases r
            LEFT JOIN ' . TOPICS_TABLE . ' t
              ON t.topic_id = r.topic_id
            WHERE ' . $this->db->sql_in_set('t.forum_id', $releases_forum_ids) . '
              AND t.topic_id IS NOT NULL
              AND t.topic_visibility = 1
              AND t.forum_id <> 0
            ORDER BY t.topic_time DESC';
    $result = $this->db->sql_query_limit($sql, $num_releases);
    $rowset = $this->db->sql_fetchrowset($result);
    $this->db->sql_freeresult($results);

    if ($rowset)
    {
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
    }

    // Fill the table with empty entries if needed.
    for ($i = ($display_empty) ? 0 : sizeof($rowset); $i < $num_releases; ++$i)
      $this->template->assign_block_vars($tpl_loopname, array());

    $this->template->assign_vars(array(
      strtoupper($tpl_loopname) . '_DISPLAY' => true
    ));
  }

  /**
  * Handle releasing the project.
  *
  * The user is routed to posting.php with an extra request variable
  * that will tell the script to prefill the forms with data from this
  * project through an event hook.
  *
  * If the user does not have permission to release the project,
  * request a mod to release the project instead.
  *
  * @param    int   @topic_id    The project's topic id.
  */
  private function release_project($topic_id)
  {
    // Grab forum id for redirect.
    $forum_id = $this->projects_helper->get_forum_id($topic_id);
    $bad_redirect = append_sid("{$this->root_path}viewtopic.$this->phpEx", "f=$forum_id&amp;t=$topic_id");

    // Is this topic in the projects forum?
    if (!$this->projects_helper->is_valid_forum($forum_id))
      redirect($bad_redirect);

    // Is this project on the last stage?
    if (!$this->projects_helper->is_last_stage($topic_id))
      redirect($bad_redirect);

    // Does the user have permission to release this project?
    if (!$this->auth->acl_get('m_prj_stageup'))
    {
      // If the user does not have permission to release,
      // and is requesting to release, handle it elsewhere.
      if ((bool) $this->request->variable('request_release', false))
        return $this->request_release($forum_id, $topic_id);
      redirect($bad_redirect);
    }

    // If no releases forum, don't do anything.
    $releases_forum_id = $this->projects_helper->get_releases_forum_id($forum_id);
    if ($releases_forum_id === 0)
      redirect($bad_redirect);

    // Success.
    $redirect = append_sid("{$this->root_path}posting.$this->phpEx", "mode=post&amp;f=$releases_forum_id&amp;prj_topic_id=$topic_id");
    redirect($redirect);
  }

  /**
  * Request a moderator to release this project.
  *
  * @param  int  @forum_id   The forum id of the project.
  *         int  @topic_id   The topic id of the project.
  */
  private function request_release($forum_id, $topic_id)
  {
    $viewtopic = append_sid("{$this->root_path}viewtopic.$this->phpEx", "f=$forum_id&amp;t=$topic_id");

    // Caller already did permission checks...

    // Except for checking if user is the topic poster.
    $sql = 'SELECT topic_poster
            FROM ' . TOPICS_TABLE . '
            WHERE topic_id = ' . $topic_id;
    $result       = $this->db->sql_query_limit($sql, 1);
    $topic_poster = $this->db->sql_fetchfield('topic_poster');
    $this->db->sql_freeresult($result);
    if ($this->user->data['user_id'] == $topic_poster)
    {
      $this->projects_helper->request_release($topic_id);

      // Grab missing variables.
      $sql = 'SELECT forum_name
              FROM ' . FORUMS_TABLE . '
              WHERE forum_id = ' . $forum_id;
      $result     = $this->db->sql_query_limit($sql, 1);
      $forum_name = $this->db->sql_fetchfield('forum_name');
      $this->db->sql_freeresult($result);

      $topic_title = $this->projects_helper->get_project_full_title(false, $topic_id);

      // Send a notification to mods when a project needs to be released.
      $project_data = array(
        'forum_id'      => $forum_id,
        'forum_name'    => $forum_name,
        'topic_id'      => $topic_id,
        'poster_id'     => $topic_poster,
        'post_username' => $this->user->data['user_name'],
        'topic_title'   => $topic_title
      );
      $this->notification_manager->add_notifications(
        array('ttpham.projects.notification.type.release_request'),
        $project_data
      );
    }
    redirect($viewtopic);
  }
}