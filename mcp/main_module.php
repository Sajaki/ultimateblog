<?php
/**
*
* @package phpBB Extension - Ultimate Blog
* @copyright (c) 2015 posey
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace posey\ultimateblog\mcp;

class main_module
{
	function main($id, $mode)
	{
		global $user, $phpbb_container;

		// Get the MCP Controller
		$mcp_controller = $phpbb_container->get('posey.ultimateblog.mcp.controller');

		switch ($mode)
		{
			// Open blog reports
			case 'open':
				$this->tpl_name = 'mcp_blog_reports';
				$this->page_title = $user->lang['MCP_BLOG_REPORTS_OPEN'];
				$mcp_controller->reports_open();
			break;

			// Closed blog reports
			case 'closed':
				$this->tpl_name = 'mcp_blog_reports';
				$this->page_title = $user->lang['MCP_BLOG_REPORTS_CLOSED'];
				$mcp_controller->reports_closed();
			break;

			// Blog Report details
			case 'details':
				$this->tpl_name = 'mcp_blog_reports_details';
				$this->page_title = $user->lang['MCP_BLOG_REPORTS_DETAILS'];
				$mcp_controller->reports_details();
			break;
		}
	}
}
