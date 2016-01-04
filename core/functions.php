<?php
/**
*
* @package phpBB Extension - Ultimate Blog
* @copyright (c) 2015 posey
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace posey\ultimateblog\core;

class functions
{
	# @var \phpbb\template\template
	protected $template;

	# @var \phpbb\db\driver\driver_interface
	protected $db;

	# @var \phpbb\controller\helper
	protected $helper;

	# @var \phpbb\user
	protected $user;

	# @var \phpbb\config\config
	protected $config;

	# @var \phpbb\auth\auth
	protected $auth;

	# @var \phpbb\log\log
	protected $log;

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

	# The database table the comments are stored in
	# @var string
	protected $ub_comments_table;

	# The database table the ratings are stored in
	# @var string
	protected $ub_rating_table;

	# The database table the blog subscriptions are stored in
	# @var string
	protected $ub_watch_blog_table;

	# The database table the category subscriptions are stored in
	# @var string
	protected $ub_watch_cat_table;

	/**
	* Constructor
	*
	* @param \phpbb\template\template			$template				Template object
	* @param \phpbb\db\driver\driver_interface	$db						Database object
	* @param \phpbb\controller\helper			$helper					Controller helper object
	* @param \phpbb\user						$user					User object
	* @param \phpbb\config\config				$config					Config object
	* @param \phpbb\auth\auth					$auth					Auth object
	* @param \phpbb\log\log						$log					Log object
	* @param \phpbb\request\request				$request				Request object
	* @param \phpbb\pagination					$pagination				Pagination object
	* @param string								$phpbb_root_path		phpBB root path
	* @param string								$php_ext				phpEx
	* @param string								$ub_blogs_table			Ultimate Blog blogs table
	* @param string								$ub_cats_table			Ultimate Blog categories table
	* @param string								$ub_comments_table		Ultimate Blog comments table
	* @param string								$ub_rating_table		Ultimate Blog rating table
	* @param string								$ub_watch_blog_table	Ultimate Blog blog subscription table
	* @param string								$ub_watch_cat_table		Ultimate Blog category subscription table
	* @access public
	*/
	public function __construct(
		\phpbb\template\template $template,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\controller\helper $helper,
		\phpbb\user $user,
		\phpbb\config\config $config,
		\phpbb\auth\auth $auth,
		\phpbb\log\log $log,
		\phpbb\request\request $request,
		\phpbb\pagination $pagination,
		$phpbb_root_path,
		$php_ext,
		$ub_blogs_table,
		$ub_cats_table,
		$ub_comments_table,
		$ub_rating_table,
		$ub_watch_blog_table,
		$ub_watch_cat_table)
	{
		$this->template	= $template;
		$this->db		= $db;
		$this->helper	= $helper;
		$this->user		= $user;
		$this->config	= $config;
		$this->auth		= $auth;
		$this->log		= $log;
		$this->request	= $request;
		$this->pagination		= $pagination;
		$this->phpbb_root_path	= $phpbb_root_path;
		$this->php_ext			= $php_ext;
		$this->ub_blogs_table	= $ub_blogs_table;
		$this->ub_cats_table	= $ub_cats_table;
		$this->ub_comments_table	= $ub_comments_table;
		$this->ub_rating_table		= $ub_rating_table;
		$this->ub_watch_blog_table	= $ub_watch_blog_table;
		$this->ub_watch_cat_table	= $ub_watch_cat_table;
	}

	function sidebar()
	{
		$sql = 'SELECT cat_id, cat_name
				FROM ' . $this->ub_cats_table . '
				ORDER BY cat_name ASC';
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('cats', [
				'NAME'	=> $row['cat_name'],
				'LINK'	=> $this->helper->route('posey_ultimateblog_category', ['cat_id' => (int) $row['cat_id']]),
			]);
		}
		$this->db->sql_freeresult($result);

		$sql = 'SELECT post_time
				FROM ' .	$this->ub_blogs_table . '
				GROUP BY MONTH(FROM_UNIXTIME(post_time)), YEAR(FROM_UNIXTIME(post_time))
				ORDER BY post_time DESC';
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('archive', [
				'MONTH_YEAR'		=> $this->user->format_date($row['post_time'], 'F Y'),

				'U_ARCHIVE_LINK'	=> $this->helper->route('posey_ultimateblog_archive', ['year' => (int) $this->user->format_date($row['post_time'], 'Y'), 'month' => (int) $this->user->format_date($row['post_time'], 'n')]),
			]);
		}
		$this->db->sql_freeresult($result);
	}

	public function archive($year, $month)
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
			'SELECT'	=> 'b.*, u.user_id, u.username, u.user_colour',

			'FROM'		=> [$this->ub_blogs_table => 'b'],

			'LEFT_JOIN' => [
				[
					'FROM'	=> [USERS_TABLE => 'u'],
					'ON'	=> 'b.poster_id = u.user_id',
				]
			],

			'WHERE'		=> 'MONTH(FROM_UNIXTIME(b.post_time)) = ' . (int) $month . '
								AND YEAR(FROM_UNIXTIME(b.post_time)) = ' . (int) $year,

			'ORDER_BY'	=> 'b.post_time DESC',
		];

		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query_limit($sql, $this->config['ub_blogs_per_page'], $start);

		while ($row = $this->db->sql_fetchrow($result))
		{
			// Grab category name and rating
			$sql_ary = [
				'SELECT'	=> 'c.cat_name, COUNT(br.rating) as total_rate_users, SUM(br.rating) as total_rate_sum',
				'FROM'		=> [
					$this->ub_cats_table => 'c',
					$this->ub_rating_table => 'br',
				],

				'WHERE'		=> 'c.cat_id = ' . (int) $row['cat_id'] . ' AND br.blog_id = ' . (int) $row['blog_id'],
			];

			$sql_extra = $this->db->sql_build_query('SELECT', $sql_ary);
			$result_extra = $this->db->sql_query($sql_extra);
			$extra = $this->db->sql_fetchrow($result_extra);
			$this->db->sql_freeresult($result_extra);

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

			$this->template->assign_block_vars('blogs', [
				'CAT'		=> $extra['cat_name'],
				'SUBJECT'	=> $row['blog_subject'],
				'TEXT'		=> $text,
				'POSTER'	=> get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
				'POST_TIME'	=> $this->user->format_date($row['post_time']),
				'RATING'	=> $extra['total_rate_users'] > 0 ? $extra['total_rate_sum'] / $extra['total_rate_users'] : 0,

				'U_BLOG'		=> $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => $row['blog_id']]),
				'U_CAT'			=> $this->helper->route('posey_ultimateblog_category', ['cat_id' => (int) $row['cat_id']]),
			]);

			// Set archive name for Nav Bar (Can be any of the rows, month and year remain the same)
			$archive_title = $this->user->lang('BLOG_ARCHIVE') . ' ' . $this->user->format_date($row['post_time'], 'F Y');
		}
		$this->db->sql_freeresult($result);

		$this->template->assign_vars([
			'ARCHIVE_TITLE'		=> $archive_title,

			'S_BLOG_CAN_ADD'	=> $this->auth->acl_get('u_blog_make'),
			'U_BLOG_ADD'		=> $this->helper->route('posey_ultimateblog_blog', ['action' => 'add']),
		]);

		// Get sidebar
		$this->sidebar();

		// Assign breadcrumb template vars
		$navlinks_array = [
			[
				'U_VIEW_FORUM'		=> $this->helper->route('posey_ultimateblog_blog'),
				'FORUM_NAME'		=> $this->user->lang('BLOG'),
			],
			[
				'U_VIEW_FORUM'		=> $this->helper->route('posey_ultimateblog_archive', ['year' => (int) $year, 'month' => (int) $month]),
				'FORUM_NAME'		=> $archive_title,
			]
		];

		foreach($navlinks_array as $name)
		{
			$this->template->assign_block_vars('navlinks', [
				'FORUM_NAME'	=> $name['FORUM_NAME'],
				'U_VIEW_FORUM'	=> $name['U_VIEW_FORUM'],
			]);
		}

		// Count archives
		$sql = 'SELECT *
			FROM ' . $this->ub_blogs_table . '
			WHERE MONTH(FROM_UNIXTIME(post_time)) = ' . (int) $month . '
			AND YEAR(FROM_UNIXTIME(post_time)) = ' . (int) $year . '
			ORDER BY post_time DESC';
		$result_total = $this->db->sql_query($sql);
		$row_total = $this->db->sql_fetchrowset($result_total);
		$total_archive = (int) sizeof($row_total);
		$this->db->sql_freeresult($result_total);

		//Start pagination
		$this->pagination->generate_template_pagination($this->helper->route('posey_ultimateblog_archive', array('year' => $year, 'month' => $month )), 'pagination', 'start', $total_archive, $this->config['ub_blogs_per_page'], $start);

		$this->template->assign_vars(array(
			'TOTAL_ARCHIVE_COUNT'		=> $this->user->lang('BLOG_ARCHIVE_COUNT', (int) $total_archive),
		));

		// Generate page title
		page_header($archive_title);
	}

	function comment_delete($blog_id, $comment_id)
	{
		if (!$this->auth->acl_get('m_blog_comment_delete'))
		{
			trigger_error($this->user->lang['AUTH_COMMENT_DELETE'] . '<br><br><a href="' . $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $blog_id]) . '">&laquo; ' . $this->user->lang['BACK_TO_PREV'] . '</a>');
		}
		else
		{
			if (confirm_box(true))
			{
				$sql = 'DELETE FROM ' . $this->ub_comments_table . '
						WHERE comment_id = ' . (int) $comment_id;
				$this->db->sql_query($sql);

				trigger_error($this->user->lang['BLOG_COMMENT_DELETED'] . '<br><br><a href="' . $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $blog_id]) . '">&laquo; ' . $this->user->lang['BACK_TO_PREV'] . '</a>');
			}
			else
			{
				$s_hidden_fields = build_hidden_fields([
					'comment_id' 	=> $comment_id,
					'action'		=> 'delete',
				]);

				confirm_box(false, $this->user->lang['BLOG_COMMENT_DEL_CONFIRM'], $s_hidden_fields);

				// Use a redirect to take the user back to the previous page
				// if the user chose not delete the comment from the confirmation page.
				redirect($this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $blog_id]));
			}
		}
	}

	function comment_report($blog_id, $comment_id)
	{
		// Check permissions
		if (!$this->auth->acl_get('u_blog_comment_report'))
		{
			trigger_error($this->user->lang['AUTH_COMMENT_REPORT'] . '<br><br><a href="' . $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $blog_id]) . '#c' . $comment_id . '">&laquo; ' . $this->user->lang['BACK_TO_PREV'] . '</a>');
		}

		// Add lang file
		$this->user->add_lang('mcp');

		// Set up report reasons
		$sql = 'SELECT *
				FROM ' . REPORTS_REASONS_TABLE;
		$result = $this->db->sql_query($sql);

		$report_reasons = '';

		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('reason', [
				'ID'			=> $row['reason_id'],
				'DESCRIPTION'	=> $row['reason_description'],
				'S_SELECTED'	=> false,
			]);
		}

		$this->db->sql_freeresult($result);

		$this->template->assign_vars([
			'CAPTCHA_TEMPLATE'	=> false,
			'S_REPORT_POST'		=> true,
			'S_CAN_NOTIFY'		=> false,
		]);

		add_form_key('report');

		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key('report'))
			{
				// Invalid form key
				trigger_error($this->user->lang['FORM_INVALID'] . '<br><br><a href="' . $this->helper->route('posey_ultimateblog_blog', ['action' => 'add']) . '">&laquo; ' . $this->user->lang['BACK_TO_PREV'] . '</a>');
			}

			// Get all comment details
			$sql = 'SELECT *
					FROM ' . $this->ub_comments_table . '
					WHERE comment_id = ' . (int) $comment_id . '
						AND blog_id = ' . (int) $blog_id;
			$result = $this->db->sql_query($sql);
			$comment = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);

			// Let's set all variables
			$data = [
				'reason_id'							=> (int) $this->request->variable('reason_id', 1),
				'post_id'							=> 0,
				'pm_id'								=> 0,
				'user_id'							=> (int) $this->user->data['user_id'],
				'user_notify'						=> 0,
				'report_closed'						=> 0,
				'report_time'						=> (int) time(),
				'report_text'						=> (string) $this->request->variable('report_text', '', true),
				'reported_post_text'				=> $comment['comment_text'],
				'reported_post_uid'					=> $comment['bbcode_uid'],
				'reported_post_bitfield'			=> $comment['bbcode_bitfield'],
				'reported_post_enable_bbcode'		=> $this->config['allow_bbcode'],
				'reported_post_enable_smilies'		=> $this->config['allow_smilies'],
				'reported_post_enable_magic_url'	=> $this->config['allow_post_links'],
				'blog_comment_id'					=> (int) $comment_id,
			];

			// Now insert the report
			$sql = 'INSERT INTO ' . REPORTS_TABLE . ' ' . $this->db->sql_build_array('INSERT', $data);
			$this->db->sql_query($sql);

			// And set the comment as reported
			$sql = 'UPDATE ' . $this->ub_comments_table . ' SET comment_reported = 1 WHERE comment_id = ' . (int) $comment_id;
			$this->db->sql_query($sql);

			// Set return URLs
			$return_block = $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $blog_id]);
			$return_comment = $return_block . '#c' . (int) $comment_id;

			trigger_error($this->user->lang['BLOG_COMMENT_REPORTED'] . '<br><br><a href="' . $return_block . '">' . $this->user->lang['BLOG_BACK'] . '</a><br><br><a href="' . $return_comment . '">' . $this->user->lang['BLOG_BACK_COMMENT'] . '</a>');
		}
	}

	function comment_edit($blog_id, $comment_id)
	{
		// Grab comment details
		$sql = 'SELECT c.*, b.blog_subject
				FROM ' . $this->ub_comments_table . ' c
				LEFT JOIN ' . $this->ub_blogs_table . ' b
					ON c.blog_id = b.blog_id
				WHERE c.comment_id = ' . (int) $comment_id . '
					AND c.blog_id = ' . (int) $blog_id;
		$result = $this->db->sql_query($sql);
		$comment = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$comment)
		{
			trigger_error($this->user->lang['BLOG_COMMENT_NOT_EXIST'] . '<br><br><a href="' . $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $blog_id]) . '">&laquo; ' . $this->user->lang['BACK_TO_PREV'] . '</a>');
		}

		// Check if authorised to edit this comment
		if (!$this->auth->acl_gets('u_blog_comment_edit', 'm_blog_comment_edit'))
		{
			trigger_error($this->user->lang['AUTH_COMMENT_EDIT'] . '<br><br><a href="' . $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $blog_id]) . '">&laquo; ' . $this->user->lang['BLOG_BACK'] . '</a>');
		}

		if (($this->auth->acl_get('u_blog_edit') && $comment['poster_id'] != $this->user->data['user_id']) && !$this->auth->acl_get('m_blog_comment_edit'))
		{
			trigger_error($this->user->lang['AUTH_COMMENT_EDIT_ELSE'] . '<br><br><a href="' . $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $blog_id]) . '">&laquo; ' . $this->user->lang['BLOG_BACK'] . '</a>');
		}

		if (!function_exists('generate_smilies'))
		{
			include($this->phpbb_root_path . 'includes/functions_posting.' . $this->php_ext);
		}

		if (!function_exists('display_custom_bbcodes'))
		{
			include($this->phpbb_root_path . 'includes/functions_display.' . $this->php_ext);
		}

		// Add lang file
		$this->user->add_lang('posting');
		display_custom_bbcodes();
		generate_smilies('inline', 0);

		// Generate text for editing
		decode_message($comment['comment_text'], $comment['bbcode_uid']);

		$this->template->assign_vars([
			'MESSAGE'	=> $comment['comment_text'],

			'S_FORM_ENCTYPE'	=> '',
			'S_BBCODE_ALLOWED'	=> $this->config['allow_bbcode'] ? true : false,
			'S_SMILIES_STATUS'	=> $this->config['allow_smilies'] ? true : false,
		]);

		add_form_key('edit_comment');

		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key('edit_comment'))
			{
				// Invalid form key
				trigger_error($this->user->lang['FORM_INVALID'] . '<br><br><a href="' . $this->helper->route('posey_ultimateblog_comment', ['blog_id' => (int) $blog_id, 'comment_id' => (int) $comment_id, 'action' => 'edit']) . '">&laquo; ' . $this->user->lang['BACK_TO_PREV'] . '</a>');
			}
			else if ($this->request->variable('comment_text', '', true) == '')
			{
				// Empty comment message
				trigger_error($this->user->lang['BLOG_COMMENT_EMPTY'] . '<br><br><a href="' . $this->helper->route('posey_ultimateblog_comment', ['blog_id' => (int) $blog_id, 'comment_id' => (int) $comment_id, 'action' => 'edit']) . '">&laquo; ' . $this->user->lang['BACK_TO_PREV'] . '</a>');
			}
			else
			{
				// Generate text for storage
				$comment_text = $this->request->variable('comment_text', '', true);
				$uid = $bitfield = $options = '';
				$allow_bbcode = $this->config['allow_bbcode'];
				$allow_smilies = $this->config['allow_smilies'];
				$allow_urls = $this->config['allow_post_links'];
				generate_text_for_storage($comment_text, $uid, $bitfield, $options, $allow_bbcode, $allow_smilies, $allow_urls);

				$comment_row= [
					'comment_text'		=> $comment_text,
					'bbcode_uid'		=> $uid,
					'bbcode_bitfield'	=> $bitfield,
					'bbcode_options'	=> $options,
				];

				// Update the blog
				$sql = 'UPDATE ' . $this->ub_comments_table . ' SET ' . $this->db->sql_build_array('UPDATE', $comment_row) . ' WHERE comment_id = ' . (int) $comment_id;
				$this->db->sql_query($sql);

				// Add it to the log
				$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_COMMENT_EDITED', false, array($comment_id));

				// Send success message
				trigger_error($this->user->lang['BLOG_COMMENT_EDITED'] . '<br><br><a href="' . $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $blog_id]) . '#c' . (int) $comment_id . '">' . $this->user->lang['BLOG_COMMENT_VIEW'] . ' &raquo;</a>');
			}
		}

		// Assign breadcrumb template vars
		$navlinks_array = [
			[
				'U_VIEW_FORUM'		=> $this->helper->route('posey_ultimateblog_blog'),
				'FORUM_NAME'		=> $this->user->lang('BLOG'),
			],
			[
				'U_VIEW_FORUM'		=> $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $blog_id]),
				'FORUM_NAME'		=> $comment['blog_subject'],
			]
		];

		foreach($navlinks_array as $name)
		{
			$this->template->assign_block_vars('navlinks', [
				'FORUM_NAME'	=> $name['FORUM_NAME'],
				'U_VIEW_FORUM'	=> $name['U_VIEW_FORUM'],
			]);
		}
	}

	function subscribe($mode)
	{
		if ($mode == 'blog' || $mode == 'cat')
		{
			$id = (int) $this->request->variable('id', 0);

			// Check if not subscribed already
			$subscribed = $this->check_subscription($mode, $id);

			$table = $mode == 'blog' ? $this->ub_watch_blog_table : $this->ub_watch_cat_table;
			$column = $mode == 'blog' ? 'blog_id' : 'cat_id';

			// Not subscribed already, so let's subscribe
			if (!$subscribed)
			{
				$sql = 'INSERT INTO ' . $table . ' ' . $this->db->sql_build_array('INSERT', [
					$column		=> (int) $id,
					'user_id'	=> (int) $this->user->data['user_id'],
				]);
				$this->db->sql_query($sql);

				// Send success message
				trigger_error($this->user->lang['BLOG_SUBSCRIBED_TO_' . strtoupper($mode)]);
			}
			else
			{
				// User is already subscribed already, so unable to subscribe again
				trigger_error($this->user->lang['BLOG_SUBSCRIBED_ALRDY_' . strtoupper($mode)]);
			}
		}
		else if ($mode == 'all')
		{
			// Update users_table bool
			$sql = 'UPDATE ' . USERS_TABLE . '
					SET ub_watch_all = 1
					WHERE user_id = ' . (int) $this->user->data['user_id'];
			$this->db->sql_query($sql);

			trigger_error($this->user->lang['BLOG_SUBSCRIBED_TO_ALL']);
		}
	}

	function unsubscribe($mode)
	{
		if ($mode == 'blog' || $mode == 'cat')
		{
			$id = (int) $this->request->variable('id', 0);

			// Check is subscribed in the first place
			$subscribed = $this->check_subscription($mode, $id);

			// Set table and column for mode (mode is either 'blog' or 'cat')
			$table = $mode == 'blog' ? $this->ub_watch_blog_table : $this->ub_watch_cat_table;
			$column = $mode == 'blog' ? 'blog_id' : 'cat_id';

			if ($subscribed)
			{
				$sql = 'DELETE FROM ' . $table . '
						WHERE ' . $column . ' = ' . (int) $id . '
							AND user_id = ' . (int) $this->user->data['user_id'];
				$this->db->sql_query($sql);

				// Send success message
				trigger_error($this->user->lang['BLOG_UNSUBSCRIBED_TO_' . strtoupper($mode)]);
			}
			else
			{
				// User is not subscribed, so unable to unsubsribe
				trigger_error($this->user->lang['BLOG_SUBSCRIBED_NOT_' . strtoupper($mode)]);
			}
		}
		else if ($mode == 'all')
		{
			// Update users_table bool
			$sql = 'UPDATE ' . USERS_TABLE . '
					SET ub_watch_all = 0
					WHERE user_id = ' . (int) $this->user->data['user_id'];
			$this->db->sql_query($sql);

			// Send success message
			trigger_error($this->user->lang['BLOG_UNSUBSCRIBED_TO_ALL']);
		}
	}

	function check_subscription($mode, $id)
	{
		// Set table and column for mode (mode is either 'blog' or 'cat')
		$table = $mode == 'blog' ? $this->ub_watch_blog_table : $this->ub_watch_cat_table;
		$column = $mode == 'blog' ? 'blog_id' : 'cat_id';

		$sql = 'SELECT COUNT(user_id) as count
					FROM ' . $table . '
					WHERE ' . $column . ' = ' . (int) $id . '
						AND user_id = ' . (int) $this->user->data['user_id'];
		$result = $this->db->sql_query($sql);
		$count = (int) $this->db->sql_fetchfield('count');
		$this->db->sql_freeresult($result);

		// Return a true or false, for subscribed
		return $count > 0 ? true : false;
	}

	function rss_feed()
	{
		if (!$this->config['ub_rss_enabled'])
		{
			trigger_error($this->user->lang['BLOG_RSS_FEED_DISABLED']);
		}
		else
		{
			// Set up standard feed information
			$feed_vars = [
				'TITLE'			=> html_entity_decode($this->config['ub_rss_title']),
				'DESCRIPTION'	=> html_entity_decode($this->config['ub_rss_desc']),
				'WEBMASTER'		=> $this->config['ub_rss_email'],
				'EMAIL'			=> $this->config['board_contact'],
				'CATEGORY'		=> html_entity_decode($this->config['ub_rss_cat']),
				'COPYRIGHT'		=> html_entity_decode($this->config['ub_rss_copy']),
				'LANGUAGE'		=> html_entity_decode($this->config['ub_rss_lang']),
				'LINK'			=> generate_board_url($without_script_path = true) . $this->helper->route('posey_ultimateblog_rss'),
				'IMAGE'			=> $this->config['ub_rss_img'],
				'AUTHOR'		=> $this->config['sitename'],
			];

			// Set up SQL array
			$sql_ary = [
				'SELECT'	=> 'b.blog_id, b.blog_subject, b.blog_text, b.post_time, b.bbcode_uid, b.bbcode_bitfield, b.enable_bbcode, b.enable_smilies, b.enable_magic_url, u.username_clean, c.cat_name',

				'FROM'		=> [
					$this->ub_blogs_table => 'b',
					$this->ub_cats_table => 'c',
				],

				'LEFT_JOIN'	=> [
					[
						'FROM'	=> [ USERS_TABLE => 'u'],
						'ON'	=> 'b.poster_id = u.user_id',
					]
				],

				'WHERE'		=> 'b.cat_id = c.cat_id',

				'ORDER_BY'	=> 'b.post_time DESC',
			];

			// Run SQL and get 10 latest blogs
			$sql = $this->db->sql_build_query('SELECT', $sql_ary);
			$result = $this->db->sql_query_limit($sql, 10);

			while ($row = $this->db->sql_fetchrow($result))
			{
				// Set up blog text for Feed display
				$flags = (($row['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) +
						(($row['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) +
						(($row['enable_magic_url']) ? OPTION_FLAG_LINKS : 0);

				$blog_text = generate_text_for_display($row['blog_text'], $row['bbcode_uid'], $row['bbcode_bitfield'], $flags);
				# Set up images source properly for regular images:
				$blog_text = str_replace('<img src="./', '<img src="' . generate_board_url(), $blog_text);
				# Censor the text:
				$blog_text = censor_text($blog_text);
				# Remove smilies from the text:
				$blog_text = preg_replace('/<img class="smilies"(.*?) \/>/', '', $blog_text);
				# Decode HTML characters:
				$blog_text = htmlentities($blog_text);

				// Assign block vars
				$item_row = [
					'link'			=> generate_board_url($without_script_path = true) . $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $row['blog_id']]),
					'author'		=> html_entity_decode($row['username_clean']),
					'published'		=> $this->user->format_date($row['post_time'], 'D, d M Y H:i:s O'),
					'category'		=> html_entity_decode($row['cat_name']),
					'title'			=> html_entity_decode(censor_text($row['blog_subject'])),
					'description'	=> $blog_text,
				];

				$item_vars[] = $item_row;
			}

			// OUTPUT THE RSS PAGE
			header("Content-Type: application/atom+xml; charset=UTF-8");
			if (!empty($this->user->data['is_bot']))
			{
				// Let reverse proxies know we detected a bot.
				header('X-PHPBB-IS-BOT: yes');
			}
			echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
			echo '<feed xmlns="http://www.w3.org/2005/Atom" xml:lang="' . $feed_vars['LANGUAGE'] . '">' . "\n";
			echo '	<link rel="self" type="application/atom+xml" href="' . $feed_vars['LINK'] . '" />' . "\n\n";
			echo (!empty($feed_vars['TITLE'])) ? '	<title>' . $feed_vars['TITLE'] . '</title>' . "\n" : '';
			echo (!empty($feed_vars['DESCRIPTION'])) ? '	<description>' . $feed_vars['DESCRIPTION'] . '</description>' . "\n" : '';
			echo (!empty($feed_vars['LINK'])) ? '	<link href="' . $feed_vars['LINK'] .'" />' . "\n" : '';
			echo (!empty($feed_vars['WEBMASTER'])) ? '	<webMaster>' . $feed_vars['EMAIL'] . '</webMaster>' . "\n" : '';
			echo (!empty($feed_vars['CATEGORY'])) ? '	<category>' . $feed_vars['CATEGORY'] . '</category>' . "\n" : '';
			echo (!empty($feed_vars['COPYRIGHT'])) ? '	<copyright>' . $feed_vars['COPYRIGHT'] . '</copyright>' . "\n" : '';
			echo '	<author><name><![CDATA[' . $feed_vars['AUTHOR'] . ']]></name></author>' . "\n\n";

			foreach ($item_vars as $row)
			{
				echo '	<entry>' . "\n";
				if (!empty($row['author']))
				{
					echo '		<author><name><![CDATA[' . $row['author'] . ']]></name></author>' . "\n";
				}
				if (!empty($row['published']))
				{
					echo '		<published>' . $row['published'] . '</published>' . "\n";
				}
				echo '		<id>' . $row['link'] . '</id>' . "\n";
				echo '		<link href="' . $row['link'] . '"/>' . "\n";
				echo '		<title type="html"><![CDATA[' . $row['title'] . ']]></title>' . "\n";
				if (!empty($row['category']))
				{
					echo '		<category term="' . $row['category'] . '" label="' . $row['category'] . '"/>' . "\n";
				}
				echo '		<content type="html" xml:base="' . $row['link'] . '"><![CDATA[' . "\n";
				echo '			' . $row['description'];
				echo "\n" . '			<hr />' . "\n" . '		]]></content>' . "\n";
				echo '	</entry>' . "\n";
			}

			echo '</feed>';

			garbage_collection();
			exit_handler();
		}
	}
}
