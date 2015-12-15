<?php
/**
*
* @package phpBB Extension - Ultimate Blog
* @copyright (c) 2015 posey
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace posey\ultimateblog\core;

class comment
{
	protected $template;
	protected $db;
	protected $helper;
	protected $user;
	protected $config;
	protected $auth;
	protected $phpbb_root_path;
	protected $php_ext;
	protected $ub_blogs_table;
	protected $ub_comments_table;

	/**
	* Constructor
	*/
	public function __construct(
		\phpbb\template\template $template,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\controller\helper $helper,
		\phpbb\user $user,
		\phpbb\config\config $config,
		\phpbb\auth\auth $auth,
		$phpbb_root_path,
		$php_ext,
		$ub_blogs_table,
		$ub_comments_table)
	{
		$this->template				= $template;
		$this->db					= $db;
		$this->helper				= $helper;
		$this->user					= $user;
		$this->config				= $config;
		$this->auth					= $auth;
		$this->phpbb_root_path		= $phpbb_root_path;
		$this->php_ext				= $php_ext;
		$this->ub_blogs_table		= $ub_blogs_table;
		$this->ub_comments_table	= $ub_comments_table;
	}

}