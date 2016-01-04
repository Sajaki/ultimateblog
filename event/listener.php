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
	# @var \phpbb\user
	protected $user;

	# @var \phpbb\template\template
	protected $template;

	# @var \phpbb\db\driver\driver_interface
	protected $db;

	# @var \phpbb\config\config
	protected $config;

	# @var \phpbb\auth\auth
	protected $auth;

	# @var \phpbb\controller\helper
	protected $helper;

	# @var \phpbb\request\request
	protected $request;

	# @var string phpBB root path
	protected $phpbb_root_path;

	# @var string phpEx
	protected $php_ext;

	# The database table the blogs are stored in
	# @var string
	protected $ub_blogs_table;

	# The database table the categories are stored in
	# @var string
	protected $ub_cats_table;

	# The database table the comments are stored in
	# @var string
	protected $ub_comments_table;

	/**
	* Constructor
	*
	* @param \phpbb\user						$user				User object
	* @param \phpbb\template\template			$template			Template object
	* @param \phpbb\db\driver\driver_interface	$db					Database object
	* @param \phpbb\config\config				$config				Config object
	* @param \phpbb\auth\auth					$auth				Auth object
	* @param \phpbb\controller\helper			$helper				Controller helper object
	* @param \phpbb\request\request				$request			Request object
	* @param string								$phpbb_root_path	phpBB root path
	* @param string								$php_ext			phpEx
	* @param string								$ub_blogs_table		Ultimate Blog blogs table
	* @param string								$ub_cats_table		Ultimate Blog categories table
	* @param string								$ub_comments_table	Ultimate Blog comments table
	* @access public
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
		$php_ext,
		$ub_blogs_table,
		$ub_cats_table,
		$ub_comments_table)
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
		$this->ub_blogs_table	= $ub_blogs_table;
		$this->ub_cats_table	= $ub_cats_table;
		$this->ub_comments_table = $ub_comments_table;
	}

	/**
	* Assign functions defined in this class to event listeners in the core
	*
	* @return array
	* @static
	* @access public
	*/
	static public function getSubscribedEvents()
	{
		return [
			'core.user_setup'							=> 'set_blog_lang',
			'core.page_header'							=> 'add_page_header_link',
			'core.memberlist_view_profile'				=> 'viewprofile',
			'core.modify_mcp_modules_display_option'	=> 'mcp_modules_display',
			'core.mcp_front_reports_count_query_before'	=> 'latest_blog_reports',
			'core.viewonline_overwrite_location'		=> 'viewonline_page',

			// ACP Event
			'core.permissions'							=> 'permissions',
		];
	}

	/**
	* Add Ultimate Blog language
	*
	* @param object		$event		Event object
	* @return null
	* @access public
	*/
	public function set_blog_lang($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = [
			'ext_name' => 'posey/ultimateblog',
			'lang_set' => 'common',
		];
		$event['lang_set_ext'] = $lang_set_ext;
	}

	/*
	* Add overall_header link for the blog page
	*
	* @return null
	* @access public
	*/
	public function add_page_header_link()
	{
		if (!empty($this->config['ub_enabled']))
		{
			$this->template->assign_vars([
				'S_BLOG_ENABLED'		=> $this->config['ub_enabled'],
				'S_BLOG_VIEW'			=> $this->auth->acl_get('u_blog_view'),
				'S_BLOG_RSS_ENABLED'	=> $this->config['ub_rss_enabled'],

				'U_BLOG'			=> $this->helper->route('posey_ultimateblog_blog'),
				'U_BLOG_RSS'		=> $this->helper->route('posey_ultimateblog_rss'),
				'U_BLOG_SEARCH'		=> $this->helper->route('posey_ultimateblog_search'),
			]);
		}
	}

	/*
	* Add total blog posts and blog comments to the profile
	*
	* @param object		$event		Event object
	* @return null
	* @access public
	*/
	public function viewprofile($event)
	{
		// Return if Ultimate Blog is disabled
		if (empty($this->config['ub_enabled']))
		{
			return;
		}

		// Get Blog Post and Blog Comment count
		$sql = 'SELECT COUNT(blog_id) AS blog_count
			FROM ' . $this->ub_blogs_table . '
			WHERE poster_id = ' . (int) $event['member']['user_id'];
		$result = $this->db->sql_query($sql);
		$total_blog_count = (int) $this->db->sql_fetchfield('blog_count');

		$sql = 'SELECT COUNT(comment_id) AS comment_count
			FROM ' . $this->ub_comments_table . '
			WHERE poster_id = ' . (int) $event['member']['user_id'];
		$result = $this->db->sql_query($sql);
		$total_comment_count = (int) $this->db->sql_fetchfield('comment_count');

		$this->template->assign_vars([
			'BLOG_POSTS'	=> $total_blog_count > 0 ? $total_blog_count : '-',
			'BLOG_COMMENTS' => $total_comment_count > 0 ? $total_comment_count : '-',

			'S_BLOG_POSTS_SEARCH'		=> $total_blog_count > 0 ? true : false,
			'S_BLOG_COMMENTS_SEARCH'	=> $total_comment_count > 0 ? true : false,

			'U_BLOG_POSTS_SEARCH'		=> $this->helper->route('posey_ultimateblog_search', ['bs_keyswords' => '', 'bs_author' => $event['member']['username_clean'], 'blog_search_in' => 'blog', 'bs_sortby' => 'post_time', 'bs_sortdir' => 'DESC', 'submit' => 'Search']),
			'U_BLOG_COMMENTS_SEARCH'	=> $this->helper->route('posey_ultimateblog_search', ['bs_keyswords' => '', 'bs_author' => $event['member']['username_clean'], 'blog_search_in' => 'comment', 'bs_sortby' => 'post_time', 'bs_sortdir' => 'DESC', 'submit' => 'Search']),

		]);
	}

	/*
	* Fix display for Ultimate Blog's MCP Modules
	*
	* Modules added:
	* - Blog reports open		'open'
	* - Blog reports closed		'closed'
	* - Blog reports details	'details'
	*
	* @param object		$event		Event object
	* @return null
	* @access public
	*/
	public function mcp_modules_display($event)
	{
		$module = $event['module'];
		$mode = $event['mode'];

		if ($mode == 'open' || $mode == 'closed' || $mode == 'details')
		{
			$module->set_display('pm_reports', 'pm_report_details', false);
			$module->set_display('reports', 'report_details', false);
		}

		if ($mode == '' || $mode == 'reports' || $mode == 'reports_closed' || $mode == 'report_details' || $mode == 'pm_reports' || $mode == 'pm_reports_closed' || $mode == 'pm_report_details' || $mode == 'open' || $mode == 'closed')
		{
			$module->set_display('\posey\ultimateblog\mcp\main_module', 'details', false);
		}
	}

	/*
	* Display latest 5 blog reports in MCP Front page
	*
	* @return null
	* @access public
	*/
	public function latest_blog_reports()
	{
		if ($this->auth->acl_get('m_blog_reports') && $this->config['ub_enabled'] == 1)
		{
			$sql = 'SELECT COUNT(r.report_id) AS total
					FROM ' . REPORTS_TABLE . ' r, ' . $this->ub_comments_table . ' c
					WHERE r.post_id = 0
						AND r.pm_id = 0
						AND r.blog_comment_id = c.comment_id
						AND r.report_closed = 0';
			$result = $this->db->sql_query($sql);
			$total = (int) $this->db->sql_fetchfield('total');
			$this->db->sql_freeresult($result);

			$this->template->assign_vars([
				'BLOG_REPORTS_TOTAL'	=> $this->user->lang('BLOG_REPORTS_TOTAL', $total),
				'S_BLOG_REPORTS'		=> true,
			]);

			if ($total)
			{
				$sql_ary = [
					'SELECT'	=> 'r.report_id, r.report_time, c.post_time, b.blog_id, b.blog_subject, u.username, u.user_colour, u.user_id, u2.username as author_name, u2.user_colour as author_colour, u2.user_id as author_id',

					'FROM'		=> [
						REPORTS_TABLE				=> 'r',
						$this->ub_comments_table	=> 'c',
						USERS_TABLE					=> ['u', 'u2'],
					],

					'LEFT_JOIN'	=> [
						[
							'FROM'	=> [$this->ub_blogs_table => 'b'],
							'ON'	=> 'b.blog_id = c.blog_id',
						],
					],

					'WHERE'		=> 'r.blog_comment_id = c.comment_id
						AND r.pm_id = 0
						AND r.post_id = 0
						AND r.report_closed = 0
						AND r.user_id = u.user_id
						AND c.poster_id = u2.user_id',

					'ORDER_BY'	=> 'c.post_time DESC, c.comment_id DESC',
				];

				$sql = $this->db->sql_build_query('SELECT', $sql_ary);
				$result = $this->db->sql_query_limit($sql, 5);

				while ($row = $this->db->sql_fetchrow($result))
				{
					// Set report ID
					$report_id = (int) $row['report_id'];

					$this->template->assign_block_vars('blog_report', [
						'AUTHOR_FULL'		=> get_username_string('full', $row['author_id'], $row['author_name'], $row['author_colour']),
						'BLOG_SUBJECT'		=> $row['blog_subject'],
						'POST_TIME'			=> $this->user->format_date($row['post_time']),
						'REPORT_TIME'		=> $this->user->format_date($row['report_time']),
						'REPORTER_FULL'		=> get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),

						'U_BLOG'			=> $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $row['blog_id']]),
						'U_DETAILS'			=> append_sid("{$this->phpbb_root_path}mcp.{$this->php_ext}?i=-posey-ultimateblog-mcp-main_module&amp;mode=details&amp;id=$report_id"),
					]);
				}
				$this->db->sql_freeresult($result);
			}
		}
	}

	/**
	* Show users as viewing Ultimate Blogs on Who Is Online page
	*
	* @param object		$event		Event object
	* @return null
	* @access public
	*/
	public function viewonline_page($event)
	{
		if ($event['on_page'][1] == 'app')
		{
			// Editing a comment
			if ((strrpos($event['row']['session_page'], 'app.' . $this->php_ext . '/blog/') === 0) && (strrpos($event['row']['session_page'], '/comment')))
			{
				$path = parse_url($event['row']['session_page'], PHP_URL_PATH);
				$path_fragments = explode('/', $path);
				$blog_id = (int) $path_fragments[2];
				$sql = 'SELECT blog_subject
						FROM ' . $this->ub_blogs_table . '
						WHERE blog_id = ' . $blog_id;
				$result = $this->db->sql_query($sql);
				$blog_subject = $this->db->sql_fetchfield('blog_subject');
				$this->db->sql_freeresult($result);

				$event['location'] = $this->user->lang('VIEWONLINE_BLOG_COMMENT_EDIT', $blog_subject);
				$event['location_url'] = $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => $blog_id]);
			}
			// Viewing a category
			else if (strrpos($event['row']['session_page'], 'app.' . $this->php_ext . '/blog/categories/') === 0)
			{
				$path = parse_url($event['row']['session_page'], PHP_URL_PATH);
				$path_fragments = explode('/', $path);
				$cat_id = (int) $path_fragments[3];

				// Viewing all categories
				if (!$cat_id)
				{
					$event['location'] = $this->user->lang['VIEWONLINE_BLOG_CATEGORIES'];
					$event['location_url'] = $this->helper->route('posey_ultimateblog_categories');
				}
				// Viewing specific category
				else
				{
					$sql = 'SELECT cat_name
							FROM ' . $this->ub_cats_table . '
							WHERE cat_id = ' . $cat_id;
					$result = $this->db->sql_query($sql);
					$cat_name = $this->db->sql_fetchfield('cat_name');
					$this->db->sql_freeresult($result);

					$event['location'] = $this->user->lang('VIEWONLINE_BLOG_CATEGORY', $cat_name);
					$event['location_url'] = $this->helper->route('posey_ultimateblog_category', ['cat_id' => $cat_id]);
				}
			}
			// Viewing archive
			else if (strrpos($event['row']['session_page'], 'app.' . $this->php_ext . '/blog/archive') === 0)
			{
				$path = parse_url($event['row']['session_page'], PHP_URL_PATH);
				$path_fragments = explode('/', $path);
				$date = explode('-', $path_fragments[3]);
				$year = (int) $date[0];
				$month = (int) $date[1];

				$event['location'] = $this->user->lang('VIEWONLINE_BLOG_ARCHIVE', $month . '-' . $year);
				$event['location_url'] = $this->helper->route('posey_ultimateblog_archive', ['year' => $year, 'month' => $month]);
			}
			// Reading a specific blog
			else if (strrpos($event['row']['session_page'], 'app.' . $this->php_ext . '/blog/') === 0)
			{
				$path = parse_url($event['row']['session_page'], PHP_URL_PATH);
				$path_fragments = explode('/', $path);
				$blog_id = (int) $path_fragments[2];

				$sql = 'SELECT blog_subject
						FROM ' . $this->ub_blogs_table . '
						WHERE blog_id = ' . $blog_id;
				$result = $this->db->sql_query($sql);
				$blog_subject = $this->db->sql_fetchfield('blog_subject');
				$this->db->sql_freeresult($result);

				$event['location'] = $this->user->lang('VIEWONLINE_BLOG', $blog_subject);
				$event['location_url'] = $this->helper->route('posey_ultimateblog_blog');
			}
			// 'Just' viewing blogs
			else if (strrpos($event['row']['session_page'], 'app.' . $this->php_ext . '/blog') === 0)
			{
				$event['location'] = $this->user->lang['VIEWONLINE_BLOGS'];
				$event['location_url'] = $this->helper->route('posey_ultimateblog_blog');
			}
		}
	}

	/*
	* Assign permissions, their language and their category
	*/
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

