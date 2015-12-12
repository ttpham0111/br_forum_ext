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

namespace ttpham\projects\event;

/**
* @ignore
*/
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
* Event listener
*/
class listener implements EventSubscriberInterface
{
  /** @var \phpbb\auth\auth */
  private $auth;

  /* @var \phpbb\config\config */
  private $config;

  /** @var \phpbb\db\driver\driver_interface */
  private $db;

  /* @var \phpbb\controller\helper */
  private $helper;

  /** @var \phpbb\request\request */
  private $request;

  /** @var \phpbb\template\template */
  private $template;

  /** @var \phpbb\user */
  private $user;

  /** @var \ttpham\projects\services\projects_helper */
  private $projects_helper;
  
  /** @var \ttpham\projects\controller\main */
  private $projects_controller;

  /** @var String */
  private $table_prefix;

  /** @var int */
  private $current_project_id;

  /**
  * Constructor
  */
  public function __construct(
    \phpbb\auth\auth                          $auth,
    \phpbb\config\config                      $config,
    \phpbb\db\driver\driver_interface         $db,
    \phpbb\controller\helper                  $helper,
    \phpbb\request\request                    $request,
    \phpbb\template\template                  $template,
    \phpbb\user                               $user,
    \ttpham\projects\services\projects_helper $projects_helper,
    \ttpham\projects\controller\main          $projects_controller,
                                              $table_prefix
  )
  {
    $this->auth                = $auth;
    $this->config              = $config;
    $this->db                  = $db;
    $this->helper              = $helper;
    $this->request             = $request;
    $this->template            = $template;
    $this->user                = $user;
    $this->projects_helper     = $projects_helper;
    $this->projects_controller = $projects_controller;
    $this->table_prefix        = $table_prefix;
    $this->current_project_id  = 0;
  }

  static public function getSubscribedEvents()
  {
    return array(
      'core.index_modify_page_title'               => 'display_project_tables',
      'core.viewtopic_assign_template_vars_before' => 'display_stages_header',
      'core.viewtopic_modify_post_row'             => 'display_stageup_icon',
      'core.posting_modify_template_vars'          => 'display_projects_form',
      'core.posting_modify_submit_post_before'     => 'add_new_project',
      'core.posting_modify_submit_post_after'      => array(
                                                        array('link_new_topic_to_project'),
                                                        array('add_new_releases')
      )
    );
  }

  /**
  * Event: core.index_modify_page_title.
  *
  * Displays the project tables above the forum body.
  */
  public function display_project_tables()
  {
    $this->projects_controller->display_project_tables();
  }

  /**
  * Event: core.viewtopic_assgn_template_vars_before
  *
  * Displays the stages above the topic body.
  */
  public function display_stages_header($event)
  {
    // No permission check needed because viewtopic.php
    // has already handled view permissions.

    // Check if topic is a project.
    if (!$this->projects_helper->in_valid_forum($event['forum_id']))
      return false;

    // Grab stages later than the current stage deadline and start template loop.
    $stages = $this->projects_helper->get_project_data(
                  array('topic_id' => $event['topic_id']),
                  array('SELECT'   => array('p.current_stage_id',
                                            'stg.stage_id',
                                            'sts.status_name',
                                            'stg.project_deadline'
                                      ),
                        // NOTE: Uncomment to only display deadlines after current.
                        // 'WHERE'    => 'stg.project_deadline >=
                        //                (SELECT stg_inner.project_deadline
                        //                 FROM ' . $this->table_prefix . 'prj_projects p_inner
                        //                 LEFT JOIN ' . $this->table_prefix . 'prj_stages stg_inner
                        //                   ON stg_inner.stage_id = p_inner.current_stage_id
                        //                 WHERE p_inner.project_id = p.project_id)',
                        'ORDER_BY' => 'stg.project_deadline ASC'
                  )
    );

    $tpl_loopname = 'prj_stages_header';
    $i = 1;
    foreach($stages as $stage)
    {
      $tpl_ary = array(
        'PROJECT_STAGE'         => $stage['status_name'],
        'PROJECT_DEADLINE_DATE' => (isset($stage['project_deadline'])) ? $this->user->format_date($stage['project_deadline'], 'M d, Y') : '',
        'CURRENT_STAGE'         => $stage['current_stage_id'] === $stage['stage_id'],
        'LAST_STAGE'            => $i === sizeof($stages)
      );
      ++$i;
      $this->template->assign_block_vars($tpl_loopname, $tpl_ary);
    }

    $this->template->assign_vars(array(
      strtoupper($tpl_loopname) . '_DISPLAY' => true
    ));
  }

  /**
  * Event: core.viewtopic_modify_post_row.
  *
  * Adds the stageup icon to the opening post of the project.
  */
  public function display_stageup_icon($event)
  {
    $post_row        = $event['post_row'];
    $post_id         = $post_row['POST_ID'];
    $is_topic_poster = $post_row['S_TOPIC_POSTER'];

    // Check if user has permission to view button.
    if (!$is_topic_poster &&
        !$this->auth->acl_get('m_prj_stageup'))
      return;

    $forum_ids = explode(',', $this->config['prj_project_forum_ids']);

    // There are no project forums, so don't display the button.
    if (sizeof($forum_ids) == 0)
      return;

    // Check if this is the first post for topics in project forums.
    $sql = 'SELECT topic_id
            FROM ' . TOPICS_TABLE . '
            WHERE ' . $this->db->sql_in_set('forum_id', $forum_ids) . '
              AND topic_first_post_id = ' . $post_id . '
              AND topic_type = 0';

    $result = $this->db->sql_query($sql);
    $row    = $this->db->sql_fetchrow($result);
    $this->db->sql_freeresult($result);

    $topic_id = $row['topic_id'];

    // This post isn't the first post.
    if (!$topic_id)
      return;

    // Grab project information.
    $sql = 'SELECT project_id, current_stage_id
            FROM ' . $this->table_prefix . 'prj_projects
            WHERE topic_id = ' . $topic_id;

    $result = $this->db->sql_query($sql);
    $row    = $this->db->sql_fetchrow($result);
    $this->db->sql_freeresult($result);

    // Why is this check necessary?
    if (!$row)
      return;

    $next_stage = $this->projects_helper->get_next_stage(
                                            $row['project_id'],
                                            $row['current_stage_id']
                                          );
    $next_status = $next_stage['status_name'];

    $this->user->add_lang_ext('ttpham/projects', 'projects');

    // Display the button.
    $post_row['U_PRJ_STAGEUP'] = $this->helper->route(
                                   'projects_stage_up_controller',
                                   array('topic_id' => $topic_id
                                 ));
    // TODO: Change releases.
    $post_row['PRJ_RELEASE_PROJECT'] = false;
    $post_row['PRJ_STAGE']           = $next_status;

    if ($next_stage['stage_id'] !== 0)
    {
      $this->template->assign_vars(array(
        'PRJ_SHOW_STAGE_FORWARD_BUTTON' => true
      ));
    }
    $event['post_row'] = $post_row;
  }

  /**
  * Event: core.posting_modify_template_vars.
  *
  * Provides extra fields for creating topics in project forums.
  */
  public function display_projects_form($event)
  {
    $this->user->add_lang_ext('ttpham/projects', 'projects');

    if(!$this->projects_helper->form_valid($event))
      return;

    // Set default values for form.
    $tpl_loopname = 'prj_deadlines';
    $get_default = true;
    if ($event['mode'] == 'edit')
    {
      $rowset = $this->projects_helper->get_project_data(
                  array('topic_id' => $event['topic_id']),
                  array('SELECT'   => array('sts.status_name', 'stg.project_deadline'),
                        'ORDER_BY' => 'stg.project_deadline ASC'
                  )
      );

      if ($rowset && (sizeof($rowset) > 1 || ($rowset[0]['status_name'] !== NULL && $rowset[0]['project_deadline'] !== NULL)))
        $get_default = false;
    }
    if ($get_default)
      $rowset = $this->projects_helper->get_statuses(3);

    foreach ($rowset as $row)
    {
      $tpl_ary = array(
        'PROJECT_STAGE' => $row['status_name'],
        'PROJECT_DEADLINE_DATE' => (isset($row['project_deadline'])) ? $this->user->format_date($row['project_deadline'], 'M d, Y') : '',
        'PROJECT_DEADLINE_UNIX' => (isset($row['project_deadline'])) ? $row['project_deadline'] * 1000 : '',
      );

      $this->template->assign_block_vars($tpl_loopname, $tpl_ary);
    }
    
    $page_data = $event['page_data'];
    $page_data['PRJ_DISPLAY_PROJECTS_FORM'] = true;
    $event['page_data'] = $page_data;
  }

  /**
  * Event: core.posting_modify_submit_post_before.
  *
  * Update projects, stages, and statuses table and link them together.
  * Then generate the subject name for the new topic to be saved.
  *
  * Note: If the topic fails to be made, the projects/stages/statuses
  *       would still be saved in the database but will not be displayed
  *       in the deadlines table.
  */
  public function add_new_project($event)
  {
    if(!$this->projects_helper->form_valid($event))
      return;

    $post_data = $event['post_data'];
    $mode      = $event['mode'];

    $project_data = array();
    $project_data['project_title'] = $post_data['post_subject'];
    $project_data['topic_id']      = $event['topic_id'];

    if ($mode == 'post')
      $project_data['project_id'] = $this->projects_helper->edit_project('post', $project_data);
    else if ($mode == 'edit')
    {
      $project_data['project_id'] = $this->projects_helper->edit_project('edit', $project_data);

      // Clear stages related to project before updating.
      $old_status_id = $this->projects_helper->get_current_status($project_data['project_id']);
      $this->projects_helper->clear_stages($project_data['project_id']);
    }

    $stage_names = $this->request->variable('prj_stage_names', array(''));
    $deadlines   = $this->request->variable('prj_dates', array(''));
    for ($i = 0; $i < sizeof($stage_names); ++$i)
    {
      if ($deadlines[$i] === '' || $stage_names[$i] === '')
        break;
      $status_name = $stage_names[$i];
      $project_deadline = $deadlines[$i] / 1000;

      $status_id = $this->projects_helper->add_new_status($status_name);
      $this->projects_helper->add_new_stage($project_data['project_id'], $status_id, $project_deadline);
    }

   // Set initial stage of the project.
    if ($mode == 'post' || $old_status_id == 0)
    { 
      $project_data['current_stage_id'] = $this->projects_helper->get_earliest_stage($project_data['project_id']);
      $this->projects_helper->edit_project('update_stage', $project_data);    
    }
    else if ($mode == 'edit')
    {
      $project_data['current_stage_id'] = $this->projects_helper->guess_stage($project_data['project_id'], $old_status_id);
      $this->projects_helper->edit_project('update_stage', $project_data);
    }

    $topic_title = $this->projects_helper->get_project_full_title($project_data['project_id']);
    $post_data['post_subject'] = $topic_title;
    $event['post_data'] = $post_data;

    // Save to modify project with new topic id, after posting.
    $this->current_project_id = $project_data['project_id'];
  }

  /**
  * Event: core.posting_modify_submit_post_after.
  *
  * Links the new project with the new topic.
  */
  public function link_new_topic_to_project($event)
  {
    if ($event['mode'] != 'post')
      return;

    if (!$this->projects_helper->form_valid($event))
      return;

    $this->projects_helper->edit_project('update_topic', array(
      'project_id' => $this->current_project_id,
      'topic_id'   => $event['data']['topic_id']
    ));
  }

  /**
  * Event: core.posting_modify_submit_post_after.
  *
  * Add to releases table when a new topic is made in the
  * releases forum
  */
  public function add_new_releases($event)
  {
    if (!$this->projects_helper->form_valid($event, true))
      return;

    // Make sure that the new topic is not a special topic (eg. announcements)
    $topic_id = $event['data']['topic_id'];
    $sql = 'SELECT topic_type
            FROM ' . TOPICS_TABLE . '
            WHERE topic_id = ' . $topic_id;
    $result = $this->db->sql_query($sql);
    $topic_type = $this->db->sql_fetchrow($result)['topic_type'];
    $this->db->sql_freeresult($result);
    if ($topic_type !== '0')
      return;

    $this->projects_helper->add_new_releases($topic_id);
  }
}