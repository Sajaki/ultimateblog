<?php
/**
*
* @package phpBB Extension - Ultimate Blog
* @copyright (c) 2015 posey
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace posey\ultimateblog\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
* Event Listener
*/
class listener implements EventSubscriberInterface
{
	protected $user;
	protected $template;
	protected $db;
	protected $config;
	protected $auth;
	protected $helper;
	protected $request;
	protected $phpbb_root_path;
	protected $php_ext;

	/**
	* Constructor
	*/
	public function __construct(
		\phpbb\user $user,
		\phpbb\template\template $template,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\config\config $config,
		\phpbb\auth\auth $auth,
		\phpbb\controller\helper $helper,
		\phpbb\request\request $request,
		$phpbb_root_path,
		$php_ext)
	{
		$this->user		= $user;
		$this->template	= $template;
		$this->db		= $db;
		$this->config	= $config;
		$this->auth		= $auth;
		$this->helper	= $helper;
		$this->request	= $request;
		$this->phpbb_root_path	= $phpbb_root_path;
		$this->php_ext			= $php_ext;
	}

	static public function getSubscribedEvents()
	{
		return [
			'core.user_setup'		=> 'set_blog_lang',
			'core.page_header'		=> 'add_page_header_link',
		];
	}

	public function set_blog_lang($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = [
			'ext_name' => 'posey/ultimateblog',
			'lang_set' => 'common',
		];
		$event['lang_set_ext'] = $lang_set_ext;
	}

	public function add_page_header_link($event)
	{
		$this->template->assign_vars(array(
			'U_UB_BLOG'		=> $this->helper->route('posey_ultimateblog_blog'),
		));
	}
}