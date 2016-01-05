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

class prj_module_acp extends \phpbb\db\migration\migration
{
  public function effectively_installed()
  {
    $sql = 'SELECT module_id
            FROM ' . MODULES_TABLE . "
            WHERE module_class = 'acp'
              AND module_basename = '\\ttpham\\projects\\acp\\acp_projects_module'
              AND module_mode = 'settings'";
    $result = $this->db->sql_query($sql);
    $module_id = $this->db->sql_fetchfield('module_id');
    $this->db->sql_freeresult($result);

    return $module_id !== false;
  }

  public function update_data()
  {
    return array(
      array('module.add', array('acp', 'ACP_CAT_DOT_MODS', 'ACP_PROJECTS')),
      array('module.add', array(
        'acp',
        'ACP_PROJECTS',
        array(
          'module_basename' => '\ttpham\projects\acp\acp_projects_module',
          'modes'           => array('settings', 'manage_projects')
        )
      ))
    );
  }

  public function revert_data()
  {
    return array(
      array('module.remove', array(
        'acp',
        'ACP_PROJECTS',
        array(
          'module_basename' => '\ttpham\projects\acp\acp_projects_module'
        ),
      )),
      array('module.remove', array('acp', 'ACP_CAT_DOT_MODS', 'ACP_PROJECTS'))
    );
  }
}