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

namespace ttpham\projects\mcp;

class mcp_projects_info
{
  function module()
  {
    return array(
      'filename' => '\ttpham\projects\mcp\mcp_projects_module',
      'title'    => 'MCP_PROJECTS',
      'modes'    => array(
        'release_requests' => array(
          'title'  => 'MCP_RELEASE_REQUESTS',
          'auth'   => 'ext_ttpham/projects && aclf_m_approve',
          'cat'    => array('MCP_PROJECTS')
        )
      )
    );
  }
}
