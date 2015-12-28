<?php
/**
*
* @package phpBB Extension - Ultimate Blog
* @copyright (c) 2015 posey
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace posey\ultimateblog\controller;

class main_controller
{
	protected $user;
	protected $config;
	protected $helper;
	protected $request;
	protected $phpbb_root_path;
	protected $php_ext;
	protected $blog;
	protected $category;
	protected $functions;
	protected $search;

	/**
	* Constructor
	*/
	public function __construct(
		\phpbb\user $user,
		\phpbb\config\config $config,
		\phpbb\controller\helper $helper,
		\phpbb\request\request $request,
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
		$this->phpbb_root_path	= $phpbb_root_path;
		$this->php_ext			= $php_ext;
		$this->blog				= $blog;
		$this->category			= $category;
		$this->functions		= $functions;
		$this->search			= $search;
	}

	public function blog()
	{
		$action = $this->request->variable('action', '');
		$blog_id = (int) $this->request->variable('blog_id', 0);

		// When blog is disabled, redirect users back to the forum index
		if (empty($this->config['ub_enabled']))
		{
			redirect(append_sid("{$this->root_path}index.{$this->php_ext}"));
		}

		switch($action)
		{
			case 'add':
				$this->blog->add();
				// Generate the page template
				return $this->helper->render('blog_add.html', $this->user->lang('BLOG_ADD'));
			break;

			case 'edit':
				$this->blog->edit($blog_id);
				// Generate the page template
				return $this->helper->render('blog_add.html', $this->user->lang('BLOG_EDIT'));
			break;

			case 'delete':
				$this->blog->delete($blog_id);
			break;

			case 'rate':
				$this->blog->rate($blog_id);
			break;

			case 'subscribe':
				$mode = $this->request->variable('mode', 'all');
				$this->functions->subscribe($mode);
			break;

			case 'unsubscribe':
				$mode = $this->request->variable('mode', 'all');
				$this->functions->unsubscribe($mode);
			break;

			default:
				$this->blog->latest();
				// Generate the page template
				return $this->helper->render('blogs_latest.html', $this->user->lang('BLOG'));
			break;
		}
	}

	public function blog_display($blog_id)
	{
		$this->blog->display($blog_id);
		// Generate the page template
		return $this->helper->render('blog.html');
	}

	public function comment($blog_id)
	{
		$action = $this->request->variable('action', '');
		$comment_id = (int) $this->request->variable('comment_id', 0);

		switch($action)
		{
			case 'edit':
				$this->functions->comment_edit($blog_id, $comment_id);
				// Generate the page template
				return $this->helper->render('comment_edit.html', $this->user->lang('BLOG_COMMENT_EDIT'));
			break;

			case 'delete':
				$this->functions->comment_delete($blog_id, $comment_id);
			break;

			case 'report':
				$this->functions->comment_report($blog_id, $comment_id);
				// Generate the page template
				return $this->helper->render('report_body.html', $this->user->lang('BLOG_COMMENT_REPORT'));
			break;
		}
	}

	public function category($cat_id)
	{
		$this->category->display($cat_id);
		// Generate the page template
		return $this->helper->render('category.html');
	}

	public function categories()
	{
		$this->category->overview();
		// Generate the page template
		return $this->helper->render('categories.html', $this->user->lang('BLOG_CATS'));
	}

	public function archive($year, $month)
	{
		$this->functions->archive($year, $month);
		// Generate the page template
		return $this->helper->render('archive.html');
	}

	public function search()
	{
		$this->search->blog_search();
		// Generate the page template
		return $this->helper->render('blog_search.html', $this->user->lang('BLOG_SEARCH'));
	}
}
