<?php
/**
 *
 * @package Project Tables Extension
 * @copyright //
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Create projects table specifically for breaking-records.com.
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
  'PROJECT_TABLE' => 'Project Table',
  'PROJECT_TABLE_LIST' => 'Display on "project table"',
  'PROJECT_TABLE_LIST_EXP' => ''))