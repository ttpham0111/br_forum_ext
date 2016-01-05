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

namespace ttpham\projects;

class ext extends \phpbb\extension\base
{
  /**
  * Check whether or not the extension can be enabled.
  * The current phpBB version should meet or exceed
  * the minimum version required by this extension:
  *
  * Requires phpBB 3.1.3 due to usage of container aware migrations.
  *
  * @return bool
  * @access public
  */
  public function is_enableable()
  {
    $config = $this->container->get('config');
    return phpbb_version_compare($config['version'], '3.1.3', '>=');
  }

  /**
  * Overwrite enable_step to enable release request notifications
  * before any included migrations are installed.
  *
  * @param mixed $old_state State returned by previous call of this method
  * @return mixed Returns false after last step, otherwise temporary state
  * @access public
  */
  public function enable_step($old_state)
  {
    switch ($old_state)
    {
      case '': // Empty means nothing has run yet
        // Enable release request notifications
        $phpbb_notifications = $this->container->get('notification_manager');
        $phpbb_notifications->enable_notifications('ttpham.projects.notification.type.release_request');
        return 'notifications';
      break;
      default:
        // Run parent enable step method
        return parent::enable_step($old_state);
      break;
    }
  }

  /**
  * Overwrite disable_step to disable release request notifications
  * before the extension is disabled.
  *
  * @param mixed $old_state State returned by previous call of this method
  * @return mixed Returns false after last step, otherwise temporary state
  * @access public
  */
  public function disable_step($old_state)
  {
    switch ($old_state)
    {
      case '': // Empty means nothing has run yet
        // Disable release request notifications
        $phpbb_notifications = $this->container->get('notification_manager');
        $phpbb_notifications->disable_notifications('ttpham.projects.notification.type.release_request');
        return 'notifications';
      break;
      default:
        // Run parent disable step method
        return parent::disable_step($old_state);
      break;
    }
  }

  /**
  * Overwrite purge_step to purge release request notifications before
  * any included and installed migrations are reverted.
  *
  * @param mixed $old_state State returned by previous call of this method
  * @return mixed Returns false after last step, otherwise temporary state
  * @access public
  */
  public function purge_step($old_state)
  {
    switch ($old_state)
    {
      case '': // Empty means nothing has run yet
        // Purge release request notifications
        $phpbb_notifications = $this->container->get('notification_manager');
        $phpbb_notifications->purge_notifications('ttpham.projects.notification.type.release_request');
        return 'notifications';
      break;
      default:
        // Run parent purge step method
        return parent::purge_step($old_state);
      break;
    }
  }
}