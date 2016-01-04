<?php
/**
*
* @package phpBB Extension - Ultimate Blog
* @copyright (c) 2015 posey
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace posey\ultimateblog\migrations\v1x;

class m5_rss_data extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\posey\ultimateblog\migrations\v1x\m1_first_schema');
	}

	public function update_data()
	{
		return array(
			// Add Config
			array('config.add', array('ub_rss_enabled', 0)),
			array('config.add', array('ub_rss_title', 'Ultimate Blog RSS Feed Title')),
			array('config.add', array('ub_rss_desc', 'Ultimate Blog RSS Feed Description. Keep it to one or two sentences.')),
			array('config.add', array('ub_rss_cat', '')),
			array('config.add', array('ub_rss_copy', '')),
			array('config.add', array('ub_rss_lang', '')),
			array('config.add', array('ub_rss_img', '')),
			array('config.add', array('ub_rss_email', 1)),
		);
	}
}
