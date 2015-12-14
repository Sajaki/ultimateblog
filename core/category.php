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
	protected $user;
	protected $template;
	protected $db;
	protected $log;
	protected $config;
	protected $auth;
	protected $helper;
	protected $request;
	protected $pagination;
	protected $phpbb_root_path;
	protected $php_ext;
	protected $ub_blogs_table;
	protected $ub_cats_table;
	protected $functions;

	/**
	* Constructor
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
		$this->functions		= $functions;
	}

	function overview()
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
		$result = $this->db->sql_query($sql);

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

		$this->template->assign_vars([
			'S_BLOG_CAN_ADD'	=> $this->auth->acl_get('u_blog_make'),
			'U_BLOG_ADD'		=> $this->helper->route('posey_ultimateblog_blog', ['action' => 'add']),
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

		// Grab category name
		$sql_cat = 'SELECT cat_name
					FROM ' . $this->ub_cats_table . '
					WHERE cat_id = ' . (int) $cat_id;
		$result_cat = $this->db->sql_query($sql_cat);
		$cat_name = $this->db->sql_fetchfield('cat_name');
		$this->db->sql_freeresult($result_cat);

		// Get blogs for this category
		$sql_array = [
			'SELECT'	=> 'b.*, u.user_id, u.username, u.user_colour',

			'FROM'		=> [$this->ub_blogs_table => 'b'],

			'LEFT_JOIN' => [
				[
					'FROM'	=> [USERS_TABLE => 'u'],
					'ON'	=> 'b.poster_id = u.user_id',
				]
			],

			'WHERE'		=> 'b.cat_id = ' . (int) $cat_id,

			'ORDER_BY'	=> 'b.post_time DESC',
		];

		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			// Check BBCode Options
			$bbcode_options =	(($row['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) +
								(($row['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) +
								(($row['enable_magic_url']) ? OPTION_FLAG_LINKS : 0);

			// Generate blog text
			$text = generate_text_for_display($row['blog_text'], $row['bbcode_uid'], $row['bbcode_bitfield'], $bbcode_options);

			// Cut off blog text
			if ($this->config['ub_cutoff'] != 0)
			{
				$text = (strlen($text) > $this->config['ub_cutoff']) ? substr($text, 0, $this->config['ub_cutoff']) . ' ... <a href="' . $this->helper->route('posey_ultimateblog_blog', ['blog_id' => (int) $row['blog_id']]) . ' alt="" title="' . $this->user->lang['BLOG_READ_FULL'] . '"><em>' . $this->user->lang['BLOG_READ_FULL'] . '</em></a>' : $text;
			}

			$this->template->assign_block_vars('blogs', [
				'SUBJECT'	=> $row['blog_subject'],
				'TEXT'		=> $text,
				'POSTER'	=> get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
				'POST_TIME'	=> $this->user->format_date($row['post_time'], 'F jS, Y'),

				'U_BLOG'		=> $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => $row['blog_id']]),
				'U_CAT'			=> $this->helper->route('posey_ultimateblog_category', ['cat_id' => (int) $cat_id]),
			]);
		}
		$this->db->sql_freeresult($result);

		$this->template->assign_vars([
			'CAT_NAME'			=> $cat_name,

			'S_BLOG_CAN_ADD'	=> $this->auth->acl_get('u_blog_make'),
			'U_BLOG_ADD'		=> $this->helper->route('posey_ultimateblog_blog', ['action' => 'add']),
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
			]
		];

		foreach($navlinks_array as $name)
		{
			$this->template->assign_block_vars('navlinks', [
				'FORUM_NAME'	=> $name['FORUM_NAME'],
				'U_VIEW_FORUM'	=> $name['U_VIEW_FORUM'],
			]);
		}

		// Generate page title
		page_header($cat_name);
	}
}