<?php
/**
*
* @package phpBB Extension - Ultimate Blog
* @copyright (c) 2015 posey
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace posey\ultimateblog\controller;

class main_controller
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
		$this->ub_comments_table = $ub_comments_table;
	}

	public function blog()
	{
		$action = $this->request->variable('action', '');
		$blog_id = (int) $this->request->variable('blog_id', 0);

		switch($action)
		{
 			case 'add':
 				$this->blog_add();
				// Generate the page template
				return $this->helper->render('blog_add.html', $this->user->lang('BLOG_ADD'));
 			break;

 			case 'edit':
				$this->blog_edit($blog_id);
				// Generate the page template
				return $this->helper->render('blog_add.html', $this->user->lang('BLOG_EDIT'));
 			break;

 			default:
 				$this->latest_blogs();
				// Generate the page template
				return $this->helper->render('blogs_latest.html', $this->user->lang('BLOG'));
 			break;
		}
	}

	public function latest_blogs()
	{
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
		$result = $this->db->sql_query_limit($sql, $this->config['ub_latest_blogs']);

		while ($row = $this->db->sql_fetchrow($result))
		{
			// Grab category name
			$sql_cat = 'SELECT cat_name
						FROM ' . $this->ub_cats_table . '
						WHERE cat_id = ' . (int) $row['cat_id'];
			$result_cat = $this->db->sql_query($sql_cat);
			$cat_name = $this->db->sql_fetchfield('cat_name');
			$this->db->sql_freeresult($result_cat);

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
				'BLOG_ID'	=> $row['blog_id'],
				'CAT'		=> $cat_name,
				'CAT_ID'	=> $row['cat_id'],
				'SUBJECT'	=> $row['blog_subject'],
				'TEXT'		=> $text,
				'POSTER'	=> get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
				'POST_TIME'	=> $this->user->format_date($row['post_time'], 'F jS, Y'),

				'U_BLOG'		=> $this->helper->route('posey_ultimateblog_display_blog', ['blog_id' => (int) $row['blog_id']]),
				'U_CAT'			=> $this->helper->route('posey_ultimateblog_category', ['cat_id' => (int) $row['cat_id']]),
			]);
		}
		$this->db->sql_freeresult($result);

		// Get categories
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

		// Get Archive
			/* ... STILL TO COME ... */

		$this->template->assign_vars([
			'U_BLOG_ADD'		=> $this->helper->route('posey_ultimateblog_blog', ['action' => 'add']),
		]);

		// Assign breadcrumb template vars
		$this->template->assign_block_vars('navlinks', [
			'U_VIEW_FORUM'		=> $this->helper->route('posey_ultimateblog_blog'),
			'FORUM_NAME'		=> $this->user->lang('BLOG'),
		]);
	}

	public function blog_add()
	{
		add_form_key('add_blog');

		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key('add_blog'))
			{
				// Invalid form key
				trigger_error($this->user->lang['FORM_INVALID'] . '<br><br><a href="' . $this->helper->route('posey_ultimateblog_blog', ['action' => 'add']) . '">&laquo; ' . $this->user->lang['BACK_TO_PREV'] . '</a>');
			}
			else
			{
				// Generate text for storage
				$text = utf8_normalize_nfc($this->request->variable('message', '', true));
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
					'enable_bbcode'	 	=> $allow_bbcode ? 1 : 0,
					'enable_smilies'		=> $allow_smilies ? 1 : 0,
					'enable_magic_url'		=> $allow_urls ? 1 : 0,
					'bbcode_uid'			=> $uid,
					'bbcode_bitfield'		=> $bitfield,
					'blog_edit_locked'		=> $this->request->variable('edit_locked', 0),
					'enable_comments'		=> $this->request->variable('enable_comments', 0),
				];

				// Insert the blog
				$sql = 'INSERT INTO ' . $this->ub_blogs_table . ' ' . $this->db->sql_build_array('INSERT', $blog_row);
				$this->db->sql_query($sql);
				$blog_id = (int) $this->db->sql_nextid();

				// Add it to the log
				$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_BLOG_ADDED', false, array($blow_row['blog_subject']));

				// Send success message
				trigger_error($this->user->lang['BLOG_ADDED'] . '<br><br><a href="' . $this->helper->route('posey_ultimateblog_display_blog', ['blog_id' => $blog_id]) . '">' . $this->user->lang['BLOG_VIEW'] . ' &raquo;</a>');
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

		// Grab all categories
		$sql = 'SELECT cat_id, cat_name
				FROM ' . $this->ub_cats_table;
		$result = $this->db->sql_query($sql);

		$categories = '<option selected="selected" disabled="disabled" >' . $this->user->lang['BLOG_CHOOSE_CAT'] . '</option>';

		while ($row = $this->db->sql_fetchrow($result))
		{
			$categories .= '<option value="' . $row['cat_id'] . '">' . $row['cat_name'] . '</option>';
		}
		$this->db->sql_freeresult($result);

		$blog_preview = '';
		if ($this->request->is_set_post('preview'))
		{
				$blog_text = utf8_normalize_nfc($this->request->variable('message', '', true));
				$uid = $bitfield = $options = ''; // will be modified by generate_text_for_storage
				$allow_bbcode = $this->request->variable('blog_bbcode', 0) == 1 ? true : false;
				$allow_smilies = $this->request->variable('blog_smilies', 0) == 1 ? true : false;
				$allow_urls = $this->request->variable('blog_urls', 0) == 1 ? true : false;
				generate_text_for_storage($blog_text, $uid, $bitfield, $options, $allow_bbcode, $allow_smilies, $allow_urls);
				$blog_preview = generate_text_for_display($blog_text, $uid, $bitfield, $options);
		}

		// Assign template vars
		$this->template->assign_vars(array(
			'CATEGORIES'			=> $categories,
			'BLOG_PREVIEW'			=> $blog_preview,

			'S_FORM_ENCTYPE'		=> $form_enctype,
			'S_HIDDEN_FIELDS'		=> $s_hidden_fields,
			'S_BBCODE_STATUS'		=> $bbcode_status,
			'S_BBCODE_ALLOWED'		=> ($bbcode_status) ? $this->user->lang('BBCODE_IS_ON', "<a href=\"{$this->phpbb_root_path}faq.{$this->php_ext}?mode=bbcode\">", '</a>') : $this->user->lang('BBCODE_IS_OFF', "<a href=\"{$this->phpbb_root_path}faq.{$this->php_ext}?mode=bbcode\">", '</a>'),
			'S_SMILIES_STATUS'		=> $smilies_status,
			'S_SMILIES_ALLOWED'		=> $smilies_status ? $this->user->lang['SMILIES_ARE_ON'] : $this->user->lang['SMILIES_ARE_OFF'],
			'S_BBCODE_IMG'			=> $img_status ? $this->user->lang['IMAGES_ARE_ON'] : $this->user->lang['IMAGES_ARE_OFF'],
			'S_BBCODE_FLASH'		=> $flash_status ? $this->user->lang['FLASH_IS_ON'] : $this->user->lang['FLASH_IS_OFF'],
			'S_BBCODE_URL'			=> $url_status ? $this->user->lang['URL_IS_ON'] : $this->user->lang['URL_IS_OFF'],
			'S_BLOG_BBCODE'			=> true,
			'S_BLOG_SMILIES'		=> true,
			'S_BLOG_URLS'			=> true,
			'S_EDIT_LOCKED'			=> false,
			'S_ENABLE_COMMENTS'		=> true,
		));
	}

	public function blog_edit($blog_id)
	{
		add_form_key('edit_blog');

		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key('edit_blog'))
			{
				// Invalid form key
				trigger_error($this->user->lang['FORM_INVALID'] . '<br><br><a href="' . $this->helper->route('posey_ultimateblog_blog', ['action' => 'edit']) . '">&laquo; ' . $this->user->lang['BACK_TO_PREV'] . '</a>');
			}
			else
			{
				// Generate text for storage
				$text = utf8_normalize_nfc($this->request->variable('message', '', true));
				$uid = $bitfield = $options = ''; // will be modified by generate_text_for_storage
				$allow_bbcode = $this->request->variable('blog_bbcode', 0) == 1 ? true : false;
				$allow_smilies = $this->request->variable('blog_smilies', 0) == 1 ? true : false;
				$allow_urls = $this->request->variable('blog_urls', 0) == 1 ? true : false;
				generate_text_for_storage($text, $uid, $bitfield, $options, $allow_bbcode, $allow_smilies, $allow_urls);

				$blog_row = [
					'cat_id'				=> (int) $this->request->variable('category', 1),
					'blog_subject'			=> ucfirst(utf8_normalize_nfc($this->request->variable('subject', '', true))),
					'blog_text'				=> $text,
					'enable_bbcode'	 	=> $allow_bbcode ? 1 : 0,
					'enable_smilies'		=> $allow_smilies ? 1 : 0,
					'enable_magic_url'		=> $allow_urls ? 1 : 0,
					'bbcode_uid'			=> $uid,
					'bbcode_bitfield'		=> $bitfield,
					'blog_edit_time'		=> time(),
					'blog_edit_user'		=> $this->user->data['user_id'],
					'blog_edit_reason'		=> $this->request->variable('edit_reason', ''),
					'blog_edit_count'		=> 'blog_edit_count' + 1,
					'blog_edit_locked'		=> $this->request->variable('edit_locked', 0),
					'enable_comments'		=> $this->request->variable('enable_comments', 0),
				];

				// Update the blog
				$sql = 'UPDATE ' . $this->ub_blogs_table . ' SET ' . $this->db->sql_build_array('UPDATE', $blog_row);
				$this->db->sql_query($sql);

				// Add it to the log
				$this->log->add('user', $this->user->data['user_id'], $this->user->ip, 'LOG_BLOG_EDITED', $blow_row['blog_subject']);

				// Send success message
				trigger_error($this->user->lang['BLOG_EDITED'] . '<br><br><a href="' . $this->helper->route('posey_ultimateblog_display_blog', ['blog_id' => (int) $blog_id]) . '">' . $this->user->lang['BLOG_VIEW'] . ' &raquo;</a>');
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

		// Grab blog info
		$sql = 'SELECT *
				FROM ' . $this->ub_blogs_table . '
				WHERE blog_id = ' . (int) $blog_id;
		$result = $this->db->sql_query($sql);
		$blog = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		// Check if blog exists
		if ($blog == '')
		{
			trigger_error($this->user->lang['BLOG_NOT_EXIST'] . '<br><br><a href="' . $this->helper->route('posey_ultimateblog_blog') . '">&laquo; ' . $this->user->lang['BLOG_BACK'] . '</a>');
		}

		// Blog editing has been locked
		if ($blog['blog_edit_locked'] == 1)
		{
			trigger_error($this->user->lang['BLOG_EDIT_LOCKED'] . '<br><br><a href="' . $this->helper->route('posey_ultimateblog_display_blog', ['blog_id' => (int) $blog_id]) . '">&laquo; ' . $this->user->lang['BACK_TO_PREV'] . '</a>');
		}

		// Grab all categories
		$sql = 'SELECT cat_id, cat_name
			FROM ' . $this->ub_cats_table;
		$result = $this->db->sql_query($sql);

		$categories = '<option disabled="disabled" >' . $this->user->lang['BLOG_CHOOSE_CAT'] . '</option>';

		while ($row = $this->db->sql_fetchrow($result))
		{
			$selected = $row['cat_id'] == $blog['cat_id'] ? ' selected="selected"' : '';
			$categories .= '<option value="' . $row['cat_id'] . '"' . $selected . '>' . $row['cat_name'] . '</option>';
		}
		$this->db->sql_freeresult($result);

		$blog_preview = '';
		if ($this->request->is_set_post('preview'))
		{
				$blog_text = utf8_normalize_nfc($this->request->variable('message', '', true));
				$uid = $bitfield = $options = ''; // will be modified by generate_text_for_storage
				$allow_bbcode = $this->request->variable('blog_bbcode', 0) == 1 ? true : false;
				$allow_smilies = $this->request->variable('blog_smilies', 0) == 1 ? true : false;
				$allow_urls = $this->request->variable('blog_urls', 0) == 1 ? true : false;
				generate_text_for_storage($blog_text, $uid, $bitfield, $options, $allow_bbcode, $allow_smilies, $allow_urls);
				$blog_preview = generate_text_for_display($blog_text, $uid, $bitfield, $options);
		}

		// Generate text for editing
		decode_message($blog['blog_text'], $blog['bbcode_uid']);

		// Assign it to the template
		$this->template->assign_vars([
			'BLOG_PREVIEW'		=> $blog_preview,
			'CATEGORIES'		=> $categories,
			'MESSAGE'			=> $blog['blog_text'],
			'SUBJECT'			=> $blog['blog_subject'],

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

	public function display_blog($blog_id)
	{
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

		// Get categories
		$sql = 'SELECT cat_id, cat_name
				FROM ' . $this->ub_cats_table . '
				ORDER BY cat_name ASC';
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			// Get category name of current blog
			if ($row['cat_id'] == $blog['cat_id'])
			{
				$cat_name = $row['cat_name'];
			}

			$this->template->assign_block_vars('cats', [
				'NAME'	=> $row['cat_name'],
				'LINK'	=> $this->helper->route('posey_ultimateblog_category', ['cat_id' => (int) $row['cat_id']]),
			]);
		}
		$this->db->sql_freeresult($result);

		$this->template->assign_vars(array(
			'BLOG_ID'			=> $blog['blog_id'],
			'BLOG_SUBJECT'		=> $blog['blog_subject'],
			'BLOG_TEXT'			=> generate_text_for_display($blog['blog_text'], $blog['bbcode_uid'], $blog['bbcode_bitfield'], $bbcode_options),
			'BLOG_POSTER'		=> get_username_string('full', $blog['user_id'], $blog['username'], $blog['user_colour']),
			'BLOG_POST_TIME'	=> $this->user->format_date($blog['post_time'], 'F jS, Y'),
			'BLOG_AVATAR'		=> phpbb_get_user_avatar($blog),

			'EDIT_LAST'		=> $this->user->lang('BLOG_EDIT_LAST', $edit_username, $this->user->format_date($blog['blog_edit_time'], 'F jS, Y')),
			'EDIT_REASON'	=> $blog['blog_edit_reason'],
			'EDIT_COUNT'	=> $this->user->lang('BLOG_EDIT_COUNT', (int) $blog['blog_edit_count']),

			'CATEGORIES'	=> $categories,
			'CAT_NAME'		=> $cat_name,
			'CAT_LINK'		=> $this->helper->route('posey_ultimateblog_category', ['cat_id' => (int) $blog['cat_id']]),

			'S_EDITED'			=> $blog['blog_edit_count'] > 0 ? true : false,
			'S_EDIT_LOCKED'		=> $blog['edit_locked'] == 1 ? true : false,
			'S_ENABLE_COMMENTS'	=> $blog['enable_comments'] == 1 ? true : false,

			'U_BLOG_EDIT'		=> $this->helper->route('posey_ultimateblog_blog', ['action' => 'edit', 'blog_id' => (int) $blog['blog_id']]),
		));
/*
		// Grab comments for this blog
		$sql_array = array(
			'SELECT'	=> 'c.*, u.user_id, u.username, u.user_colour, u.user_avatar, u.user_avatar_type, u.user_avatar_height, u.user_avatar_width',

			'FROM'		=> array(
				$this->ub_comments_table => 'c',
			),

			'LEFT_JOIN' => array(
				array(
					'FROM'	=> array(USERS_TABLE => 'u'),
					'ON'	=> 'c.poster_id = u.user_id',
				)
			),

			'WHERE'		=> 'c.blog_id = ' . (int) $blog_id,

			'ORDER_BY'	=> 'c.post_time ASC',
		);
		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			// Check BBCode Options
			$bbcode_options =	(($row['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) +
								(($row['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) +
								(($row['enable_magic_url']) ? OPTION_FLAG_LINKS : 0);

			$this->template->assign_block_vars('comments', array(
				'ID'		=> $row['comment_id'],
				'SUBJECT'	=> $row['comment_subject'],
				'TEXT'		=> generate_text_for_display($row['comment_text'], $row['bbcode_uid'], $row['bbcode_bitfield'], $bbcode_options),
				'POSTER'	=> get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
				'AVATAR'	=> phpbb_get_user_avatar($row),
			));
		}
		$this->db->sql_freeresult($result); */

		// Assign breadcrumb template vars
		$navlinks_array = [
			[
				'U_VIEW_FORUM'		=> $this->helper->route('posey_ultimateblog_blog'),
				'FORUM_NAME'		=> $this->user->lang('BLOG'),
			],
			[
				'U_VIEW_FORUM'		=> $this->helper->route('posey_ultimateblog_display_blog', ['blog_id' => (int) $blog['blog_id']]),
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

		// Generate the page template
		return $this->helper->render('blog.html', $blog['blog_subject']);
	}

	public function categories()
	{
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
		$sql = $this->db->sql_build_query('SELECT_DISTINCT', $sql_array);
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

		// Send it to the template
		return $this->helper->render('categories.html', $this->user->lang('BLOG_CATS'));
	}

	public function category($cat_id)
	{
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

			'LEFT_JOIN' => array(
				array(
					'FROM'	=> [USERS_TABLE => 'u'],
					'ON'	=> 'b.poster_id = u.user_id',
				)
			),

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
				$text = (strlen($text) > $this->config['ub_cutoff']) ? substr($text, 0, $this->config['ub_cutoff']) . ' ... <a href="' . $this->helper->route('posey_ultimateblog_blog', array('blog_id' => (int) $row['blog_id'])) . ' alt="" title="' . $this->user->lang['BLOG_READ_FULL'] . '"><em>' . $this->user->lang['BLOG_READ_FULL'] . '</em></a>' : $text;
			}

			$this->template->assign_block_vars('blogs', [
				'SUBJECT'	=> $row['blog_subject'],
				'TEXT'		=> $text,
				'POSTER'	=> get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
				'POST_TIME'	=> $this->user->format_date($row['post_time'], 'F jS, Y'),

				'U_BLOG'		=> $this->helper->route('posey_ultimateblog_display_blog', ['blog_id' => $row['blog_id']]),
				'U_BLOG_EDIT'	=> $this->helper->route('posey_ultimateblog_blog', ['action' => 'edit', 'blog_id' => (int) $row['blog_id']]),
				'U_CAT'			=> $this->helper->route('posey_ultimateblog_category', ['cat_id' => (int) $cat_id]),
			]);
		}
		$this->db->sql_freeresult($result);

		$this->template->assign_vars(array(
			'CAT_NAME'			=> $cat_name,
			'U_BLOG_ADD'		=> $this->helper->route('posey_ultimateblog_blog', ['action' => 'add']),
		));

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

		// Generate the page template
		return $this->helper->render('category.html', $cat_name);
	}
}