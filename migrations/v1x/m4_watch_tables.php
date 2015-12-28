<?php
/**
*
* @package phpBB Extension - Ultimate Blog
* @copyright (c) 2015 posey
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace posey\ultimateblog\migrations\v1x;

class m4_watch_tables extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\posey\ultimateblog\migrations\v1x\m1_first_schema');
	}

	public function update_schema()
	{
		return array(
			'add_tables'	=> array(
				$this->table_prefix . 'ub_watch_blog'	=> array(
					'COLUMNS'	=> array(
						'blog_id'			=> array('UINT:10', 0),
						'user_id'			=> array('UINT:10', 0),
					),
				),
				$this->table_prefix . 'ub_watch_cat'	=> array(
					'COLUMNS'	=> array(
						'cat_id'			=> array('UINT:10', 0),
						'user_id'			=> array('UINT:10', 0),
					),
				),
			),
			'add_columns'	=> array(
				$this->table_prefix . 'users' => array(
					'ub_watch_all' 			=> array('BOOL', 0),
				),
				$this->table_prefix . 'reports' => array(
					'blog_comment_id'		=> array('UINT:10', 0),
				),
				$this->table_prefix . 'ub_comments' => array(
					'comment_reported'		=> array('BOOL', 0),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'ub_watch_blog',
				$this->table_prefix . 'ub_watch_cat',
			),
			'drop_columns' => array(
				$this->table_prefix . 'users'	=> array(
					'ub_watch_all',
				),
				$this->table_prefix . 'reports'	=> array(
					'blog_comment_id',
				),
			),
		);
	}
}
