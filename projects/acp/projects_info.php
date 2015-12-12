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

class projects_info
{
	function module()
	{
		return array(
      'filename' => '\ttpham\projects\acp\projects_module',
      'title'    => 'PROJECT',
      'modes'    => array(
        'settings' => array(
          'title'  => 'ACP_PROJECTS_SETTINGS',
          'auth'   => 'ext_ttpham/projects && acl_a_board',
          'cat'    => array('PROJECTS')
        ),
        'manage_projects' => array(
          'title' => 'ACP_MANAGE_PROJECTS',
          'auth'  => 'ext_ttpham/projects && acl_a_board',
          'cat'   => array('PROJECTS')
        )
      )
		);
	}
}
