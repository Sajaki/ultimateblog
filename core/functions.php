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
	protected $template;
	protected $db;
	protected $helper;
	protected $user;
	protected $config;
	protected $auth;
	protected $log;
	protected $request;
	protected $pagination;
	protected $phpbb_root_path;
	protected $php_ext;
	protected $ub_blogs_table;
	protected $ub_cats_table;
	protected $ub_comments_table;
	protected $ub_rating_table;

	/**
	* Constructor
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
		$ub_rating_table)
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
		$this->ub_comments_table = $ub_comments_table;
		$this->ub_rating_table	= $ub_rating_table;
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
				$text = (strlen($text) > $this->config['ub_cutoff']) ? substr($text, 0, $this->config['ub_cutoff']) . ' ... <a href="' . $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $row['blog_id']]) . '" alt="" title="' . $this->user->lang['BLOG_READ_FULL'] . '"><em>' . $this->user->lang['BLOG_READ_FULL'] . '</em></a>' : $text;
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

	function comment_edit($blog_id, $comment_id)
	{
		$sql = 'SELECT *
				FROM ' . $this->ub_comments_table . '
				WHERE comment_id = ' . (int) $comment_id;
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

		// Grab blog subject for Nav links
		$sql = 'SELECT blog_subject
				FROM ' . $this->ub_blogs_table . '
				WHERE blog_id = ' . (int) $blog_id;
		$result = $this->db->sql_query($sql);
		$blog_subject = $this->db->sql_fetchfield('blog_subject');
		$this->db->sql_freeresult($result);

		// Assign breadcrumb template vars
		$navlinks_array = [
			[
				'U_VIEW_FORUM'		=> $this->helper->route('posey_ultimateblog_blog'),
				'FORUM_NAME'		=> $this->user->lang('BLOG'),
			],
			[
				'U_VIEW_FORUM'		=> $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $blog_id]),
				'FORUM_NAME'		=> $blog_subject,
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
}
