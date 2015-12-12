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
  public function form_valid(&$event, $releases = false)
  {
    $forum_id = $event['forum_id'];
    if (!$this->in_valid_forum($forum_id, $releases))
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
  * @param   int   @forum_id      The id of the forum.
  *          bool  @releases      Check releases forum instead.
  *
  * @return  bool  @is_valid      True if valid, false otherwise.
  */
  public function in_valid_forum($forum_id, $releases = false)
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
  * Gets the next stage for this project.
  *
  * @param    int   @project_id         The id of the project.
  *           int   @current_stage_id   The id of the project's current stage.
  *
  * @return   Array @next_stage         The next stage object containing:
  *                                       status_name,
  *                                       stage_id,
  *                                       project_deadline
  *                                     Will return null if not found.
  */
  public function get_next_stage($project_id, $current_stage_id)
  {
    $stages = $this->get_project_data(
                array('project_id' => $project_id),
                array('SELECT'     => array('sts.status_name',
                                            'stg.stage_id',
                                            'stg.project_deadline'
                                      ),
                      'ORDER_BY'   => 'stg.project_deadline ASC'
                )
    );

    $next_stage = array(
      'status_name'      => '',
      'stage_id'         => 0,
      'project_deadline' => 0
    );

    // This project has no stages.
    if (!sizeof($stages))
      return $next_stage;

    // Get next stage.
    else
    {
      for ($i = 0; $i < sizeof($stages) - 1; ++$i)
      {
        if ($stages[$i]['stage_id'] == $current_stage_id)
          $next_stage = $stages[$i + 1];
      }
    }
    return $next_stage;
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
    $sql = 'SELECT status_id, status_name
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
  * Adds a new status to the database if it doesn't exist.
  *
  * @param  String   @status_name   The name of the new/existing status.
  *
  * @return int      @status_id     The id of the new/existing status.
  */
  public function add_new_status($status_name)
  {
    // Add statuses if not exists.
    $sql = 'INSERT INTO ' . $this->table_prefix . 'prj_statuses
            VALUES (NULL, "' . $status_name . '")
            ON DUPLICATE KEY
              UPDATE status_id = LAST_INSERT_ID(status_id)';
    $this->db->sql_query($sql);
    $status_id = $this->db->sql_nextid();

    return $status_id;
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
}