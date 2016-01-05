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

namespace ttpham\projects\notification;

/**
* Projects waiting to be released.
*
* This class handles notifications for projects when they are
* requested to be released by the project host.
*/
class release_request extends \phpbb\notification\type\topic
{
  /**
  * Get notification type name
  *
  * @return string
  */
  public function get_type()
  {
    return 'ttpham.projects.notification.type.release_request';
  }

  /**
  * Language key used to output the text
  *
  * @var string
  */
  protected $language_key = 'PRJ_NOTIFICATION_RELEASE_REQUEST';

  /**
  * Notification option data (for outputting to the user)
  *
  * @var bool|array False if the service should use it's default data
  *           Array of data (including keys 'id', 'lang', and 'group')
  */
  public static $notification_option = array(
    'lang'  => 'PRJ_NOTIFICATION_TYPE_RELEASE_PROJECT',
    'group' => 'NOTIFICATION_GROUP_MODERATION'
  );

  /**
  * Permission to check for (in find_users_for_notification)
  *
  * @var string Permission name
  */
  protected $permission = 'm_prj_stageup';

  /**
  * Is available
  */
  public function is_available()
  {
    $has_permission = $this->auth->acl_getf($this->permission, true);

    return (!empty($has_permission));
  }

  /**
  * Get the id of the item
  *
  * @param array $project The data from the project.
  */
  public static function get_item_id($project)
  {
    return (int) $project['topic_id'];
  }

  /**
  * Get the id of the parent
  *
  * @param array $project The data from the project.
  */
  public static function get_item_parent_id($project)
  {
    return (int) $project['forum_id'];
  }

  /**
  * Find the users who want to receive notifications
  *
  * @param array $topic Data from the topic
  * @param array $options Options for finding users for notification
  *
  * @return array
  */
  public function find_users_for_notification($topic, $options = array())
  {
    $options = array_merge(array(
      'ignore_users'    => array(),
    ), $options);

    $acl_list = $this->auth->acl_get_list(false, array('m_prj_stageup'));
    if (empty($acl_list))
      return array();

    return $this->check_user_notification_options($acl_list[$topic['forum_id']][$this->permission], $options);
  }
  
  /**
  * Get the url to this item
  *
  * @return string URL
  */
  public function get_url()
  {
    return append_sid($this->phpbb_root_path . 'mcp.' . $this->php_ext, "i=-ttpham-projects-mcp-mcp_projects_module&mode=release_requests");
  }

  /**
  * Function for preparing the data for insertion in an SQL query
  * (The service handles insertion)
  *
  * @param array $project Data from project
  * @param array $pre_create_data Data from pre_create_insert_array()
  *
  * @return array Array of data ready to be inserted into the database
  */
  public function create_insert_array($project, $pre_create_data = array())
  {
    $data = parent::create_insert_array($project, $pre_create_data);

    $this->notification_time = $data['notification_time'] = time();

    return $data;
  }
}