<?php
/**
*
* @package phpBB Extension - Ultimate Blog
* @copyright (c) 2015 posey
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace posey\ultimateblog\migrations\v1x;

class m2_first_data extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\posey\ultimateblog\migrations\v1x\m1_first_schema');
	}

	public function update_data()
	{
		return array(
			// Add Config
			array('config.add', array('ub_version', 1.0)),
			array('config.add', array('ub_enabled', 1)),
			array('config.add', array('ub_latest_blogs', 1)),
			array('config.add', array('ub_cutoff', 1500)),
			// Add permission
			array('permission.add', array('u_blog_view', true)),
			array('permission.add', array('u_blog_make', true)),
			array('permission.add', array('u_blog_edit', true)),
			array('permission.add', array('u_blog_rate', true)),
			array('permission.add', array('u_blog_report', true)),
			array('permission.add', array('u_blog_comment_make', true)),
			array('permission.add', array('u_blog_comment_edit', true)),
			array('permission.add', array('u_blog_comment_report', true)),
			array('permission.add', array('m_blog_edit', true)),
			array('permission.add', array('m_blog_delete', true)),
			array('permission.add', array('m_blog_lock', true)),
			array('permission.add', array('m_blog_comment_edit', true)),
			array('permission.add', array('m_blog_comment_delete', true)),
			array('permission.add', array('m_blog_reports', true)),
			array('permission.add', array('a_blog_settings', true)),
			array('permission.add', array('a_blog_categories', true)),
			array('permission.add', array('a_blog_tags', true)),

			// Set permission
			array('permission.permission_set', array('REGISTERED', 'u_blog_view', 'group')),
			array('permission.permission_set', array('REGISTERED', 'u_blog_rate', 'group')),
			array('permission.permission_set', array('REGISTERED', 'u_blog_comment_make', 'group')),
			array('permission.permission_set', array('REGISTERED', 'u_blog_comment_edit', 'group')),
			array('permission.permission_set', array('REGISTERED', 'u_blog_comment_report', 'group')),
			array('permission.permission_set', array('ROLE_MOD_FULL', 'm_blog_edit')),
			array('permission.permission_set', array('ROLE_MOD_FULL', 'm_blog_delete')),
			array('permission.permission_set', array('ROLE_MOD_FULL', 'm_blog_lock')),
			array('permission.permission_set', array('ROLE_MOD_FULL', 'm_blog_comment_edit')),
			array('permission.permission_set', array('ROLE_MOD_FULL', 'm_blog_comment_delete')),
			array('permission.permission_set', array('ROLE_MOD_FULL', 'm_blog_reports')),
			array('permission.permission_set', array('ROLE_ADMIN_FULL', 'a_blog_settings')),
			array('permission.permission_set', array('ROLE_ADMIN_FULL', 'a_blog_categories')),
			array('permission.permission_set', array('ROLE_ADMIN_FULL', 'a_blog_tags')),

			// Add ACP Module
			array('module.add', array('acp', 'ACP_CAT_DOT_MODS', 'ACP_ULTIMATEBLOG')),
			array('module.add', array(
				'acp', 'ACP_ULTIMATEBLOG', array(
					'module_basename'	=> '\posey\ultimateblog\acp\main_module',
					'modes'				=> array('settings', 'categories', 'tags'),
				),
			)),

			// Add First Category
			array('custom', array(
				array(&$this, 'ultimateblog_first_category')
			)),
		);
	}

	public function ultimateblog_first_category()
	{
		if ($this->db_tools->sql_table_exists($this->table_prefix . 'ub_cats'))
		{
			$sql_ary = array(
				'cat_name'	=> 'My first category',
				'cat_desc'	=> 'Do NOT delete this category! Edit and rename it instead!',
			);
			$this->db->sql_multi_insert($this->table_prefix . 'ub_cats', $sql_ary);
		}
	}
}