<?php
/**
*
* @package phpBB Extension - Ultimate Blog
* @copyright (c) 2015 posey 
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace posey\ultimateblog\acp;

class main_info
{
	function module()
	{
		return array(
			'filename'	=> '\posey\ultimateblog\acp\main_module',
			'title'		=> 'ACP_ULTIMATEBLOG',
			'modes'		=> array(
				'settings'		=> array('title' => 'ACP_ULTIMATEBLOG_SETTINGS_TITLE', 'auth' => 'ext_posey/ultimateblog && acl_a_board', 'cat' => array('ACP_ULTIMATEBLOG')),
				'categories'	=> array('title' => 'ACP_ULTIMATEBLOG_CATEGORIES_TITLE', 'auth' => 'ext_posey/ultimateblog && acl_a_board', 'cat' => array('ACP_ULTIMATEBLOG')),
				'tags'			=> array('title' => 'ACP_ULTIMATEBLOG_TAGS_TITLE', 'auth' => 'ext_posey/ultimateblog && acl_a_board', 'cat' => array('ACP_ULTIMATEBLOG')),
			),
		);
	}
}