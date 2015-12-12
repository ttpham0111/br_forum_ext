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

class release_0_0_1 extends \phpbb\db\migration\migration
{
  private $version = '0.0.1';

  public function effectively_installed()
  {
    $installed_version = $this->config['projects_version'];
    return isset($installed_version) && version_compare($installed_version, $this->version, '>=');
  }

  public function update_schema()
  {
    return array(
      'add_tables' => array(
        $this->table_prefix . 'prj_releases' => array(
          'COLUMNS' => array(
            'release_id'  => array('UINT', null, 'auto_increment'),
            'topic_id'    => array('UINT', 0),
          ),
          'PRIMARY_KEY' => 'release_id',
          'KEYS'        => array(
            'idx_topic' => array('INDEX', array('topic_id'))
          )
        ),
        $this->table_prefix . 'prj_projects' => array(
          'COLUMNS' => array(
            'project_id'        => array('UINT', null, 'auto_increment'),
            'topic_id'          => array('UINT', 0),
            'project_title'     => array('VCHAR:100', ''),
            'current_stage_id'  => array('UINT', 0),
          ),
          'PRIMARY_KEY' => 'project_id',
          'KEYS'        => array(
            'idx_topic' => array('INDEX', array('topic_id')),
            'idx_stage' => array('INDEX', array('current_stage_id'))
          )
        ),
        $this->table_prefix . 'prj_stages' => array(
          'COLUMNS' => array(
            'stage_id'         => array('UINT', null, 'auto_increment'),
            'project_id'       => array('UINT', 0),
            'status_id'        => array('UINT', 0),
            'project_deadline' => array('TIMESTAMP', 0)
          ),
          'PRIMARY_KEY' => 'stage_id',
          'KEYS'        => array(
            'idx_project' => array('INDEX', array('project_id')),
            'idx_status'  => array('INDEX', array('status_id'))
          )
        ),
        $this->table_prefix . 'prj_statuses' => array(
          'COLUMNS' => array(
            'status_id'   => array('UINT', null, 'auto_increment'),
            'status_name' => array('VCHAR:25', '')
          ),
          'PRIMARY_KEY' => 'status_id',
          'KEYS'        => array(
            'status_x' => array('UNIQUE', 'status_name')
          )
        )
      )
    );
  }

  public function update_data()
  {
    return array(
      array('config.add', array('projects_version', $this->version)),

      array('config.add', array('prj_default_table_size', 5)),

      array('config.add', array('prj_releases_forum_id', 0)),
      array('config.add', array('prj_number_of_releases_to_display', 0)),

      array('config.add', array('prj_project_forum_ids', '')),
      array('config.add', array('prj_number_of_projects_to_display', 0)),
      array('config.add', array('prj_days_threshold', 30)),
      array('config.add', array('prj_allow_projects_form', true)),

      array('permission.add', array('m_prj_stageup', true, 'm_move')),

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
        $this->table_prefix . 'prj_releases',
        $this->table_prefix . 'prj_projects',
        $this->table_prefix . 'prj_stages',
        $this->table_prefix . 'prj_statuses'
      )
    );
  }

  public function revert_data()
  {
    return array(
      array('config.remove', array('projects_version')),

      array('config.remove', array('prj_default_table_size')),

      array('config.remove', array('prj_releases_forum_id')),
      array('config.remove', array('prj_number_of_releases_to_display')),

      array('config.remove', array('prj_project_forum_ids')),
      array('config.remove', array('prj_number_of_projects_to_display')),
      array('config.remove', array('prj_days_threshold')),
      array('config.remove', array('prj_allow_projects_form')),

      array('permission.remove', array('m_prj_stageup')),

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