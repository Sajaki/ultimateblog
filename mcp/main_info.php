<?php
/**
*
* @package phpBB Extension - Ultimate Blog
* @copyright (c) 2015 posey
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace posey\ultimateblog\mcp;

class main_info
{
	function module()
	{
		return array(
			'filename'	=> '\posey\ultimateblog\mcp\main_module',
			'title'		=> 'MCP_BLOG_REPORTS',
			'version'	=> '1.0.0',
			'modes'		=> array(
				'open'		=> array('title' => 'MCP_BLOG_REPORTS_OPEN', 'auth' => 'ext_posey/ultimateblog && aclf_m_blog_reports', 'cat' => array('MCP_REPORTS')),
				'closed'	=> array('title' => 'MCP_BLOG_REPORTS_CLOSED', 'auth' => 'ext_posey/ultimateblog && aclf_m_blog_reports', 'cat' => array('MCP_REPORTS')),
				'details'	=> array('title' => 'MCP_BLOG_REPORTS_DETAILS', 'auth' => 'ext_posey/ultimateblog && aclf_m_blog_reports', 'cat' => array('MCP_REPORTS')),

			),
		);
	}
}
