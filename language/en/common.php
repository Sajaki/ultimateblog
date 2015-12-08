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
	'BLOG'				=> 'Blog',
	'BLOG_ADD'			=> 'Add blog',
	'BLOG_ADDED'		=> 'Your blog has been succesfully added',
	'BLOG_AUTHOR'		=> 'Author',
	'BLOG_CATS'			=> 'Blog Categories',
	'BLOG_CHOOSE_CAT'	=> 'Choose a category..',
	'BLOG_FOR_CAT'		=> 'Blogs for category',
	'BLOG_IN'			=> 'in', // example: Someone posted “in” My Awesome Category “on” Dec 12th, 2015
	'BLOG_ON'			=> 'on', // See example above
	'BLOG_NEW'			=> 'New blog entry',
	'BLOG_POSTED_BY'	=> 'Posted by',
	'BLOG_POSTED_ON'	=> 'Posted on',
	'BLOG_POSTS'		=> 'Blog Posts',
	'BLOG_READ_FULL'	=> 'Read the full blog',
	'BLOG_VIEW'			=> 'View your blog',
	
	'CAT_NO_BLOGS'		=> 'This category does not have any blogs yet',
	'CATEGORY'			=> 'Category',
	'CATEGORIES'		=> 'Categories',
	
	'LOG_BLOG_ADDED'	=> 'New blog entry: %1$s',
));