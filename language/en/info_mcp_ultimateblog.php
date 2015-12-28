<?php
/**
*
* @package phpBB Extension - Ultimate Blog
* @copyright (c) 2015 posey
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* DO NOT CHANGE
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine
//
// Some characters you may want to copy&paste:
// ’ » “ ” …
//
// Merge the following language entries into the lang array

$lang = array_merge($lang, array(
	'MCP_BLOG_REPORTS'			=> 'Blog reports',
	'MCP_BLOG_REPORTS_OPEN'		=> 'Open blog reports',
	'MCP_BLOG_REPORTS_CLOSED'	=> 'Closed blog reports',
	'MCP_BLOG_REPORTS_DETAILS'	=> 'Blog Report details',

	'BLOG_AND_REPORTER'		=> 'Reporter & Blog',
	'BLOG_REPORT_CLOSED'	=> 'The blog report has been closed.',
	'BLOG_REPORT_DELETED'	=> 'The blog report has been deleted.',
	'BLOG_REPORT_NOT_BLOG'	=> 'The requested report is not a blog report.',
	'BLOG_REPORT_NOT_EXIST'	=> 'The requested report does not exist.',
	'BLOG_REPORTED_BY'		=> 'Reported by',
	'BLOG_REPORTS_EMPTY_ID'	=> 'The requested ID(s) do not exist.',
	'BLOG_REPORTS_EXPLAIN'	=> 'This is a list of all reported blog comments which are still to be handled.',
	'BLOG_REPORTS_LATEST'	=> 'Latest 5 Blog Reports',
	'BLOG_REPORTS_OPEN_NONE'	=> 'There are no open blog reports.',
	'BLOG_REPORTS_CLOSED_NONE'	=> 'There are no closed blog reports.',
	'BLOG_REPORTS_RETURN'		=> 'Return to blog reports',
	'BLOG_REPORTS_TOTAL' => array(
		0	=> 'There are no blog reports to review.',
		1	=> 'There is <strong>%1$s</strong> blog report to review.',
		2	=> 'There are <strong>%2$s</strong> blog reports to review.',
	),

	'AUTH_BLOG_REPORTS'		=> 'You are not authorised to manage blog reports.',

	'LOG_BLOG_REPORT_CLOSED'	=> '<strong>Closed a blog report</strong><br>» Re: %1$s',
	'LOG_BLOG_REPORT_DELETED'	=> '<strong>Deleted a blog report</strong><br>» Re: %1$s',

	'VIEW_BLOG'			=> 'View blog',
	'VIEW_BLOG_COMMENT'	=> 'View comment',
));
