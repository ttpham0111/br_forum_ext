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

class release_0_0_5 extends \phpbb\db\migration\migration
{
  private $version = '0.0.5';

  public function effectively_installed()
  {
    $installed_version = $this->config['projects_version'];
    return isset($installed_version) && version_compare($installed_version, $this->version, '>=');
  }

  public static function depends_on()
  {
    return array(
      '\ttpham\projects\migrations\release_0_0_4'
    );
  }

  public function update_data()
  {
    return array(
      array('config.update', array('projects_version', $this->version)),
      
      array('config.add', array('prj_projects_table_size', 0)),
      array('config.add', array('prj_releases_table_ratio', 2.0)),
      array('config.remove', array('prj_allow_projects_form'))
    );
  }

  public function revert_data()
  {
    return array(
      array('config.remove', array('prj_projects_table_size')),
      array('config.remove', array('prj_releases_table_ratio')),
      array('config.add', array('prj_allow_projects_form', true))
    );
  }
}