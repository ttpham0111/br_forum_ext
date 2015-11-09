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

namespace ttpham\projects\event;

/**
* @ignore
*/
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
* Event listener
*/
class listener implements EventSubscriberInterface
{
  /** @var \ttpham\projects\controller\main */
  private $prj_functions;

  /* @var \phpbb\template\template */
  private $template;

  /**
  * Constructor
  */
  public function __construct(
    \ttpham\projects\controller\main $prj_functions,
    \phpbb\template\template $template
  )
  {
    $this->prj_functions = $prj_functions;
    $this->template = $template;
  }

  static public function getSubscribedEvents()
  {
    return array(
      'core.index_modify_page_title' => 'display_project_tables',
    );
  }

  /**
  * Event: core.index_modify_page_title.
  *
  * Displays the project tables on above the forumbody.
  */
  public function display_project_tables()
  {
    $this->prj_functions->display_project_tables();

    // Include dependencies.
    $this->template->assign_vars(array(
      'PRJ_INCLUDECSS' => true
    ));
  }
}
