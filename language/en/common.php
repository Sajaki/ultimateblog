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
	'BLOG'					=> 'Blog',
	'BLOG_ADD'				=> 'Add blog',
	'BLOG_ADDED'			=> 'Your blog has been succesfully added',
	'BLOG_ARCHIVE'			=> 'Archive',
	'BLOG_ARCHIVE_COUNT'	=> array(
		1 => '%d archive',
		2 => '%d archives',
	),
	'BLOG_ARCHIVE_NO_BLOGS'	=> 'There are no blogs for this period',
	'BLOG_AUTHOR'			=> 'Author',
	'BLOG_BACK'				=> 'Back to the blog page',
	'BLOG_BLOG_COUNT'	=> array(
		1 => '%d blog',
		2 => '%d blogs',
	),
	'BLOG_CATS'				=> 'Blog Categories',
	'BLOG_CATS_COUNT'	=> array(
		1 => '%d category',
		2 => '%d categories',
	),
	'BLOG_CHOOSE_CAT'		=> 'Choose a category..',
	'BLOG_COMMENT'			=> 'Comment',
	'BLOG_COMMENTS'			=> 'Comments',
	'BLOG_COMMENTS_COUNT'	=> array(
		1 => '%d comment',
		2 => '%d comments',
	),
	'BLOG_COMMENT_DELETED'	=> 'The blog comment has been successfully deleted.',
	'BLOG_COMMENT_DEL_CONFIRM'	=> 'Are you sure you wish to delete this blog comment?',
	'BLOG_COMMENT_EDIT'			=> 'Edit blog comment',
	'BLOG_COMMENT_EDITED'		=> 'The blog comment has been successfully edited.',
	'BLOG_COMMENT_EMPTY'		=> 'The entered comment is too short.',
	'BLOG_COMMENT_NOT_EXIST' 	=> 'The requested blog comment does not exist..',
	'BLOG_COMMENT_VIEW'			=> 'View your blog comment',
	'BLOG_COMMENTS_FURTHER'		=> 'Further comments',
	'BLOG_COMMENTS_NONE'		=> 'No comments have been made for this blog thusfar!',
	'BLOG_COMMENTS_DISABLED' 	=> 'have been disabled for this blog!',
	'BLOG_DELETE'			=> 'Delete blog',
	'BLOG_DELETED'			=> 'The blog has been successfully deleted',
	'BLOG_DELETE_CONFIRM'	=> 'Are you sure you wish to delete this blog?',
	'BLOG_DESCRIPTION'		=> 'Blog Description',
	'BLOG_EDIT'				=> 'Edit blog',
	'BLOG_EDITED'			=> 'The blog has been successfully edited',
	'BLOG_EDIT_COUNT'		=> array(
		1	=> 'This blog has been edited %1$s time in total', // singular
		2	=> 'This blog has been edited %1$s times in total', // plural
	),
	'BLOG_EDIT_LAST'	=> 'This blog was last edited by %1$s on %2$s', // 1 = user | 2 = date
	'BLOG_EDIT_LOCKED'	=> 'This blog has been locked from any further editing and you\'re not authorised to edit it anymore!',
	'BLOG_EDIT_REASON'	=> 'Reason for editing',
	'BLOG_FOR_CAT'		=> 'Blogs for category',
	'BLOG_IN'			=> 'in', // example: Posted by Someone “in” My Awesome Category “on” Dec 12th, 2015
	'BLOG_ON'			=> 'on', // See example above
	'BLOG_NEW'			=> 'New blog entry',
	'BLOG_NOT_EXIST'	=> 'The requested blog does not exist..',
	'BLOG_POSTED_BY'	=> 'Posted by',
	'BLOG_POSTED_ON'	=> 'Posted on',
	'BLOG_POSTS'		=> 'Blog Posts',
	'BLOG_RATE'			=> 'Rate this blog',
	'BLOG_RATE_NONE'	=> 'This blog hasn\'t been rated yet!',
	'BLOG_RATE_USERS'		=> array(
		1	=> 'Based on <span itemprop="ratingCount">%1$s</span> rating',
		2	=> 'Based on <span itemprop="ratingCount">%1$s</span> ratings',
	),
	'BLOG_RATED'			=> array(
		1	=> 'Thank you for rating this blog with %1$s star',
		2	=> 'Thank you for rating this blog with %1$s stars',
	),
	'BLOG_RATED_ALREADY'	=> array(
		1	=> 'You\'ve already rated this blog with %1$s star!',
		2	=> 'You\'ve already rated this blog with %1$s stars!',
	),
	'BLOG_RATING'			=> 'Blog rating',
	'BLOG_READ_FULL'		=> 'Read the full blog',
	'BLOG_VIEW'				=> 'View your blog',

	'AUTH_BLOG_ADD'			=> 'You\'re not authorised to add a new blog.',
	'AUTH_BLOG_COMMENT_ADD'	=> 'You\'re not authorised to add a comment to this blog.',
	'AUTH_BLOG_DELETE'		=> 'You\'re not authorised to delete this blog.',
	'AUTH_BLOG_EDIT'		=> 'You\'re not authorised to edit this blog.',
	'AUTH_BLOG_EDIT_ELSE'	=> 'You\'re not authorised to edit someone else\'s blog.',
	'AUTH_BLOG_VIEW'		=> 'You\'re not authorised to view a blog.',
	'AUTH_COMMENT_DELETE'	=> 'You\'re not authorised to delete a comment.',
	'AUTH_COMMENT_EDIT'		=> 'You\'re not authorised to edit a blog comment.',
	'AUTH_COMMENT_EDIT_ELSE' => 'You\'re not authorised to edit someone else\'s blog comment.',

	'CAT_NO_BLOGS'		=> 'This category does not have any blogs yet',
	'CATEGORY'			=> 'Category',
	'CATEGORIES'		=> 'Categories',
	'CAT_INVALID'		=> 'You\'ve not selected a category in which this blog should be posted.',

	'ENABLE_COMMENTS'	=> 'Enable Comments',

	'LOCK_EDIT'			=> 'Lock editing',

	'LOG_BLOG_ADDED'	=> 'New blog entry: %1$s',
	'LOG_BLOG_EDITED'	=> 'Blog has been edited: %1$s',
	'LOG_BLOG_DELETED'	=> 'Blog has been deleted: %1$s',
	'LOG_COMMENT_EDITED'	=> 'Blog comment has been edited, comment id: %1$s',

	'PARSE_BBCODE'		=> 'Parse BBCode',
	'PARSE_SMILIES'		=> 'Parse Smilies',
	'PARSE_URLS'		=> 'Parse URL\'s',

	'VIEWONLINE_BLOG'				=> 'Reading blog: %1$s',
	'VIEWONLINE_BLOGS'				=> 'Reading blogs',
	'VIEWONLINE_BLOG_ARCHIVE'		=> 'Viewing blog archive: %1$s',
	'VIEWONLINE_BLOG_COMMENT_EDIT'	=> 'Editing a blog comment for: %1$s',
	'VIEWONLINE_BLOG_CATEGORIES'	=> 'Viewing blog categories',
	'VIEWONLINE_BLOG_CATEGORY'		=> 'Viewing blog category: %1$s',
));
