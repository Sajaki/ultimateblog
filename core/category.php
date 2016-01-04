<?php
/**
*
* @package phpBB Extension - Ultimate Blog
* @copyright (c) 2015 posey
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace posey\ultimateblog\core;

class category
{
	# @var \phpbb\user
	protected $user;

	# @var \phpbb\template\template
	protected $template;

	# @var \phpbb\db\driver\driver_interface
	protected $db;

	# @var \phpbb\log\log
	protected $log;

	# @var \phpbb\config\config
	protected $config;

	# @var \phpbb\auth\auth
	protected $auth;

	# @var \phpbb\controller\helper
	protected $helper;

	# @var \phpbb\request\request
	protected $request;

	# @var \phpbb\pagination
	protected $pagination;

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

	# The database table the ratings are stored in
	# @var string
	protected $ub_rating_table;

	# @var \posey\ultimateblog\core\functions
	protected $functions;

	/**
	* Constructor
	*
	* @param \phpbb\user						$user				User object
	* @param \phpbb\template\template			$template			Template object
	* @param \phpbb\db\driver\driver_interface	$db					Database object
	* @param \phpbb\log\log						$log				Log object
	* @param \phpbb\config\config				$config				Config object
	* @param \phpbb\auth\auth					$auth				Auth object
	* @param \phpbb\controller\helper			$helper				Controller helper object
	* @param \phpbb\request\request				$request			Request object
	* @param \phpbb\pagination					$pagination			Pagination object
	* @param string								$phpbb_root_path	phpBB root path
	* @param string								$php_ext			phpEx
	* @param string								$ub_blogs_table		Ultimate Blog blogs table
	* @param string								$ub_cats_table		Ultimate Blog categories table
	* @param string								$ub_rating_table	Ultimate Blog rating table
	* @param \posey\ultimateblog\core\functions	$functions			Ultimate Blog general functions
	* @access public
	*/
	public function __construct(
		\phpbb\user $user,
		\phpbb\template\template $template,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\log\log $log,
		\phpbb\config\config $config,
		\phpbb\auth\auth $auth,
		\phpbb\controller\helper $helper,
		\phpbb\request\request $request,
		\phpbb\pagination $pagination,
		$phpbb_root_path,
		$php_ext,
		$ub_blogs_table,
		$ub_cats_table,
		$ub_rating_table,
		$functions)
	{
		$this->user		= $user;
		$this->template	= $template;
		$this->db		= $db;
		$this->log		= $log;
		$this->config	= $config;
		$this->auth		= $auth;
		$this->helper	= $helper;
		$this->request	= $request;
		$this->pagination		= $pagination;
		$this->phpbb_root_path	= $phpbb_root_path;
		$this->php_ext			= $php_ext;
		$this->ub_blogs_table	= $ub_blogs_table;
		$this->ub_cats_table	= $ub_cats_table;
		$this->ub_rating_table	= $ub_rating_table;
		$this->functions		= $functions;
	}

	function overview()
	{
		// When blog is disabled, redirect users back to the forum index
		if ($this->config['ub_enabled'] == 0)
		{
			redirect(append_sid("{$this->phpbb_root_path}index.{$this->php_ext}"));
		}

		// Check if user can view blogs
		if (!$this->auth->acl_get('u_blog_view'))
		{
			trigger_error($this->user->lang['AUTH_BLOG_VIEW'] . '<br><br>' . $this->user->lang('RETURN_INDEX', '<a href="' . append_sid("{$this->phpbb_root_path}index.{$this->php_ext}") . '">&laquo; ', '</a>'));
		}

		$start = $this->request->variable('start', 0);

		$sql_array = [
			'SELECT'	=> 'c.*, COUNT(b.cat_id) as blog_count',

			'FROM'		=> [
				$this->ub_cats_table => 'c',
			],

			'LEFT_JOIN' => [
				[
					'FROM'	=> [$this->ub_blogs_table => 'b'],
					'ON'	=> 'c.cat_id = b.cat_id',
				]
			],

			'GROUP_BY'	=> 'c.cat_id',

			'ORDER_BY'	=> 'c.cat_id ASC',
		];
		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query_limit($sql, $this->config['posts_per_page'], $start);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$bbcode_options =	(($row['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) +
								(($row['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) +
								(($row['enable_magic_url']) ? OPTION_FLAG_LINKS : 0);

			$this->template->assign_block_vars('categories', [
				'LINK'	=> $this->helper->route('posey_ultimateblog_category', ['cat_id' => (int) $row['cat_id']]),
				'NAME'	=> $row['cat_name'],
				'DESC'	=> generate_text_for_display($row['cat_desc'], $row['bbcode_uid'], $row['bbcode_bitfield'], $bbcode_options),
				'COUNT'	=> $row['blog_count'],
			]);
		}
		$this->db->sql_freeresult($result);

		// Get Sidebar
		$this->functions->sidebar();

		// Assign breadcrumb template vars
		$navlinks_array = [
			[
				'U_VIEW_FORUM'		=> $this->helper->route('posey_ultimateblog_blog'),
				'FORUM_NAME'		=> $this->user->lang('BLOG'),
			],
			[
				'U_VIEW_FORUM'		=> $this->helper->route('posey_ultimateblog_categories'),
				'FORUM_NAME'		=> $this->user->lang('CATEGORIES'),
			]
		];

		foreach($navlinks_array as $name)
		{
			$this->template->assign_block_vars('navlinks', [
				'FORUM_NAME'	=> $name['FORUM_NAME'],
				'U_VIEW_FORUM'	=> $name['U_VIEW_FORUM'],
			]);
		}

		// Check subscription
		$subscribed = $this->user->data['ub_watch_all'] == 1 ? true : false;

		$this->template->assign_vars([
			'S_BLOG_CAN_ADD'		=> $this->auth->acl_get('u_blog_make'),
			'S_BLOG_SUBSCRIBED_ALL'	=> $subscribed,
			'S_IN_BLOG_ALL'			=> true,

			'U_BLOG_ADD'			=> $this->helper->route('posey_ultimateblog_blog', ['action' => 'add']),
			'U_BLOG_SUBSCRIBE_ALL'	=> !$subscribed ? $this->helper->route('posey_ultimateblog_blog', ['action' => 'subscribe', 'mode' => 'all']) : $this->helper->route('posey_ultimateblog_blog', ['action' => 'unsubscribe', 'mode' => 'all']),
		]);

		// Count categories
		$total_count = (int) $this->db->get_row_count($this->ub_cats_table);

		// Start pagination
		$this->pagination->generate_template_pagination($this->helper->route('posey_ultimateblog_categories'), 'pagination', 'start', $total_count, $this->config['posts_per_page'], $start);

		$this->template->assign_vars([
			'TOTAL_CATS'		=> $this->user->lang('BLOG_CATS_COUNT', (int) $total_count),
		]);
	}

	function display($cat_id)
	{
		// When blog is disabled, redirect users back to the forum index
		if ($this->config['ub_enabled'] == 0)
		{
			redirect(append_sid("{$this->root_path}index.{$this->php_ext}"));
		}

		// Check if user can view blogs
		if (!$this->auth->acl_get('u_blog_view'))
		{
			trigger_error($this->user->lang['AUTH_BLOG_VIEW'] . '<br><br>' . $this->user->lang('RETURN_INDEX', '<a href="' . append_sid("{$this->phpbb_root_path}index.{$this->php_ext}") . '">&laquo; ', '</a>'));
		}

		$start = $this->request->variable('start', 0);

		// Get blogs for this category
		$sql_array = [
			'SELECT'	=> 'b.*, c.cat_name, u.user_id, u.username, u.user_colour',

			'FROM'		=> [
				$this->ub_blogs_table => 'b',
				$this->ub_cats_table => 'c',
			],

			'LEFT_JOIN' => [
				[
					'FROM'	=> [USERS_TABLE => 'u'],
					'ON'	=> 'b.poster_id = u.user_id',
				]
			],

			'WHERE'		=> 'b.cat_id = ' . (int) $cat_id . ' AND b.cat_id = c.cat_id',

			'ORDER_BY'	=> 'b.post_time DESC',
		];

		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query_limit($sql, $this->config['ub_blogs_per_page'], $start);

		while ($row = $this->db->sql_fetchrow($result))
		{
			// Grab blog rating
			$sql_rating = 'SELECT COUNT(rating) as total_rate_users, SUM(rating) as total_rate_sum
						FROM ' . $this->ub_rating_table . '
						WHERE blog_id = ' . (int) $row['blog_id'];
			$result_rating = $this->db->sql_query($sql_rating);
			$rating = $this->db->sql_fetchrow($result_rating);
			$this->db->sql_freeresult($result_rating);

			// Check BBCode Options
			$bbcode_options =	(($row['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) +
								(($row['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) +
								(($row['enable_magic_url']) ? OPTION_FLAG_LINKS : 0);

			// Generate blog text
			$text = generate_text_for_display($row['blog_text'], $row['bbcode_uid'], $row['bbcode_bitfield'], $bbcode_options);

			// Cut off blog text
			if ($this->config['ub_cutoff'] != 0)
			{
				if ($this->config['ub_show_desc'] == 1)
				{
					$text = (strlen($text) > $this->config['ub_cutoff']) ? $row['blog_description'] . '<span class="blog-read-full"> <br><br><a href="' . $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $row['blog_id']]) . '" alt="" title="' . $this->user->lang['BLOG_READ_FULL'] . '">' . $this->user->lang['BLOG_READ_FULL'] . '</a></span>' : $text;
				}
				else
				{
					$text = (strlen($text) > $this->config['ub_cutoff']) ? substr($text, 0, $this->config['ub_cutoff']) . '<span class="blog-read-full"> ... <a href="' . $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $row['blog_id']]) . '" alt="" title="' . $this->user->lang['BLOG_READ_FULL'] . '">' . $this->user->lang['BLOG_READ_FULL'] . '</a></span>' : $text;
				}
			}

			// Get category name, same for all rows
			$cat_name = $row['cat_name'];

			$this->template->assign_block_vars('blogs', [
				'SUBJECT'	=> $row['blog_subject'],
				'TEXT'		=> $text,
				'POSTER'	=> get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
				'POST_TIME'	=> $this->user->format_date($row['post_time']),
				'RATING'	=> $rating['total_rate_users'] > 0 ? $rating['total_rate_sum'] / $rating['total_rate_users'] : 0,

				'U_BLOG'		=> $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => $row['blog_id']]),
				'U_CAT'			=> $this->helper->route('posey_ultimateblog_category', ['cat_id' => (int) $cat_id]),
			]);
		}
		$this->db->sql_freeresult($result);

		// If category is empty we still need the category name
		if (!$row)
		{
			$sql = 'SELECT cat_name
					FROM ' . $this->ub_cats_table . '
					WHERE cat_id = ' . (int) $cat_id;
			$result = $this->db->sql_query($sql);
			$cat_name = $this->db->sql_fetchfield('cat_name');
			$this->db->sql_freeresult($result);
		}

		// Check if user is subscribed to this blog
		$subscribed = $this->functions->check_subscription('cat', (int) $cat_id);

		$this->template->assign_vars([
			'CAT_NAME'			=> $cat_name,

			'S_BLOG_CAN_ADD'		=> $this->auth->acl_get('u_blog_make'),
			'S_BLOG_SUBSCRIBED_CAT'	=> $subscribed,
			'S_IN_BLOG_CAT'			=> true,

			'U_BLOG_ADD'			=> $this->helper->route('posey_ultimateblog_blog', ['action' => 'add']),
			'U_BLOG_SUBSCRIBE_CAT'	=> !$subscribed ? $this->helper->route('posey_ultimateblog_blog', ['action' => 'subscribe', 'mode' => 'cat', 'id' => (int) $cat_id]) : $this->helper->route('posey_ultimateblog_blog', ['action' => 'unsubscribe', 'mode' => 'cat', 'id' => (int) $cat_id]),
		]);

		// Get sidebar
		$this->functions->sidebar();

		// Assign breadcrumb template vars
		$navlinks_array = [
			[
				'U_VIEW_FORUM'		=> $this->helper->route('posey_ultimateblog_blog'),
				'FORUM_NAME'		=> $this->user->lang('BLOG'),
			],
			[
				'U_VIEW_FORUM'		=> $this->helper->route('posey_ultimateblog_categories'),
				'FORUM_NAME'		=> $this->user->lang('CATEGORIES'),
			],
			[
				'U_VIEW_FORUM'		=> $this->helper->route('posey_ultimateblog_category', ['cat_id' => (int) $cat_id]),
				'FORUM_NAME'		=> $cat_name,
			],
		];

		foreach($navlinks_array as $name)
		{
			$this->template->assign_block_vars('navlinks', [
				'FORUM_NAME'	=> $name['FORUM_NAME'],
				'U_VIEW_FORUM'	=> $name['U_VIEW_FORUM'],
			]);
		}

		// Count categories blogs
		$sql = 'SELECT *
			FROM ' . $this->ub_blogs_table . '
			WHERE cat_id = ' . (int) $cat_id . '
			ORDER BY cat_id ASC';
		$result_total = $this->db->sql_query($sql);
		$row_total = $this->db->sql_fetchrowset($result_total);
		$total_catergory_blogs = (int) sizeof($row_total);
		$this->db->sql_freeresult($result_total);

		//Start pagination
		$this->pagination->generate_template_pagination($this->helper->route('posey_ultimateblog_category', array('cat_id' => $cat_id)), 'pagination', 'start', $total_catergory_blogs, $this->config['ub_blogs_per_page'], $start);

		$this->template->assign_vars(array(
			'TOTAL_CATEGORY_BLOGS'		=> $this->user->lang('BLOG_BLOG_COUNT', (int) $total_catergory_blogs),
		));

		// Generate page title
		page_header($cat_name);
	}
}

