<?php
/**
*
* @package phpBB Extension - Ultimate Blog
* @copyright (c) 2015 posey
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace posey\ultimateblog\controller;

class mcp_controller
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
	protected $ub_comments_table;

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
		$ub_comments_table)
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
		$this->ub_comments_table	= $ub_comments_table;
	}

	public function reports_open()
	{
		// When blog is disabled, redirect users back to the forum index
		if ($this->config['ub_enabled'] == 0)
		{
			redirect(append_sid("{$this->phpbb_root_path}index.{$this->php_ext}"));
		}

		// Check permissions
		if (!$this->auth->acl_get('m_blog_reports'))
		{
			redirect(append_sid("{$this->phpbb_root_path}mcp.{$this->php_ext}"));
		}

		// Get all open blog comment reports
		$sql = 'SELECT r.*, u.user_id, u.username, u.user_colour
				FROM ' . REPORTS_TABLE . ' r
				LEFT JOIN ' . USERS_TABLE . ' u
					ON r.user_id = u.user_id
				WHERE r.blog_comment_id <> 0
					AND r.report_closed = 0
				ORDER BY r.report_time DESC';
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			// Get blog information on this row
			$sql_array = [
				'SELECT'	=> 'b.*, u.user_id, u.username, u.user_colour',

				'FROM'		=> [
					$this->ub_blogs_table => 'b',
					$this->ub_comments_table => 'bc',
				],

				'LEFT_JOIN' => [
					[
						'FROM'	=> [USERS_TABLE => 'u'],
						'ON'	=> 'bc.poster_id = u.user_id',
					]
				],

				'WHERE'		=> 'b.blog_id = bc.blog_id AND bc.comment_id = ' . (int) $row['blog_comment_id']
			];

			$blog_sql = $this->db->sql_build_query('SELECT', $sql_array);
			$blog_res = $this->db->sql_query($blog_sql);
			$blog = $this->db->sql_fetchrow($blog_res);
			$this->db->sql_freeresult($blog_res);

			// Set report ID
			$report_id = (int) $row['report_id'];

			$this->template->assign_block_vars('reports', [
				'REPORT_ID'		=> $report_id,
				'BLOG_SUBJECT'	=> $blog['blog_subject'],
				'POSTED_BY'		=> get_username_string('full', $blog['user_id'], $blog['username'], $blog['user_colour']),
				'POSTED_ON'		=> $this->user->format_date($blog['post_time']),
				'REPORTED_BY'	=> get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
				'REPORTED_ON'	=> $this->user->format_date($row['report_time']),

				'S_BLOG_REPORTS_ACTION' => append_sid("{$this->phpbb_root_path}mcp.{$this->php_ext}?i=-posey-ultimateblog-mcp-main_module&amp;mode=open"),

				'U_BLOG'		=> $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $blog['blog_id']]),
				'U_COMMENT'		=> $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $blog['blog_id']]) . '#c' . (int) $row['blog_comment_id'],
				'U_DETAILS'		=> append_sid("{$this->phpbb_root_path}mcp.{$this->php_ext}?i=-posey-ultimateblog-mcp-main_module&amp;mode=details&amp;id=$report_id"),
			]);

			$this->template->assign_var('S_REPORTS_OPEN', true);
		}

		// Add lang file
		$this->user->add_lang('mcp');

		// If we want to delete reports
		if ($this->request->is_set_post('delete'))
		{
			$ids = $this->request->variable('blog_report_id_list', array(0));
			$this->handle_reports('delete', $ids);
		}

		// If we want to close reports
		if ($this->request->is_set_post('close'))
		{
			$ids = $this->request->variable('blog_report_id_list', array(0));
			$this->handle_reports('close', $ids);
		}
	}

	public function reports_closed()
	{
		// When blog is disabled, redirect users back to the forum index
		if ($this->config['ub_enabled'] == 0)
		{
			redirect(append_sid("{$this->phpbb_root_path}index.{$this->php_ext}"));
		}

		// Check permissions
		if (!$this->auth->acl_get('m_blog_reports'))
		{
			redirect(append_sid("{$this->phpbb_root_path}mcp.{$this->php_ext}"));
		}

		// Get all closed blog comment reports
		$sql = 'SELECT r.*, u.user_id, u.username, u.user_colour
				FROM ' . REPORTS_TABLE . ' r
				LEFT JOIN ' . USERS_TABLE . ' u
					ON r.user_id = u.user_id
				WHERE r.blog_comment_id <> 0
					AND r.report_closed = 1
				ORDER BY r.report_time DESC';
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			// Get blog information on this row
			$sql_array = [
				'SELECT'	=> 'b.*, u.user_id, u.username, u.user_colour',

				'FROM'		=> [
					$this->ub_blogs_table => 'b',
					$this->ub_comments_table => 'bc',
				],

				'LEFT_JOIN' => [
					[
						'FROM'	=> [USERS_TABLE => 'u'],
						'ON'	=> 'bc.poster_id = u.user_id',
					]
				],

				'WHERE'		=> 'b.blog_id = bc.blog_id AND bc.comment_id = ' . (int) $row['blog_comment_id']
			];

			$blog_sql = $this->db->sql_build_query('SELECT', $sql_array);
			$blog_res = $this->db->sql_query($blog_sql);
			$blog = $this->db->sql_fetchrow($blog_res);
			$this->db->sql_freeresult($blog_res);

			// Set report ID
			$report_id = (int) $row['report_id'];

			$this->template->assign_block_vars('reports', [
				'REPORT_ID'		=> $report_id,
				'BLOG_SUBJECT'	=> $blog['blog_subject'],
				'POSTED_BY'		=> get_username_string('full', $blog['user_id'], $blog['username'], $blog['user_colour']),
				'POSTED_ON'		=> $this->user->format_date($blog['post_time']),
				'REPORTED_BY'	=> get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
				'REPORTED_ON'	=> $this->user->format_date($row['report_time']),

				'S_BLOG_REPORTS_ACTION'	=> append_sid("{$this->phpbb_root_path}mcp.{$this->php_ext}?i=-posey-ultimateblog-mcp-main_module&amp;mode=closed"),

				'U_BLOG'		=> $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $blog['blog_id']]),
				'U_COMMENT'		=> $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $blog['blog_id']]) . '#c' . (int) $row['blog_comment_id'],
				'U_DETAILS'		=> append_sid("{$this->phpbb_root_path}mcp.{$this->php_ext}?i=-posey-ultimateblog-mcp-main_module&amp;mode=details&amp;id=$report_id"),
			]);

			$this->template->assign_var('S_REPORTS_CLOSED', true);
		}

		// Add lang file
		$this->user->add_lang('mcp');

		// If we want to delete reports
		if ($this->request->is_set_post('delete'))
		{
			$ids = $this->request->variable('blog_report_id_list', array(0));
			$this->handle_reports('delete', $ids);
		}
	}

	public function reports_details()
	{
		// When blog is disabled, redirect users back to the MCP Front page
		if ($this->config['ub_enabled'] == 0)
		{
			redirect(append_sid("{$this->phpbb_root_path}mcp.{$this->php_ext}"));
		}

		// Check permissions
		if (!$this->auth->acl_get('m_blog_reports'))
		{
			trigger_error($this->user->lang['AUTH_MANAGE_BLOG_REPORTS'] . '<br><br><a href="' . append_sid("{$this->phpbb_root_path}mcp.{$this->php_ext}") . '">&laquo; ' . $this->user->lang['BACK_TO_PREV'] . '</a>');
		}

		// Grab the report_id
		$report_id = (int) $this->request->variable('id', 0);

		// Get all report information
		$sql_array = [
			'SELECT'	=> 'r.*, rr.*, u.user_id, u.username, u.user_colour',

			'FROM'		=> [
				REPORTS_TABLE => 'r',
				REPORTS_REASONS_TABLE => 'rr',
			],

			'LEFT_JOIN' => [
				[
					'FROM'	=> [USERS_TABLE => 'u'],
					'ON'	=> 'r.user_id = u.user_id',
				]
			],

			'WHERE'		=> 'r.reason_id = rr.reason_id AND r.report_id = ' . (int) $report_id,
		];
		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query($sql);
		$report = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		// Check if reports exists
		if (!$report)
		{
			trigger_error($this->user->lang['BLOG_REPORT_NOT_EXIST'] . '<br><br><a href="' . append_sid("{$this->phpbb_root_path}mcp.{$this->php_ext}?i=-posey-ultimateblog-mcp-main_module&amp;mode=open") . '">&laquo; ' . $this->user->lang['BACK_TO_PREV'] . '</a>');
		}

		// Check if it's not a blog report
		if (($report['post_id'] != 0 || $report['pm_id'] != 0) && $report['blog_comment_id'] == 0)
		{
			trigger_error($this->user->lang['BLOG_REPORT_NOT_BLOG'] . '<br><br><a href="' . append_sid("{$this->phpbb_root_path}mcp.{$this->php_ext}?i=-posey-ultimateblog-mcp-main_module&amp;mode=open") . '">&laquo; ' . $this->user->lang['BACK_TO_PREV'] . '</a>');
		}

		// Grab blog information
		$sql_array = [
			'SELECT'	=> 'b.*, u.user_id, u.username, u.user_colour',

			'FROM'		=> [
				$this->ub_blogs_table => 'b',
				$this->ub_comments_table => 'c',
			],

			'LEFT_JOIN' => [
				[
					'FROM'	=> [USERS_TABLE => 'u'],
					'ON'	=> 'c.poster_id = u.user_id',
				]
			],

			'WHERE'		=> 'b.blog_id = c.blog_id AND c.comment_id = ' . (int) $report['blog_comment_id'],
		];
		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query($sql);
		$blog = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		// Generate comment text for display
		$bbcode_options =	(($report['reported_post_enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) +
							(($report['reported_post_enable_smilies']) ? OPTION_FLAG_SMILIES : 0) +
							(($report['reported_post_enable_magic_url']) ? OPTION_FLAG_LINKS : 0);
		$comment_text = generate_text_for_display($report['reported_post_text'], $report['reported_post_uid'], $report['reported_post_bitfield'], $bbcode_options);

		$this->template->assign_vars([
			'REPORT_ID'		=> $report_id,
			'REPORT_REASON'	=> $report['reason_title'],
			'REPORT_TEXT'	=> $report['report_text'],
			'REPORTED_BY'	=> get_username_string('full', $report['user_id'], $report['username'], $report['user_colour']),
			'REPORTED_ON'	=> $this->user->format_date($report['report_time']),

			'COMMENT_TEXT'		=> $comment_text,
			'COMMENT_TITLE'		=> 'Re: ' . $blog['blog_subject'],
			'COMMENT_POSTED_BY'	=> get_username_string('full', $blog['user_id'], $blog['username'], $blog['user_colour']),
			'COMMENT_POSTED_ON'	=> $this->user->format_date($blog['post_time']),

			'S_BLOG_REPORT_DETAILS_ACTION'	=> append_sid("{$this->phpbb_root_path}mcp.{$this->php_ext}?i=-posey-ultimateblog-mcp-main_module&amp;mode=details&amp;id=$report_id"),
			'S_REPORT_OPEN'	=> $report['report_closed'] == 0 ? true : false,

			'U_BLOG'		=> $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $blog['blog_id']]),
			'U_COMMENT'		=> $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $blog['blog_id']]) . '#c' . (int) $report['blog_comment_id'],
			'U_REPORTS'		=> append_sid("{$this->phpbb_root_path}mcp.{$this->php_ext}?i=-posey-ultimateblog-mcp-main_module&amp;mode=open"),
		]);

		// Add lang file
		$this->user->add_lang('mcp');

		// If we want to delete this report
		if ($this->request->is_set_post('delete'))
		{
			$ids = $this->request->variable('blog_report_id_list', array(0));
			$this->handle_reports('delete', $ids);
		}

		// If we want to close this report
		if ($this->request->is_set_post('close'))
		{
			$ids = $this->request->variable('blog_report_id_list', array(0));
			$this->handle_reports('close', $ids);
		}
	}

	/*
	* Handle blog reports
	* $action	string		'close' or 'delete'
	* $ids		array		report ids
	*/
	function handle_reports($action, $ids)
	{
		// Check if ID's are not empty..
		if (!sizeof($ids))
		{
			trigger_error($this->user->lang['BLOG_REPORTS_EMPTY_ID'] . '<br><br><a href="' . append_sid("{$this->phpbb_root_path}mcp.{$this->php_ext}?i=-posey-ultimateblog-mcp-main_module&amp;mode=open") . '">&laquo; ' . $this->user->lang['BACK_TO_PREV'] . '</a>');
		}

		// Select comment IDs from Reports table
		$sql = 'SELECT blog_comment_id
				FROM ' . REPORTS_TABLE . '
				WHERE ' . $this->db->sql_in_set('report_id', $ids);
		$result = $this->db->sql_query($sql);

		// Put all comment ids in an array
		$comments = [];
		$i = 0;

		while ($row = $this->db->sql_fetchrow($result))
		{
			$comments[$i] = $row['blog_comment_id'];
			$i++;
		}

		$this->db->sql_freeresult($result);

		// Update the blog comments, mark as unreported
		$sql = 'UPDATE ' . $this->ub_comments_table . '
				SET comment_reported = 0
				WHERE ' . $this->db->sql_in_set('comment_id', $comments);
		$this->db->sql_query($sql);

		if ($action == 'close')
		{
			// Close the reports
			$sql = 'UPDATE ' . REPORTS_TABLE . '
					SET report_closed = 1
					WHERE ' . $this->db->sql_in_set('report_id', $ids);
			$this->db->sql_query($sql);
		}

		if ($action == 'delete')
		{
			// Delete the reports
			$sql = 'DELETE FROM ' . REPORTS_TABLE . '
					WHERE ' . $this->db->sql_in_set('report_id', $ids);
			$this->db->sql_query($sql);
		}

		// Add it to the moderators log
		foreach ($comments as $comment_id)
		{
			$sql = 'SELECT b.blog_subject
					FROM ' . $this->ub_blogs_table . ' b
					LEFT JOIN ' . $this->ub_comments_table . ' c
						ON b.blog_id = c.blog_id
					WHERE c.comment_id = ' . (int) $comment_id;
			$result = $this->db->sql_query($sql);
			$blog_subject = $this->db->sql_fetchfield('blog_subject');
			$this->db->sql_freeresult($result);

			$this->log->add('mod', $this->user->data['user_id'], $this->user->ip, 'LOG_BLOG_REPORT_' . strtoupper($action) . 'D', false, array($blog_subject));
		}

		// Send success message
		trigger_error($this->user->lang['BLOG_REPORT_' . strtoupper($action) . 'D'] . '<br><br><a href="' . append_sid("{$this->phpbb_root_path}mcp.{$this->php_ext}?i=-posey-ultimateblog-mcp-main_module&amp;mode=open") . '">&laquo; ' . $this->user->lang['BLOG_REPORTS_RETURN'] . '</a>');
	}
}
