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

namespace ttpham\projects\mcp;

class mcp_projects_module
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

    switch ($mode)
    {
      case 'release_requests':
      // no break.
      default:
        $this->tpl_name   = 'mcp_release_requests';
        $this->page_tutle = $user->lang('MCP_RELEASE_REQUESTS');
        $this->release_requests();
        break;
    }
  }

  private function release_requests($tpl_loopname = 'postrow')
  {
    global $auth, $db, $phpbb_container, $template, $user, $phpbb_root_path, $phpEx;
    $helper = $phpbb_container->get('controller.helper');

    // Permission to release project is required.
    if (!$auth->acl_get('m_prj_stageup'))
      return;
    
    $projects_forum_ids = $this->projects_helper->get_projects_forum_ids();
    $release_requests   = $this->projects_helper->get_release_requests();
    foreach ($release_requests as $index => $project_id)
    {
      $rowset = $this->projects_helper->get_project_data(
                  array('project_id' => $project_id),
                  array(
                    'JOIN_TOPICS_TABLE' => true,
                    'JOIN_FORUMS_TABLE' => true,
                    'SELECT' => array(
                                  'p.project_title',
                                  'p.request_time',
                                  'stg.project_deadline',
                                  'sts.status_name',
                                  't.topic_id',
                                  't.topic_poster',
                                  't.topic_first_poster_name',
                                  't.topic_first_poster_colour',
                                  'f.forum_id',
                                  'f.forum_name'
                    ),
                    'WHERE'    => '(p.release_request_sent = 1 AND p.current_stage_id = stg.stage_id AND ' . $db->sql_in_set('f.forum_id', $projects_forum_ids) . ')',
                    'LIMIT'    => 1
                  )
      );
      if (!sizeof($rowset))
        continue;

      $row      = $rowset[0];
      $forum_id = $row['forum_id'];
      $topic_id = $row['topic_id'];
      $template->assign_block_vars($tpl_loopname, array(
        'PROJECT_TITLE' => $this->projects_helper->build_topic_title($row['status_name'], $row['project_title'], $row['project_deadline']),
        'PROJECT_HOST'  => get_username_string('full', $row['topic_poster'], $row['topic_first_poster_name'], $row['topic_first_poster_colour']),
        'REQUEST_TIME'  => $user->format_date($row['request_time'], 'M jS'),
        'FORUM_NAME'    => $row['forum_name'],

        'S_ROW_COUNT'   => $index,

        'U_VIEWFORUM'   => append_sid("{$phpbb_root_path}viewforum.$phpEx", "f=$forum_id"),
        'U_VIEWTOPIC'   => append_sid("{$phpbb_root_path}viewtopic.$phpEx", "f=$forum_id&amp;t=$topic_id"),
        'U_STAGEUP'     => $helper->route(
                             'projects_stage_up_controller',
                             array(
                              'topic_id'        => $topic_id,
                              'release_project' => true)
                             )
      ));
    }
  }
}