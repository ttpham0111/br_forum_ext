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
  'M_PRJ_STAGEUP'          => 'Can move project stage forward and release project.',
  'M_PRJ_RELEASE_PROJECT'  => 'Can release projects in this forum.',
  'M_PRJ_FINISHED_PROJECT' => 'Can move finished projects to this forum.',
  'F_PRJ_PROJECTS'         => 'Can host projects in this forum.',

  'RELEASES_TABLE'         => 'New Releases',
  'NEW_PROJECTS_TABLE'     => 'New Projects',
  'ONGOING_PROJECTS_TABLE' => 'Ongoing Projects',

  'PRJ_RELEASE_PROJECT' => 'Release the project',
  'PRJ_REQUEST_RELEASE' => 'Send a request to release the project',
  'PRJ_STAGEUP'         => 'Change status to',
  
  'PRJ_FORM_DEADLINES'    => 'Deadlines',
  'PRJ_FORM_ADD_DEADLINE' => '(+) Click to add another deadline.',

  'PRJ_STAGES_DEADLINE_CONNECT' => 'deadline is on',

  'PRJ_RELEASE_CODES_LIST' => 'Release Code',

  'PRJ_CONFIRM' => 'Are you sure you want to'
));
