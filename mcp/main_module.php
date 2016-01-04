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

		// Get an instance of the mcp controller
		$mcp_controller = $phpbb_container->get('posey.ultimateblog.mcp.controller');

		// Load the "open", "closed" or "details" module modes
		switch ($mode)
		{
			// Open blog reports
			case 'open':
				// Load a template for our MCP page
				$this->tpl_name = '/mcp/blog_reports';

				// Set the page title for our MCP page
				$this->page_title = $user->lang['MCP_BLOG_REPORTS_OPEN'];

				// Load the open reports handle in the mcp controller
				$mcp_controller->reports_open();
			break;

			// Closed blog reports
			case 'closed':
				// Load a template for our MCP page
				$this->tpl_name = '/mcp/blog_reports';

				// Set the page title for our MCP page
				$this->page_title = $user->lang['MCP_BLOG_REPORTS_CLOSED'];

				// Load the closed reports handle in the mcp controller
				$mcp_controller->reports_closed();
			break;

			// Blog Report details
			case 'details':
				// Load a template for our MCP page
				$this->tpl_name = '/mcp/blog_reports_details';

				// Set the page title for our MCP page
				$this->page_title = $user->lang['MCP_BLOG_REPORTS_DETAILS'];

				// Load the report details handle in the mcp controller
				$mcp_controller->reports_details();
			break;
		}
	}
}
