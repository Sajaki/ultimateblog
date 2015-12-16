<?php
/**
*
* @package phpBB Extension - Ultimate Blog
* @copyright (c) 2015 posey
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace posey\ultimateblog\migrations\v1x;

class m1_first_schema extends \phpbb\db\migration\migration
{
	public function update_schema()
	{
		return array(
			'add_tables'	=> array(
				$this->table_prefix . 'ub_blogs'	=> array(
					'COLUMNS'	=> array(
						'blog_id'			=> array('UINT:10', null, 'auto_increment'),
						'cat_id'			=> array('UINT:10', 0),
						'blog_subject'		=> array('VCHAR:100', null),
						'blog_text'			=> array('TEXT', null),
						'poster_id'			=> array('UINT', 0),
						'post_time'			=> array('INT:11', 0),
						'enable_bbcode'		=> array('TINT:1', 1),
						'enable_smilies'	=> array('TINT:1', 1),
						'enable_magic_url'	=> array('TINT:1', 1),
						'enable_comments'	=> array('TINT:1', 1),
						'bbcode_uid'		=> array('VCHAR:8', null),
						'bbcode_bitfield'	=> array('VCHAR', null),
						'blog_edit_time'	=> array('INT:11', 0),
						'blog_edit_user'	=> array('UINT', 0),
						'blog_edit_reason'	=> array('VCHAR', null),
						'blog_edit_count'	=> array('USINT', 0),
						'blog_edit_locked'	=> array('TINT:1', 0),
						'blog_description'	=> array('VCHAR:175', ''),
					),
					'PRIMARY_KEY'	=> 'blog_id',
				),
				$this->table_prefix . 'ub_cats'	=> array(
					'COLUMNS'	=> array(
						'cat_id'			=> array('UINT:10', null, 'auto_increment'),
						'cat_name'			=> array('VCHAR_UNI', ''),
						'cat_desc'			=> array('VCHAR_UNI', ''),
						'enable_bbcode'		=> array('TINT:1', 1),
						'enable_smilies'	=> array('TINT:1', 1),
						'enable_magic_url'	=> array('TINT:1', 1),
						'bbcode_uid'		=> array('VCHAR:8', null),
						'bbcode_bitfield'	=> array('VCHAR', null),
					),
					'PRIMARY_KEY'	=> 'cat_id',
				),
				$this->table_prefix . 'ub_comments'	=> array(
					'COLUMNS'	=> array(
						'comment_id'		=> array('UINT:10', null, 'auto_increment'),
						'comment_text'		=> array('TEXT', null),
						'blog_id'			=> array('UINT:10', null),
						'poster_id'			=> array('UINT', 0),
						'post_time'			=> array('INT:11', 0),
						'bbcode_uid'		=> array('VCHAR:9', null),
						'bbcode_bitfield'	=> array('VCHAR', null),
						'bbcode_options'	=> array('USINT', null),
					),
					'PRIMARY_KEY'	=> 'comment_id',
				),
				$this->table_prefix . 'ub_rating' => array (
					'COLUMNS'	=> array(
						'rating_id'			=> array('UINT:10', null, 'auto_increment'),
						'blog_id'			=> array('UINT:10', null),
						'user_id'			=> array('UINT:10', null),
						'rating'			=> array('TINT:5', null),
						'rate_time'			=> array('INT:11', 0),
					),
					'PRIMARY_KEY'	=> 'rating_id',
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'ub_blogs',
				$this->table_prefix . 'ub_cats',
				$this->table_prefix . 'ub_comments',
				$this->table_prefix . 'ub_rating',
			),
		);
	}
}