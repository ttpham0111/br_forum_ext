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

  // Project Settings.
  'ACP_PROJECTS'                => 'Projects',
  'ACP_PROJECTS_SETTINGS'       => 'Projects Settings',
  'ACP_PROJECTS_SETTINGS_TITLE' => 'Projects Settings',
  'ACP_PROJECTS_SETTINGS_SAVED' => 'Projects Settings Saved.',

  'PRJ_DEFAULT_TABLE_SIZE'       => 'Default table size',
  'PRJ_DEFAULT_TABLE_SIZE_EXP'   => 'The table will never be smaller than this size.',
  'PRJ_PROJECTS_TABLE_GUEST'     => 'Projects table visible to guest',
  'PRJ_PROJECTS_TABLE_GUEST_EXP' => 'Enable to allow guest to see ongoing and
                                     released projects on the index page. Note:
                                     The guest will see an empty table if
                                     disabled. Users without permissions to
                                     host projects/release projects will not
                                     see projects in those forums where they do
                                     not have permission.',

  'PRJ_RELEASES_FORUMS'                   => 'Releases Forums (read only)',
  'PRJ_RELEASES_FORUMS_EXP'               => 'List of forums that projects are released into. This
                                              list is derived from forums where moderators
                                              are allowed to post a new release.',
  'PRJ_NUMBER_OF_RELEASES_TO_DISPLAY'     => 'Number of Releases',
  'PRJ_NUMBER_OF_RELEASES_TO_DISPLAY_EXP' => 'Enter the number of releases to display
                                              (Enter 0 to display as many as the projects table).',
  'PRJ_MOVE_AFTER_RELEASE'                => 'Move after release',
  'PRJ_MOVE_AFTER_RELEASE_EXP'            => 'Enable to move the project after releasing',
  'PRJ_MOVE_LOCK_TOPICS'                  => 'Lock after move',
  'PRJ_MOVE_LOCK_TOPICS_EXP'              => 'Enable to lock the project after moving it to the
                                              specified forum.',
  'PRJ_FINISHED_FORUMS'                   => 'Finished Projects Forums (read only)',
  'PRJ_FINISHED_FORUMS_EXP'               => 'List of forums for finished projects. This list is
                                              derived from forums where moderators can move
                                              finished projects into.',

  'PRJ_PROJECTS_FORUMS'                    => 'Projects Forums (read only)',
  'PRJ_PROJECTS_FORUMS_EXP'                => 'List of forums where projects can be hosted. This
                                               list is derived from forums where users can host
                                               projects.',
  'PRJ_NUMBER_OF_PROJECTS_TO_DISPLAY'     => 'Number of Projects',
  'PRJ_NUMBER_OF_PROJECTS_TO_DISPLAY_EXP' => 'Enter the number of projects to display
                                              (Enter 0 to display all ongoing projects.',
  'PRJ_DAYS_THRESHOLD'                    => 'Days till decay',
  'PRJ_DAYS_THRESHOLD_EXP'                => 'The number of days until a project is no longer considered new.',

  'PRJ_PRUNE_RELEASES_GC'        => 'Prune Releases Table Interval',
  'PRJ_PRUNE_RELEASES_GC_EXP'    => 'Days between clean up of rows in the releases
                                     table. Only a certain amount is kept in the
                                     database at once to save memory.',
  'PRJ_RELEASES_TABLE_RATIO'     => 'Reserved Ratio',
  'PRJ_RELEASES_TABLE_RATIO_EXP' => 'This value multiplied by the table size shown
                                     on the index page is the number of releases
                                     that are kepted in the database. CAUTION: If
                                     not enough are kept in the database, the
                                     releases table will contain empty rows.',
  'PRJ_PRUNE_PROJECTS_GC'        => 'Prune Projects Table Interval',
  'PRJ_PRUNE_PROJECTS_GC_EXP'    => 'Days between clean up of projects and stages
                                     table. Projects and stages that are no longer
                                     active will be deleted.',

  'PRJ_RESET_DATABASE'     => 'Reset Releases Table',
  'PRJ_RESET_DATABASE_EXP' => 'Enable to reset the database all releases.
                               CAUTION: Forum IDS must be saved and valid
                               before doing this operation',
  'PRJ_PRUNE_DATABASE'     => 'Prune Database',
  'PRJ_PRUNE_DATABASE_EXP' => 'Enable to prune the projects, stages, and releases
                               table.',

  // Manage Projects.
  'ACP_MANAGE_PROJECTS'       => 'Manage Projects Database',
  'ACP_MANAGE_PROJECTS_TITLE' => 'Manage Projects Database',
  'ACP_MANAGE_PROJECTS_SAVED' => 'Projects Settings Saved.',

  'PRJ_MOVE_RULES_LIST' => 'Move Rules',
  'PRJ_MOVE_RULES_EXP'  => 'Make changes to the move rules here.
                            These move rules determine where a project
                            will be moved to after it is released, and which
                            release forum it will be released to.',

  'PRJ_MOVE_FROM'        => 'Projects Forums',
  'PRJ_MOVE_TO_RELEASES' => 'Releases Forums',
  'PRJ_MOVE_TO_FINISHED' => 'Finished Projects Forums',

  'PRJ_STATUSES_LIST' => 'Statuses',
  'PRJ_STATUSES_EXP'  => 'Make changes to statuses in the database
                          here. All primary statuses will be suggested
                          to the user when creating new projects.
                          You may only delete statuses that are not
                          currently in use by any project.',

  'PRJ_STATUSES'         => 'Status Name',
  'PRJ_STATUSES_IN_USE'  => 'In Use',
  'PRJ_PRIMARY_STATUSES' => 'Primary',
  'PRJ_DELETE_STATUSES'  => 'Remove',

  'PRJ_STATUS_IN_USE' => '&#10003;',
  'PRJ_ADD_STATUS'    => '(+) Click to add a new status.',

  'PRJ_RELEASE_CODES_LIST' => 'Release Codes',
  'PRJ_RELEASE_CODES_EXP'  => 'Make changes to the release codes in the
                               database here. The index is the current
                               number of releases using that release
                               code.',

  'PRJ_RELEASE_CODES'           => 'Release Code',
  'PRJ_RELEASE_CODES_FORUM_IDS' => 'Forum IDs',
  'PRJ_RELEASE_CODES_INDEX'     => 'Index',
  'PRJ_DELETE_RELEASE_CODES'    => 'Remove',

  'PRJ_ADD_RELEASE_CODE' => '(+) Click to add a new release code.'
));
