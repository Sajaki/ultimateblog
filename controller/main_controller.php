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
				$this->add_blog();
				// Generate the page template
				return $this->helper->render('blog_add.html', $this->user->lang('UB_BLOG_ADD'));
			break;

			case 'edit':
				$this-> ($blog_id);
				// Generate the page template
//EDIT CHANGE				return $this->helper->render('blogs_latest.html', $this->user->lang('UB_BLOGS'));
			break;

			default:
				$this->latest_blogs();
				// Generate the page template
				return $this->helper->render('blogs_latest.html', $this->user->lang('UB_BLOGS'));
			break;
		}
	}

	public function latest_blogs()
	{
		// Get latest blogs
		$sql_array = array(
			'SELECT'	=> 'b.*, u.user_id, u.username, u.user_colour',

			'FROM'		=> array(
				$this->ub_blogs_table => 'b',
			),

			'LEFT_JOIN' => array(
				array(
					'FROM'	=> array(USERS_TABLE => 'u'),
					'ON'	=> 'b.poster_id = u.user_id',
				)
			),

			'ORDER_BY'	=> 'b.post_time DESC',
		);

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
				$text = (strlen($text) > $this->config['ub_cutoff']) ? substr($text, 0, $this->config['ub_cutoff']) . ' ... ' . $this->user->lang['BLOG_READ_FULL'] : $text;
			}

			$this->template->assign_block_vars('blogs', array(
				'BLOG_ID'	=> $row['blog_id'],
				'CAT'		=> $cat_name,
				'CAT_ID'	=> $row['cat_id'],
				'SUBJECT'	=> $row['blog_subject'],
				'TEXT'		=> $text,
				'POSTER'	=> get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
				'POST_TIME'	=> $this->user->format_date($row['post_time'], 'F jS, Y'),

				'U_BLOG'	=> $this->helper->route('posey_ultimateblog_display_blog', array('blog_id' => (int) $row['blog_id'])),
				'U_CAT'		=> $this->helper->route('posey_ultimateblog_category', array('cat_id' => (int) $row['cat_id'])),
			));
		}
		$this->db->sql_freeresult($result);

		// Get categories
		$sql = 'SELECT cat_id, cat_name
				FROM ' . $this->ub_cats_table . '
				ORDER BY cat_name ASC';
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('cats', array(
				'NAME'	=> $row['cat_name'],
				'LINK'	=> $this->helper->route('posey_ultimateblog_category', array('cat_id' => (int) $row['cat_id'])),
			));
		}
		$this->db->sql_freeresult($result);

		// Get Archive
			/* ... STILL TO COME ... */

		$this->template->assign_vars(array(
			'U_UB_BLOG_ADD'		=> $this->helper->route('posey_ultimateblog_blog', array('action' => 'add')),
		));

		// Assign breadcrumb template vars
		$this->template->assign_block_vars('navlinks', array(
			'U_VIEW_FORUM'		=> $this->helper->route('posey_ultimateblog_blog'),
			'FORUM_NAME'		=> $this->user->lang('UB_BLOG'),
		));

	}

	public function add_blog()
	{
		$sql = 'SELECT cat_id, cat_name
				FROM ' . $this->ub_cats_table;
		$result = $this->db->sql_query($sql);

		$categories = '<option selected="selected" disabled="disabled" >' . $this->user->lang['CHOOSE_CATEGORY'] . '</option>';

		while ($row = $this->db->sql_fetchrow($result))
		{
			$categories .= '<option value="' . $row['cat_id'] . '">' . $row['cat_name'] . '</option>';
		}
		$this->db->sql_freeresult($result);

		add_form_key('add_blog');

		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key('add_blog'))
			{
				// Invalid form key
				trigger_error($this->user->lang['FORM_INVALID'] . '<br /><br /><a href="' . $this->helper->route('posey_ultimateblog_blog', array('action' => 'add')) . '">&laquo; ' . $this->user->lang['BACK_TO_PREV'] . '</a>');
			}
			else
			{
				$text = utf8_normalize_nfc($this->request->variable('message', '', true));
				$uid = $bitfield = $options = ''; // will be modified by generate_text_for_storage
				$allow_bbcode = $this->request->variable('blog_bbcode', 0) == 1 ? true : false;
				$allow_smilies = $this->request->variable('blog_smilies', 0) == 1 ? true : false;
				$allow_urls = $this->request->variable('blog_urls', 0) == 1 ? true : false;
				generate_text_for_storage($text, $uid, $bitfield, $options, $allow_bbcode, $allow_smilies, $allow_urls);

				$blog_row = array(
					'cat_id'				=> (int) $this->request->variable('category', 1),
					'blog_subject'			=> ucfirst(utf8_normalize_nfc($this->request->variable('subject', '', true))),
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
				);

				// Insert the blog
				$sql = 'INSERT INTO ' . $this->ub_blogs_table . ' ' . $this->db->sql_build_array('INSERT', $blog_row);
				$this->db->sql_query($sql);
				$blog_id = (int) $this->db->sql_nextid();

				// Add it to the log
				$this->log->add('user', $this->user->data['user_id'], $this->user->ip, 'LOG_UB_BLOG_ADDED');

				// Send success message
				trigger_error($this->user->lang['BLOG_ADDED'] . '<br /><br /><a href="' . $this->helper->route('posey_ultimateblog_display_blog', array('blog_id' => $blog_id)) . '">' . $this->user->lang['VIEW_BLOG'] . ' &raquo;</a>');
			}
		}

		$this->template->assign_vars(array(
			'CATEGORIES'		=> $categories,
			'S_BLOG_BBCODE'		=> true,
			'S_BLOG_SMILIES'	=> true,
			'S_BLOG_URLS'		=> true,
			'S_EDIT_LOCKED'		=> false,
			's_ENABLE_COMMENTS'	=> true,
		));
	}

	public function display_blog($blog_id)
	{
		// Get blog and poster info
		$sql_array = array(
			'SELECT'	=> 'b.*, u.user_id, u.username, u.user_colour, u.user_avatar, u.user_avatar_type, u.user_avatar_height, u.user_avatar_width',

			'FROM'		=> array(
				$this->ub_blogs_table => 'b',
			),

			'LEFT_JOIN' => array(
				array(
					'FROM'	=> array(USERS_TABLE => 'u'),
					'ON'	=> 'b.poster_id = u.user_id',
				)
			),

			'WHERE'		=> 'b.blog_id = ' . (int) $blog_id,

			'ORDER_BY'	=> 'b.post_time DESC',
		);

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

			$this->template->assign_block_vars('cats', array(
				'NAME'	=> $row['cat_name'],
				'LINK'	=> $this->helper->route('posey_ultimateblog_category', array('cat_id' => (int) $row['cat_id'])),
			));
		}
		$this->db->sql_freeresult($result);

		$this->template->assign_vars(array(
			'BLOG_ID'		=> $blog['blog_id'],
			'BLOG_SUBJECT'	=> $blog['blog_subject'],
			'BLOG_TEXT'		=> generate_text_for_display($blog['blog_text'], $blog['bbcode_uid'], $blog['bbcode_bitfield'], $bbcode_options),
			'BLOG_POSTER'	=> get_username_string('full', $blog['user_id'], $blog['username'], $blog['user_colour']),
			'BLOG_POST_TIME'	=> $this->user->format_date($blog['post_time'], 'F jS, Y'),
			'BLOG_AVATAR'	=> phpbb_get_user_avatar($blog),

			'EDIT_TIME'		=> $blog['blog_edit_time'],
			'EDIT_USER' 	=> get_username_string('full', $edit_user['user_id'], $edit_user['username'], $edit_user['user_colour']),
			'EDIT_REASON'	=> $blog['edit_reason'],
			'EDIT_COUNT'	=> $blog['edit_count'],

			'CATEGORIES'	=> $categories,
			'CAT_NAME'		=> $cat_name,
			'CAT_LINK'		=> $this->helper->route('posey_ultimateblog_category', array('cat_id' => (int) $blog['cat_id'])),

			'S_EDIT_LOCKED'		=> $blog['edit_locked'] == 1 ? true : false,
			'S_ENABLE_COMMENTS'	=> $blog['enable_comments'] == 1 ? true : false,
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
		$navlinks_array = array(
			array(
				'U_VIEW_FORUM'		=> $this->helper->route('posey_ultimateblog_blog'),
				'FORUM_NAME'		=> $this->user->lang('UB_BLOG'),
			),
			array(
				'U_VIEW_FORUM'		=> $this->helper->route('posey_ultimateblog_display_blog', array('blog_id' => (int) $blog['blog_id'])),
				'FORUM_NAME'		=> $blog['blog_subject'],
			)
		);

		foreach($navlinks_array as $name)
		{
			$this->template->assign_block_vars('navlinks', array(
				'FORUM_NAME'	=> $name['FORUM_NAME'],
				'U_VIEW_FORUM'	=> $name['U_VIEW_FORUM'],
			));
		}

		// Generate the page template
		return $this->helper->render('blog.html', $blog['blog_subject']);
	}

	public function categories()
	{
		$sql_array = array(
			'SELECT'	=> 'c.*, COUNT(b.cat_id) as blog_count',

			'FROM'		=> array(
				$this->ub_cats_table => 'c',
			),

			'LEFT_JOIN' => array(
				array(
					'FROM'	=> array($this->ub_blogs_table => 'b'),
					'ON'	=> 'c.cat_id = b.cat_id',
				)
			),

			'GROUP_BY'	=> 'c.cat_id',

			'ORDER_BY'	=> 'c.cat_id ASC',
		);
		$sql = $this->db->sql_build_query('SELECT_DISTINCT', $sql_array);
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$bbcode_options =	(($row['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) +
								(($row['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) +
								(($row['enable_magic_url']) ? OPTION_FLAG_LINKS : 0);

			$this->template->assign_block_vars('categories', array(
				'LINK'	=> $this->helper->route('posey_ultimateblog_category', array('cat_id' => (int) $row['cat_id'])),
				'NAME'	=> $row['cat_name'],
				'DESC'	=> generate_text_for_display($row['cat_desc'], $row['bbcode_uid'], $row['bbcode_bitfield'], $bbcode_options),
				'COUNT'	=> $row['blog_count'],
			));
		}
		$this->db->sql_freeresult($result);

		// Assign breadcrumb template vars
		$navlinks_array = array(
			array(
				'U_VIEW_FORUM'		=> $this->helper->route('posey_ultimateblog_blog'),
				'FORUM_NAME'		=> $this->user->lang('UB_BLOG'),
			),
			array(
				'U_VIEW_FORUM'		=> $this->helper->route('posey_ultimateblog_categories'),
				'FORUM_NAME'		=> $this->user->lang('UB_CATEGORIES'),
			)
		);

		foreach($navlinks_array as $name)
		{
			$this->template->assign_block_vars('navlinks', array(
				'FORUM_NAME'	=> $name['FORUM_NAME'],
				'U_VIEW_FORUM'	=> $name['U_VIEW_FORUM'],
			));
		}

		// Send it to the template
		return $this->helper->render('categories.html', $this->user->lang('UB_CATEGORIES'));
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
		$sql_array = array(
			'SELECT'	=> 'b.*, u.user_id, u.username, u.user_colour',

			'FROM'		=> array(
				$this->ub_blogs_table => 'b',
			),

			'LEFT_JOIN' => array(
				array(
					'FROM'	=> array(USERS_TABLE => 'u'),
					'ON'	=> 'b.poster_id = u.user_id',
				)
			),

			'WHERE'		=> 'b.cat_id = ' . (int) $cat_id,

			'ORDER_BY'	=> 'b.post_time DESC',
		);

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
				$text = (strlen($text) > $this->config['ub_cutoff']) ? substr($text, 0, $this->config['ub_cutoff']) . ' ... ' . $this->user->lang['BLOG_READ_FULL'] : $text;
			}

			$this->template->assign_block_vars('blogs', array(
				'SUBJECT'	=> $row['blog_subject'],
				'TEXT'		=> $text,
				'POSTER'	=> get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
				'POST_TIME'	=> $this->user->format_date($row['post_time'], 'F jS, Y'),

				'U_BLOG'	=> $this->helper->route('posey_ultimateblog_display_blog', array('blog_id' => $row['blog_id'])),
				'U_CAT'		=> $this->helper->route('posey_ultimateblog_category', array('cat_id' => (int) $cat_id)),
			));
		}
		$this->db->sql_freeresult($result);

		$this->template->assign_vars(array(
			'CAT_NAME'			=> $cat_name,
			'U_UB_BLOG_ADD'		=> $this->helper->route('posey_ultimateblog_blog', array('action' => 'add')),
		));

		// Assign breadcrumb template vars
		$navlinks_array = array(
			array(
				'U_VIEW_FORUM'		=> $this->helper->route('posey_ultimateblog_blog'),
				'FORUM_NAME'		=> $this->user->lang('UB_BLOG'),
			),
			array(
				'U_VIEW_FORUM'		=> $this->helper->route('posey_ultimateblog_categories'),
				'FORUM_NAME'		=> $this->user->lang('UB_CATEGORIES'),
			)
		);

		foreach($navlinks_array as $name)
		{
			$this->template->assign_block_vars('navlinks', array(
				'FORUM_NAME'	=> $name['FORUM_NAME'],
				'U_VIEW_FORUM'	=> $name['U_VIEW_FORUM'],
			));
		}

		// Generate the page template
		return $this->helper->render('category.html', $cat_name);
	}
}