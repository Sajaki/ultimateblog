<?php
/**
*
* @package phpBB Extension - Ultimate Blog
* @copyright (c) 2015 posey
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace posey\ultimateblog\controller;

/**
* Admin controller
*/
class admin_controller
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

	# @var \phpbb\cache\service
	protected $cache;

	# @var \phpbb\controller\helper
	protected $helper;

	# @var \phpbb\request\request
	protected $request;

	# @var \phpbb\pagination
	protected $pagination;

	# @var \phpbb\extension\manager
	protected $phpbb_ext_manager;

	# @var string phpBB root path
	protected $phpbb_root_path;

	# @var string Custom form action
	protected $u_action;

	# The database table the blogs are stored in
	# @var string
	protected $ub_blogs_table;

	# The database table the categories are stored in
	# @var string
	protected $ub_cats_table;

	/**
	* Constructor
	*
	* @param \phpbb\user						$user				User object
	* @param \phpbb\template\template			$template			Template object
	* @param \phpbb\db\driver\driver_interface	$db					Database object
	* @param \phpbb\log\log						$log				Log object
	* @param \phpbb\config\config				$config				Config object
	* @param \phpbb\cache\service				$cache				Cache object
	* @param \phpbb\controller\helper			$helper				Controller helper object
	* @param \phpbb\request\request				$request			Request object
	* @param \phpbb\pagination					$pagination			Pagination object
	* @param \phpbb\extension\manager			$extension_manager	Extension manager object
	* @param string								$phpbb_root_path	phpBB root path
	* @param string								$ub_blogs_table		Ultimate Blog blogs table
	* @param string								$ub_cats_table		Ultimate Blog categories table
	* @access public
	*/
	public function __construct(
		\phpbb\user $user,
		\phpbb\template\template $template,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\log\log $log,
		\phpbb\config\config $config,
		\phpbb\cache\service $cache,
		\phpbb\controller\helper $helper,
		\phpbb\request\request $request,
		\phpbb\pagination $pagination,
		\phpbb\extension\manager $phpbb_ext_manager,
		$phpbb_root_path,
		$ub_blogs_table,
		$ub_cats_table)
	{
		$this->user		= $user;
		$this->template	= $template;
		$this->db		= $db;
		$this->log		= $log;
		$this->config	= $config;
		$this->cache	= $cache;
		$this->helper	= $helper;
		$this->request	= $request;
		$this->pagination		= $pagination;
		$this->phpbb_ext_manager = $phpbb_ext_manager;
		$this->phpbb_root_path	= $phpbb_root_path;
		$this->ub_blogs_table	= $ub_blogs_table;
		$this->ub_cats_table	= $ub_cats_table;
	}

	/**
	* Ultimate Blog | ACP | Settings
	*/
	public function settings()
	{
		// Requests
		$action	= $this->request->variable('action', '');
		$id		= $this->request->variable('id', 0);

		// Create a form key for preventing CSRF attacks
		add_form_key('acp_ub_settings');

		// Is the form being submitted to us?
		if ($this->request->is_set('submit'))
		{
			// Check if the submitted form is valid
			if (!check_form_key('acp_ub_settings'))
			{
				trigger_error($this->user->lang['FORM_INVALID'] . adm_back_link($this->u_action), E_USER_WARNING);
			}
			// Check if Ultimate Blog RSS Feed is enabled,
			// if so check if Feed Title and Feed Description are not empty
			else if ($this->request->variable('ub_rss_enabled', 0) && ($this->request->variable('ub_rss_title', '') == '' || $this->request->variable('ub_rss_desc', '') == ''))
			{
				trigger_error($this->user->lang['ACP_UB_SETTINGS_RSS_REQUIRED'] . adm_back_link($this->u_action), E_USER_WARNING);
			}
			else
			{
				// Requests
				$ub_enabled 		= $this->request->variable('ub_enabled', 1);
				$ub_blogs_per_page	= $this->request->variable('ub_blogs_per_page', 5);
				$ub_cutoff 			= $this->request->variable('ub_cutoff', 1500);
				$ub_show_desc		= $this->request->variable('ub_show_desc', 1);
				$ub_rss_enabled		= $this->request->variable('ub_rss_enabled', 0);
				$ub_rss_title		= $this->request->variable('ub_rss_title', '');
				$ub_rss_desc		= $this->request->variable('ub_rss_desc', '');
				$ub_rss_cat			= $this->request->variable('ub_rss_cat', '');
				$ub_rss_copy		= $this->request->variable('ub_rss_copy', '');
				$ub_rss_lang		= $this->request->variable('ub_rss_title', '');
				$ub_rss_img			= $this->request->variable('ub_rss_img', '');
				$ub_rss_email		= $this->request->variable('ub_rss_email', 1);

				// Check if submitted value is different that stored value,
				// if so change it to the submitted value
				if ($ub_enabled != $this->config['ub_enabled'])
				{
					$this->config->set('ub_enabled', $ub_enabled);
				}
				if ($ub_blogs_per_page != $this->config['ub_blogs_per_page'])
				{
					$this->config->set('ub_blogs_per_page', $ub_blogs_per_page);
				}
				if ($ub_cutoff != $this->config['ub_cutoff'])
				{
					$this->config->set('ub_cutoff', $ub_cutoff);
				}
				if ($ub_show_desc != $this->config['ub_show_desc'])
				{
					$this->config->set('ub_show_desc', $ub_show_desc);
				}
				if ($ub_rss_enabled != $this->config['ub_rss_enabled'])
				{
					$this->config->set('ub_rss_enabled', $ub_rss_enabled);
				}
				if ($ub_rss_title != $this->config['ub_rss_title'])
				{
					$this->config->set('ub_rss_title', $ub_rss_title);
				}
				if ($ub_rss_desc != $this->config['ub_rss_desc'])
				{
					$this->config->set('ub_rss_desc', $ub_rss_desc);
				}
				if ($ub_rss_cat != $this->config['ub_rss_cat'])
				{
					$this->config->set('ub_rss_cat', $ub_rss_cat);
				}
				if ($ub_rss_copy != $this->config['ub_rss_copy'])
				{
					$this->config->set('ub_rss_copy', $ub_rss_copy);
				}
				if ($ub_rss_lang != $this->config['ub_rss_lang'])
				{
					$this->config->set('ub_rss_lang', $ub_rss_lang);
				}
				if ($ub_rss_img != $this->config['ub_rss_img'])
				{
					$this->config->set('ub_rss_img', $ub_rss_img);
				}
				if ($ub_rss_email != $this->config['ub_rss_email'])
				{
					$this->config->set('ub_rss_email', $ub_rss_email);
				}

				// Add the change of Ultimate Blog settings to the log
				$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_UB_SETTINGS_CHANGED');

				// Ultimate Blog settings have been updated and logged
				// Confirm this to the user and provide link back to previous page
				trigger_error($this->user->lang('ACP_UB_SETTINGS_SAVED') . adm_back_link($this->u_action));
			}
		}
		else
		{
			// Set output vars for display in the template
			$this->template->assign_vars([
				'UB_ENABLED'		=> ($this->config['ub_enabled']) ? true : false,
				'UB_BLOGS_PER_PAGE'	=> $this->config['ub_blogs_per_page'],
				'UB_CUTOFF'			=> $this->config['ub_cutoff'],
				'UB_SHOW_DESC'		=> $this->config['ub_show_desc'],

				'UB_RSS_ENABLED'	=> $this->config['ub_rss_enabled'],
				'UB_RSS_TITLE'		=> $this->config['ub_rss_title'],
				'UB_RSS_DESC'		=> $this->config['ub_rss_desc'],
				'UB_RSS_CAT'		=> $this->config['ub_rss_cat'],
				'UB_RSS_COPY'		=> $this->config['ub_rss_copy'],
				'UB_RSS_LANG'		=> $this->config['ub_rss_lang'],
				'UB_RSS_IMG'		=> $this->config['ub_rss_img'],
				'UB_RSS_EMAIL'		=> $this->config['ub_rss_email'],
				'UB_SETTINGS_RSS_EMAIL_EXPLAIN' => $this->user->lang('ACP_UB_SETTINGS_RSS_EMAIL_EXPLAIN', $this->config['board_contact']),

				'S_UB_MAIN'			=> true,
			]);

		// Version check
		$this->user->add_lang(['install', 'acp/extensions', 'migrator']);
		$ext_name = 'posey/ultimateblog';
		$md_manager = new \phpbb\extension\metadata_manager($ext_name, $this->config, $this->phpbb_ext_manager, $this->template, $this->user, $this->phpbb_root_path);
		try
		{
			$this->metadata = $md_manager->get_metadata('all');
		}
		catch(\phpbb\extension\exception $e)
		{
			trigger_error($e, E_USER_WARNING);
		}
		$md_manager->output_template_data();
		try
		{
			$updates_available = $this->version_check($md_manager, $this->request->variable('versioncheck_force', false));
			$this->template->assign_vars([
				'S_UP_TO_DATE'		=> empty($updates_available),
				'S_VERSIONCHECK'	=> true,
				'UP_TO_DATE_MSG'	=> $this->user->lang(empty($updates_available) ? 'UP_TO_DATE' : 'NOT_UP_TO_DATE', $md_manager->get_metadata('display-name')),
			]);
			foreach ($updates_available as $branch => $version_data)
			{
				$this->template->assign_block_vars('updates_available', $version_data);
			}
		}
		catch (\RuntimeException $e)
		{
			$this->template->assign_vars([
				'S_VERSIONCHECK_STATUS'			=> $e->getCode(),
				'VERSIONCHECK_FAIL_REASON'		=> ($e->getMessage() !== $this->user->lang('VERSIONCHECK_FAIL')) ? $e->getMessage() : '',
			]);
		}
		}
	}

	/**
	* Ultimate Blog | ACP | Categories: Display
	*/
	public function display_categories()
	{
		// Select all categories
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

		// Build the 'SELECT' sql
		$sql = $this->db->sql_build_query('SELECT', $sql_array);

		// Run the sql
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			// Set up BBCode options for this Category description
			$bbcode_options =	(($row['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) +
								(($row['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) +
								(($row['enable_magic_url']) ? OPTION_FLAG_LINKS : 0);

			// Set output vars for display in the template
			$this->template->assign_block_vars('cats', [
				'NAME'			=> $row['cat_name'],
				'DESC'			=> generate_text_for_display($row['cat_desc'], $row['bbcode_uid'], $row['bbcode_bitfield'], $bbcode_options),
				'ID'			=> $row['cat_id'],
				'BLOG_COUNT'	=> $row['blog_count'],
				'U_EDIT'		=> $this->u_action . "&amp;cat_id={$row['cat_id']}&amp;action=edit",
				'U_DELETE'		=> $this->u_action . "&amp;cat_id={$row['cat_id']}&amp;action=delete",
			]);
		}

		$this->db->sql_freeresult($result);

		// Define that we are in the overview page
		$this->template->assign_vars(array(
			'S_CATS'		=> true,
		));
	}

	/**
	* Ultimate Blog | ACP | Categories: Add
	*/
	public function add_category()
	{
		// Create a form key for preventing CSRF attacks
		add_form_key('add_category');

		// Is the form submitted to us?
		if ($this->request->is_set_post('submit'))
		{
			// Check if the submitted form is valid
			if (!check_form_key('add_category'))
			{
				trigger_error($this->user->lang['FORM_INVALID'] . adm_back_link($this->u_action), E_USER_WARNING);
			}
			else
			{
				// Requests
				$name			= $this->request->variable('cat_name', '', true); // Set _$multibyte_ to true, so utf8_normalize_nfc is run automatically
				$desc			= $this->request->variable('cat_desc', '', true); // Set _$multibyte_ to true, so utf8_normalize_nfc is run automatically
				$allow_bbcode	= $this->request->variable('cat_desc_bbcode', 0) == 1 ? true : false;
				$allow_smilies	= $this->request->variable('cat_desc_smilies', 0) == 1 ? true : false;
				$allow_urls		= $this->request->variable('cat_desc_urls', 0) == 1 ? true : false;

				// Generate the category description text for storage in the database
				$uid = $bitfield = $options = ''; // will be modified by generate_text_for_storage
				generate_text_for_storage($desc, $uid, $bitfield, $options, $allow_bbcode, $allow_urls, $allow_smilies);

				$cat_row = array(
					'cat_name'			=> ucfirst($name),
					'cat_desc'			=> $desc,
					'bbcode_uid'		=> $uid,
					'bbcode_bitfield'	=> $bitfield,
					'enable_bbcode'	 	=> $allow_bbcode ? 1 : 0,
					'enable_magic_url'	=> $allow_urls ? 1 : 0,
					'enable_smilies'	=> $allow_smilies ? 1 : 0,
				);

				// Insert the category
				$sql = 'INSERT INTO ' . $this->ub_cats_table . ' ' . $this->db->sql_build_array('INSERT', $cat_row);
				$this->db->sql_query($sql);

				// Add the creation of the new Ultimate Blog category to the log
				$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_UB_CAT_ADD', false, [$cat_row['cat_name']]);

				// Ultimate Blog category has been created and logged
				// Confirm this to the user and provide link back to previous page
				trigger_error($this->user->lang['ACP_UB_CAT_ADDED'] . adm_back_link($this->u_action));
			}
		}
		else
		{
			// Set output vars for display in the template
			$this->template->assign_vars([
				'U_ACTION'		=> $this->u_action . '&amp;action=add',
				'U_BACK'		=> $this->u_action,
				'CAT_NAME'		=> $cat_row['cat_name'],
				'CAT_DESC'		=> $cat_row['cat_desc'],

				'S_ADD_CAT'				=> true, // Define that we're adding a cat
				'S_CAT_DESC_BBCODE'		=> true, // 'Check' the parsing of BBCodes
				'S_CAT_DESC_SMILIES'	=> true, // 'Check' the parsing of Smilies
				'S_CAT_DESC_URLS'		=> true, // 'Check' the parsing of URLs
			]);
		}
	}

	/**
	* Ultimate Blog | ACP | Category: Edit
	*/
	public function edit_category($cat_id)
	{
		// Create a form key for preventing CSRF attacks
		add_form_key('edit_category');

		// Is the form submitted to us?
		if ($this->request->is_set_post('submit'))
		{
			// Check if the submitted form is valid
			if (!check_form_key('edit_category'))
			{
				trigger_error($this->user->lang['FORM_INVALID'] . adm_back_link($this->u_action), E_USER_WARNING);
			}
			else
			{
				// Requests
				$name			= $this->request->variable('cat_name', '', true); // Set _$multibyte_ to true, so no need to run through utf8_normalize_nfc
				$desc			= $this->request->variable('cat_desc', '', true); // Set _$multibyte_ to true, so no need to run through utf8_normalize_nfc
				$allow_bbcode	= $this->request->variable('cat_desc_bbcode', 0) == 1 ? true : false;
				$allow_smilies	= $this->request->variable('cat_desc_smilies', 0) == 1 ? true : false;
				$allow_urls		= $this->request->variable('cat_desc_urls', 0) == 1 ? true : false;

				// Generate the category description text for storage in the database
				$uid = $bitfield = $options = ''; // will be modified by generate_text_for_storage
				generate_text_for_storage($desc, $uid, $bitfield, $options, $allow_bbcode, $allow_urls, $allow_smilies);

				$cat_row = array(
					'cat_name'			=> ucfirst($name),
					'cat_desc'			=> $desc,
					'bbcode_uid'		=> $uid,
					'bbcode_bitfield'	=> $bitfield,
					'enable_bbcode'	 	=> $allow_bbcode ? 1 : 0,
					'enable_smilies'	=> $allow_smilies ? 1 : 0,
					'enable_magic_url'	=> $allow_urls ? 1 : 0,
				);

				// Update the database with the new $cat_row data
				$sql = 'UPDATE ' . $this->ub_cats_table . '
						SET ' . $this->db->sql_build_array('UPDATE', $cat_row) . '
						WHERE cat_id = ' . (int) $cat_id;
				$this->db->sql_query($sql);

				// Add the update of the Ultimate Blog category to the log
				$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_UB_CAT_EDIT', false, array($cat_row['cat_name']));

				// The Ultimate Blog category has been updated and logged
				// Confirm this to the user and provide link back to the previous page
				trigger_error($this->user->lang['ACP_UB_CAT_EDITED'] . adm_back_link($this->u_action));
			}
		}

		// Grab category information
		$sql = 'SELECT *
				FROM ' . $this->ub_cats_table . '
				WHERE cat_id =' . (int) $cat_id;
		$result = $this->db->sql_query($sql);
		$cat_row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		// No valid ID
		if (!$cat_row)
		{
			trigger_error($this->user->lang['ACP_UB_CAT_NOT_EXIST'] . adm_back_link($this->u_action . '&amp;mode=categories'), E_USER_WARNING);
		}

		// Decode category description text for editing
		decode_message($cat_row['cat_desc'], $cat_row['bbcode_uid']);

		// Set output vars for display in the template
		$this->template->assign_vars(array(
			'U_ACTION'		=> $this->u_action . "&amp;cat_id=$cat_id&amp;action=edit",
			'U_BACK'		=> $this->u_action . '&amp;mode=categories',
			'CAT_NAME'		=> $cat_row['cat_name'],
			'CAT_DESC'		=> $cat_row['cat_desc'],
			'CAT_ID'		=> $cat_row['cat_id'],

			'S_ADD_CAT'		=> true,
			'S_CAT_DESC_BBCODE'		=> $cat_row['enable_bbcode'],
			'S_CAT_DESC_SMILIES'	=> $cat_row['enable_smilies'],
			'S_CAT_DESC_URLS'		=> $cat_row['enable_magic_url'],
			)
		);
	}

	/**
	* Ultimate Blog | ACP | Category: Delete
	*/
	public function delete_category($cat_id)
	{
		if (confirm_box(true))
		{
			/*
			* We do not allow the deletion of the first category.
			* This will ensure that there is always alteast one category,
			* In which blogs can be created and stored.
			* This is to prevent bugs and errors.
			*/
			if ($cat_id == 1)
			{
				trigger_error($this->user->lang['ACP_UB_CAT_DEL_FIRST'] . adm_back_link($this->u_action), E_USER_WARNING);
			}

			// Grab the Category name for the log
			$sql = 'SELECT cat_name
					FROM ' . $this->ub_cats_table . '
					WHERE cat_id = ' .(int) $cat_id;
			$result = $this->db->sql_query($sql);
			$cat_name = $this->db->sql_fetchfield('cat_name');
			$this->db->sql_freeresult($result);

			// Delete the Category from the database
			$sql = 'DELETE FROM ' . $this->ub_cats_table . '
					WHERE cat_id = ' . (int) $cat_id;
			$this->db->sql_query($sql);

			// Reset the category for blogs, default back to category 1
			$sql = 'UPDATE ' . $this->ub_blogs_table . '
					SET cat_id = 1
					WHERE cat_id = ' . (int) $cat_id;
			$this->db->sql_query($sql);

			// Add the deletion of the Ultimate Blog category to the log
			$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_UB_CAT_DELETE', false, [$cat_name]);

			// The Ultimate Blog category has been deleted and logged
			// Confirm this to the user and provide link back to the previous page
			trigger_error($this->user->lang['ACP_UB_CAT_DELETED'] . adm_back_link($this->u_action . "&amp;mode=categories"));
		}
		else
		{
			$message = $this->user->lang['ACP_UB_CAT_DEL_CONFIRM'];

			confirm_box(false, $message, build_hidden_fields(array(
				'id'		=> (int) $cat_id,
				'mode'		=> 'categories',
				'action'	=> 'delete'))
			);

			// Use a redirect to take the user back to the previous page
			// if the user chose not delete the category from the confirmation page.
			redirect("{$this->u_action}");
		}
	}

	protected function version_check(\phpbb\extension\metadata_manager $md_manager, $force_update = false, $force_cache = false)
	{
		$meta = $md_manager->get_metadata('all');
		if (!isset($meta['extra']['version-check']))
		{
			throw new \RuntimeException($this->user->lang('NO_VERSIONCHECK'), 1);
		}
		$version_check = $meta['extra']['version-check'];
		$version_helper = new \phpbb\version_helper($this->cache, $this->config, new \phpbb\file_downloader(), $this->user);
		$version_helper->set_current_version($meta['version']);
		$version_helper->set_file_location($version_check['host'], $version_check['directory'], $version_check['filename']);
		$version_helper->force_stability($this->config['extension_force_unstable'] ? 'unstable' : null);
		return $updates = $version_helper->get_suggested_updates($force_update, $force_cache);
	}

	// Set u_action accordingly
	public function set_page_url($u_action)
	{
		$this->u_action = $u_action;
	}
}
