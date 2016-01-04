# phpBB3.1 Extension - Ultimate Blog 
## Extension tree

* Ultimate Blog root
  * acp
    * __main_info.php__ <br>*Add ACP Module*
    * __main_module.php__ <br>*Load "Settings" and "Categories" modules to the Admin Controller*
  * adm
	* style
		* css
			* __ultimateblogacp.css__ <br>*ACP styling file*
		* __acp_ultimateblog_cats.html__ <br>*ACP template file for categories*
		* __acp_ultimateblog_main.html__ <br>*ACP template file for settings*
  * config
	* __routing.yml__ <br>*File that contains all routes*
	* __services.yml__ <br>*File that contains all dependencies for all php files*
	* __tables.yml__ <br>*File that contains all Ultimate Blog database tables*
  * controller
	* __admin_controller.php__ <br>*Controller instance for the ACP modules*
	* __main_controller.php__ <br>*Controller to distribute all routes*
	* __mcp_controller.php__ <br>*Controller instance for the MCP modules*
  * core
	* __blog.php__ <br>*Functions regarding blogs*
	* __category.php__<br>*Functions regarding category*
	* __functions.php__<br>*Functions for general usage*
	* __search.php__<br>*Function for the search*
  * event
	* __listener.php__<br>*Used to hook into listeners in phpBB core*
  * language
	* en
		* email
			* __newblog_notify.txt__ <br>*Notification Email for a new blog entry*
			* __newcomment_notify.txt__ <br>*Notification Email for a new blog comment*
		* __common.php__ <br>*Common language file for Ultimate Blog*
		* __info_acp_ultimateblog.php__ <br>*Language file for the ACP*
		* __info_mcp_ultimateblog.php__ <br>*Language file for the MCP*
  * mcp
	* __main_info.php__<br>*Add MCP Module*
	* __main_module.php__<br>*Load "Open", "Closed" and "Details" modules to the MCP Controller*
  * migrations
	* __migrations__<br>*All migrations for this extensions*
  * notification
	* __subscribe__<br>*Notification information*
