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
			'core.user_setup'						=> 'set_blog_lang',
			'core.page_header'						=> 'add_page_header_link',
			'core.permissions'						=> 'permissions',
			'core.viewonline_overwrite_location'	=> 'viewonline_page',
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
		if ($this->config['ub_enabled'] == 1)
		{
			$this->template->assign_vars([
				'S_BLOG_ENABLED'	=> true,
				'U_BLOG'			=> $this->helper->route('posey_ultimateblog_blog'),
			]);
		}
	}

	public function viewonline_page($event)
	{
		if ($event['on_page'][1] == 'app')
		{
			if (strrpos($event['row']['session_page'], 'app.' . $this->php_ext . '/blog') === 0)
			{
				$event['location'] = $this->user->lang('BLOG_VIEWONLINE');
				$event['location_url'] = $this->helper->route('posey_ultimateblog_blog');
			}
			else if (strrpos($event['row']['session_page'], 'app.' . $this->php_ext . '/categor') === 0)
			{
				$event['location'] = $this->user->lang('CAT_VIEWONLINE');
				$event['location_url'] = $this->helper->route('posey_ultimateblog_categories');
			}
		}
	}

	public function permissions($event)
	{
		$permissions = $event['permissions'];
		$permissions += array(
			'u_blog_view'		=> array(
				'lang'		=> 'ACL_U_BLOG_VIEW',
				'cat'		=> 'ultimateblog'
			),
			'u_blog_make'	=> array(
				'lang'		=> 'ACL_U_BLOG_MAKE',
				'cat'		=> 'ultimateblog'
			),
			'u_blog_edit'	=> array(
				'lang'		=> 'ACL_U_BLOG_EDIT',
				'cat'		=> 'ultimateblog'
			),
			'u_blog_rate'	=> array(
				'lang'		=> 'ACL_U_BLOG_RATE',
				'cat'		=> 'ultimateblog'
			),
			'u_blog_report'	=> array(
				'lang'		=> 'ACL_U_BLOG_REPORT',
				'cat'		=> 'ultimateblog'
			),
			'u_blog_comment_make'	=> array(
				'lang'		=> 'ACL_U_BLOG_COMMENT_MAKE',
				'cat'		=> 'ultimateblog'
			),
			'u_blog_comment_edit'	=> array(
				'lang'		=> 'ACL_U_BLOG_COMMENT_EDIT',
				'cat'		=> 'ultimateblog'
			),
			'u_blog_comment_report'	=> array(
				'lang'		=> 'ACL_U_BLOG_COMMENT_REPORT',
				'cat'		=> 'ultimateblog'
			),
			'm_blog_edit'	=> array(
				'lang'		=> 'ACL_M_BLOG_EDIT',
				'cat'		=> 'ultimateblog'
			),
			'm_blog_delete'	=> array(
				'lang'		=> 'ACL_M_BLOG_DELETE',
				'cat'		=> 'ultimateblog'
			),
			'm_blog_lock'	=> array(
				'lang'		=> 'ACL_M_BLOG_LOCK',
				'cat'		=> 'ultimateblog'
			),
			'm_blog_comment_edit'	=> array(
				'lang'		=> 'ACL_M_BLOG_COMMENT_EDIT',
				'cat'		=> 'ultimateblog'
			),
			'm_blog_comment_delete'	=> array(
				'lang'		=> 'ACL_M_BLOG_COMMENT_DELETE',
				'cat'		=> 'ultimateblog'
			),
			'm_blog_reports'	=> array(
				'lang'		=> 'ACL_M_BLOG_REPORTS',
				'cat'		=> 'ultimateblog'
			),
			'a_blog_settings'	=> array(
				'lang'		=> 'ACL_A_BLOG_SETTINGS',
				'cat'		=> 'ultimateblog'
			),
			'a_blog_categories'	=> array(
				'lang'		=> 'ACL_A_BLOG_CATEGORIES',
				'cat'		=> 'ultimateblog'
			),
			'a_blog_tags'		=> array(
				'lang'		=> 'ACL_A_BLOG_TAGS',
				'cat'		=> 'ultimateblog',
			),
		);
		$event['permissions'] = $permissions;
		$categories['ultimateblog'] = 'ACL_CAT_ULTIMATEBLOG';
		$event['categories'] = array_merge($event['categories'], $categories);
	}
}