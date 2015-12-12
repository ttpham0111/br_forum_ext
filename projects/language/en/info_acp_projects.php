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
  'ACP_PROJECTS'                => 'Projects',
  'ACP_PROJECTS_SETTINGS'       => 'Projects Settings',
  'ACP_PROJECTS_SETTINGS_SAVED' => 'Projects Settings Saved.',
  'ACP_MANAGE_PROJECTS'         => 'Manage Projects',

  'PRJ_SETTINGS'        => 'Settings',
  'PRJ_MANAGE_PROJECTS' => 'Manage Projects Database',

  'PRJ_DEFAULT_TABLE_SIZE'     => 'Default table size',
  'PRJ_DEFAULT_TABLE_SIZE_EXP' => 'The table will never be smaller than this size.',

  'PRJ_RELEASES_FORUM_ID'                 => 'Releases Forum ID',
  'PRJ_RELEASES_FORUM_ID_EXP'             => 'Enter the id of the releases forum.',
  'PRJ_NUMBER_OF_RELEASES_TO_DISPLAY'     => 'Number of Releases',
  'PRJ_NUMBER_OF_RELEASES_TO_DISPLAY_EXP' => 'Enter the number of releases to display ' .
                                             '(Enter 0 to display as many as the projects table).',
  
  'PRJ_PROJECT_FORUM_IDS'                 => 'Project Forum IDs',
  'PRJ_PROJECT_FORUM_IDS_EXP'             => 'Enter the ids of the project forums (ex. 13,42,10).',
  'PRJ_NUMBER_OF_PROJECTS_TO_DISPLAY'     => 'Number of Projects',
  'PRJ_NUMBER_OF_PROJECTS_TO_DISPLAY_EXP' => 'Enter the number of projects to display ' .
                                             '(Enter 0 to display all ongoing projects.',
  'PRJ_DAYS_THRESHOLD'                    => 'Days till decay',
  'PRJ_DAYS_THRESHOLD_EXP'                => 'The number of days until a project is no longer considered new.',
  'PRJ_ALLOW_PROJECTS_FORM'               => 'Allow projects form',
  'PRJ_ALLOW_PROJECTS_FORM_EXP'           => 'Enable to allow users to use a template to create new projects.',

  'PRJ_RESET_DATABASE'     => 'Reset Releases and Projects Table',
  'PRJ_RESET_DATABASE_EXP' => 'Enable to reset the database with new projects ' .
                              'and release. CAUTION: Forum IDS must be saved ' .
                              'and valid before doing this operation'
  ));
