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
	'ACP_ULTIMATEBLOG'					=> 'Ultimate Blog',
	'ACP_ULTIMATEBLOG_SETTINGS_TITLE'	=> 'Blog Settings',
	'ACP_ULTIMATEBLOG_CATEGORIES_TITLE'	=> 'Blog Categories',
	'ACP_ULTIMATEBLOG_TAGS_TITLE'		=> 'Blog Tags',

	'ACP_UB_CAT_ADD'			=> 'Add Category',
	'ACP_UB_CAT_ADD_EXPLAIN'	=> 'Add a new category that can contain blog entries',
	'ACP_UB_CAT_ADDED'			=> 'The new blog category has been created',
	'ACP_UB_CAT_EDIT'			=> 'Edit Category',
	'ACP_UB_CAT_EDITED'			=> 'The blog category has been edited',
	'ACP_UB_CAT_DELETED'		=> 'The blog category has been deleted',
	'ACP_UB_CAT_DEL_CONFIRM'	=> 'Are you sure you wish to delete this blog category?',
	'ACP_UB_CAT_DEL_FIRST'		=> 'You can <strong>not</strong> delete the first blog category!',
	'ACP_UB_CAT_NAME'			=> 'Category Name',
	'ACP_UB_CAT_NAME_EXPLAIN'	=> 'The category name that can contain blog entries. <strong>NO</strong> BBCode allowed!',
	'ACP_UB_CAT_DESC'			=> 'Category Description',
	'ACP_UB_CAT_DESC_EXPLAIN'	=> 'The category description displayed when viewing the category. BBCode is <strong>enabled</strong>!',
	'ACP_UB_CAT_BLOGS'			=> 'Blog Count',
	'ACP_UB_CAT_NOT_EXIST'		=> 'That category doesn\'t exist!',
	'ACP_UB_CATS_EXPLAIN'		=> 'A list of all categories available to place blog entries in',

	'ACP_UB_SETTINGS_SAVED'				=> 'Ultimate Blog Settings have been changed successfully',
	'ACP_UB_SETTINGS_EXPLAIN'			=> 'Alter the settings for the Ultimate Blog extension in here',
	'ACP_UB_SETTINGS_ENABLE'			=> 'Enable Ultimate Blog',
	'ACP_UB_SETTINGS_DISABLED'			=> 'Ultimate Blog is currently disabled!',
	'ACP_UB_SETTINGS_BLOGS_PER_PAGE'	=> 'Blogs per page',
	'ACP_UB_SETTINGS_BLOGS_PER_PAGE_EXPLAIN'	=> 'The amount of blogs that should be displayed per page.',
	'ACP_UB_SETTINGS_CUTOFF'			=> 'Blog text cut off',
	'ACP_UB_SETTINGS_CUTOFF_EXPLAIN'	=> 'Enter after how many characters the blog post should be cut off for display in overview pages.<br>Users will then have to visit the blog to read the full story.<br>Set to <strong>0</strong> to display the full blog.',
	'ACP_UB_SETTINGS_SHOW_DESC'			=> 'Show blog description',
	'ACP_UB_SETTINGS_SHOW_DESC_EXPLAIN'	=> 'This will show the blog description instead of the cut off text, when the cut off limit has been reached.',

	'LOG_UB_SETTINGS_CHANGED'	=> 'Ultimate Blog Settings have been changed',
	'LOG_UB_CAT_ADD'			=> 'Ultimate Blog Category has been created: %1$s',
	'LOG_UB_CAT_EDIT'			=> 'Ultimate Blog Category has been edited: %1$s',
	'LOG_UB_CAT_DELETE'			=> 'Ultimate Blog Category has been deleted: %1$s',

	// Permissions
	'ACL_CAT_ULTIMATEBLOG'			=> 'Ultimate Blog',
	'ACL_U_BLOG_VIEW'				=> 'Can view blogs',
	'ACL_U_BLOG_MAKE'				=> 'Can create blogs',
	'ACL_U_BLOG_EDIT'				=> 'Can edit own blogs',
	'ACL_U_BLOG_RATE'				=> 'Can rate blogs',
	'ACL_U_BLOG_REPORT'				=> 'Can report blogs',
	'ACL_U_BLOG_COMMENT_MAKE'		=> 'Can comment on blogs',
	'ACL_U_BLOG_COMMENT_EDIT'		=> 'Can edit own comments',
	'ACL_U_BLOG_COMMENT_REPORT'		=> 'Can report comments',
	'ACL_M_BLOG_EDIT'				=> 'Can edit blogs',
	'ACL_M_BLOG_DELETE'				=> 'Can delete blogs',
	'ACL_M_BLOG_LOCK'				=> 'Can lock blogs',
	'ACL_M_BLOG_COMMENT_EDIT'		=> 'Can edit comments',
	'ACL_M_BLOG_COMMENT_DELETE'		=> 'Can delete comments',
	'ACL_M_BLOG_REPORTS'			=> 'Can manage reported blogs/comments',
	'ACL_A_BLOG_SETTINGS'			=> 'Can manage blog settings',
	'ACL_A_BLOG_CATEGORIES'			=> 'Can manage blog categories',
	'ACL_A_BLOG_TAGS'				=> 'Can manage blog tags',
));
