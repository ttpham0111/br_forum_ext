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

class prj_initial_permissions extends \phpbb\db\migration\migration
{
  public function update_data()
  {
    return array(

      // Moderator permissions.
      array('permission.add', array('m_prj_stageup', true, 'm_move')),
      array('permission.add', array('m_prj_release_project', false)),
      array('permission.add', array('m_prj_finished_project', false)),

      // Forum permissions.
      array('permission.add', array('f_prj_projects', false))

      // Don't set default local permissions because they determine
      // where projects are held and released to.
    );
  }

  public function revert_data()
  {
    return array(
      array('permission.remove', array('m_prj_stageup')),
      array('permission.remove', array('m_prj_release_project', false)),
      array('permission.remove', array('m_prj_finished_project', false)),
      array('permission.remove', array('f_prj_projects', false))
    );
  }
}