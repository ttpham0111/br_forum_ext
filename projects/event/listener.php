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

  /* @var \phpbb\mimetype\guesser */
  private $mimetype_guesser;

  /* @var \phpbb\plupload\plupload */
  private $plupload;

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

    /** @var string PHP extension */
  private $phpEx;

  /** @var string phpBB root path */
  private $root_path;

  /** @var string phpBB table prefix */
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
    \phpbb\mimetype\guesser                   $mimetype_guesser,
    \phpbb\plupload\plupload                  $plupload,
    \phpbb\request\request                    $request,
    \phpbb\template\template                  $template,
    \phpbb\user                               $user,
    \ttpham\projects\services\projects_helper $projects_helper,
    \ttpham\projects\controller\main          $projects_controller,
                                              $phpEx,
                                              $root_path,
                                              $table_prefix
  )
  {
    $this->auth                = $auth;
    $this->config              = $config;
    $this->db                  = $db;
    $this->helper              = $helper;
    $this->mimetype_guesser    = $mimetype_guesser;
    $this->plupload            = $plupload;
    $this->request             = $request;
    $this->template            = $template;
    $this->user                = $user;
    $this->projects_helper     = $projects_helper;
    $this->projects_controller = $projects_controller;
    $this->root_path           = $root_path;
    $this->phpEx               = $phpEx;
    $this->table_prefix        = $table_prefix;
    $this->current_project_id  = 0;
  }

  static public function getSubscribedEvents()
  {
    return array(
      'core.index_modify_page_title'               => 'display_project_tables',
      'core.viewtopic_assign_template_vars_before' => 'display_stages_header',
      'core.viewtopic_modify_post_row'             => 'display_stageup_icon',
      'core.posting_modify_template_vars'          => array(
                                                        array('display_projects_form'),
                                                        array('prefill_release_form'),
                                                        array('display_releases_form')
                                                      ),
      'core.posting_modify_submit_post_before'     => array(
                                                        array('add_new_project'),
                                                        array('modify_release_title')
                                                      ),
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
    if (!$this->projects_helper->is_valid_forum($event['forum_id']))
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

    $this->user->add_lang_ext('ttpham/projects', 'projects');
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

    // This post isn't the first post of a project.
    if (!$topic_id)
      return;

    $next_stage = $this->projects_helper->get_next_stage($topic_id);
    $next_status = $next_stage['status_name'];

    $this->user->add_lang_ext('ttpham/projects', 'projects');

    $release_project = ($next_stage['stage_id'] === 0) ? true : false;

    // Display the button.
    $post_row['U_PRJ_STAGEUP'] = $this->helper->route(
                                   'projects_stage_up_controller',
                                   array('topic_id' => $topic_id,
                                         'release'  => $release_project
    ));
    $post_row['PRJ_RELEASE_PROJECT'] = $release_project;
    $post_row['PRJ_STAGE']           = $next_status;

    // Only show the buttons to those with permission to release the project
    // if it needs to be released.
    if (!$release_project || ($release_project && $this->auth->acl_get('m_prj_release_project')))
    {
      $this->template->assign_vars(array(
        'PRJ_SHOW_STAGE_FORWARD_BUTTON' => true
      ));
      $event['post_row'] = $post_row;
    }
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
    if ($event['mode'] === 'edit' && !$event['preview'])
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
    // If preview, add the new request variable to the template.
    else if ($event['preview'])
    {
      $stage_names = $this->request->variable('prj_stage_names', array(''));
      $deadlines   = $this->request->variable('prj_dates', array(''));
      $rowset = array();
      for ($i = 0; $i < sizeof($stage_names); ++$i)
      {
        if ($deadlines[$i] === '' || $stage_names[$i] === '')
          break;
        $rowset[] = array('project_deadline' => $deadlines[$i] / 1000, 'status_name' => $stage_names[$i]);
      }

      if ($rowset && (sizeof($rowset) > 1 || ($rowset[0]['status_name'] !== NULL && $rowset[0]['project_deadline'] !== NULL)))
        $get_default = false;
    }
    if ($get_default)
      $rowset = $this->projects_helper->get_primary_statuses();

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
  * Event: core.posting_modify_template_vars.
  *
  * Prefill the subject and message fields with data from project
  * to be released.
  *
  * Suggest release codes.
  */
  public function prefill_release_form($event)
  {
    $topic_id = $this->request->variable('prj_topic_id', 0);

    if ($event['mode'] != 'post' || $topic_id === 0)
      return;

    // Can the user releases this project?
    if (!$this->auth->acl_get('m_prj_release_project'))
      return;

    // Can the user view this topic?
    $sql = 'SELECT forum_id, topic_visibility
            FROM ' . TOPICS_TABLE . '
            WHERE topic_id = ' . $topic_id;
    $result = $this->db->sql_query($sql);
    $row    = $this->db->sql_fetchrow($result);
    $this->db->sql_freeresult($result);

    if (!$row ||
        !$this->auth->acl_get('f_read', $row['forum_id']) ||
        $row['topic_visibility' !== '1'])
      return;

    // Grab project title and project first post.
    $rowset = $this->projects_helper->get_project_data(
                                        array('topic_id' => $topic_id),
                                        array('SELECT'   => array('project_title'))
    );
    if (!$rowset)
      return;

    $sql = 'SELECT post_text, bbcode_uid, bbcode_bitfield
            FROM ' . POSTS_TABLE . '
            WHERE post_id = (SELECT topic_first_post_id
                             FROM ' . TOPICS_TABLE . '
                             WHERE topic_id = ' . $topic_id . ')';
    $result = $this->db->sql_query($sql);
    $row    = $this->db->sql_fetchrow($result);
    $this->db->sql_freeresult($result);

    // Refer to https://wiki.phpbb.com/Practical.Displaying_posts_and_topics_on_external_pages.
    // Convert the db message to the original post message.
    if (!function_exists('smiley_text') || !function_exists('censor_text'))
      include($this->root_path . 'includes/functions_content.' . $this->phpEx);
    if (!class_exists('bbcode'))
      include($this->root_path . 'includes/bbcode.' . $this->phpEx);

    $project_message = $row['post_text'];
    $bbcode          = new \bbcode();
    $bbcode->bbcode_second_pass($project_message, $row['bbcode_uid']);
    $project_message = smiley_text($project_message, true);
    $project_title   = $rowset[0]['project_title'];

    // Set post form template.
    $page_data = $event['page_data'];
    if ($page_data['SUBJECT'] === '')
      $page_data['SUBJECT'] = $project_title;
    if ($page_data['MESSAGE'] === '')
      $page_data['MESSAGE'] = censor_text($project_message);
    $page_data['S_POST_ACTION'] = $page_data['S_POST_ACTION'] . "&amp;prj_topic_id=$topic_id";

    $event['page_data'] = $page_data;
  }

  /**
  * Event: core.posting_modify_template_vars.
  *
  * Provides extra fields for creating topics in releases forums.
  */
  public function display_releases_form($event)
  {
    // No other permission checks required since handled pre-event.
    if (!$this->projects_helper->form_valid($event, true))
      return;

    // Set template variables for release codes.
    $tpl_loopname = 'prj_release_codes';
    $release_codes = $this->projects_helper->get_release_codes();

    if ($event['preview'])
      $release_code_selected = $this->request->variable('prj_release_code', 0);

    $i = 0;
    foreach ($release_codes as $release_code)
    {
      $release_code_id = $release_code['release_code_id'];
      $index = sprintf("%02d", $release_code['release_code_index']+1);
      $release_code_text = $release_code['release_code_name'] . ' ' . $index;
      $tpl_ary = array(
        'CODE_ID'    => $release_code_id,
        'CODE_TEXT'  => $release_code_text,
        'SELECTED'   => (isset($release_code_selected)) ? ($release_code_selected === (int) $release_code_id) : ($i === 0)
      );
      $this->template->assign_block_vars($tpl_loopname, $tpl_ary);
      ++$i;
    }

    // Add a blank option for topics not using release codes.
    $tpl_default = array(
      'CODE_ID' => 0,
      'CODE_TEXT' => '',
      'SELECTED' => (isset($release_code_selected)) ? $release_code_selected === 0 : false
    );
    $this->template->assign_block_vars($tpl_loopname, $tpl_default);

    $this->user->add_lang_ext('ttpham/projects', 'projects');
    $this->template->assign_vars(array(
      'PRJ_RELEASE_CODES_LIST_DISPLAY' => true
    ));
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

    // Make sure that the new topic is not a special topic (eg. announcements)
    if ($post_data['topic_type'] !== 0)
      return;

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
  * Event: core.posting_modify_submit_post_before.
  *
  * Generate the subject name for the new topic to be saved.
  */
  public function modify_release_title($event)
  {
    // No other checks required, handled by event.
    if (!$this->projects_helper->form_valid($event, true))
      return;

    // Make sure that the new topic is not a special topic (eg. announcements)
    if ($event['post_data']['topic_type'] !== 0)
      return;

    // Get release code of project.
    $release_code_id = $this->request->variable('prj_release_code', 0);
    $release_code = $this->projects_helper->get_release_code($release_code_id);
    $release_code_text = '';
    if ($release_code)
    {
      $index = sprintf("%02d", $release_code['release_code_index']+1);
      $release_code_text .= '[' . $release_code['release_code_name'] . ' ' . $index . '] ';
    }

    // Modify title to be saved to database.
    $post_data = $event['post_data'];
    $post_data['post_subject'] = $release_code_text . ($post_data['post_subject']);
    $event['post_data'] = $post_data;
  }

  /**
  * Event: core.posting_modify_submit_post_after.
  *
  * Links the new project with the new topic.
  */
  public function link_new_topic_to_project($event)
  {
    // No permission check required because it was handled pre-event.

    if ($event['mode'] != 'post')
      return;

    if (!$this->projects_helper->form_valid($event))
      return;

    // Make sure that the new topic is not a special topic (eg. announcements)
    if ($event['post_data']['topic_type'] !== 0)
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
    // No permission check required because it was handled pre-event.

    if (!$this->projects_helper->form_valid($event, true))
      return;

    $topic_id = $event['data']['topic_id'];
    $forum_id = $event['data']['forum_id'];

    // Make sure that the new topic is not a special topic (eg. announcements)
    if ($event['post_data']['topic_type'] !== 0)
      return;

    // Since the release post was successful, we will increment the
    // index of the release code used.
    $release_code_id = $this->request->variable('prj_release_code', 0);
    $this->projects_helper->increment_index($release_code_id);
    $this->projects_helper->add_new_releases($topic_id);

    /**
    * Move the Topic.
    */

    // Grab relevant config variables.
    $move_after_release = (isset($this->config['prj_move_after_release'])) ? $this->config['prj_move_after_release'] : false;
    $move_lock_topics   = (isset($this->config['prj_move_lock_topics'])) ? $this->config['prj_move_lock_topics'] : false;
    if (!$move_after_release)
      return;

    $prj_topic_id = $this->request->variable('prj_topic_id', 0);

    // Grab forum_id of the project.
    $sql = 'SELECT forum_id
            FROM ' . TOPICS_TABLE . '
            WHERE topic_id = ' . $prj_topic_id;
    $result = $this->db->sql_query($sql);
    $row    = $this->db->sql_fetchrow($result);
    $this->db->sql_freeresult($result);
    if (!$row)
      return;

    $prj_forum_id = $row['forum_id'];
    $redirect     = append_sid("{$this->root_path}viewtopic.$this->phpEx", "f=$forum_id&t=$topic_id", false);
    $to_forum_id  = $this->projects_helper->get_to_forum_id($prj_forum_id);
    if ($to_forum_id === 0)
      return;

    // From functions.php -> confirm_box(): Generate activation key
    $confirm_key = gen_rand_string(10);
    $sql = 'UPDATE ' . USERS_TABLE . "
            SET user_last_confirm_key = '" . $this->db->sql_escape($confirm_key) . "'
            WHERE user_id = " . $this->user->data['user_id'];
    $this->db->sql_query($sql);

    // Update user last confirm key.
    $this->user->data['user_last_confirm_key'] = $confirm_key;

    // Set variables needed for moving the topic.
    $this->request->overwrite('f', $prj_forum_id);
    $this->request->overwrite('i', 'main');
    $this->request->overwrite('mode', 'forum_view');
    $this->request->overwrite('start', 0);
    $this->request->overwrite('topic_id_list[]', $prj_topic_id);
    $this->request->overwrite('action', 'move');
    $this->request->overwrite('to_forum_id', $to_forum_id);
    $this->request->overwrite('move_lock_topics', $move_lock_topics, \phpbb\request\request_interface::POST);
    $this->request->overwrite('confirm', $this->user->lang['YES'], \phpbb\request\request_interface::POST);
    $this->request->overwrite('confirm_key', $confirm_key);
    $this->request->overwrite('confirm_uid', $this->user->data['user_id']);
    $this->request->overwrite('sess', $this->user->data['session_id']);
    $this->request->overwrite('redirect', $redirect);

    // Include functions used my mcp_move_topic.
    if (!function_exists('mcp_move_topic'))
      include($this->root_path . 'includes/mcp/mcp_main.' . $this->phpEx);
    if (!function_exists('phpbb_check_ids'))
      include($this->root_path . 'includes/functions_mcp.' . $this->phpEx);
    if (!function_exists('make_forum_select'))
      include($this->root_path . 'includes/functions_admin.' . $this->phpEx);
    mcp_move_topic(array($prj_topic_id));
  }
}