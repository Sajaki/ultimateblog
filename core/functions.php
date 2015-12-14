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
	protected $ub_blogs_table;
	protected $ub_cats_table;

	/**
	* Constructor
	*/
	public function __construct(
		\phpbb\template\template $template,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\controller\helper $helper,
		\phpbb\user $user,
		$ub_blogs_table,
		$ub_cats_table)
	{
		$this->template	= $template;
		$this->db		= $db;
		$this->helper	= $helper;
		$this->user		= $user;
		$this->ub_blogs_table	= $ub_blogs_table;
		$this->ub_cats_table	= $ub_cats_table;
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
			]);
		}
		$this->db->sql_freeresult($result);
	}

	public function archive($year, $month)
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
			'SELECT'	=> 'b.*, u.user_id, u.username, u.user_colour, c.cat_name',

			'FROM'		=> [$this->ub_blogs_table => 'b'],

			'LEFT_JOIN' => [
				[
					'FROM'	=> [USERS_TABLE => 'u',
								$this->ub_cats_table => 'c'],
					'ON'	=> 'b.poster_id = u.user_id AND b.cat_id = c.cat_id',
				]
			],

			'WHERE'		=> 'MONTH(FROM_UNIXTIME(b.post_time)) = ' . (int) $month . '
								AND YEAR(FROM_UNIXTIME(b.post_time)) = ' . (int) $year,

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
				'CAT'		=> $row['cat_name'],
				'SUBJECT'	=> $row['blog_subject'],
				'TEXT'		=> $text,
				'POSTER'	=> get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
				'POST_TIME'	=> $this->user->format_date($row['post_time'], 'F jS, Y'),

				'U_BLOG'		=> $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => $row['blog_id']]),
				'U_CAT'			=> $this->helper->route('posey_ultimateblog_category', ['cat_id' => (int) $row['cat_id']]),
			]);

			// Set archive name for Nav Bar (Can be any of the rows, month and year remain the same)
			$archive_title = $this->user->lang('BLOG_ARCHIVE') . ' ' . $this->user->format_date($row['post_time'], 'F Y');
		}
		$this->db->sql_freeresult($result);

		$this->template->assign_vars([
			'CAT_NAME'			=> $cat_name,

			'S_BLOG_CAN_ADD'	=> $this->auth->acl_get('u_blog_make'),
			'U_BLOG_ADD'		=> $this->helper->route('posey_ultimateblog_blog', ['action' => 'add']),
		]);

		// Get sidebar
		$this->sidebar();

		// Get sidebar
		$this->functions->sidebar();

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

		// Generate page title
		page_header($archive_title);
	}
}