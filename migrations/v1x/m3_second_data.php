<?php
/**
*
* @package phpBB Extension - Ultimate Blog
* @copyright (c) 2015 posey
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace posey\ultimateblog\migrations\v1x;

class m3_second_data extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\posey\ultimateblog\migrations\v1x\m1_first_schema');
	}

	public function update_data()
	{
		return array(
			// Add Config
			array('config.add', array('ub_show_desc', 1)),

			// Add MCP Module
			array('module.add', array(
				'mcp', 'MCP_REPORTS', array(
					'module_basename'	=> '\posey\ultimateblog\mcp\main_module',
					'modes'				=> array('open', 'details', 'closed'),
				),
			)),
		);
	}
}