<?php
/**
 *
 * @package Project Tables Extension
 * @copyright //
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Create projects table specifically for breaking-records.com.
 */

namespace ttpham\project_table\acp;

class project_table_info
{
	function module()
	{
		return array(
      'filename' => '\ttpham\project_table_module',
      'title'    => 'PROJECT_TABLE',
      'modes'    => array(
        'project_table_config' => array('title' => 'PT_CONFIG', 'auth' => 'ext_ttpham/project_table && acl_a_board', 'cat' => array('PROJECT_TABLE')),
      )
		);
	}
}