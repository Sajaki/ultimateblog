<?php
/**
*
* @package phpBB Extension - Ultimate Blog
* @copyright (c) 2015 posey
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace posey\ultimateblog\notification;

/**
* Ultimate Blog: Subscribe notification class
*/

class subscribe extends \phpbb\notification\type\base
{
	protected $helper;

	/**
	* Notification Type: Subscribe | Constructor
	*
	* @param \phpbb\user_loader 					$user_loader
	* @param \phpbb\db\driver\driver_interface 		$db
	* @param \phpbb\cache\driver\driver_interface 	$cache
	* @param \phpbb\user 							$user
	* @param \phpbb\auth\auth 						$auth
	* @param \phpbb\config\config 					$config
	* @param \phpbb\controller\helper 				$helper
	* @param string 								$phpbb_root_path
	* @param string 								$php_ext
	* @param string 								$notification_types_table
	* @param string 								$notifications_table
	* @param string 								$user_notifications_table
	* @return \phpbb\notification\type\base
	*/

	public function __construct(\phpbb\user_loader $user_loader, \phpbb\db\driver\driver_interface $db, \phpbb\cache\driver\driver_interface $cache, $user, \phpbb\auth\auth $auth, \phpbb\config\config $config, \phpbb\controller\helper $helper, $phpbb_root_path, $php_ext, $notification_types_table, $notifications_table, $user_notifications_table)
	{
		$this->helper = $helper;
		parent::__construct($user_loader, $db, $cache, $user, $auth, $config, $phpbb_root_path, $php_ext, $notification_types_table, $notifications_table, $user_notifications_table);
	}

	/**
	* Get notification type name
	*
	* @return string
	*/
	public function get_type()
	{
		return 'posey.ultimateblog.notification.type.subscribe';
	}

	public static $notification_option = [
		'lang'		=> 'NOTIFICATION_BLOG_SUBSCRIPTION',
		'group'		=> 'NOTIFICATION_GROUP_POSTING',
	];

	/**
	* Is this type available to the current user (defines whether or not it will be shown in the UCP Edit notification options)
	*
	* @return bool True/False whether or not this is available to the user
	*/
	public function is_available()
	{
		return true;
	}

	/**
	* Get the id of the notification
	*
	* @param array $data The data for the blog subscription
	* @return int Id of the notification
	*/
	public static function get_item_id($data)
	{
		return (int) $data['child_id'];
	}

	/**
	* Get the id of the parent
	*
	* @param array $data The data for the blog subscription
	* @return int Id of the parent
	*/
	public static function get_item_parent_id($data)
	{
		return (int) $data['parent_id'];
	}

	/**
	* Find the users who will receive notifications
	*
	* @param array $data The type specific blog subscription
	* @param array $options Options for finding users for notification
	* @return array Array of user_ids
	*/
	public function find_users_for_notification($data, $options = [])
	{
		$users = [];

		$data['user_ids'] = (!is_array($data['user_ids'])) ? [$data['user_ids']] : $data['user_ids'];

		foreach ($data['user_ids'] as $user_id)
		{
			$users[$user_id] = [''];
		}

		// Don't send notification to the poster self
		unset($users[$data['poster_id']]);

		return $users;
	}

	/**
	* Users needed to query before this notification can be displayed
	*
	* @return array Array of user_ids
	*/
	public function users_to_query()
	{
		return [];
	}

	/**
	* Get the user's avatar
	*/
	public function get_avatar()
	{
		$this->user_loader->load_users([$this->get_data('poster_id')]);
		return $this->user_loader->get_avatar($this->get_data('poster_id'));
	}

	/**
	* Get the HTML formatted title of this notification
	*
	* @return string
	*/
	public function get_title()
	{
		return $this->user->lang('NOTIFICATION_NEW_' . strtoupper($this->get_data('mode')), $this->get_data('blog_title'));
	}

	/**
	* Get the url to this item
	*
	* @return string URL
	*/
	public function get_url()
	{
		$url = $this->helper->route('posey_ultimateblog_blog_display', ['blog_id' => (int) $this->get_data('blog_id')]);

		if ($this->get_data('mode') == 'comment')
		{
			$url .= '#c' . $this->item_id;
		}

		return $url;
	}

	/**
	* Get email template
	*
	* @return string|bool
	*/
	public function get_email_template()
	{
		return false;
	}

	/**
	* Get email template variables
	*
	* @return array
	*/
	public function get_email_template_variables()
	{
		return [];
	}

	/**
	* Function for preparing the data for insertion in an SQL query
	* (The service handles insertion)
	*
	* @param array $data The data for the blog subscription
	*					 'mode' can be:
	*					 -- 'comment'	=> new comment
	*					 -- 'blog'		=> new blog
	* @param array $pre_create_data Data from pre_create_insert_array()
	*
	* @return array Array of data ready to be inserted into the database
	*/
	public function create_insert_array($data, $pre_create_data = [])
	{
		$this->set_data('blog_title', $data['blog_title']);
		$this->set_data('poster_id', $data['poster_id']);
		$this->set_data('blog_id', $data['blog_id']);
		$this->set_data('mode', $data['mode']);

		return parent::create_insert_array($data, $pre_create_data);
	}
}
