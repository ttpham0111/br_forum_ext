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

class prj_initial_schema extends \phpbb\db\migration\migration
{
  public function effectively_installed()
  {
    return $this->db_tools->sql_table_exists($this->table_prefix . 'prj_projects');
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
        ),
        $this->table_prefix . 'prj_projects' => array(
          'COLUMNS' => array(
            'project_id'           => array('UINT', null, 'auto_increment'),
            'topic_id'             => array('UINT', 0),
            'project_title'        => array('VCHAR:100', ''),
            'current_stage_id'     => array('UINT', 0),
            'release_request_sent' => array('BOOL', 0),
            'request_time'         => array('TIMESTAMP', 0)
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
            'status_id'      => array('UINT', null, 'auto_increment'),
            'status_name'    => array('VCHAR:25', ''),
            'primary_status' => array('BOOL', 0)
          ),
          'PRIMARY_KEY' => 'status_id',
          'KEYS'        => array(
            'status_x' => array('UNIQUE', 'status_name')
          )
        ),
        $this->table_prefix . 'prj_move_rules' => array(
          'COLUMNS' => array(
            'move_rule_id'    => array('UINT', null, 'auto_increment'),
            'move_from'       => array('UINT', 0),
            'move_to'      => array('UINT', 0)
          ),
          'PRIMARY_KEY' => 'move_rule_id',
          'KEYS'        => array(
            'idx_from' => array('INDEX', array('move_from')),
            'idx_to'   => array('INDEX', array('move_to'))
          )
        )
      )
    );
  }

  public function revert_schema()
  {
    return array(
      'drop_tables' => array(
        $this->table_prefix . 'prj_releases',
        $this->table_prefix . 'prj_release_codes',
        $this->table_prefix . 'prj_projects',
        $this->table_prefix . 'prj_stages',
        $this->table_prefix . 'prj_statuses',
        $this->table_prefix . 'prj_move_rules'
      )
    );
  }
}