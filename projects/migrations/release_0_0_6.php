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

class release_0_0_6 extends \phpbb\db\migration\migration
{
  private $version = '0.0.6';

  public function effectively_installed()
  {
    $installed_version = $this->config['projects_version'];
    return isset($installed_version) && version_compare($installed_version, $this->version, '>=');
  }

  public static function depends_on()
  {
    return array(
      '\ttpham\projects\migrations\release_0_0_5'
    );
  }

  public function update_schema()
  {
    return array(
      'add_columns' => array(
        $this->table_prefix . 'prj_statuses' => array(
          'primary_status' => array('BOOL', 0)
        )
      )
    );
  }

  public function update_data()
  {
    return array(
      array('config.update', array('projects_version', $this->version)),
    );
  }

  public function revert_schema()
  {
    return array(
      'drop_columns' => array(
        $this->table_prefix . 'prj_statuses' => array(
          'primary_status'
        )
      )
    );
  }
}