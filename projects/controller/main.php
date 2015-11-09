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

use ttpham\projects\prefixes;

class main
{
  /** @var \phpbb\config\config */
  private $config;

  /** @var \phpbb\db\driver\driver_interface */
  private $db;

  /** @var \phpbb\template\template */
  private $template;

  /** @var \phpbb\user */
  private $user;

  /** @var string phpBB root path */
  private $root_path;

  /** @var string PHP extension */
  private $phpEx;

  public function __construct(\phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, \phpbb\template\template $template, \phpbb\user $user, $root_path, $phpEx)
  {
    $this->config = $config;
    $this->db = $db;
    $this->template = $template;
    $this->user = $user;
    $this->root_path = $root_path;
    $this->phpEx = $phpEx;
  }

  public function display_project_tables()
  {
    // If bot, don't waste time querying database.
    if ($this->user->data['is_bot'])
      return;

    $this->user->add_lang_ext('ttpham/projects', 'projects');

    // Grab config varibles.
    $display_above = $config[prefixes::CONFIG . 'display_above_forumbody'];

    // Nothing to display.
    if (!$display_above)
      return;

    $this->display_releases_table();
    // $this->display_projects_table();
  }

  private function display_releases_table($tpl_loopname = 'prj_releases_table')
  {
    // Grab config variables.
    $num_releases = $config[prefixes::CONFIG . 'number_of_releases_to_display'];

    $this->template->assign_block_vars($tpl_loopname, array(
        'PROJECT_NAME'   => 'test',
        'HOST'           => 'test1',
        'HOST_COLOR'     => 'test11',
        'U_VIEW_TOPIC'   => 'test111',
        'U_VIEW_PROFILE' => 'test1111'
      ));
    
    // Nothing to display.
    if ($num_releases <= 0)
      return;

    // Grab releases in forum id provided.
    $sql = 'SELECT t.topic_id, t.topic_poster, t.topic_first_poster_name, t.topic_first_poster_colour, t.topic_title, t.forum_id
    FROM ' . $this->table_prefix . prefixes::TABLE . 'releases r
    LEFT JOIN ' . TOPICS_TABLE . ' t
      ON r.topic_id = t.topic_id
    ORDER BY t.topic_time ASC';
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
      $host_name = $row['topic_first_poster_name'];
      $host_id = $row['topic_poster'];
      $host_color = $row['topic_first_poster_colour'];
      $topic_id = $row['topic_id'];
      $forum_id = $row['forum_id'];
      
      $view_topic_url = append_sid("{$this->root_path}viewtopic.$this->phpEx", 'f=' . $forum_id . '&amp;t=' . $topic_id);
      $view_user_profile = append_sid("{$this->root_path}memberlist.$this->phpEx", 'mode=viewprofile&amp;u=' . $host_id);
      $tpl_ary = array(
        'PROJECT_NAME'   => $project_name,
        'HOST'           => $host_name,
        'HOST_COLOR'     => $host_color,
        'U_VIEW_TOPIC'   => $view_topic_url,
        'U_VIEW_PROFILE' => $view_user_profile
      );

      $this->template->assign_block_vars($tpl_loopname, $tpl_ary);
    }

    $this->template->assign_vars(array(
      strtoupper($tpl_loopname) . '_DISPLAY' => true
    ));
  }



  //   // Project ids are stored as comma seperated string.
  //   $project_forum_ids[] = explode(',', $config[prefixes::CONFIG . 'project_forum_ids']);
  //   $num_projects = $config[prefixes::CONFIG . 'number_of_projects_to_display'];

  //   // Set num_rows
  //   $rows_required = 0;
  //   $sql = 'SELECT topic_id, forum_id, COUNT(topic_id) t_count
  //     FROM ' . TOPICS_TABLE . '
  //     WHERE ' . $this->db->sql_in_set('topic_id', $project_forum_ids) . '
  //     GROUP BY forum_id
  //     ORDER BY t_count DESC';

  //   $result = $this->db->sql_query_limit($sql, 1);
  //   $rows_required = sql_fetchrow($result)['t_count'];
  //   $this->db->sql_freeresult($result);

  //   if ($num_rows < $rows_required)
  //   {
  //     $num_rows = $rows_required;
  //   }

  //   // Retrieve topic ids from release forum.
  //   $sql = 'SELECT topic_id, forum_id
  //     FROM ' . TOPICS_TABLE . '
  //     WHERE forum_id = ' . $release_forum_id;
  //   $result = $this->db->sql_query_limit($sql, $num_rows);

  //   while ($row = $this->db->sql_fetchrow($result))
  //   {
  //     $topic_ids[] = $row['topic_id'];
  //   }
  //   $this->db->sql_freeresult($result);

  //   // Retrieve topic ids from projects forum.
  //   $sql = 'SELECT topic_id, forum_id
  //     FROM ' . TOPICS_TABLE . '
  //     WHERE ' . $this->db->sql_in_set('topic_id', $project_forum_ids);
  //   $result = $this->db->sql_query($sql);

  //   while ($row = $this->db->sql_fetchrow($result))
  //   {
  //     $topic_ids[] = $row['topic_id'];
  //   }
  //   $this->db->sql_freeresult($result);

  //   // Retrieve all topics in topic_ids.
  //   $sql = 'SELECT t.topic_id, t.forum_id, t.topic_title, u.username
  //     FROM ' . TOPICS_TABLE . ' t
  //     LEFT_JOIN ' . USERS_TABLE . ' u
  //       ON t.topic_poster = u.user_id
  //     WHERE ' . $this->db->sql_in_set('t.topic_id', $topic_ids);
  //   $result = $this->db->sql_query($sql);

  //   $rowset = array();

  //   while ($row = $this->db->sql_fetchrow($result))
  //   {
  //     $rowset[] = $row;
  //   }
  //   $this->db->sql_freeresult($result);

  //   if (sizeof($rowset))
  //   {
  //     foreach($rowset as $row)
  //     {
  //       $forum_id    = $row['forum_id'];
  //       $topic_id    = $row['topic_id'];
  //       $topic_title = $row['topic_title'];
  //       $username    = $row['username'];

  //       $view_topic_url = append_sid("{$this->root_path}viewtopic.$this->phpEx", 'f=' . $forum_id . '&amp;t=' .$topic_id);

  //       $topic_attr = get_topic_attr($topic_title);

  //       $tpl_array = array(
  //         'TOPIC_TYPE'   => $topic_attr.topic_type,
  //         'TOPIC_CODE'   => $topic_attr.topic_code,
  //         'TOPIC_NAME'   => $topic_attr.topic_name,
  //         'TOPIC_STATUS' => $topic_attr.topic_status,
  //         'TOPIC_DATE'   => $topic_attr.topic_date,
  //         'U_TOPIC_ICON' => $topic_attr.topic_icon,

  //         'U_TOPIC' => $view_topic_url
  //       );

  //       $this->template->assign_block_vars($tpl_loopname, $tpl_array);
  //     }

  //     $this->template->assign_vars(array(
  //       strtoupper($tpl_loopname) . '_DISPLAY' => true
  //     ));
  //   }
  // }
}
