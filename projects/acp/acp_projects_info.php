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

namespace ttpham\projects\acp;

class acp_projects_info
{
	function module()
	{
		return array(
      'filename' => '\ttpham\projects\acp\acp_projects_module',
      'title'    => 'ACP_PROJECTS',
      'modes'    => array(
        'settings' => array(
          'title'  => 'ACP_PROJECTS_SETTINGS',
          'auth'   => 'ext_ttpham/projects && acl_a_board',
          'cat'    => array('ACP_PROJECTS')
        ),
        'manage_projects' => array(
          'title' => 'ACP_MANAGE_PROJECTS',
          'auth'  => 'ext_ttpham/projects && acl_a_board',
          'cat'   => array('ACP_PROJECTS')
        )
      )
		);
	}
}
