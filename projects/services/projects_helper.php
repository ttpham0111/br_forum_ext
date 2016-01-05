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

namespace ttpham\projects\services;

class projects_helper
{
  /** @var \phpbb\auth\auth */
  private $auth;
  
  /* @var \phpbb\config\config */
  private $config;

  /** @var \phpbb\db\driver\driver_interface */
  private $db;

  /** @var \phpbb\user */
  private $user;

  /** @var string */
  private $table_prefix;

  public function __construct(
    \phpbb\auth\auth                  $auth,
    \phpbb\config\config              $config,
    \phpbb\db\driver\driver_interface $db,
    \phpbb\user                       $user,
                                      $table_prefix
  )
  {
    $this->auth         = $auth;
    $this->config       = $config;
    $this->db           = $db;
    $this->user         = $user;
    $this->table_prefix = $table_prefix;
  }

  /*************
  * Validation *
  *************/
  // form_valid
  // in_valid_forum
  // is_valid_forum
  // is_special_topic
  // is_initial_release_request

  /******************
  * phpBB Functions *
  ******************/
  // get_forum_id
  // get_forum_name
  // get_forums_info
  // get_acl_f

  /*********************
  * Releases Functions *
  *********************/
  // get_releases_forum_id
  // get_releases_forum_ids
  // get_releases_forums

  // get_finished_forum_ids
  // get_finished_forums

  // add_new_releases
  // prune_releases

  // get_move_rule
  // get_move_rules
  // set_move_rules
  // get_to_forum_id

  // get_release_requests
  // request_release
  // unset_release_request

  // get_release_code
  // get_release_codes
  // add_new_release_code
  // increment_index
  // modify_release_code_index
  // delete_release_code

  /*********************
  * Projects Functions *
  *********************/
  // get_projects_forum_ids
  // get_projects_forums
  // get_project_data
  // edit_project
  // prune_projects

  // get_earliest_stage
  // get_next_stage
  // add_new_stage
  // guess_stage
  // is_last_stage
  // clear_stages

  // get_statuses
  // get_current_status
  // get_primary_statuses
  // get_statuses_in_use
  // add_new_status
  // set_primary_statuses
  // delete_status
  
  // get_project_full_title
  // sync_topic_title
  // build_topic_title
  // destruct_topic_title

////////////////////////// Validation //////////////////////////

  /**
  * Check conditions to see if this form is valid for projects.
  *
  * @param   Array  @event     The event holding all the variables from the post.
  *          bool   @releases  Is this form for the releases forums instead?
  *
  * @return  bool   @is_valid  True if valid, false otherwise.
  */
  public function form_valid($event, $releases = false)
  {
    $forum_id = $event['forum_id'];
    if (!$this->is_valid_forum($forum_id, $releases))
      return false;

    // If mode is not posting, or editing first post, don't show extra fields.
    $mode = $event['mode'];
    if ($mode != 'post' && $mode != 'edit')
      return false;

    // If mode is edit, check authorization and first post.
    else if ($mode == 'edit')
    {
      // We don't do anything if editting in releases forum.
      if ($releases)
        return false;

      // Check if user has permission to edit this post.
      $post_data = $event['post_data'];
      if ($this->user->data['user_id'] != $post_data['topic_poster'] &&
          !$this->auth->acl_get('m_edit', $forum_id))
        return false;

      // Check if this post is the first post in the topic.
      if ($post_data['topic_first_post_id'] != $post_data['post_id'])
        return false;
    }

    return true;
  }

  /**
  * Check if the forum is part of one of the special forums (releases/projects).
  *
  * @param   int   @topic_id      The id of the topic.
  *          bool  @releases      Check releases forum instead.
  *
  * @return  bool  @is_valid      True if valid, false otherwise.
  */
  public function in_valid_forum($topic_id, $releases = false)
  {
    if ($releases)
      $allowed_forum_ids = $this->get_releases_forum_ids();
    else
      $allowed_forum_ids = $this->get_projects_forum_ids();

    $sql = 'SELECT forum_id
            FROM ' . TOPICS_TABLE . '
            WHERE topic_id = ' . $topic_id;
    $result   = $this->db->sql_query($sql);
    $forum_id = (int) $this->db->sql_fetchfield('forum_id');
    $this->db->sql_freeresult($result);

    if (!in_array($forum_id, $allowed_forum_ids))
      return false;
    return true;
  }

  /**
  * Check if the forum is part of one of the special forums (releases/projects).
  *
  * @param   int   @forum_id      The id of the forum.
  *          bool  @releases      Check releases forum instead.
  *
  * @return  bool  @is_valid      True if valid, false otherwise.
  */
  public function is_valid_forum($forum_id, $releases = false)
  {
    if ($releases)
      $allowed_forum_ids = $this->get_releases_forum_ids();
    else
      $allowed_forum_ids = $this->get_projects_forum_ids();

    if (!in_array($forum_id, $allowed_forum_ids))
      return false;
    return true;
  }

  /**
  * Check if the topic is an announcement/sticky.
  *
  * @param    int    @topic_id    The id of the topic.
  */
  public function is_special_topic($topic_id)
  {
    $sql = 'SELECT topic_type
        FROM ' . TOPICS_TABLE . '
        WHERE topic_id = ' . $topic_id;
    $result     = $this->db->sql_query($sql);
    $topic_type = $this->db->sql_fetchfield('topic_type');
    $this->db->sql_freeresult($result);
    if ($topic_type !== '0')
      return true;
    return false;
  }

  /**
  * Check if a request to be released has already been sent
  * for this project.
  *
  * @param     int  @topic_id                        The topic_id of the project.
  *
  * @return    bool @is_initial_release_request      True if release_request sent is
  *                                                  set to false;
  */
  public function is_initial_release_request($topic_id)
  {
    $rowset = $this->get_project_data(
                array('topic_id' => $topic_id),
                array('SELECT'   => array('p.release_request_sent'))
    );
    if (!$rowset)
      return false;
    return !($rowset[0]['release_request_sent']);
  }

////////////////////////// phpBB Functions //////////////////////////

  /**
  * Gets the forum id of the topic.
  *
  * @param   int  @topic_id   The topic id.
  *
  * @return  int  @forum_id   The forum id.
  */
  public function get_forum_id($topic_id)
  {
    $sql = 'SELECT forum_id
            FROM ' . TOPICS_TABLE . '
            WHERE topic_id = ' . $topic_id;
    $result   = $this->db->sql_query_limit($sql, 1);
    $forum_id = $this->db->sql_fetchfield('forum_id');
    $this->db->sql_freeresult($result);
    return $forum_id;
  }

  /**
  * Get the forum name of the forum with the id provided.
  *
  * @param   int     @forum_id    The forum id.
  *
  * @return  string  @forum_name  The forum name.
  */
  public function get_forum_name($forum_id)
  {
    $sql = 'SELECT forum_name
            FROM ' . FORUMS_TABLE . '
            WHERE forum_id = ' . $forum_id;
    $result     = $this->db->sql_query($sql);
    $forum_name = $this->db->sql_fetchfield('forum_name');
    $this->db->sql_freeresult($result);
    return $forum_name;
  }

  /**
  * Gets the forum names of the forum ids given.
  *
  * @param   Array   @forum_ids   The ids of the forums to retrieve the names of.
  *
  * @return  Array   @forums      The array containing the forums id and names.
  */
  public function get_forums_info($forum_ids)
  {
    $sql = 'SELECT forum_id, forum_name
            FROM ' . FORUMS_TABLE . '
            WHERE ' . $this->db->sql_in_set('forum_id', $forum_ids);
    $result = $this->db->sql_query($sql);
    $rowset = $this->db->sql_fetchrowset($result);
    $this->db->sql_freeresult($result);
    return $rowset;
  }

  /**
  * Helper function to get forum_ids of those forums
  * with permissions for $opt set to true.
  *
  * @return   Array   @forum_ids   Return an array of forum ids,
  */
  private function get_acl_f($opt)
  {
    $forum_ids = array();
    $acl_f     = $this->auth->acl_getf($opt, true);
    foreach ($acl_f as $forum_id => $opt)
      $forum_ids[] = (int) $forum_id;
    return (sizeof($forum_ids)) ? $forum_ids : array(0);
  }

////////////////////////// Releases Functions //////////////////////////

  /**
  * Gets the releases forum id of the project.
  *
  * @param   int   @forum_id            The forum id of the project.
  *
  * @return  int   @releases_forum_id   The releases forum id of the project.
  */
  public function get_releases_forum_id($forum_id)
  {
    $move_rule = $this->get_move_rule($forum_id);
    return $move_rule[0]['forum_id'];
  }

  /**
  * Retrieves the releases forums by getting the forums
  * where mods can release projects.
  *
  * @return   Array   @releases_forum_ids   Return an array of forum ids,
  */
  public function get_releases_forum_ids()
  {
    if ($this->config['prj_projects_table_guest'])
    {
      $releases_forum_ids = array();
      $acl_list = $this->auth->acl_get_list(false, array('m_prj_release_project'));
      foreach($acl_list as $forum_id => $auth_option)
        $releases_forum_ids[] = $forum_id;
      return (sizeof($releases_forum_ids)) ? $releases_forum_ids : array(0);
    }
    else
    {
      $acl_f = $this->get_acl_f('m_prj_release_project');
      if (sizeof($acl_f) > 1 && $acl_f[0] !== 0)
        return $acl_f;
      else
        return array(0);
    }
  }

  /**
  * Retrieves the forum id and forum name of the releases forums
  *
  * @return   Array   @releases_forums   An array of the releases forums.
  */
  public function get_releases_forums()
  {
    return $this->get_forums_info($this->get_releases_forum_ids());
  }

  /**
  * Retrieves the finished forums by getting the forums
  * where mods can move finished projects to.
  *
  * @return   Array   @finished_forum_ids   Return an array of forum ids,
  */
  public function get_finished_forum_ids()
  {
    if ($this->config['prj_projects_table_guest'])
    {
      $finished_forum_ids = array();
      $acl_list = $this->auth->acl_get_list(false, array('m_prj_finished_project'));
      foreach($acl_list as $forum_id => $auth_option)
        $finished_forum_ids[] = $forum_id;
      return (sizeof($finished_forum_ids)) ? $finished_forum_ids : array(0);
    }
    else
    {
      $acl_f = $this->get_acl_f('m_prj_finished_project');
      if (sizeof($acl_f) > 1 && $acl_f[0] !== 0)
        return $acl_f;
      else
        return array(0);
    }
  }

  /**
  * Retrieves the forum id and forum name of the finished forums
  *
  * @return   Array   @finished_forums   An array of the finished forums.
  */
  public function get_finished_forums()
  {
    return $this->get_forums_info($this->get_finished_forum_ids());
  }

  /**
  * Insert a new row into the releases table.
  *
  * @param      int    @topic_id    The id of the new topic.
  *
  * @return     int    @release_id The id of the new release.
  */
  public function add_new_releases($topic_id)
  {
    $sql = 'INSERT INTO ' . $this->table_prefix . 'prj_releases
            VALUES (NULL, ' . $topic_id . ')';
    $this->db->sql_query($sql);
    $release_id = $this->db->sql_nextid();

    return $release_id;
  }

  /**
  * Prune releases so that only x times the projects table size remain
  * in the database.
  *
  * The ratio to keep can be specified in the extension settings.
  */
  public function prune_releases($override = false)
  {
    $reserve_ratio      = $this->config['prj_releases_table_ratio'];
    $required_size      = $this->config['prj_projects_table_size'];
    $releases_forum_ids = $this->get_releases_forum_ids();

    // Drop all rows from the releases table.
    $sql = 'TRUNCATE TABLE ' . $this->table_prefix . 'prj_releases';
    $this->db->sql_query($sql);

    // Add data to releases table.
    $sql = 'SELECT topic_id
            FROM ' . TOPICS_TABLE . '
            WHERE ' . $this->db->sql_in_set('forum_id', $releases_forum_ids) . '
              AND topic_visibility = 1
              AND topic_type = 0
            ORDER BY topic_time DESC';
    if ($override)
      $result = $this->db->sql_query($sql);
    else
      $result = $this->db->sql_query_limit($sql, $required_size * $reserve_ratio);

    $topic_ids = array();
    while ($row = $this->db->sql_fetchrow($result))
      $topic_ids[] = array('topic_id' => $row['topic_id']);
    $this->db->sql_freeresult($result);

    if ($topic_ids)
      $this->db->sql_multi_insert($this->table_prefix . 'prj_releases', $topic_ids);
  }

  /**
  * Gets the move rule of given project.
  *
  * @param   int    @forum_id   The forum id of the project.
  *
  * @return  Array  @nove_rule  Array of move rule for the project.
  *                             Format: refer to get_move_rules().
  */
  public function get_move_rule($forum_id)
  {
    $move_rules = $this->get_move_rules();
    return $move_rules[$forum_id];
  }

  /**
  * Gets the move rules.
  *
  * @return  Array   @move_rule   The array containing the move rule
  *                               of all projects.
  */
  public function get_move_rules()
  {
    $move_rules = array();

    $sql = 'SELECT move_from, move_to
            FROM ' . $this->table_prefix . 'prj_move_rules';
    $result = $this->db->sql_query($sql);
    while ($row = $this->db->sql_fetchrow($result))
    {
      $move_rules[$row['move_from']][] = array(
       'forum_id'   => $row['move_to'],
       'forum_name' => $this->get_forum_name($row['move_to'])
      );
    }
    $this->db->sql_freeresult($result);
    return $move_rules;
  }

  /**
  * Fill the move rules table with entries corresponding to
  * acp settings.
  *
  * Note: Does not check for moving from and to the same forum.
  *
  * @param    Array   @move_to_array    An array of forum ids.
  */
  public function set_move_rules($move_to_array)
  {
    // Remove all entries in move rules.
    $sql = 'TRUNCATE TABLE ' . $this->table_prefix . 'prj_move_rules';
    $this->db->sql_query($sql);

    $move_rules         = array();
    $projects_forum_ids = $this->get_projects_forum_ids();
    for ($i = 0; $i < sizeof($move_to_array); ++$i)
    {
      $move_from    = $projects_forum_ids[$i/2];
      $move_to      = $move_to_array[$i];
      $move_rules[] = array(
                        'move_from' => $move_from,
                        'move_to'   => $move_to,
      );
    }
    $this->db->sql_multi_insert($this->table_prefix . 'prj_move_rules', $move_rules);
  }

  /**
  * Determine where a topic is moved to when it is released.
  *
  * @param     int    @forum_id     The forum id of the topic to be moved.
  *
  * @return    int    @to_forum_id  The forum id the topic should be moved to.
  */
  public function get_to_forum_id($forum_id)
  {
    $move_rule = $this->get_move_rule($forum_id);
    return $move_rule[1]['forum_id'];
  }

  /**
  * Retrieves all projects with a release request.
  *
  * @return   Array   @release_requests   An array of project ids.
  */
  public function get_release_requests()
  {
    $sql = 'SELECT project_id
            FROM ' . $this->table_prefix . 'prj_projects
            WHERE release_request_sent = 1
            ORDER BY request_time DESC';
    $result      = $this->db->sql_query($sql);
    $project_ids = array();
    while ($row = $this->db->sql_fetchrow($result))
      $project_ids[] = $row['project_id'];
    $this->db->sql_freeresult($result);
    return $project_ids;
  }

  /**
  * Set release_request_sent to true so it will display
  * the project on the Moderator Control Panel.
  *
  * @param   int   @topic_id   The topic id of the project.
  */
  public function request_release($topic_id)
  {
    $sql = 'UPDATE ' . $this->table_prefix . 'prj_projects
            SET release_request_sent = 1, request_time = ' . time() . '
            WHERE topic_id = ' . $topic_id . '
              AND release_request_sent = 0';
    $this->db->sql_query($sql);
  }

  /**
  * Set release_request_sent to false.
  *
  * @param   int   @topic_id   The topic id of the project.
  */
  public function unset_release_request($topic_id)
  {
    $sql = 'UPDATE ' . $this->table_prefix . 'prj_projects
            SET release_request_sent = 0, request_time = 0
            WHERE topic_id = ' . $topic_id . '
              AND release_request_sent = 1';
    $this->db->sql_query($sql);
  }

  /**
  * Gets all fields for release code id provided.
  *
  * @param   int   @release_code_id   The id of the release code.
  *
  * @return  Array @release_code      The row returned from the database.
  */
  public function get_release_code($release_code_id)
  {
    $sql = 'SELECT *
            FROM ' . $this->table_prefix . 'prj_release_codes
            WHERE release_code_id = ' . $release_code_id;
    $result = $this->db->sql_query_limit($sql, 1);
    $row = $this->db->sql_fetchrow($result);
    $this->db->sql_freeresult($result);
    return $row;
  }

  /**
  * Gets all the release codes currently in the database.
  *
  * @return    Array    @release_codes    The results of the database.
  */
  public function get_release_codes()
  {
    $sql = 'SELECT *
            FROM ' . $this->table_prefix . 'prj_release_codes
            ORDER BY release_code_id ASC';
    $result = $this->db->sql_query($sql);
    $rowset = $this->db->sql_fetchrowset($result);
    $this->db->sql_freeresult($result);
    return $rowset;
  }

  /**
  * Adds a new release code to the database if it doesn't exist.
  *
  * @param  String   @release_code_name   The name of the new/existing release code.
  *                  @index               The index of the release code.
  *
  * @return int      @release_code_id     The id of the new/existing release code.
  */
  public function add_new_release_code($release_code_name, $index = 0)
  {
    $sql = 'INSERT INTO ' . $this->table_prefix . 'prj_release_codes
            VALUES (NULL, "' . $release_code_name . '", ' . $index . ')';
    $this->db->sql_query($sql);
    $release_code_id = $this->db->sql_nextid();

    return $release_code_id;
  }

  /**
  * Increment the release code index in the database.
  *
  * @param    int   @release_code_id    The id of the release code to modify.
  */
  public function increment_index($release_code_id)
  {
    $sql = 'UPDATE ' . $this->table_prefix . 'prj_release_codes
            SET release_code_index = release_code_index + 1
            WHERE release_code_id = ' . $release_code_id;
    $this->db->sql_query($sql);
  }

  /**
  * Modify the release code index in the database.
  *
  * @param    int   @release_code_id    The id of the release code to modify.
  *           int   @index              The index of the release code.
  */
  public function modify_release_code_index($release_code_id, $index = 0)
  {
    $sql = 'UPDATE ' . $this->table_prefix . 'prj_release_codes
            SET release_code_index = ' . $index . '
            WHERE release_code_id = ' . $release_code_id;
    $this->db->sql_query($sql);
  }

  /**
  * Remove the release code from the database.
  *
  * @param    int   @release_code_id   The id of the release code to be removed.
  */
  public function delete_release_code($release_code_id)
  {
    $sql = 'DELETE FROM ' . $this->table_prefix . 'prj_release_codes
            WHERE release_code_id = ' . $release_code_id;
    $this->db->sql_query($sql);
  }

////////////////////////// Projects Functions //////////////////////////

  /**
  * Retrieves the projects forums by getting the forums
  * where users can host projects.
  *
  * @return   Array   @projects_forum_ids  Return an array of forum ids,
  */
  public function get_projects_forum_ids()
  {
    if ($this->config['prj_projects_table_guest'])
    {
      $projects_forum_ids = array();
      $acl_list = $this->auth->acl_get_list(false, array('f_prj_projects'));
      foreach($acl_list as $forum_id => $auth_option)
        $projects_forum_ids[] = $forum_id;
      return (sizeof($projects_forum_ids)) ? $projects_forum_ids : array(0);
    }
    else
    {
      $acl_f = $this->get_acl_f('f_prj_projects');
      if (sizeof($acl_f) > 1 && $acl_f[0] !== 0)
        return $acl_f;
      else
        return array(0);
    }
  }
  
  /**
  * Retrieves the forum id and forum name of the projects forums
  *
  * @return   Array   @projects_forums   An array of the projects forums.
  */
  public function get_projects_forums()
  {
    return $this->get_forums_info($this->get_projects_forum_ids());
  }

  /**
  * General db query for project data.
  *
  * @param     Array    @project     The project data with either
  *                                  topic_id or project_id set.
  *            Array    @options     Additional options for the query,
  *                                  ie. SELECT, WHERE, ORDER_BY.
  *
  * @return    bool     @success     True if query had results and $project
  *                                  is set, false otherwise.
  */
  public function get_project_data($project, $options = NULL)
  {
    if (!isset($options['SELECT']))
      $select_str = '*';
    else
      $select_str = implode(', ', $options['SELECT']); 
    
    $sql_array = array(
      'SELECT'    => $select_str,
      'FROM'      => array($this->table_prefix . 'prj_projects' => 'p'),
      'LEFT_JOIN' => array(
        array(
          'FROM'  => array($this->table_prefix . 'prj_stages' => 'stg'),
          'ON'    => 'stg.project_id = p.project_id'
        ),
        array(
          'FROM'  => array($this->table_prefix . 'prj_statuses' => 'sts'),
          'ON'    => 'sts.status_id = stg.status_id'
        )
      )
    );

    if (isset($project['topic_id']) && $project['topic_id'] != 0)
      $sql_array['WHERE'] = 'p.topic_id = ' . $project['topic_id'];
    else if (isset($project['project_id']) && $project['project_id'] != 0)
      $sql_array['WHERE'] = 'p.project_id = ' . $project['project_id'];

    if (isset($options['JOIN_TOPICS_TABLE']) && $options['JOIN_TOPICS_TABLE'])
    {
      $sql_array['LEFT_JOIN'][] = array(
                                    'FROM' => array(TOPICS_TABLE => 't'),
                                    'ON'   => 't.topic_id = p.topic_id'
      );
      if (isset($sql_array['WHERE']))
        $sql_array['WHERE'] .= ' AND ';
      $sql_array['WHERE'] .= 't.topic_id IS NOT NULL AND t.topic_visibility = 1';

      if (isset($options['JOIN_FORUMS_TABLE']) && $options['JOIN_FORUMS_TABLE'])
      {
        $sql_array['LEFT_JOIN'][] = array(
                                      'FROM' => array(FORUMS_TABLE => 'f'),
                                      'ON'   => 'f.forum_id = t.forum_id'
        );
      }
    }

    if (isset($options['WHERE']))
    {
      if (isset($sql_array['WHERE']))
        $sql_array['WHERE'] .= ' AND ';
      $sql_array['WHERE'] .= $options['WHERE'];
    }

    if (isset($options['ORDER_BY']))
      $sql_array['ORDER_BY'] = $options['ORDER_BY'];

    $sql = $this->db->sql_build_query('SELECT', $sql_array);
    if (isset($options['LIMIT']))
      $result = $this->db->sql_query_limit($sql, $options['LIMIT']);
    else
      $result = $this->db->sql_query($sql);
    $rowset = $this->db->sql_fetchrowset($result);
    $this->db->sql_freeresult($result);

    return $rowset;
  }

  /**
  * Add or update project.
  *
  * @param    String   @mode            The mode of the post request.
  *           Array    @project_data    An array containing all project data.
  *
  * @return   int      @project_id      The id of the new project.
  */
  public function edit_project($mode, $project_data)
  {
    $project_id       = (isset($project_data['project_id'])) ? $project_data['project_id'] : 0;
    $project_title    = (isset($project_data['project_title'])) ? $project_data['project_title'] : '';
    $topic_id         = (isset($project_data['topic_id'])) ? $project_data['topic_id'] : 0;
    $current_stage_id = (isset($project_data['current_stage_id'])) ? $project_data['current_stage_id'] : 0;

    switch ($mode)
    {
      case 'post':
        $sql = 'INSERT INTO ' . $this->table_prefix . 'prj_projects
                VALUES (NULL, ' . $topic_id . ', "' . $project_title . '", ' . $current_stage_id . ', 0, 0)';
        break;

      case 'update_stage':
        $sql = 'UPDATE ' . $this->table_prefix . 'prj_projects
                SET current_stage_id = ' . $current_stage_id . '
                WHERE project_id = ' . $project_id;
        break;

      case 'update_topic':
        $sql = 'UPDATE ' . $this->table_prefix . 'prj_projects
                SET topic_id = ' . $topic_id . '
                WHERE project_id = ' . $project_id;
        break;

      case 'edit':
        // no break
      default:
        $sql = 'SELECT project_id
                FROM ' . $this->table_prefix . 'prj_projects
                WHERE topic_id = ' . $topic_id;
        break;
    }

    $result = $this->db->sql_query($sql);
    if ($mode == 'edit')
    {
      $row = $this->db->sql_fetchrow($result);
      $project_id = $row['project_id'];
    }
    else
      $project_id = $this->db->sql_nextid();

    return $project_id;
  }
  
  /**
  * Prune projects that are no longer found in the project forums along with its
  * stages in the projects table and stages table.
  */
  public function prune_projects()
  {
    $projects_forum_ids = $this->get_projects_forum_ids();

    // Grab all projects that are no longer attached to an active project
    // and delete them from the table.
    $rowset = $this->get_project_data(
                array(),
                array('SELECT' => array('p.project_id'),
                      'WHERE'  => 'p.topic_id = 0
                                   OR t.topic_id is NULL
                                   OR t.topic_visibility <> 1
                                   OR ' . $this->db->sql_in_set('t.forum_id', $projects_forum_ids, true),
                      'JOIN_TOPICS_TABLE' => true
                )
    );
    $project_ids = array(0);
    if ($rowset)
    {
      foreach ($rowset as $row)
        $project_ids[] = $row['project_id'];

      $sql = 'DELETE FROM ' . $this->table_prefix . 'prj_projects
              WHERE ' . $this->db->sql_in_set('project_id', $project_ids);
      $this->db->sql_query($sql);
    }

    // Delete orphaned stages.
    $sql = 'SELECT stg.stage_id
            FROM ' . $this->table_prefix . 'prj_stages stg
            LEFT JOIN ' . $this->table_prefix . 'prj_projects p
              ON p.project_id = stg.project_id
            WHERE ' . $this->db->sql_in_set('stg.project_id', $project_ids) . '
              OR p.project_id is NULL
              OR stg.project_id = 0';
    $result = $this->db->sql_query($sql);
    $rowset = $this->db->sql_fetchrowset($result);
    if ($rowset)
    {
      $stage_ids = array();
      foreach ($rowset as $row)
        $stage_ids[] = $row['stage_id'];

      $sql = 'DELETE FROM ' . $this->table_prefix . 'prj_stages
              WHERE ' . $this->db->sql_in_set('stage_id', $stage_ids);
      $this->db->sql_query($sql);
    }
  }
  
  /**
  * Gets the project stage with the earliest deadline.
  *
  * @param     int    @project_id      The id of the project.
  *
  * @return    int    @initial_stage   The earliest stage id.
  */
  public function get_earliest_stage($project_id)
  {
    $rowset = $this->get_project_data(
                array('project_id' => $project_id),
                array('SELECT'     => array('stg.stage_id'),
                      'ORDER_BY'   => 'stg.project_deadline ASC'
                )
    );
    return $rowset[0]['stage_id'];
  }

  /**
  * Gets the next stage for this project.
  *
  * @param    int   @topic_id         The topic id of the project.
  *
  * @return   Array @next_stage         The next stage object containing:
  *                                       status_name,
  *                                       stage_id,
  *                                       project_deadline
  *                                     Will return null if not found.
  */
  public function get_next_stage($topic_id)
  {
    $next_stage = $this->get_project_data(
                array('topic_id' => $topic_id),
                array('SELECT'     => array('sts.status_name',
                                            'stg.stage_id',
                                            'stg.project_deadline'
                                      ),
                      'WHERE'      => 'stg.project_deadline > (SELECT stg_inner.project_deadline
                                                               FROM ' . $this->table_prefix . 'prj_stages stg_inner
                                                                 LEFT JOIN ' . $this->table_prefix . 'prj_projects p_inner
                                                                 ON p_inner.current_stage_id = stg_inner.stage_id
                                                               WHERE p_inner.topic_id = ' . $topic_id . ')',
                      'ORDER_BY'   => 'stg.project_deadline ASC',
                      'LIMIT'      => 1
                )
    );
    if (!$next_stage)
      return array();
    return $next_stage[0];
  }

  /**
  * Adds a new stage to the database.
  *
  * @param  int              @project_id         The id of the associated project.
  *         int              @status_id          The id of the associated status.
  *         UNIX timestamp   @project_deadline   The project deadline as a UNIX timestamp.
  *
  * @return int              @stage_id           The id of the new stage.
  */
  public function add_new_stage($project_id = 0, $status_id = 0, $project_deadline = 0)
  {
    // Add stage.
    $sql = 'INSERT INTO ' . $this->table_prefix . 'prj_stages
            VALUES (NULL, ' . $project_id . ', ' . $status_id . ', ' . $project_deadline . ')';
    $this->db->sql_query($sql);
    $stage_id = $this->db->sql_nextid();

    return $stage_id;
  }

  /**
  * Guess the new stage of the project after it has been editted.
  * The new stage will be the one with the same status as the old
  * status before the edit.
  *
  * @param   int    @project_id       The id of the project.
  *          int    @old_status_id    The id of the old current status.
  *
  * @return  int    @stage_id         The id of either the stage with the same status
  *                                   as the old status, or the earliest stage.
  */
  public function guess_stage($project_id, $old_status_id)
  {
    $rowset = $this->get_project_data(
                array('project_id' => $project_id),
                array('SELECT'     => array('stg.stage_id'),
                      'WHERE'      => 'sts.status_id = ' . $old_status_id
                )
    );
    if (!$rowset)
      $stage_id = $this->get_earliest_stage($project_id);
    else
      $stage_id = $rowset[0]['stage_id'];
    return $stage_id;
  }

  /**
  * Checks if the project is on the last stage.
  *
  * @param     int    @topic_id        The topic id of the project.
  *
  * @return    bool   @is_last_stage   True if last stage, false otherwise.
  */
  public function is_last_stage($topic_id)
  {
    $next_stage = $this->get_next_stage($topic_id);
    return (sizeof($next_stage) === 0);
  }

  /**
  * Removes all stages associated with this project.
  *
  * @param    int    @project_id    The id of the project.
  */
  public function clear_stages($project_id)
  {
    $sql = 'DELETE FROM ' . $this->table_prefix . 'prj_stages
            WHERE project_id = ' . $project_id;
    $this->db->sql_query($sql);
  }

  /**
  * Gets statuses from the database.
  *
  * @param    int    @limit     Adds a limit clause onto the query.
  *
  * @return   Array  @statuses  The statuses returned from the query.
  */
  public function get_statuses($limit = 0)
  {
    $sql = 'SELECT *
            FROM ' . $this->table_prefix . 'prj_statuses
            ORDER BY status_id ASC';

    if ($limit == 0)
      $result = $this->db->sql_query($sql);
    else
      $result = $this->db->sql_query_limit($sql, $limit);
    $rowset = $this->db->sql_fetchrowset($result);
    $this->db->sql_freeresult($result);
    return $rowset;
  }

  /**
  * Gets the current status id of the project.
  *
  * @param   int    @project_id           The id of the project.
  *
  * @return  int    @current_status_id    The id of the current status.
  */ 
  public function get_current_status($project_id)
  {
    $rowset = $this->get_project_data(
                array('project_id' => $project_id),
                array('SELECT'     => array('sts.status_id'),
                      'WHERE'      => 'p.current_stage_id = stg.stage_id'
                )
    );
    if (!$rowset)
      $current_status_id = 0;
    else
      $current_status_id = $rowset[0]['status_id'];
    return $current_status_id;
  }

  /**
  * Gets primary statuses from the database.
  *
  * @return   Array  @statuses  The statuses returned from the query.
  */
  public function get_primary_statuses()
  {
    $sql = 'SELECT *
            FROM ' . $this->table_prefix . 'prj_statuses
            WHERE primary_status = 1
            ORDER BY status_id ASC';
    $result = $this->db->sql_query($sql);
    $rowset = $this->db->sql_fetchrowset($result);
    $this->db->sql_freeresult($result);
    return $rowset;
  }

  /**
  * Gets the status ids of all statuses in use.
  *
  * @return     Array    @statuses_in_use   The statuses currently used by active projects.
  */
  public function get_statuses_in_use()
  {
    $sql = 'SELECT DISTINCT stg.status_id
            FROM ' . $this->table_prefix . 'prj_stages stg
            ORDER BY status_id ASC';
    $result = $this->db->sql_query($sql);
    $statuses_in_use = array();
    while ($row = $this->db->sql_fetchrow())
      $statuses_in_use[] = $row['status_id'];
    return $statuses_in_use;
  }

  /**
  * Adds a new status to the database if it doesn't exist.
  *
  * @param  String   @status_name   The name of the new/existing status.
  *                  @is_primary    Is the status a primary status?
  *
  * @return int      @status_id     The id of the new/existing status.
  */
  public function add_new_status($status_name, $is_primary = false)
  {
    $primary = ($is_primary) ? '1' : '0';
    // Add statuses if not exists.
    $sql = 'INSERT INTO ' . $this->table_prefix . 'prj_statuses
            VALUES (NULL, "' . $status_name . '", ' . (($is_primary) ? '1' : '0') . ')
            ON DUPLICATE KEY
              UPDATE status_id = LAST_INSERT_ID(status_id)';
    $this->db->sql_query($sql);
    $status_id = $this->db->sql_nextid();

    return $status_id;
  }

  /**
  * Set statuses to primary status.
  *
  * @param    Array   @status_ids     The ids of the statuses to make primary.
  */
  public function set_primary_statuses($status_ids)
  {
    // Set all statuses to non-primary first.
    $sql = 'UPDATE ' . $this->table_prefix . 'prj_statuses
            SET primary_status = 0';
    $this->db->sql_query($sql);

    $sql = 'UPDATE ' . $this->table_prefix . 'prj_statuses
            SET primary_status = 1
            WHERE ' . $this->db->sql_in_set('status_id', $status_ids);
    $this->db->sql_query($sql);
  }

  /**
  * Deletes the status from the database.
  *
  * @param    int     @status_id     The id of the status to be deleted.
  */
  public function delete_status($status_id)
  {
    $sql = 'DELETE FROM ' . $this->table_prefix . 'prj_statuses
            WHERE status_id = ' . $status_id;
    $this->db->sql_query($sql);
  }

  /**
  * Build the complete topic title of the project.
  *
  * @param   int     @project_id   The id of the project
  *          int     @topic_id     The id of the topic.
  *
  * @return  String  @topic_title  The complete title of the topic.
  */
  public function get_project_full_title($project_id = false, $topic_id = false)
  {
    if ($project_id)
      $query = array('project_id' => $project_id);
    else if ($topic_id)
      $query = array('topic_id' => $topic_id);
    else
      return '';

    $rowset = $this->get_project_data($query,
                array('SELECT'     => array('p.project_title',
                                            'stg.project_deadline',
                                            'sts.status_name'
                                      ),
                      'WHERE'      => 'p.current_stage_id = stg.stage_id'
                )
    );
    if (!$rowset)
      return '';
    else
      return $this->build_topic_title($rowset[0]['status_name'], $rowset[0]['project_title'], $rowset[0]['project_deadline']);
  }

  /**
  * Updates the topic title, along with the first post,
  * with the given parameters.
  *
  * @param    int     @topic_id       The id of the topic to change.
  *           String  @topic_title    The complete title of the topic.
  */
  public function sync_topic_title($topic_id, $topic_title)
  {
    $sql = 'UPDATE ' . TOPICS_TABLE . '
            SET topic_title = "' . $topic_title . '"
            WHERE topic_id = ' . $topic_id;

    $this->db->sql_query($sql);

    // Update first post title.
    $sql = 'SELECT topic_first_post_id
            FROM ' . TOPICS_TABLE . '
            WHERE topic_id = ' . $topic_id;

    $result  = $this->db->sql_query($sql);
    $row = $this->db->sql_fetchrow($result);
    $this->db->sql_freeresult($result);

    $post_id = $row['topic_first_post_id'];

    $sql = 'UPDATE ' . POSTS_TABLE . '
            SET post_subject = "' . $topic_title . '"
            WHERE post_id = ' . $post_id;

    $this->db->sql_query($sql);
  }

  /**
  * Builds the topic title from parameters.
  *
  * @param    String  @project_status    The status of the project.
  *           String  @project_title     The title of the project.
  *           Date    @project_deadline  The deadline of the project.
  *
  * @return   String  @topic_title       The full topic title.
  */
  public function build_topic_title($project_status, $project_title = '', $project_deadline = 0)
  {
    if (!$project_title)
      $project_title = '';
    if (!$project_status && !$project_deadline)
      return $project_title;

    $project_status   = ($project_status) ? '[' . $project_status . '] ' : '';
    $project_deadline = ($project_deadline) ? ' (' . $this->user->format_date($project_deadline, 'M jS') . ')' : '';
    $topic_title      = $project_status . $project_title . $project_deadline;
    return $topic_title;
  }

  /**
  * Reverses the algorithm used in build_topic_title.
  *
  * @param    String     @topic_title     The topic title with status and deadline info.
  *
  * @return   String     @project_title   The raw project title without the status and deadlines included.
  */
  public function destruct_topic_title($topic_title)
  {
    $pattern = '/^\[(.*?)\] (.+) \((.*?)\)$/';
    $project_title = trim(preg_replace($pattern, '$2', $topic_title));
    return $project_title;
  }
}