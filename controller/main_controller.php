<?php
/**
*
* @package phpBB Extension - Ultimate Blog
* @copyright (c) 2015 posey
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace posey\ultimateblog\controller;

/**
* Main cotnroller
*/
class main_controller
{
	# @var \phpbb\user
	protected $user;

	# @var	\phpbb\config\config
	protected $config;

	# @var \phpbb\controller\helper
	protected $helper;

	# @var \phpbb\request\request
	protected $request;

	# @var \phpbb\template\template
	protected $template;

	# @var string phpBB root path
	protected $phpbb_root_path;

	# @var string phpEx
	protected $php_ext;

	# @var \posey\ultimateblog\core\blog
	protected $blog;

	# @var \posey\ultimateblog\core\category
	protected $category;

	# @var \posey\ultimateblog\core\functions
	protected $functions;

	# @var \posey\ultimateblog\core\search
	protected $search;

	/**
	* Constructor
	*
	* @param \phpbb\user						$user				User object
	* @param \phpbb\config\config				$config				Config object
	* @param \phpbb\controller\helper			$helper				Controller helper object
	* @param \phpbb\request\request				$request			Request object
	* @param \phpbb\template\template			$template			Template object
	* @param string								$phpbb_root_path	phpBB root path
	* @param string								$php_ext			phpEx
	* @param \posey\ultimateblog\core\blog		$blog				Ultimate Blog blog functions
	* @param \posey\ultimateblog\core\category	$category			Ultimate Blog category functions
	* @param \posey\ultimateblog\core\functions	$functions			Ultimate Blog general functions
	* @param \posey\ultimateblog\core\search	$search				Ultimate Blog search object
	* @access public
	*/
	public function __construct(
		\phpbb\user $user,
		\phpbb\config\config $config,
		\phpbb\controller\helper $helper,
		\phpbb\request\request $request,
		\phpbb\template\template $template,
		$phpbb_root_path,
		$php_ext,
		$blog,
		$category,
		$functions,
		$search)
	{
		$this->user		= $user;
		$this->config	= $config;
		$this->helper	= $helper;
		$this->request	= $request;
		$this->template = $template;
		$this->phpbb_root_path	= $phpbb_root_path;
		$this->php_ext			= $php_ext;
		$this->blog				= $blog;
		$this->category			= $category;
		$this->functions		= $functions;
		$this->search			= $search;
	}

	/**
	* Ultimate Blog | Main | Blog
	* all actions regarding a blog
	*/
	public function blog()
	{
		// Requests
		$action		= $this->request->variable('action', '');
		$blog_id	= (int) $this->request->variable('blog_id', 0);

		// When blog is disabled, redirect users back to the forum index
		if (empty($this->config['ub_enabled']))
		{
			redirect(append_sid("{$this->root_path}index.{$this->php_ext}"));
		}

		// Perform any actions submitted by the user,
		// Otherwise default to showing the latest blogs overview
		switch($action)
		{
			case 'add':
				// Load the add handle in the blog functions
				$this->blog->add();

				// Generate the page template
				return $this->helper->render('/blog/blog_add.html', $this->user->lang('BLOG_ADD'));
			break;

			case 'edit':
				// Load the edit handle in the blog functions
				$this->blog->edit($blog_id);

				// Generate the page template
				return $this->helper->render('/blog/blog_add.html', $this->user->lang('BLOG_EDIT'));
			break;

			case 'delete':
				// Load the delete handle in the blog functions
				$this->blog->delete($blog_id);
			break;

			case 'rate':
				// Load the rate handle in the blog functions
				$this->blog->rate($blog_id);
			break;

			case 'subscribe':
				// Request the mode to which the user wishes to subscribe
				$mode = $this->request->variable('mode', 'all');

				// Load the subscribe handle in the general functions
				$this->functions->subscribe($mode);
			break;

			case 'unsubscribe':
				// Request the mode to which the user wishes to unsubscribe
				$mode = $this->request->variable('mode', 'all');

				// Load the unsubscribe handle in the general functions
				$this->functions->unsubscribe($mode);
			break;

			default:
				// Load the latest handle in the blog functions
				$this->blog->latest();

				// Generate the page template
				return $this->helper->render('/blog/blogs_latest.html', $this->user->lang('BLOG'));
			break;
		}
	}

	/**
	* Ultimate Blog | Main | Blog: display
	* displaying of one specific blog entry
	*/
	public function blog_display($blog_id)
	{
		// Load the display handle in the blog functions
		$this->blog->display($blog_id);

		// Generate the page template
		return $this->helper->render('/blog/blog.html', '');
	}

	/**
	* Ultimate Blog | Main | Comment
	* all actions regarding the blog comments
	*/
	public function comment($blog_id)
	{
		// Requests
		$action = $this->request->variable('action', '');
		$comment_id = (int) $this->request->variable('comment_id', 0);

		switch($action)
		{
			case 'edit':
				// Load the comment edit handle in the general functions
				$this->functions->comment_edit($blog_id, $comment_id);

				// Generate the page template
				return $this->helper->render('/misc/comment_edit.html', $this->user->lang('BLOG_COMMENT_EDIT'));
			break;

			case 'delete':
				// Load the comment delete handle in the general functions
				$this->functions->comment_delete($blog_id, $comment_id);
			break;

			case 'report':
				// Load the comment report handle in the general functions
				$this->functions->comment_report($blog_id, $comment_id);

				// Generate the page template
				return $this->helper->render('report_body.html', $this->user->lang('BLOG_COMMENT_REPORT'));
			break;
		}
	}

	/**
	* Ultimate Blog | Main | Category
	* display of one specific blog category
	*/
	public function category($cat_id)
	{
		// Load the display handle in the category functions
		$this->category->display($cat_id);

		// Generate the page template
		return $this->helper->render('/category/category.html');
	}

	/**
	* Ultimate Blog | Main | Category
	* display of the overview of blog categories
	*/
	public function categories()
	{
		// Load the overview handle in the category functions
		$this->category->overview();

		// Generate the page template
		return $this->helper->render('/category/categories.html', $this->user->lang('BLOG_CATS'));
	}

	/**
	* Ultimate Blog | Main | Archive
	*/
	public function archive($year, $month)
	{
		// Load the archive handle in the general functions
		$this->functions->archive($year, $month);

		// Generate the page template
		return $this->helper->render('/misc/archive.html');
	}

	/**
	* Ultimate Blog | Main | Search
	*/
	public function search()
	{
		// Load the blog search handle in the search functions
		$this->search->blog_search();

		// Generate the page template
		return $this->helper->render('/search/blog_search.html', $this->user->lang('BLOG_SEARCH'));
	}

	/**
	* Ultimate Blog | Main | RSS Feed
	*/
	public function rss()
	{
		// Load the rss feed handle in the general functions
		$this->functions->rss_feed();
	}
}
