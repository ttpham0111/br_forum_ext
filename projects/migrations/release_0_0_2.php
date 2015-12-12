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

class release_0_0_2 extends \phpbb\db\migration\migration
{
  private $version = '0.0.2';

  public function effectively_installed()
  {
    $installed_version = $this->config['projects_version'];
    return isset($installed_version) && version_compare($installed_version, $this->version, '>=');
  }

  public static function depends_on()
  {
    return array(
      '\ttpham\projects\migrations\release_0_0_1'
    );
  }

  public function update_data()
  {
    return array(
      array('config.add', array('projects_version', $this->version)),

      array('module.add', array(
        'acp', 'ACP_PROJECTS', array(
          'module_basename' => '\ttpham\projects\acp\projects_module',
          'modes'           => array('manage_projects')
        )
      ))
    );
  }
}