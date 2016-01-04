<?php
/**
*
* @package phpBB Extension - Ultimate Blog
* @copyright (c) 2015 posey
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace posey\ultimateblog\acp;

class main_module
{
	public $u_action;

	function main($id, $mode)
	{
		global $phpbb_container, $request, $user;

		// Get an instance of the admin controller
		$admin_controller = $phpbb_container->get('posey.ultimateblog.admin.controller');

		// Requests
		$action = $request->variable('action', '');
		$cat_id = $request->variable('cat_id', 0);
		if ($request->is_set_post('add'))
		{
			$action = 'add';
		}

		// Make the $u_action url available in the admin controller
		$admin_controller->set_page_url($this->u_action);

		// Load the "settings" or "categories" module modes
		switch ($mode)
		{
			case 'settings':
				// Load a template from adm/style for our ACP page
				$this->tpl_name = 'acp_ultimateblog_main';

				// Set the page title for our ACP page
				$this->page_title = $user->lang['ACP_ULTIMATEBLOG_SETTINGS_TITLE'];

				// Load the settings handle in the admin controller
				$admin_controller->settings();
			break;

			case 'categories':
				// Load a template from adm/style for our ACP page
				$this->tpl_name = 'acp_ultimateblog_cats';

				// Set the page title for our ACP page
				$this->page_title = $user->lang['ACP_ULTIMATEBLOG_CATEGORIES_TITLE'];

				// Perform any actions submitted by the user, otherwise display categories
				switch ($action)
				{
					case 'add':
						// Set the page title for our ACP page
						$this->page_title = $user->lang['ACP_UB_CAT_ADD'];

						// Load the add category handle in the admin controller
						$admin_controller->add_category();

						// Return to stop execution of this script
						return;
					break;

					case 'edit':
						// Set the page title for our ACP page
						$this->page_title = $user->lang('ACP_UB_CAT_EDIT');

						// Load the edit category handle in the admin controller
						$admin_controller->edit_category($cat_id);

						// Return to stop execution of this script
						return;
					break;

					case 'delete':
						// Load the delete category handle in the admin controller
						$admin_controller->delete_category($cat_id);
					break;

					default:
						// Load the display categories handle in the admin controller
						$admin_controller->display_categories();
					break;
				}
			break;
		}
	}
}
