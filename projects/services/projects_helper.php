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
  * @param   int   @forum_id      The id of the topic.
  *          bool  @releases      Check releases forum instead.
  *
  * @return  bool  @is_valid      True if valid, false otherwise.
  */
  public function in_valid_forum($topic_id, $releases = false)
  {
    if ($releases)
      $allowed_forum_ids = array($this->config['prj_releases_forum_id']);
    else
      $allowed_forum_ids = explode(',', $this->config['prj_project_forum_ids']);

    $sql = 'SELECT forum_id
            FROM ' . TOPICS_TABLE . '
            WHERE topic_id = ' . $topic_id;
    $result = $this->db->sql_query($sql);
    $row    = $this->db->sql_fetchrow($result);
    $this->db->sql_freeresult($result);

    $forum_id = $row['forum_id'];
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
      $allowed_forum_ids = array($this->config['prj_releases_forum_id']);
    else
      $allowed_forum_ids = explode(',', $this->config['prj_project_forum_ids']);

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
    $result = $this->db->sql_query($sql);
    $row = $this->db->sql_fetchrow($result);
    $this->db->sql_freeresult($result);
    if ($row['topic_type'] !== '0')
      return true;
    return false;
  }

  /**
  * Determine where a topic is moved to when it is released.
  *
  * Note: Does not account for syntax errors and for when move rules
  *       set "from" and "to" to the same forum.
  *
  * @param     int    @forum_id     The forum id of the topic to be moved.
  *
  * @return    int    @to_forum_id  The forum id the topic should be moved to.
  */
  public function get_to_forum_id($forum_id)
  {
    $to_forum_id = 0;

    $move_rules = (isset($this->config['prj_move_rules'])) ? $this->config['prj_move_rules'] : '';
    if (!$move_rules)
      return $to_forum_id;

    // Parse move rules. Ex: "11,4|5,2|1,7 to 33,12,3"
    $move_rules = explode(' to ', $move_rules);
    if (sizeof($move_rules) !== 2)
      return;

    $move_from = explode('|', $move_rules[0]);
    $move_to   = explode(',', $move_rules[1]);

    if (sizeof($move_from) !== sizeof($move_to))
      return;

    // Create move map.
    $move = array();
    for ($i = 0; $i < sizeof($move_from); ++$i)
    {
      $from_forum_ids = explode(',', $move_from[$i]);
      foreach ($from_forum_ids as $from_forum_id)
        $move[$from_forum_id] = $move_to[$i];
    }

    $to_forum_id = (isset($move[$forum_id])) ? $move[$forum_id] : 0;
    return $to_forum_id;
  }

  /**
  * Insert a new row into the releases table.
  *
  * @param      int    @topic_id    The id of the new topic.
  *
  * @return     int    @release_id The id of the new release.
  */
  function add_new_releases($topic_id)
  {
    $sql = 'INSERT INTO ' . $this->table_prefix . 'prj_releases
            VALUES (NULL, ' . $topic_id . ')';
    $this->db->sql_query($sql);
    $release_id = $this->db->sql_nextid();

    return $release_id;
  }

  /**
  * Adds a new release code to the database if it doesn't exist.
  *
  * @param  String   @release_code_name   The name of the new/existing release code.
  *                  @index               The index of the release code.
  *
  * @return int      @release_code_id     The id of the new/existing release code.
  */
  function add_new_release_code($release_code_name, $index = 0)
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
  function increment_index($release_code_id)
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
  function modify_release_code_index($release_code_id, $index = 0)
  {
    $sql = 'UPDATE ' . $this->table_prefix . 'prj_release_codes
            SET release_code_index = ' . $index . '
            WHERE release_code_id = ' . $release_code_id;
    $this->db->sql_query($sql);
  }

  /**
  * Gets all the release codes currently in the database.
  *
  * @return    Array    @release_codes    The results of the database.
  */
  function get_release_codes()
  {
    $sql = 'SELECT *
            FROM ' . $this->table_prefix . 'prj_release_codes
            ORDER BY release_code_id ASC';
    $result = $this->db->sql_query($sql);
    $rowset = $this->db->sql_fetchrowset($result);
    $this->db->sql_freeresult($result);
    return $rowset;
  }

  function get_release_code($release_code_id)
  {
    $sql = 'SELECT *
            FROM ' . $this->table_prefix . 'prj_release_codes
            WHERE release_code_id = ' . $release_code_id;
    $result = $this->db->sql_query_limit($sql, 1);
    $row = $this->db->sql_fetchrow($result);
    $this->db->sql_freeresult($result);
    return $row;
  }

  function delete_release_code($release_code_id)
  {
    $sql = 'DELETE FROM ' . $this->table_prefix . 'prj_release_codes
            WHERE release_code_id = ' . $release_code_id;
    $this->db->sql_query($sql);
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
  function get_project_data($project, $options = NULL)
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
                VALUES (NULL, ' . $topic_id . ', "' . $project_title . '", ' . $current_stage_id .')';
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
    return $next_stage['stage_id'] === 0;
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
      return array(
        'status_name'      => '',
        'stage_id'         => 0,
        'project_deadline' => 0);
    return $next_stage[0];
  }

  /**
  * Gets the project stage with the earliest deadline.
  *
  * @param     int    @project_id      The id of the project.
  *
  * @return    int    @initial_stage   The earliest stage id.
  */
  function get_earliest_stage($project_id)
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
  * Build the complete topic title of the project.
  *
  * @param   int     @project_id   The id of the project
  *
  * @return  String  @topic_title  The complete title of the topic.
  */
  public function get_project_full_title($project_id)
  {
    $rowset = $this->get_project_data(
                array('project_id' => $project_id),
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
  * Builds the topic title from parameters.
  *
  * @param    String  @project_status    The status of the project.
  *           String  @project_title     The title of the project.
  *           Date    @project_deadline  The deadline of the project.
  *
  * @return   String  @topic_title       The full topic title.
  */
  public function build_topic_title($project_status, $project_title, $project_deadline)
  {
    if (!$project_title)
      $project_title = '';
    if (!$project_status || !$project_deadline)
      return $project_title;

    $topic_title = '[' . $project_status . '] ' . $project_title . ' (' . $this->user->format_date($project_deadline, 'M jS') . ')';
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

  /**
  * Prune projects that are no longer found in the project forums along with its
  * stages in the projects table and stages table.
  */
  public function prune_projects()
  {
    $project_forum_ids = explode(',', $this->config['prj_project_forum_ids']);

    // Grab all projects that are no longer attached to an active project
    // and delete them from the table.
    $rowset = $this->get_project_data(
                array(),
                array('SELECT' => array('p.project_id'),
                      'WHERE'  => 'p.topic_id = 0
                                   OR t.topic_id is NULL
                                   OR t.topic_visibility <> 1
                                   OR ' . $this->db->sql_in_set('t.forum_id', $project_forum_ids, true),
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
  * Prune releases so that only x times the projects table size remain
  * in the database.
  *
  * The ratio to keep can be specified in the extension settings.
  */
  public function prune_releases($override = false)
  {
    $reserve_ratio     = $this->config['prj_releases_table_ratio'];
    $required_size     = $this->config['prj_projects_table_size'];
    $releases_forum_id = $this->config['prj_releases_forum_id'];

    // Drop all rows from the releases table.
    $sql = 'TRUNCATE TABLE ' . $this->table_prefix . 'prj_releases';
    $this->db->sql_query($sql);

    // Add data to releases table.
    $sql = 'SELECT topic_id
            FROM ' . TOPICS_TABLE . '
            WHERE forum_id = ' . $releases_forum_id . '
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
}