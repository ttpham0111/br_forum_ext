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

class release_0_0_7 extends \phpbb\db\migration\migration
{
  private $version = '0.0.7';

  public function effectively_installed()
  {
    $installed_version = $this->config['projects_version'];
    return isset($installed_version) && version_compare($installed_version, $this->version, '>=');
  }

  public static function depends_on()
  {
    return array(
      '\ttpham\projects\migrations\release_0_0_6'
    );
  }

  public function update_schema()
  {
    return array(
      'add_tables' => array(
        $this->table_prefix . 'prj_release_codes' => array(
          'COLUMNS' => array(
            'release_code_id'    => array('UINT', null, 'auto_increment'),
            'release_code_name'  => array('VCHAR:10', ''),
            'release_code_index' => array('UINT', 0)
          ),
          'PRIMARY_KEY' => 'release_code_id',
          'KEYS'        => array(
            'code_x'    => array('UNIQUE', 'release_code_name')
          )
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
      'drop_tables' => array(
        $this->table_prefix . 'prj_release_codes'
      )
    );
  }
}