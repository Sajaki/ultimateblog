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

		// Get the Admin Controller
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

		switch ($mode)
		{
			case 'settings':
				$this->tpl_name = 'acp_ultimateblog_main';
				$this->page_title = $user->lang['ACP_ULTIMATEBLOG_SETTINGS_TITLE'];
				$admin_controller->settings();
			break;

			case 'categories':
				$this->tpl_name = 'acp_ultimateblog_cats';
				$this->page_title = $user->lang['ACP_ULTIMATEBLOG_CATEGORIES_TITLE'];

				switch ($action)
				{
					case 'add':
						$this->page_title = $user->lang['ACP_UB_CAT_ADD'];
						$admin_controller->add_category();
						return;
					break;
					case 'edit':
						$this->page_title = $user->lang('ACP_UB_CAT_EDIT');
						$admin_controller->edit_category($cat_id);
						return;
					break;
					case 'delete':
						$admin_controller->delete_category($cat_id);
					break;
					default:
						$admin_controller->display_categories();
					break;
				}
			break;
		}
	}
}