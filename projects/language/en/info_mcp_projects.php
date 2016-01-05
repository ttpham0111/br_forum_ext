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

if (!defined('IN_PHPBB'))
{
  exit;
}
if (empty($lang) || !is_array($lang))
{
  $lang = array();
}

$lang = array_merge($lang, array(
  'MCP_PROJECTS'         => 'Projects',
  'MCP_RELEASE_REQUESTS' => 'Projects awaiting release',

  'PRJ_MCP_RELEASE_REQUESTS'     => 'Projects waiting to be released',
  'PRJ_MCP_RELEASE_REQUESTS_EXP' => 'This is a list of projects that
                                     are finished and are awaiting to be
                                     released.',

  'PRJ_MCP_PROJECTS'       => 'Projects',
  'PRJ_MCP_RELEASE_HEADER' => 'Release?',
  'PRJ_MCP_RELEASE'        => 'Release',

  'PRJ_MCP_SENT_BY'         => 'Request sent by',
  'PRJ_MCP_REQUEST_SENT_AT' => 'on',

  'PRJ_MCP_NO_TOPICS' => 'There are currently no projects waiting to be
                          released.'
));
