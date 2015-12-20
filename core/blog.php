<?php
/**
*
* @package phpBB Extension - Ultimate Blog
* @copyright (c) 2015 posey
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace posey\ultimateblog\core;

class blog
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
	protected $ub_rating_table;
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
		$ub_comments_table,
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
		$this->ub_comments_table = $ub_comments_table;
		$this->ub_rating_table	= $ub_rating_table;
		$this->functions		= $functions;
	}

	function latest()
	{
		$start = $this->request->variable('start', 0);

		// Get latest blogs
		$sql_array = [
			'SELECT'	=> 'b.*, u.user_id, u.username, u.user_colour',

			'FROM'		=> [
				$this->ub_blogs_table => 'b',
			],

			'LEFT_JOIN' => [
				[
					'FROM'	=> [USERS_TABLE => 'u'],
					'ON'	=> 'b.poster_id = u.user_id',
				]
			],

			'ORDER_BY'	=> 'b.post_time DESC',
		];

		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query_limit($sql, $this->config['ub_blogs_per_page'], $start);

		while ($row = $this->db->sql_fetchrow($result))
		{
			// Grab category name
			$cat_sql = 'SELECT cat_name
						FROM ' . $this->ub_cats_table . '
						WHERE cat_id = ' . (int) $row['cat_id'];
			$cat_result = $this->db->sql_query($cat_sql);
			$cat_name = $this->db->sql_fetchfield('cat_name');
			$this->db->sql_freeresult($cat_result);

			// Grab rating
			$r_sql = 'SELECT COUNT(rating) as total_rate_users, SUM(rating) as total_rate_sum
					FROM ' . $this->ub_rating_table . '
					WHERE blog_id = ' . (int) $row['blog_id'];
			$r_result = $this->db->sql_query($r_sql);
			$extra = $this->db->sql_fetchrow($r_result);
			$this->db->sql_freeresult($r_result);

			// Check BBCode Options
			$bbcode_options =	(($row['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) +
								(($row['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) +
								(($row['enable_magic_url']) ? OPTION_FLAG_LINKS : 0);

			// Generate blog text
			$text = generate_text_for_display($row['blog_text'], $row['bbcode_uid'], $row['bbcode_bitfield'], $bbcode_options);

			// Cut off blog text
			if ($this->config['ub_cutoff'] != 0)
			{
				$text = (strlen($text) > $this->config['ub_cutoff']) ? substr($text, 0, $this->config['ub_cutoff']) . '<span class="blog-read-full"> ... <a href="' . $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $row['blog_id']]) . '" alt="" title="' . $this->user->lang['BLOG_READ_FULL'] . '">' . $this->user->lang['BLOG_READ_FULL'] . '</a></span>' : $text;
			}

			$this->template->assign_block_vars('blogs', [
				'BLOG_ID'	=> $row['blog_id'],
				'CAT'		=> $cat_name,
				'CAT_ID'	=> $row['cat_id'],
				'SUBJECT'	=> $row['blog_subject'],
				'TEXT'		=> $text,
				'POSTER'	=> get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
				'POST_TIME'	=> $this->user->format_date($row['post_time']),
				'RATING'	=> $extra['total_rate_users'] > 0 ? $extra['total_rate_sum'] / $extra['total_rate_users'] : 0,

				'U_BLOG'		=> $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $row['blog_id']]),
				'U_CAT'			=> $this->helper->route('posey_ultimateblog_category', ['cat_id' => (int) $row['cat_id']]),
			]);
		}
		$this->db->sql_freeresult($result);

		// Get Sidebar
		$this->functions->sidebar();

		$this->template->assign_vars([
			'S_BLOG_CAN_ADD'	=> $this->auth->acl_get('u_blog_make'),
			'U_BLOG_ADD'		=> $this->helper->route('posey_ultimateblog_blog', ['action' => 'add']),
		]);

		// Assign breadcrumb template vars
		$this->template->assign_block_vars('navlinks', [
			'U_VIEW_FORUM'		=> $this->helper->route('posey_ultimateblog_blog'),
			'FORUM_NAME'		=> $this->user->lang('BLOG'),
		]);

		// Count blogs
		$sql = 'SELECT *
			FROM ' . $this->ub_blogs_table . '
			ORDER BY blog_id ASC';
		$result_total = $this->db->sql_query($sql);
		$row_total = $this->db->sql_fetchrowset($result_total);
		$total_blog_count = (int) sizeof($row_total);
		$this->db->sql_freeresult($result_total);

		//Start pagination
		$this->pagination->generate_template_pagination($this->helper->route('posey_ultimateblog_blog'), 'pagination', 'start', $total_blog_count, $this->config['ub_blogs_per_page'], $start);

		$this->template->assign_var('TOTAL_BLOGS', $this->user->lang('BLOG_BLOG_COUNT', (int) $total_blog_count));
	}

	function add()
	{
		add_form_key('add_blog');

		if (!$this->auth->acl_get('u_blog_make'))
		{
			trigger_error($this->user->lang['AUTH_BLOG_ADD'] . '<br><br><a href="' . $this->helper->route('posey_ultimateblog_blog') . '">&laquo; ' . $this->user->lang['BLOG_BACK'] . '</a>');
		}

		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key('add_blog'))
			{
				// Invalid form key
				trigger_error($this->user->lang['FORM_INVALID'] . '<br><br><a href="' . $this->helper->route('posey_ultimateblog_blog', ['action' => 'add']) . '">&laquo; ' . $this->user->lang['BACK_TO_PREV'] . '</a>');
			}
			else if ($this->request->variable('category', 0) == '')
			{
				// No category selected
				trigger_error($this->user->lang['CAT_INVALID'] . '<br><br><a href="' . $this->helper->route('posey_ultimateblog_blog', ['action' => 'edit']) . '">&laquo; ' . $this->user->lang['BACK_TO_PREV'] . '</a>');
			}
			else
			{
				// Generate text for storage
				$text = $this->request->variable('message', '', true);
				$uid = $bitfield = $options = ''; // will be modified by generate_text_for_storage
				$allow_bbcode = $this->request->variable('blog_bbcode', 0) == 1 ? true : false;
				$allow_smilies = $this->request->variable('blog_smilies', 0) == 1 ? true : false;
				$allow_urls = $this->request->variable('blog_urls', 0) == 1 ? true : false;
				generate_text_for_storage($text, $uid, $bitfield, $options, $allow_bbcode, $allow_smilies, $allow_urls);

				$blog_row = [
					'cat_id'				=> (int) $this->request->variable('category', 1),
					'blog_subject'			=> ucfirst($this->request->variable('subject', '', true)),
					'blog_text'				=> $text,
					'poster_id'				=> (int) $this->user->data['user_id'],
					'post_time'				=> time(),
					'enable_bbcode'	 		=> $allow_bbcode ? 1 : 0,
					'enable_smilies'		=> $allow_smilies ? 1 : 0,
					'enable_magic_url'		=> $allow_urls ? 1 : 0,
					'bbcode_uid'			=> $uid,
					'bbcode_bitfield'		=> $bitfield,
					'blog_edit_locked'		=> $this->request->variable('edit_locked', 0),
					'enable_comments'		=> $this->request->variable('enable_comments', 0),
					'blog_description'		=> $this->request->variable('blog_description', ''),
				];

				// Insert the blog
				$sql = 'INSERT INTO ' . $this->ub_blogs_table . ' ' . $this->db->sql_build_array('INSERT', $blog_row);
				$this->db->sql_query($sql);
				$blog_id = (int) $this->db->sql_nextid();

				// Add it to the log
				$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_BLOG_ADDED', false, [$blog_row['blog_subject']]);

				// Send success message
				trigger_error($this->user->lang['BLOG_ADDED'] . '<br><br><a href="' . $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => $blog_id]) . '">' . $this->user->lang['BLOG_VIEW'] . ' &raquo;</a>');
			}
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

		$bbcode_status	= ($this->config['allow_bbcode']) ? true : false;
		$smilies_status	= ($this->config['allow_smilies']) ? true : false;
		$img_status		= ($bbcode_status) ? true : false;
		$url_status		= ($this->config['allow_post_links']) ? true : false;
		$flash_status	= ($bbcode_status && $this->config['allow_post_flash']) ? true : false;
		$quote_status	= true;
		$s_hidden_fields = '';
		$form_enctype = '';

		$blog_preview = '';
		if ($this->request->is_set_post('preview'))
		{
				$edit_locked = $this->request->variable('edit_locked', 0) == 1 ? true : false;
				$enable_comments = $this->request->variable('enable_comments', 0) == 1 ? true : false;

				$blog_text = $this->request->variable('message', '', true);
				$uid = $bitfield = $options = ''; // will be modified by generate_text_for_storage
				$allow_bbcode = $this->request->variable('blog_bbcode', 0) == 1 ? true : false;
				$allow_smilies = $this->request->variable('blog_smilies', 0) == 1 ? true : false;
				$allow_urls = $this->request->variable('blog_urls', 0) == 1 ? true : false;
				generate_text_for_storage($blog_text, $uid, $bitfield, $options, $allow_bbcode, $allow_smilies, $allow_urls);
				$blog_preview = generate_text_for_display($blog_text, $uid, $bitfield, $options);

				$this->template->assign_var('MESSAGE', $this->request->variable('message', '', true));
				$this->template->assign_var('DESCRIPTION', $this->request->variable('blog_description', '', true));
		}

		// Grab all categories
		$sql = 'SELECT cat_id, cat_name
				FROM ' . $this->ub_cats_table;
		$result = $this->db->sql_query($sql);

		$selected = $blog_preview ? '' : ' selected';
		$categories = '<option value="" required disabled' . $selected . '>' . $this->user->lang['BLOG_CHOOSE_CAT'] . '</option>';

		while ($row = $this->db->sql_fetchrow($result))
		{
			$selected = '';
			if ($blog_preview)
			{
				$selected = ($row['cat_id'] == $this->request->variable('category', 0)) ? ' selected' : '';
			}
			$categories .= '<option value="' . $row['cat_id'] . '"' . $selected . '>' . $row['cat_name'] . '</option>';
		}
		$this->db->sql_freeresult($result);

		// Assign breadcrumb template vars
		$navlinks_array = [
			[
				'U_VIEW_FORUM'		=> $this->helper->route('posey_ultimateblog_blog'),
				'FORUM_NAME'		=> $this->user->lang('BLOG'),
			],
			[
				'U_VIEW_FORUM'		=> $this->helper->route('posey_ultimateblog_blog', ['action' => 'add']),
				'FORUM_NAME'		=> $this->user->lang['BLOG_ADD'],
			]
		];

		foreach($navlinks_array as $name)
		{
			$this->template->assign_block_vars('navlinks', [
				'FORUM_NAME'	=> $name['FORUM_NAME'],
				'U_VIEW_FORUM'	=> $name['U_VIEW_FORUM'],
			]);
		}

		// Assign template vars
		$this->template->assign_vars([
			'CATEGORIES'			=> $categories,
			'BLOG_PREVIEW'			=> $blog_preview,
			'SUBJECT'				=> $blog_preview ? $this->request->variable('subject', '') : '',

			'S_FORM_ENCTYPE'		=> $form_enctype,
			'S_HIDDEN_FIELDS'		=> $s_hidden_fields,
			'S_BBCODE_STATUS'		=> $bbcode_status,
			'S_BBCODE_ALLOWED'		=> ($bbcode_status) ? $this->user->lang('BBCODE_IS_ON', "<a href=\"{$this->phpbb_root_path}faq.{$this->php_ext}?mode=bbcode\">", '</a>') : $this->user->lang('BBCODE_IS_OFF', "<a href=\"{$this->phpbb_root_path}faq.{$this->php_ext}?mode=bbcode\">", '</a>'),
			'S_SMILIES_STATUS'		=> $smilies_status,
			'S_SMILIES_ALLOWED'		=> $smilies_status ? $this->user->lang['SMILIES_ARE_ON'] : $this->user->lang['SMILIES_ARE_OFF'],
			'S_BBCODE_IMG'			=> $img_status ? $this->user->lang['IMAGES_ARE_ON'] : $this->user->lang['IMAGES_ARE_OFF'],
			'S_BBCODE_FLASH'		=> $flash_status ? $this->user->lang['FLASH_IS_ON'] : $this->user->lang['FLASH_IS_OFF'],
			'S_BBCODE_URL'			=> $url_status ? $this->user->lang['URL_IS_ON'] : $this->user->lang['URL_IS_OFF'],
			'S_BLOG_BBCODE'			=> $blog_preview ? $allow_bbcode : true,
			'S_BLOG_SMILIES'		=> $blog_preview ? $allow_smilies : true,
			'S_BLOG_URLS'			=> $blog_preview ? $allow_urls : true,
			'S_EDIT_LOCKED'			=> $blog_preview ? $edit_locked : false,
			'S_ENABLE_COMMENTS'		=> $blog_preview ? $enable_comments : true,
		]);
	}

	function edit($blog_id)
	{
		// Grab blog info
		$sql = 'SELECT *
				FROM ' . $this->ub_blogs_table . '
				WHERE blog_id = ' . (int) $blog_id;
		$result = $this->db->sql_query($sql);
		$blog = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		add_form_key('edit_blog');

		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key('edit_blog'))
			{
				// Invalid form key
				trigger_error($this->user->lang['FORM_INVALID'] . '<br><br><a href="' . $this->helper->route('posey_ultimateblog_blog', ['action' => 'edit']) . '">&laquo; ' . $this->user->lang['BACK_TO_PREV'] . '</a>');
			}
			else if ($this->request->variable('category', 0) == '')
			{
				// No category selected
				trigger_error($this->user->lang['CAT_INVALID'] . '<br><br><a href="' . $this->helper->route('posey_ultimateblog_blog', ['action' => 'edit']) . '">&laquo; ' . $this->user->lang['BACK_TO_PREV'] . '</a>');
			}
			else
			{
				// Generate text for storage
				$text = $this->request->variable('message', '', true);
				$uid = $bitfield = $options = ''; // will be modified by generate_text_for_storage
				$allow_bbcode = $this->request->variable('blog_bbcode', 0) == 1 ? true : false;
				$allow_smilies = $this->request->variable('blog_smilies', 0) == 1 ? true : false;
				$allow_urls = $this->request->variable('blog_urls', 0) == 1 ? true : false;
				generate_text_for_storage($text, $uid, $bitfield, $options, $allow_bbcode, $allow_smilies, $allow_urls);

				$blog_row = [
					'cat_id'				=> (int) $this->request->variable('category', 1),
					'blog_subject'			=> ucfirst($this->request->variable('subject', '', true)),
					'blog_text'				=> $text,
					'enable_bbcode'	 		=> $allow_bbcode ? 1 : 0,
					'enable_smilies'		=> $allow_smilies ? 1 : 0,
					'enable_magic_url'		=> $allow_urls ? 1 : 0,
					'bbcode_uid'			=> $uid,
					'bbcode_bitfield'		=> $bitfield,
					'blog_edit_time'		=> time(),
					'blog_edit_user'		=> $this->user->data['user_id'],
					'blog_edit_reason'		=> $this->request->variable('edit_reason', ''),
					'blog_edit_count'		=> (int) ($blog['blog_edit_count'] + 1),
					'blog_edit_locked'		=> $this->request->variable('edit_locked', 0),
					'enable_comments'		=> $this->request->variable('enable_comments', 0),
					'blog_description'		=> $this->request->variable('blog_description', '', true),
				];

				// Update the blog
				$sql = 'UPDATE ' . $this->ub_blogs_table . ' SET ' . $this->db->sql_build_array('UPDATE', $blog_row) . ' WHERE blog_id = ' . (int) $blog_id;
				$this->db->sql_query($sql);

				// Add it to the log
				$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_BLOG_EDITED', false, array($blog_row['blog_subject']));

				// Send success message
				trigger_error($this->user->lang['BLOG_EDITED'] . '<br><br><a href="' . $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $blog_id]) . '">' . $this->user->lang['BLOG_VIEW'] . ' &raquo;</a>');
			}
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

		$bbcode_status	= ($this->config['allow_bbcode']) ? true : false;
		$smilies_status	= ($this->config['allow_smilies']) ? true : false;
		$img_status		= ($bbcode_status) ? true : false;
		$url_status		= ($this->config['allow_post_links']) ? true : false;
		$flash_status	= ($bbcode_status && $this->config['allow_post_flash']) ? true : false;
		$quote_status	= true;
		$s_hidden_fields = '';
		$form_enctype = '';

		// Check if blog exists
		if (!$blog)
		{
			trigger_error($this->user->lang['BLOG_NOT_EXIST'] . '<br><br><a href="' . $this->helper->route('posey_ultimateblog_blog') . '">&laquo; ' . $this->user->lang['BLOG_BACK'] . '</a>');
		}

		// Check if authorised to edit this blog
		if (!$this->auth->acl_gets('u_blog_edit', 'm_blog_edit'))
		{
			trigger_error($this->user->lang['AUTH_BLOG_EDIT'] . '<br><br><a href="' . $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $blog_id]) . '">&laquo; ' . $this->user->lang['BLOG_BACK'] . '</a>');
		}

		if (($this->auth->acl_get('u_blog_edit') && $blog['poster_id'] != $this->user->data['user_id']) && !$this->auth->acl_get('m_blog_edit'))
		{
			trigger_error($this->user->lang['AUTH_BLOG_EDIT_ELSE'] . '<br><br><a href="' . $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $blog_id]) . '">&laquo; ' . $this->user->lang['BLOG_BACK'] . '</a>');
		}

		// Blog editing has been locked
		if ($blog['blog_edit_locked'] == 1 && !$this->auth->acl_get('m_blog_edit'))
		{
			trigger_error($this->user->lang['BLOG_EDIT_LOCKED'] . '<br><br><a href="' . $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $blog_id]) . '">&laquo; ' . $this->user->lang['BACK_TO_PREV'] . '</a>');
		}

		// Generate text for editing
		decode_message($blog['blog_text'], $blog['bbcode_uid']);

		// Assign breadcrumb template vars
		$navlinks_array = [
			[
				'U_VIEW_FORUM'		=> $this->helper->route('posey_ultimateblog_blog'),
				'FORUM_NAME'		=> $this->user->lang('BLOG'),
			],
			[
				'U_VIEW_FORUM'		=> $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $blog['blog_id']]),
				'FORUM_NAME'		=> $blog['blog_subject'],
			]
		];

		foreach($navlinks_array as $name)
		{
			$this->template->assign_block_vars('navlinks', [
				'FORUM_NAME'	=> $name['FORUM_NAME'],
				'U_VIEW_FORUM'	=> $name['U_VIEW_FORUM'],
			]);
		}

		$blog_preview = '';
		if ($this->request->is_set_post('preview'))
		{
				$blog_text = $this->request->variable('message', '', true);
				$uid = $bitfield = $options = ''; // will be modified by generate_text_for_storage
				$allow_bbcode = $this->request->variable('blog_bbcode', 0) == 1 ? true : false;
				$allow_smilies = $this->request->variable('blog_smilies', 0) == 1 ? true : false;
				$allow_urls = $this->request->variable('blog_urls', 0) == 1 ? true : false;
				generate_text_for_storage($blog_text, $uid, $bitfield, $options, $allow_bbcode, $allow_smilies, $allow_urls);
				$blog_preview = generate_text_for_display($blog_text, $uid, $bitfield, $options);
		}

		// Grab all categories
		$sql = 'SELECT cat_id, cat_name
				FROM ' . $this->ub_cats_table;
		$result = $this->db->sql_query($sql);

		$categories = '<option value="" disabled>' . $this->user->lang['BLOG_CHOOSE_CAT'] . '</option>';

		while ($row = $this->db->sql_fetchrow($result))
		{
			if ($blog_preview)
			{
				$selected = $row['cat_id'] == $this->request->variable('category', 0) ? ' selected' : '';
			} else {
				$selected = $row['cat_id'] == $blog['cat_id'] ? ' selected' : '';
			}
			$categories .= '<option value="' . $row['cat_id'] . '"' . $selected . '>' . $row['cat_name'] . '</option>';
		}
		$this->db->sql_freeresult($result);

		// Assign it to the template
		$this->template->assign_vars([
			'BLOG_PREVIEW'		=> $blog_preview,
			'CATEGORIES'		=> $categories,
			'DESCRIPTION'		=> $blog_preview ? $this->request->variable('blog_description', '', true) : $blog['blog_description'],
			'MESSAGE'			=> $blog_preview ? $this->request->variable('message', '', true) : $blog['blog_text'],
			'SUBJECT'			=> $blog['blog_subject'],

			'S_CAN_LOCK_EDIT'		=> $this->auth->acl_get('m_blog_edit'),
			'S_FORM_ENCTYPE'		=> $form_enctype,
			'S_HIDDEN_FIELDS'		=> $s_hidden_fields,
			'S_BBCODE_STATUS'		=> $bbcode_status,
			'S_BBCODE_ALLOWED'		=> ($bbcode_status) ? $this->user->lang('BBCODE_IS_ON', "<a href=\"{$this->phpbb_root_path}faq.{$this->php_ext}?mode=bbcode\">", '</a>') : $this->user->lang('BBCODE_IS_OFF', "<a href=\"{$this->phpbb_root_path}faq.{$this->php_ext}?mode=bbcode\">", '</a>'),
			'S_SMILIES_STATUS'		=> $smilies_status,
			'S_SMILIES_ALLOWED'		=> $smilies_status ? $this->user->lang['SMILIES_ARE_ON'] : $this->user->lang['SMILIES_ARE_OFF'],
			'S_BBCODE_IMG'			=> $img_status ? $this->user->lang['IMAGES_ARE_ON'] : $this->user->lang['IMAGES_ARE_OFF'],
			'S_BBCODE_FLASH'		=> $flash_status ? $this->user->lang['FLASH_IS_ON'] : $this->user->lang['FLASH_IS_OFF'],
			'S_BBCODE_URL'			=> $url_status ? $this->user->lang['URL_IS_ON'] : $this->user->lang['URL_IS_OFF'],
			'S_BLOG_BBCODE'			=> $blog['enable_bbcode'] == 1 ? true : false,
			'S_BLOG_SMILIES'		=> $blog['enable_smilies'] == 1 ? true : false,
			'S_BLOG_URLS'			=> $blog['enable_magic_url'] == 1 ? true : false,
			'S_EDIT_LOCKED'			=> $blog['blog_edit_locked'] == 1 ? true : false,
			'S_ENABLE_COMMENTS'		=> $blog['enable_comments'] == 1 ? true : false,
			'S_BLOG_EDIT'			=> true,
		]);
	}

	function delete($blog_id)
	{
		// Check if user is authorised to delete blogs
		if (!$this->auth->acl_get('m_blog_delete'))
		{
			trigger_error($this->user->lang['AUTH_BLOG_DELETE'] . '<br><br><a href="' . $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $blog_id]) . '">&laquo; ' . $this->user->lang['BACK_TO_PREV'] . '</a>');
		}

		if (confirm_box(true))
		{
			// Grab the blog subject for the log
			$sql = 'SELECT blog_subject
					FROM ' . $this->ub_blogs_table . '
					WHERE blog_id = ' .(int) $blog_id;
			$result = $this->db->sql_query($sql);
			$blog_name = $this->db->sql_fetchfield('blog_subject');
			$this->db->sql_freeresult($result);

			// Delete the blog
			$sql = 'DELETE FROM ' . $this->ub_blogs_table . '
					WHERE blog_id = ' . (int) $blog_id;
			$this->db->sql_query($sql);

			// Delete the blog comments
			$sql = 'DELETE FROM ' . $this->ub_comments_table . '
					WHERE blog_id = ' . (int) $blog_id;
			$this->db->sql_query($sql);

			// Delete the blog ratings
			$sql = 'DELETE FROM ' . $this->ub_rating_table . '
					WHERE blog_id = ' . (int) $blog_id;
			$this->db->sql_query($sql);

			// Add it to the log
			$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_BLOG_DELETED', false, array($blog_name));

			// Send success message
			trigger_error($this->user->lang['BLOG_DELETED'] . '<br><br><a href="' . $this->helper->route('posey_ultimateblog_blog') . '">&laquo; ' . $this->user->lang['BLOG_BACK'] . '</a>');
		}
		else
		{
			$message = $this->user->lang['BLOG_DELETE_CONFIRM'];

			confirm_box(false, $message, build_hidden_fields(array(
				'blog_id'		=> (int) $blog_id,
				'action'		=> 'delete'))
			);

			// Use a redirect to take the user back to the previous page
			// if the user chose not delete the blog from the confirmation page.
			redirect($this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $blog_id]));
		}
	}

	function display($blog_id)
	{
		// When blog is disabled, redirect users back to the forum index
		if (empty($this->config['ub_enabled']))
		{
			redirect(append_sid("{$this->root_path}index.{$this->php_ext}"));
		}

		// Check if user can view blogs
		if (!$this->auth->acl_get('u_blog_view'))
		{
			trigger_error($this->user->lang['AUTH_BLOG_VIEW'] . '<br><br>' . $this->user->lang('RETURN_INDEX', '<a href="' . append_sid("{$this->phpbb_root_path}index.{$this->php_ext}") . '">&laquo; ', '</a>'));
		}

		$start = $this->request->variable('start', 0);

		// Get blog and poster info
		$sql_array = [
			'SELECT'	=> 'b.*, u.user_id, u.username, u.user_colour, u.user_avatar, u.user_avatar_type, u.user_avatar_height, u.user_avatar_width',

			'FROM'		=> [
				$this->ub_blogs_table => 'b',
			],

			'LEFT_JOIN' => [
				[
					'FROM'	=> [USERS_TABLE => 'u'],
					'ON'	=> 'b.poster_id = u.user_id',
				]
			],

			'WHERE'		=> 'b.blog_id = ' . (int) $blog_id,

			'ORDER_BY'	=> 'b.post_time DESC',
		];

		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query($sql);
		$blog = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		// Get category name
		$sql = 'SELECT cat_name
				FROM ' . $this->ub_cats_table . '
				WHERE cat_id = ' . (int) $blog['cat_id'];
		$result = $this->db->sql_query($sql);
		$cat_name = $this->db->sql_fetchfield('cat_name');
		$this->db->sql_freeresult($result);

		// Grab rating
		$sql = 'SELECT COUNT(rating) as total_rate_users, SUM(rating) as total_rate_sum
				FROM ' . $this->ub_rating_table . '
				WHERE blog_id = ' . (int) $blog_id;
		$result = $this->db->sql_query($sql);
		$rate = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		// Get user who last edited
		$sql = 'SELECT user_id, username, user_colour
				FROM ' . USERS_TABLE . '
				WHERE user_id = ' . (int) $blog['blog_edit_user'];
		$result = $this->db->sql_query($sql);
		$edit_user = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		$edit_username = get_username_string('full', $edit_user['user_id'], $edit_user['username'], $edit_user['user_colour']);

		// Check BBCode Options
		$bbcode_options =	(($blog['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) +
							(($blog['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) +
							(($blog['enable_magic_url']) ? OPTION_FLAG_LINKS : 0);

		// Get Sidebar
		$this->functions->sidebar();

		// Check to see if user has already rated this blog
		// If he has already rated, grab the rating
		$rating = $this->has_rated($blog['blog_id']);

		$this->template->assign_vars([
			'BLOG_ID'			=> $blog['blog_id'],
			'BLOG_SUBJECT'		=> $blog['blog_subject'],
			'BLOG_TEXT'			=> generate_text_for_display($blog['blog_text'], $blog['bbcode_uid'], $blog['bbcode_bitfield'], $bbcode_options),
			'BLOG_DESCRIPTION'	=> $blog['blog_description'],
			'BLOG_POSTER'		=> get_username_string('full', $blog['user_id'], $blog['username'], $blog['user_colour']),
			'BLOG_POST_TIME'	=> $this->user->format_date($blog['post_time']),
			'BLOG_AVATAR'		=> phpbb_get_user_avatar($blog),
			'BLOG_RATE_USERS'	=> $this->user->lang('BLOG_RATE_USERS', (int) $rate['total_rate_users']),
			'BLOG_RATE_AVRG'	=> $rate['total_rate_users'] > 0 ? round(($rate['total_rate_sum'] / $rate['total_rate_users']), 1, PHP_ROUND_HALF_UP) : 0.0,
			'BLOG_RATE_IMG'		=> $rate['total_rate_users'] > 0 ? round(($rate['total_rate_sum'] / $rate['total_rate_users']), 0, PHP_ROUND_HALF_UP) : 0,
			'BLOG_RATE_HAS'		=> $rating ? $this->user->lang('BLOG_RATED_ALREADY', $rating) : false,

			'EDIT_LAST'		=> $blog['blog_edit_time'] > 0 ? $this->user->lang('BLOG_EDIT_LAST', '<span itemprop="editor">' . $edit_username . '</span>', '<span itemprop="dateModified">' . $this->user->format_date($blog['blog_edit_time']) . '</span>') : '',
			'EDIT_REASON'	=> $blog['blog_edit_reason'],
			'EDIT_COUNT'	=> $this->user->lang('BLOG_EDIT_COUNT', (int) $blog['blog_edit_count']),

			'CAT_NAME'		=> $cat_name,
			'CAT_LINK'		=> $this->helper->route('posey_ultimateblog_category', ['cat_id' => (int) $blog['cat_id']]),

			'S_BLOG_CAN_ADD'	=> $this->auth->acl_get('u_blog_make'),
			'S_BLOG_CAN_DELETE'	=> $this->auth->acl_get('m_blog_delete'),
			'S_BLOG_CAN_EDIT'	=> (($this->auth->acl_get('u_blog_edit') && $this->user->data['user_id'] == $blog['user_id']) || $this->auth->acl_get('m_blog_edit')) ? true : false,
			'S_BLOG_CAN_RATE'	=> ($this->auth->acl_get('u_blog_rate') && !$rating),
			'S_BLOG_RATED'		=> $rate['total_rate_users'] > 0 ? true : false,
			'S_COMMENT_CAN_ADD'	=> $this->auth->acl_get('u_blog_comment_make'),
			'S_COMMENT_CAN_DEL'	=> $this->auth->acl_get('m_blog_comment_delete'),
			'S_EDITED'			=> $blog['blog_edit_count'] > 0 ? true : false,
			'S_EDIT_LOCKED'		=> $blog['blog_edit_locked'] == 1 ? true : false,
			'S_ENABLE_COMMENTS'	=> $blog['enable_comments'] == 1 ? true : false,

			'U_BLOG_ADD'		=> $this->helper->route('posey_ultimateblog_blog', ['action' => 'add']),
			'U_BLOG_DELETE'		=> $this->helper->route('posey_ultimateblog_blog', ['action' => 'delete', 'blog_id' => (int) $blog['blog_id']]),
			'U_BLOG_EDIT'		=> $this->helper->route('posey_ultimateblog_blog', ['action' => 'edit', 'blog_id' => (int) $blog['blog_id']]),
			'U_BLOG_RATE'		=> $this->helper->route('posey_ultimateblog_blog', ['action' => 'rate', 'blog_id' => (int) $blog['blog_id']]),
		]);

		// Grab comments for this blog
		$sql_array = [
			'SELECT'	=> 'c.*, u.user_id, u.username, u.user_colour, u.user_avatar, u.user_avatar_type, u.user_avatar_height, u.user_avatar_width',

			'FROM'		=> [$this->ub_comments_table => 'c'],

			'LEFT_JOIN' => [
				[
					'FROM'	=> [USERS_TABLE => 'u'],
					'ON'	=> 'c.poster_id = u.user_id',
				]
			],

			'WHERE'		=> 'c.blog_id = ' . (int) $blog_id,

			'ORDER_BY'	=> 'c.post_time ASC',
		];

		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query_limit($sql, $this->config['posts_per_page'], $start);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('comments', [
				'ID'		=> $row['comment_id'],
				'TEXT'		=> generate_text_for_display($row['comment_text'], $row['bbcode_uid'], $row['bbcode_bitfield'], $row['bbcode_options']),
				'POST_TIME'	=> $this->user->format_date($row['post_time']),
				'POSTER'	=> get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
				'AVATAR'	=> phpbb_get_user_avatar($row),

				'U_COMMENT_DELETE'	=> $this->helper->route('posey_ultimateblog_comment', ['blog_id' => (int) $blog_id, 'comment_id' => (int) $row['comment_id'], 'action' => 'delete']),
				'U_COMMENT_EDIT'	=> $this->helper->route('posey_ultimateblog_comment', ['blog_id' => (int) $blog_id, 'comment_id' => (int) $row['comment_id'], 'action' => 'edit']),
			]);
		}
		$this->db->sql_freeresult($result);

		add_form_key('submit_comment');

		// Add a comment
		if ($this->request->is_set_post('submit_comment'))
		{
			if (!$this->auth->acl_get('u_blog_comment_make'))
			{
				// Not authorised to comment (permissions)
				trigger_error($this->user->lang['AUTH_BLOG_COMMENT_ADD'] . '<br><br><a href="' . $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $blog_id]) . '">&laquo; ' . $this->user->lang['BACK_TO_PREV'] . '</a>');
			}
			else if ($blog['enable_comments'] == 0)
			{
				// Comments have been disabled
				trigger_error($this->user->lang['BLOG_COMMENTS'] . ' ' . $this->user->lang['BLOG_COMMENTS_DISABLED'] . '<br><br><a href="' . $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $blog_id]) . '">&laquo; ' . $this->user->lang['BACK_TO_PREV'] . '</a>');
			}
			else if ($this->request->variable('comment_text', '', true) == '')
			{
				// Comment is empty
				trigger_error($this->user->lang['BLOG_COMMENT_EMPTY'] . ' ' . $this->user->lang['BLOG_COMMENTS_DISABLED'] . '<br><br><a href="' . $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $blog_id]) . '">&laquo; ' . $this->user->lang['BACK_TO_PREV'] . '</a>');
			}
			else if (!check_form_key('submit_comment'))
			{
				// Invalid form key
				trigger_error($this->user->lang['FORM_INVALID'] . '<br><br><a href="' . $this->helper->route('posey_ultimateblog_blog', ['action' => 'add']) . '">&laquo; ' . $this->user->lang['BACK_TO_PREV'] . '</a>');
			}
			else
			{
				$comment_text = $this->request->variable('comment_text', '', true);
				$uid = $bitfield = $options = '';
				$allow_bbcode = $this->config['allow_bbcode'];
				$allow_smilies = $this->config['allow_smilies'];
				$allow_urls = $this->config['allow_post_links'];
				generate_text_for_storage($comment_text, $uid, $bitfield, $options, $allow_bbcode, $allow_urls, $allow_smilies);

				$sql_ary = [
					'comment_text'		=> $comment_text,
					'blog_id'			=> $blog_id,
					'poster_id'			=> $this->user->data['user_id'],
					'post_time'			=> time(),
					'bbcode_uid'		=> $uid,
					'bbcode_bitfield'	=> $bitfield,
					'bbcode_options'	=> $options,
				];

				// Insert the comment
				$sql = 'INSERT INTO ' . $this->ub_comments_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
				$this->db->sql_query($sql);
				$comment_id = $this->db->sql_nextid();

				// Success! Redirect to the comment
				redirect($this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $blog_id]) . '#c' . $comment_id);
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
				'FORUM_NAME'		=> $blog['blog_subject'],
			]
		];

		foreach($navlinks_array as $name)
		{
			$this->template->assign_block_vars('navlinks', [
				'FORUM_NAME'	=> $name['FORUM_NAME'],
				'U_VIEW_FORUM'	=> $name['U_VIEW_FORUM'],
			]);
		}

		// Count comments
		$sql = 'SELECT *
				FROM ' . $this->ub_comments_table . '
				WHERE blog_id = ' . (int) $blog_id . '
				ORDER BY comment_id ASC';
		$result_total = $this->db->sql_query($sql);
		$row_total = $this->db->sql_fetchrowset($result_total);
		$total_comment_count = (int) sizeof($row_total);
		$this->db->sql_freeresult($result_total);

		//Start pagination
		$this->pagination->generate_template_pagination($this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $blog_id]), 'pagination', 'start', $total_comment_count, $this->config['posts_per_page'], $start);

		$this->template->assign_var('TOTAL_BLOG_COMMENTS', $this->user->lang('BLOG_COMMENTS_COUNT', (int) $total_comment_count));

		// Generate the page title
		page_header($blog['blog_subject']);
	}

	function rate($blog_id)
	{
		$rating = $this->has_rated($blog_id);

		if ($rating)
		{
			// User has already rated this blog
			trigger_error($this->user->lang('BLOG_RATED_ALREADY', $rating) . '<br><br><a href="' . $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $blog_id]) . '">&laquo; ' . $this->user->lang['BACK_TO_PREV'] . '</a>');
		}
		else
		{
			// Insert Rating
			$data = [
				'blog_id'	=> (int) $blog_id,
				'user_id'	=> (int) $this->user->data['user_id'],
				'rating'	=> (int) $this->request->variable('blog_rating', 0),
				'rate_time'	=> time(),
			];

			$sql = 'INSERT INTO ' . $this->ub_rating_table . ' ' . $this->db->sql_build_array('INSERT', $data);
			$this->db->sql_query($sql);

			// Trigger 'thank you for voting' message
			trigger_error($this->user->lang('BLOG_RATED', $this->request->variable('blog_rating', 0)) . '<br><br><a href="' . $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $blog_id]) . '">&laquo; ' . $this->user->lang['BACK_TO_PREV'] . '</a>');
		}
	}

	function has_rated($blog_id)
	{
		// Check to see if the user has already rated, if so; grab the rating
		$sql = 'SELECT rating
				FROM ' . $this->ub_rating_table . '
				WHERE blog_id = ' . (int) $blog_id . '
					AND user_id = ' . (int) $this->user->data['user_id'];
		$result = $this->db->sql_query($sql);
		$rating = $this->db->sql_fetchfield('rating');
		$this->db->sql_freeresult($result);

		return $rating ? $rating : false;
	}
}
