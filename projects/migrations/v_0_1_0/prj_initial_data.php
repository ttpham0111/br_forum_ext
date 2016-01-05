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

namespace ttpham\projects\migrations\v_0_1_0;

class prj_initial_data extends \phpbb\db\migration\migration
{
  private $version = '0.1.0';

  public function effectively_installed()
  {
    $installed_version = $this->config['projects_version'];
    return isset($installed_version) && version_compare($installed_version, $this->version, '>=');
  }

  public static function depends_on()
  {
    return array(
      '\ttpham\projects\migrations\v_0_1_0\prj_initial_schema',
      '\ttpham\projects\migrations\v_0_1_0\prj_initial_permissions',
      '\ttpham\projects\migrations\v_0_1_0\prj_module_acp',
      '\ttpham\projects\migrations\v_0_1_0\prj_module_mcp'
    );
  }
  
  public function update_data()
  {
    return array(
      array('config.add', array('projects_version', $this->version)),

      array('config.add', array('prj_default_table_size', 5)),
      array('config.add', array('prj_projects_table_size', 0)),
      array('config.add', array('prj_projects_table_guest', true)),

      array('config.add', array('prj_number_of_releases_to_display', 0)),
      array('config.add', array('prj_move_after_release', true)),
      array('config.add', array('prj_move_lock_topics', true)),

      array('config.add', array('prj_number_of_projects_to_display', 0)),
      array('config.add', array('prj_days_threshold', 30)),

      array('config.add', array('prj_prune_projects_last_gc', 0)),
      array('config.add', array('prj_prune_projects_gc', (60 * 60 * 24 * 7))), // 7 Days.
      array('config.add', array('prj_prune_releases_last_gc', 0)),
      array('config.add', array('prj_prune_releases_gc', (60 * 60 * 24 * 7))), // 7 Days.
      array('config.add', array('prj_releases_table_ratio', 2.0))
    );
  }

  public function revert_data()
  {
    return array(
      array('config.remove', array('projects_version')),

      array('config.remove', array('prj_default_table_size')),
      array('config.remove', array('prj_projects_table_size')),
      array('config.remove', array('prj_projects_table_guest')),

      array('config.remove', array('prj_number_of_releases_to_display')),
      array('config.remove', array('prj_move_after_release')),
      array('config.remove', array('prj_move_lock_topics')),

      array('config.remove', array('prj_number_of_projects_to_display')),
      array('config.remove', array('prj_days_threshold')),

      array('config.remove', array('prj_prune_projects_last_gc')),
      array('config.remove', array('prj_prune_projects_gc')),
      array('config.remove', array('prj_prune_releases_last_gc')),
      array('config.remove', array('prj_prune_releases_gc')),
      array('config.remove', array('prj_releases_table_ratio'))
    );
  }
}