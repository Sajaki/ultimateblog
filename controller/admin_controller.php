<?php
/**
*
* @package phpBB Extension - Ultimate Blog
* @copyright (c) 2015 posey
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace posey\ultimateblog\controller;

class admin_controller
{
	protected $user;
	protected $template;
	protected $db;
	protected $log;
	protected $config;
	protected $auth;
	protected $cache;
	protected $helper;
	protected $request;
	protected $pagination;
	protected $phpbb_ext_manager;
	protected $phpbb_root_path;
	protected $ub_blogs_table;
	protected $ub_cats_table;

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
		$this->auth		= $auth;
		$this->cache	= $cache;
		$this->helper	= $helper;
		$this->request	= $request;
		$this->pagination		= $pagination;
		$this->phpbb_ext_manager = $phpbb_ext_manager;
		$this->phpbb_root_path	= $phpbb_root_path;
		$this->ub_blogs_table	= $ub_blogs_table;
		$this->ub_cats_table	= $ub_cats_table;
	}

	// Ultimate Blog Settings
	public function settings()
	{
		$action	= $this->request->variable('action', '');
		$id		= $this->request->variable('id', 0);

		add_form_key('acp_ub_settings');

		$this->template->assign_vars(array(
			'BASE'		=> $this->u_action,
		));

		$submit = $this->request->variable('submit', '');

		if ($submit)
		{
			if (!check_form_key('acp_ub_settings'))
			{
				trigger_error($this->user->lang['FORM_INVALID'] . adm_back_link($this->u_action), E_USER_WARNING);
			}
			else
			{
				$ub_enabled 		= $this->request->variable('ub_enabled', 1);
				$ub_blogs_per_page	= $this->request->variable('ub_blogs_per_page', 5);
				$ub_cutoff 			= $this->request->variable('ub_cutoff', 1500);
				$ub_show_desc		= $this->request->variable('ub_show_desc', 1);

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

				// Add to the log
				$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_UB_SETTINGS_CHANGED');

				// Show success message
				trigger_error($this->user->lang('ACP_UB_SETTINGS_SAVED') . adm_back_link($this->u_action));
			}
		}
		else
		{
			$this->template->assign_vars(array(
				'UB_ENABLED'		=> ($this->config['ub_enabled']) ? true : false,
				'UB_BLOGS_PER_PAGE'	=> $this->config['ub_blogs_per_page'],
				'UB_CUTOFF'			=> $this->config['ub_cutoff'],
				'UB_SHOW_DESC'		=> $this->config['ub_show_desc'],
				'S_UB_MAIN'			=> true,
			));

		// Version check
		$this->user->add_lang(array('install', 'acp/extensions', 'migrator'));
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
			$this->template->assign_vars(array(
				'S_UP_TO_DATE'		=> empty($updates_available),
				'S_VERSIONCHECK'	=> true,
				'UP_TO_DATE_MSG'	=> $this->user->lang(empty($updates_available) ? 'UP_TO_DATE' : 'NOT_UP_TO_DATE', $md_manager->get_metadata('display-name')),
			));
			foreach ($updates_available as $branch => $version_data)
			{
				$this->template->assign_block_vars('updates_available', $version_data);
			}
		}
		catch (\RuntimeException $e)
		{
			$this->template->assign_vars(array(
				'S_VERSIONCHECK_STATUS'			=> $e->getCode(),
				'VERSIONCHECK_FAIL_REASON'		=> ($e->getMessage() !== $this->user->lang('VERSIONCHECK_FAIL')) ? $e->getMessage() : '',
			));
		}
		}
	}

	/**
	 * Display categories
	 */
	public function display_categories()
	{
		// Select categories
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

			$this->template->assign_block_vars('cats', array(
				'NAME'			=> $row['cat_name'],
				'DESC'			=> generate_text_for_display($row['cat_desc'], $row['bbcode_uid'], $row['bbcode_bitfield'], $bbcode_options),
				'ID'			=> $row['cat_id'],
				'BLOG_COUNT'	=> $row['blog_count'],
				'U_EDIT'		=> $this->u_action . "&amp;cat_id={$row['cat_id']}&amp;action=edit",
				'U_DELETE'		=> $this->u_action . "&amp;cat_id={$row['cat_id']}&amp;action=delete",
			));
		}

		$this->db->sql_freeresult($result);

		$this->template->assign_vars(array(
			'S_CATS'		=> true,
		));
	}

	/**
	 * Add a category
	 */
	public function add_category()
	{
		// Add form key
		add_form_key('add_category');

		$desc = $this->request->variable('cat_desc', '', true);
		$uid = $bitfield = $options = ''; // will be modified by generate_text_for_storage
		$allow_bbcode = $this->request->variable('cat_desc_bbcode', 0) == 1 ? true : false;
		$allow_smilies = $this->request->variable('cat_desc_smilies', 0) == 1 ? true : false;
		$allow_urls = $this->request->variable('cat_desc_urls', 0) == 1 ? true : false;
		generate_text_for_storage($desc, $uid, $bitfield, $options, $allow_bbcode, $allow_urls, $allow_smilies);

		$cat_row = array(
			'cat_name'			=> ucfirst($this->request->variable('cat_name', '', true)), // Set _$multibyte_ to true, so no need to run through utf8_normalize_nfc
			'cat_desc'			=> $desc,
			'bbcode_uid'		=> $uid,
			'bbcode_bitfield'	=> $bitfield,
			'enable_bbcode'	 => $allow_bbcode ? 1 : 0,
			'enable_magic_url'	=> $allow_urls ? 1 : 0,
			'enable_smilies'	=> $allow_smilies ? 1 : 0,
		);

		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key('add_category'))
			{
				// Form is invalid
				trigger_error($this->user->lang['FORM_INVALID'] . adm_back_link($this->u_action), E_USER_WARNING);
			}
			else
			{
				// Insert the category
				$sql = 'INSERT INTO ' . $this->ub_cats_table . ' ' . $this->db->sql_build_array('INSERT', $cat_row);
				$this->db->sql_query($sql);

				// Add it to the log
				$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_UB_CAT_ADD', false, array($cat_row['cat_name']));

				// Send success message
				trigger_error($this->user->lang['ACP_UB_CAT_ADDED'] . adm_back_link($this->u_action));
			}
		}
		else
		{
			$this->template->assign_vars(array(
				'U_ACTION'		=> $this->u_action . '&amp;action=add',
				'U_BACK'		=> $this->u_action,
				'CAT_NAME'		=> $cat_row['cat_name'],
				'CAT_DESC'		=> $cat_row['cat_desc'],
				'S_ADD_CAT'		=> true,
				'S_CAT_DESC_BBCODE'		=> true,
				'S_CAT_DESC_SMILIES'	=> true,
				'S_CAT_DESC_URLS'		=> true,
			));
		}
	}

	/**
	 * Edit a category
	 */
	public function edit_category($cat_id)
	{
		add_form_key('edit_category');

		$desc = $this->request->variable('cat_desc', '', true);
		$uid = $bitfield = $options = ''; // will be modified by generate_text_for_storage
		$allow_bbcode = $this->request->variable('cat_desc_bbcode', 0) == 1 ? true : false;
		$allow_smilies = $this->request->variable('cat_desc_smilies', 0) == 1 ? true : false;
		$allow_urls = $this->request->variable('cat_desc_urls', 0) == 1 ? true : false;
		generate_text_for_storage($desc, $uid, $bitfield, $options, $allow_bbcode, $allow_urls, $allow_smilies);

		$cat_row = array(
			'cat_name'			=> ucfirst($this->request->variable('cat_name', '', true)), // Set _$multibyte_ to true, so no need to run through utf8_normalize_nfc
			'cat_desc'			=> $desc,
			'bbcode_uid'		=> $uid,
			'bbcode_bitfield'	=> $bitfield,
			'enable_bbcode'	 => $allow_bbcode ? 1 : 0,
			'enable_smilies'	=> $allow_smilies ? 1 : 0,
			'enable_magic_url'	=> $allow_urls ? 1 : 0,
		);

		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key('edit_category'))
			{
				trigger_error($this->user->lang['FORM_INVALID'] . adm_back_link($this->u_action), E_USER_WARNING);
			}
			else
			{
				$sql = 'UPDATE ' . $this->ub_cats_table . '
						SET ' . $this->db->sql_build_array('UPDATE', $cat_row) . '
						WHERE cat_id = ' . (int) $cat_id;
				$this->db->sql_query($sql);

				// Add it to the logg
				$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_UB_CAT_EDIT', false, array($cat_row['cat_name']));

				// Send success message
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

		// Decode description for editing
		decode_message($cat_row['cat_desc'], $cat_row['bbcode_uid']);

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
	 * Delete a category
	 */
	public function delete_category($cat_id)
	{
		if (confirm_box(true))
		{
			// Can NOT delete first category
			if ($cat_id == 1)
			{
				trigger_error($this->user->lang['ACP_UB_CAT_DEL_FIRST'] . adm_back_link($this->u_action), E_USER_WARNING);
			}

			// Grab the cat name for the log
			$sql = 'SELECT cat_name
					FROM ' . $this->ub_cats_table . '
					WHERE cat_id = ' .(int) $cat_id;
			$result = $this->db->sql_query($sql);
			$cat_name = $this->db->sql_fetchfield('cat_name');
			$this->db->sql_freeresult($result);

			// Delete the category
			$sql = 'DELETE FROM ' . $this->ub_cats_table . '
					WHERE cat_id = ' . (int) $cat_id;
			$this->db->sql_query($sql);

			// Reset the category for blogs, default back to category 1
			$sql = 'UPDATE ' . $this->ub_blogs_table . '
					SET cat_id = 1
					WHERE cat_id = ' . (int) $cat_id;
			$this->db->sql_query($sql);
			$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_UB_CAT_DELETE', false, array($cat_name));
			trigger_error($this->user->lang['ACP_UB_CAT_DELETED'] . adm_back_link($this->u_action . "&amp;mode=categories"));
		}
		else
		{
			// Display blog count
			$sql = 'SELECT COUNT(cat_id) AS blog_count
					FROM ' . $this->ub_blogs_table . '
					WHERE cat_id = ' . (int) $cat_id;
			$result = $this->db->sql_query($sql);
			$blog_count = $this->db->sql_fetchfield('blog_count');
			$this->db->sql_freeresult($result);
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
