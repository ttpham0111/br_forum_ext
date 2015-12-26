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

namespace ttpham\projects\migrations;

class release_0_0_4 extends \phpbb\db\migration\migration
{
  private $version = '0.0.4';

  public function effectively_installed()
  {
    $installed_version = $this->config['projects_version'];
    return isset($installed_version) && version_compare($installed_version, $this->version, '>=');
  }

  public static function depends_on()
  {
    return array(
      '\ttpham\projects\migrations\release_0_0_3'
    );
  }

  public function update_data()
  {
    return array(
      array('config.update', array('projects_version', $this->version)),
      
      array('config.add', array('prj_prune_projects_last_gc', 0)),
      array('config.add', array('prj_prune_projects_gc', (60 * 60 * 24 * 7))), // 7 Days.
      array('config.add', array('prj_prune_releases_last_gc', 0)),
      array('config.add', array('prj_prune_releases_gc', (60 * 60 * 24 * 7))) // 7 Days.
    );
  }

  public function revert_data()
  {
    return array(
      array('config.remove', array('prj_prune_projects_last_gc')),
      array('config.remove', array('prj_prune_projects_gc')),
      array('config.remove', array('prj_prune_releases_last_gc')),
      array('config.remove', array('prj_prune_releases_gc'))
    );
  }
}