<?php
/**
*
* @package phpBB Extension - Ultimate Blog
* @copyright (c) 2015 posey
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace posey\ultimateblog;

/**
* Ultimate Blog: Extension class
*/

class ext extends \phpbb\extension\base
{
	function enable_step($old_state)
	{
		switch ($old_state)
		{
			case '':
				$phpbb_notifications = $this->container->get('notification_manager');
				$phpbb_notifications->enable_notifications('posey.ultimateblog.notification.type.subscribe');

				return 'notifications';
			break;

			default:
				return parent::enable_step($old_state);
			break;
		}
	}

	function disable_step($old_state)
	{
		switch ($old_state)
		{
			case '':
				$phpbb_notifications = $this->container->get('notification_manager');
				$phpbb_notifications->disable_notifications('posey.ultimateblog.notification.type.subscribe');

				return 'notifications';
			break;

			default:
				return parent::disable_step($old_state);
			break;
		}
	}

	function purge_step($old_state)
	{
		switch ($old_state)
		{
			case '':
				try
				{
					$phpbb_notifications = $this->container->get('notification_manager');
					$phpbb_notifications->purge_notifications('posey.ultimateblog.notification.type.subscribe');
				}

				catch (\phpbb\notification\exception $e)
				{
					// continue
				}

				return 'notifications';
			break;

			default:
				return parent::purge_step($old_state);
			break;
		}
	}
}