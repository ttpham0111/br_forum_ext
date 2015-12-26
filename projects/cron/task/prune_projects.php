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

namespace ttpham\projects\cron\task;

class prune_projects extends \phpbb\cron\task\base
{
  /* @var \phpbb\config\config */
  private $config;

  /** @var \ttpham\projects\services\projects_helper */
  private $projects_helper;

  public function __construct(
    \phpbb\config\config                      $config,
    \ttpham\projects\services\projects_helper $projects_helper
  )
  {
    $this->config              = $config;
    $this->projects_helper     = $projects_helper;
  }

  /**
  * Returns whether this cron task should run now, because enough time
  * has passed since it was last run.
  *
  * The interval between pruning projects is specified in board
  * configuration.
  *
  * @return bool
  */
  public function should_run()
  {
    return $this->config['prj_prune_projects_last_gc'] < time() - $this->config['prj_prune_projects_gc'];
  }

  /**
  * Prune projects that are no longer found in the project forums along with its
  * stages in the projects table and stages table.
  */
  public function run()
  {
    $this->projects_helper->prune_projects();
    
    // Update last gc.
    $this->config->set('prj_prune_projects_last_gc', time());
  }
}
