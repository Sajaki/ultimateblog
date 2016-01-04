<?php
/**
*
* @package phpBB Extension - Ultimate Blog
* @copyright (c) 2015 posey
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace posey\ultimateblog\core;

class search
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

	/**
	* Constructor
	*
	* @param \phpbb\template\template			$template			Template object
	* @param \phpbb\db\driver\driver_interface	$db					Database object
	* @param \phpbb\controller\helper			$helper				Controller helper object
	* @param \phpbb\user						$user				User object
	* @param \phpbb\config\config				$config				Config object
	* @param \phpbb\auth\auth					$auth				Auth object
	* @param \phpbb\log\log						$log				Log object
	* @param \phpbb\request\request				$request			Request objecct
	* @param \phpbb\pagination					$pagination			Pagination object
	* @param string								$phpbb_root_path	phpBB root path
	* @param string								$php_ext			phpEx
	* @param string								$ub_blogs_table		Ultimate Blog blogs table
	* @param string								$ub_cats_table		Ultimate Blog categories table
	* @param string								$ub_comments_table	Ultimate Blog comments table
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
		$ub_comments_table)
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
	}

	function blog_search()
	{
		// Add search language
		$this->user->add_lang('search');

		// Get all category options
		$sql = 'SELECT cat_id, cat_name
				FROM ' . $this->ub_cats_table;
		$result = $this->db->sql_query($sql);

		$categories_options = '';
		$categories = []; // Set up array, needed to retrieve name in results

		while ($row = $this->db->sql_fetchrow($result))
		{
			$categories_options .= '<option value="' . $row['cat_id'] . '">' . $row['cat_name'] . '</option>';
			$categories[$row['cat_id']] = $row['cat_name'];
		}

		$this->db->sql_freeresult($result);

		$this->template->assign_vars([
			'S_BLOG_SEARCH'			=> true,
			'S_CATEGORIES_OPTIONS'	=> $categories_options,

			'U_BLOG_SEARCH_RESULTS'	=> $this->helper->route('posey_ultimateblog_search', ['r' => 'results']),
		]);

		if ($this->request->is_set_post('submit') || $this->request->variable('submit', '') == 'Search')
		{
			// Is user able to search? Has search been disabled?
			if (!$this->auth->acl_get('u_search') || !$this->auth->acl_getf_global('f_search') || !$this->config['load_search'])
			{
				trigger_error($this->user->lang['NO_SEARCH']);
			}

			// Both keywords and author empty
			if (($this->request->variable('bs_keywords', '') == '') && $this->request->variable('bs_author', '') == '')
			{
				trigger_error($this->user->lang['BLOG_SEARCH_EMPTY'] . '<br><br><a href="' . $this->helper->route('posey_ultimateblog_search') . '">&laquo; ' . $this->user->lang['BACK_TO_PREV'] . '</a>');
			}

			// Keyword needs to contain more than 3 characters..
			if (utf8_strlen($this->request->variable('bs_keywords', '')) < 4 && $this->request->variable('bs_author', '') == '')
			{
				trigger_error($this->user->lang['BLOG_SEARCH_TOO_COMMON'] . '<br><br><a href="' . $this->helper->route('posey_ultimateblog_search') . '">&laquo; ' . $this->user->lang['BACK_TO_PREV'] . '</a>');
			}

			// Let's grab all the variables
			$keyw = $this->request->variable('bs_keywords', '');
			$author = $this->request->variable('bs_author', '');
			$cats = $this->request->variable('bs_cats', array(0));
			$si = $this->request->variable('blog_search_in', 'blog');
			$sb = $this->request->variable('bs_sortyby', 'post_time');
			$sd = $this->request->variable('bs_sortdir', 'DESC');
			$ib = ($si == 'blog' || $si == 'title') ? true : false;

			// Get keywords
			$kw_ary = [];
			$kw_ary = explode(' ', $keyw);
			$kw = [];
			$i = 0;

			// Check all words for length, if they're smaller than 4, we ommit them.
			foreach($kw_ary as $keyword)
			{
				if (utf8_strlen($keyword) > 3)
				{
					$kw[$i] = $keyword;
					$i++;
				}
			}

			// Get author ID, if author name is filled in
			if ($author)
			{
				$sql_where = (strpos($author, '*') !== false) ? ' username_clean ' . $this->db->sql_like_expression(str_replace('*', $this->db->get_any_char(), utf8_clean_string($author))) : " username_clean = '" . $this->db->sql_escape(utf8_clean_string($author)) . "'";

				$sql = 'SELECT user_id
						FROM ' . USERS_TABLE . '
						WHERE ' . $sql_where . '
							AND user_type <> ' . USER_IGNORE;
				$result = $this->db->sql_query($sql);
				$uid = (int) $this->db->sql_fetchfield('user_id');
				$this->db->sql_freeresult($result);
			}

			// Get category options
			$cat_options = sizeof($cats) ? $this->db->sql_in_set('b.cat_id', $cats) : $this->db->sql_in_set('b.cat_id', 0, true);

			// Sorting options
			if ($sb == 'author')
			{
				$sort = 'u.username_clean ' . $sd;
			}
			else if ($sb == 'title')
			{
				$sort = 'b.blog_subject ' . $sd;
			}
			else if ($sb == 'post_time')
			{
				$sort = $ib ? 'b.post_time ' . $sd : 'c.post_time ' . $sd;
			}

			if ($ib)
			{
				$select = 'b.*, u.user_id, u.username, u.username_clean, u.user_colour';
				$from = [$this->ub_blogs_table => 'b',];
				$left_join_on = 'b.poster_id = u.user_id';
				$where = '';

				if ($author && $uid)
				{
					$where .= ' AND b.poster_id = ' . (int) $uid;
				}
			}
			else
			{
				$select = 'c.*, b.blog_id, b.blog_subject, u.user_id, u.username, u.username_clean, u.user_colour';
				$from = [$this->ub_comments_table => 'c', $this->ub_blogs_table => 'b',];
				$left_join_on = 'c.poster_id = u.user_id';
				$where = ' AND c.blog_id = b.blog_id';

				if ($author && $uid)
				{
					$where .= ' AND c.poster_id = ' . (int) $uid;
				}
			}

			$sql_array = [
				'SELECT'	=> 'COUNT(b.blog_id) as search_count, b.cat_id, ' . $select,

				'FROM'		=> $from,

				'LEFT_JOIN'	=> [
					[
						'FROM'	=> [USERS_TABLE => 'u'],
						'ON'	=> $left_join_on,
					]
				],

				'WHERE'		=> $cat_options . $where,

				'ORDER_BY'	=> $sort,
			];

			// Build and run the query
			$sql = $this->db->sql_build_query('SELECT', $sql_array);
			$result = $this->db->sql_query($sql);

			// Are there any keywords filled in?
			// If YES: We need to check blog_subject, blog_text and comment_text for possession of the keywords
			// If NO: We can display all the results, as it's an author search
			if ($kw[0] != '')
			{
				// Set up search count
				$search_count = 0;

				while ($row = $this->db->sql_fetchrow($result))
				{
					// Set up keyword string and comment check
					$has_kw = false;

					if ($si == 'title')
					{
						$kw_str = $row['blog_subject'];
					}
					else
					{
						$kw_str = $ib ? $row['blog_text'] . ' ' . $row['blog_subject'] : $row['comment_text'];
					}

					foreach($kw as $keyword)
					{
						if (strpos($kw_str, $keyword) !== false)
						{
							$has_kw = true; // Has keyword
							break; // Stop searching the rest of the keywords if one was found
						}
					}

					// If it has any of the keywords, we display the result
					if ($has_kw)
					{
						// Counts as a result, so we increment the count
						$search_count++;

						// Set up text for display
						if ($ib)
						{
							$row['bbcode_options'] = (($row['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) +
													(($row['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) +
													(($row['enable_magic_url']) ? OPTION_FLAG_LINKS : 0);
							$text = generate_text_for_display($row['blog_text'], $row['bbcode_uid'], $row['bbcode_bitfield'], $row['bbcode_options']);
						}
						else
						{
							$text = generate_text_for_display($row['comment_text'], $row['bbcode_uid'], $row['bbcode_bitfield'], $row['bbcode_options']);
						}

						// Set up highlighting
						$subject = $row['blog_subject'];
						foreach($kw as $keyword)
						{
							$text = str_replace($keyword, '<span class="posthilit">' . $keyword . '</span>', $text);
							$subject = str_replace($keyword, '<span class="posthilit">' . $keyword . '</span>', $row['blog_subject']);
						}
						$hilit = $this->request->variable('bs_keywords', '');

						// Cut off text
						$text = (strlen($text) > 500) ? substr($text, 0, $this->config['ub_cutoff']) . ' ...' : $text;

						$this->template->assign_block_vars('results', [
							'AUTHOR'	=> get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
							'CAT'		=> $categories[$row['cat_id']],
							'SUBJECT'	=> $subject,
							'TEXT'		=> $text,
							'TIME'		=> $this->user->format_date($row['post_time']),

							'S_IS_COMMENT'	=> $ib ? false : true,

							'U_BLOG'	=> $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $row['blog_id'], 'hilit' => $hilit]),
							'U_CAT'		=> $this->helper->route('posey_ultimateblog_category', ['cat_id' => (int) $row['cat_id']]),
							'U_COMMENT'	=> !$ib ? $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $row['blog_id'], 'hilit' => $hilit]) . '#c' . (int) $row['comment_id'] : '',
						]);
					}
				}
			}
			else
			{
				while ($row = $this->db->sql_fetchrow($result))
				{
					// Set search results count
					$search_count = (int) $row['search_count'];

					// Set up text for display
					if ($ib)
					{
						$row['bbcode_options'] = (($row['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) +
												(($row['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) +
												(($row['enable_magic_url']) ? OPTION_FLAG_LINKS : 0);
						$text = generate_text_for_display($row['blog_text'], $row['bbcode_uid'], $row['bbcode_bitfield'], $row['bbcode_options']);
					}
					else
					{
						$text = generate_text_for_display($row['comment_text'], $row['bbcode_uid'], $row['bbcode_bitfield'], $row['bbcode_options']);
					}

					// Cut off text
					$text = (strlen($text) > 500) ? substr($text, 0, $this->config['ub_cutoff']) . ' ...' : $text;

					$this->template->assign_block_vars('results', [
						'AUTHOR'	=> get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
						'CAT'		=> $categories[$row['cat_id']],
						'SUBJECT'	=> $row['blog_subject'],
						'TEXT'		=> $text,
						'TIME'		=> $this->user->format_date($row['post_time']),

						'S_IS_COMMENT'	=> $ib ? false : true,

						'U_BLOG'	=> $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $row['blog_id']]),
						'U_CAT'		=> $this->helper->route('posey_ultimateblog_category', ['cat_id' => (int) $row['cat_id']]),
						'U_COMMENT'	=> !$ib ? $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $row['blog_id']]) . '#c' . (int) $row['comment_id'] : '',
					]);
				}
			}

			// Master gave Dobby a sock, now Dobby is freeeee!!
			$this->db->sql_freeresult($result);

			// Generate the search template
			$this->template->assign_vars([
				'SEARCH_RESULTS_COUNT'	=> $this->user->lang('BLOG_SEARCH_RESULTS_COUNT', $search_count),

				'S_BLOG_SEARCH'					=> false,
				'S_BLOG_SEARCH_RESULTS'			=> true,
			]);
		}
	}
}

