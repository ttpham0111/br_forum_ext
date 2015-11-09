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

use ttpham\projects\prefixes;

class release_0_0_1 extends \phpbb\db\migration\migration
{
  private $version = '0.0.1';

  public function effectively_installed()
  {
    $installed_version = $this->config['likes_version'];
    return isset($installed_version) && version_compare($installed_version, $this->version, '>=');
  }

  public function update_schema()
  {
    return array(
      'add_tables' => array(
        $this->table_prefix . prefixes::TABLE . 'releases' => array(
          'COLUMNS' => array(
            'id'       => array('UINT', null, 'auto_increment'),
            'topic_id' => array('UINT', 0),
          ),
          'PRIMARY_KEY' => 'id',
          'KEYS' => array(
            'idx_topic' => array('INDEX', array('topic_id'))
          )
        )
      )
    );
  }

  public function update_data()
  {
    return array(
      array('config.add', array('projects_version', $this->version)),

      array('module.add', array(
        'acp',
        'ACP_CAT_DOT_MODS',
        'ACP_PROJECTS'
      )),

      array('module.add', array(
        'acp', 'ACP_PROJECTS', array(
          'module_basename' => '\ttpham\projects\acp\projects_module',
          'modes'           => array('settings')
        )
      ))
    );
  }

  public function revert_schema()
  {
    return array(
      'drop_tables' => array(
        $this->table_prefix . prefixes::TABLE . 'releases'
      )
    );
  }

  public function revert_data()
  {
    return array(
      array('config.remove', array('projects_version', $this->version)),

      array('module.remove', array(
        'acp', 'ACP_PROJECTS', array(
          'module_basename' => '\ttpham\projects\acp\projects_module'
        ),
      )),
      array('module.remove', array(
        'acp', 'ACP_CAT_DOT_MODS', 'ACP_PROJECTS'
      )),
    );
  }
}