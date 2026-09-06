<?php
/**********************************************************************
    Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL, 
	as published by the Free Software Foundation, either version 3 
	of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
    See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
***********************************************************************/

	// Self-hosted inline SVGs for the footer (site_footer(), below) - no
	// external icon font/CDN, so the footer never depends on internet
	// access or a third-party request.
	function svg_icon($name)
	{
		$common = "width='16' height='16' viewBox='0 0 24 24' fill='currentColor' xmlns='http://www.w3.org/2000/svg'";
		switch ($name)
		{
			case 'facebook':
				return "<svg $common><path d='M22 12a10 10 0 1 0-11.6 9.9v-7H7.9V12h2.5V9.8c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.5h-1.3c-1.2 0-1.6.8-1.6 1.6V12h2.8l-.4 2.9h-2.4v7A10 10 0 0 0 22 12Z'/></svg>";
			case 'instagram':
				return "<svg $common><path d='M12 2c2.7 0 3.1 0 4.1.1 1 .1 1.7.2 2.3.5.6.2 1.1.6 1.6 1.1.5.5.8 1 1.1 1.6.2.6.4 1.3.5 2.3.1 1 .1 1.4.1 4.1s0 3.1-.1 4.1c-.1 1-.2 1.7-.5 2.3a4.6 4.6 0 0 1-2.7 2.7c-.6.2-1.3.4-2.3.5-1 .1-1.4.1-4.1.1s-3.1 0-4.1-.1c-1-.1-1.7-.2-2.3-.5a4.6 4.6 0 0 1-2.7-2.7c-.2-.6-.4-1.3-.5-2.3C2 15.1 2 14.7 2 12s0-3.1.1-4.1c.1-1 .2-1.7.5-2.3a4.6 4.6 0 0 1 2.7-2.7c.6-.2 1.3-.4 2.3-.5C8.9 2 9.3 2 12 2Zm0 2.7c-2.6 0-2.9 0-4 .1-.8 0-1.3.2-1.6.3-.4.1-.7.3-1 .6-.3.3-.5.6-.6 1-.1.3-.3.8-.3 1.6-.1 1.1-.1 1.4-.1 4s0 2.9.1 4c0 .8.2 1.3.3 1.6.1.4.3.7.6 1 .3.3.6.5 1 .6.3.1.8.3 1.6.3 1.1.1 1.4.1 4 .1s2.9 0 4-.1c.8 0 1.3-.2 1.6-.3.4-.1.7-.3 1-.6.3-.3.5-.6.6-1 .1-.3.3-.8.3-1.6.1-1.1.1-1.4.1-4s0-2.9-.1-4c0-.8-.2-1.3-.3-1.6a2 2 0 0 0-.6-1 2 2 0 0 0-1-.6c-.3-.1-.8-.3-1.6-.3-1.1-.1-1.4-.1-4-.1Zm0 3.6a3.7 3.7 0 1 1 0 7.4 3.7 3.7 0 0 1 0-7.4Zm0 1.9a1.8 1.8 0 1 0 0 3.6 1.8 1.8 0 0 0 0-3.6Zm4.7-2.1a.9.9 0 1 1 0 1.8.9.9 0 0 1 0-1.8Z'/></svg>";
			case 'youtube':
				return "<svg $common><path d='M22 12s0-3-.4-4.5c-.2-.8-.8-1.5-1.6-1.7C18.3 5.3 12 5.3 12 5.3s-6.3 0-8 .5c-.8.2-1.4.9-1.6 1.7C2 9 2 12 2 12s0 3 .4 4.5c.2.8.8 1.4 1.6 1.7 1.7.5 8 .5 8 .5s6.3 0 8-.5c.8-.3 1.4-.9 1.6-1.7.4-1.5.4-4.5.4-4.5ZM10 15V9l5.2 3-5.2 3Z'/></svg>";
			case 'pin':
				return "<svg $common><path d='M12 2a7 7 0 0 0-7 7c0 5.2 7 13 7 13s7-7.8 7-13a7 7 0 0 0-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5Z'/></svg>";
			case 'phone':
				return "<svg $common><path d='M6.6 2c.4 0 .8.3.9.7l1 3.6c.1.4 0 .8-.3 1.1L6.8 9c1 2.2 2.9 4.1 5.1 5.1l1.6-1.4c.3-.3.7-.4 1.1-.3l3.6 1c.4.1.7.5.7.9v3.4c0 1-.9 1.8-1.9 1.7C9.7 19.6 4.4 14.3 3.6 7c-.1-1 .7-1.9 1.7-1.9h1.3Z'/></svg>";
			case 'mail':
				return "<svg $common><path d='M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Zm0 2v.01L12 12l8-5.99V6H4Zm16 12V8.2l-8 6-8-6V18h16Z'/></svg>";
			default:
				return '';
		}
	}

	class renderer
	{
		function get_icon($category)
		{
			global  $path_to_root, $SysPrefs;

			if ($SysPrefs->show_menu_category_icons)
				$img = $category == '' ? 'right.gif' : $category.'.png';
			else	
				$img = 'right.gif';
			return "<img src='$path_to_root/themes/".user_theme()."/images/$img' style='vertical-align:middle;' border='0'>&nbsp;&nbsp;";
		}

		function wa_header()
		{
			page(_($help_context = "Main Menu"), false, true);
		}

		function wa_footer()
		{
			end_page(false, true);
		}

		function menu_header($title, $no_menu, $is_index)
		{
			global $path_to_root, $SysPrefs, $db_connections;
			echo "<table class='callout_main' border='0' cellpadding='0' cellspacing='0'>\n";
			echo "<tr>\n";
			echo "<td colspan='2' rowspan='2'>\n";

			echo "<table class='main_page' border='0' cellpadding='0' cellspacing='0'>\n";
			echo "<tr>\n";
			echo "<td>\n";
			echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
			echo "<tr>\n";
			echo "<td class='quick_menu'>\n"; // tabs

			$indicator = "$path_to_root/themes/".user_theme(). "/images/ajax-loader.gif";
			if (!$no_menu)
			{
				$applications = $_SESSION['App']->applications;
				$local_path_to_root = $path_to_root;
				$sel_app = $_SESSION['sel_app'];
				echo "<table cellpadding='0' cellspacing='0' width='100%'><tr><td>";
				echo "<div class='tabs'>";
				foreach($applications as $app)
				{
                    if ($_SESSION["wa_current_user"]->check_application_access($app))
                    {
                        $acc = access_string($app->name);
                        echo "<a class='".($sel_app == $app->id ? 'selected' : 'menu_tab')
                            ."' href='$local_path_to_root/index.php?application=".$app->id
                            ."'$acc[1]>" .$acc[0] . "</a>";
                    }
				}
				echo "</div>";
				echo "</td></tr></table>";
				// top status bar
				$rimg = "<img src='$path_to_root/themes/".user_theme()."/images/report.png' style='width:14px;height:14px;border:0;vertical-align:middle;' alt='"._('Dashboard')."'>&nbsp;&nbsp;";
				$pimg = "<img src='$local_path_to_root/themes/".user_theme()."/images/preferences.gif' style='width:14px;height:14px; border:0;vertical-align:middle;' alt='"._('Preferences')."'>&nbsp;&nbsp;";
				$limg = "<img src='$local_path_to_root/themes/".user_theme()."/images/lock.gif' style='width:14px;height:14px;border:0;vertical-align:middle;' alt='"._('Change Password')."'>&nbsp;&nbsp;";
				$img = "<img src='$local_path_to_root/themes/".user_theme()."/images/login.gif' style='width:14px;height:14px;border:0;vertical-align:middle;' alt='"._('Logout')."'>&nbsp;&nbsp;";
				$himg = "<img src='$local_path_to_root/themes/".user_theme()."/images/help.gif' style='width:14px;height:14px;border:0;vertical-align:middle;'' alt='"._('Help')."'>&nbsp;&nbsp;";
				echo "<table class='logoutBar'>";
				echo "<tr><td class='headingtext3'>" . $db_connections[user_company()]["name"] . " | " . $_SERVER['SERVER_NAME'] . " | " . $_SESSION["wa_current_user"]->name . "</td>";
				echo "<td class='logoutBarRight'><img id='ajaxmark' src='$indicator' align='center' style='visibility:hidden;' alt='ajaxmark'></td>";
				echo "<td class='logoutBarRight'><a href='$path_to_root/admin/dashboard.php?sel_app=$sel_app'>$rimg" . _("Dashboard") . "</a>&nbsp;&nbsp;&nbsp;\n";
				
				echo "<a class='shortcut' href='$path_to_root/admin/display_prefs.php?'>$pimg" . _("Preferences") . "</a>&nbsp;&nbsp;&nbsp;\n";
				echo "  <a class='shortcut' href='$path_to_root/admin/change_current_user_password.php?selected_id=" . $_SESSION["wa_current_user"]->username . "'>$limg" . _("Change password") . "</a>&nbsp;&nbsp;&nbsp;\n";

				if ($SysPrefs->help_base_url != null)
				{
					echo "<a target = '_blank' onclick=" .'"'."javascript:openWindow(this.href,this.target); return false;".'" '. "href='". help_url()."'>$himg" . _("Help") . "</a>&nbsp;&nbsp;&nbsp;";
				}
				echo "<a class='shortcut' href='$local_path_to_root/access/logout.php?'>$img" . _("Logout") . "</a>&nbsp;&nbsp;&nbsp;";
				echo "</td></tr><tr><td colspan=3>";
				echo "</td></tr></table>";
			}
			echo "</td></tr></table>";

			// Persistent left sidebar (Transactions/Inquiries/Maintenance for
			// the current app), matching TechCloud's layout - core's own
			// menu_header only ever showed this list on the index page itself;
			// on every other page it disappeared. Index page is left alone
			// below since it already renders the same list full-width.
			if (!$no_menu && !$is_index)
			{
				$sel_app = $_SESSION['sel_app'];
				$current_app = isset($_SESSION['App']) ? $_SESSION['App']->get_application($sel_app) : null;
				echo "<table width='100%' cellpadding='0' cellspacing='0'><tr>";
				echo "<td class='knb-sidebar' valign='top'>";
				if ($current_app)
					$this->render_sidebar($current_app);
				echo "</td>";
				echo "<td class='knb-content' valign='top'>";
			}

			if ($no_menu)
			{	// ajax indicator for installer and popups
				echo "<center><table class='tablestyle_noborder'>"
					."<tr><td><img id='ajaxmark' src='$indicator' align='center' style='visibility:hidden;' alt='ajaxmark'></td></tr>"
					."</table></center>";
			} elseif ($title && !$is_index)
			{
				echo "<center><table id='title'><tr><td width='100%' class='titletext'>$title</td>"
				."<td align=right>"
				.(user_hints() ? "<span id='hints'></span>" : '')
				."</td>"
				."</tr></table></center>";
			}
		}

		function menu_footer($no_menu, $is_index)
		{
			global $version, $path_to_root, $Pagehelp, $Ajax, $SysPrefs;

			include_once($path_to_root . "/includes/date_functions.inc");

			if (!$no_menu && !$is_index)
				echo "</td></tr></table>\n"; // closes 'knb-content' td + sidebar row/table

			echo "</td></tr></table>\n"; // 'main_page'
			if ($no_menu == false) // bottom status line
			{
				if ($is_index)
					echo "<table class='bottomBar'>\n";
				else
					echo "<table class='bottomBar2'>\n";
				echo "<tr>";
				if (isset($_SESSION['wa_current_user'])) {
					$phelp = implode('; ', $Pagehelp);
					echo "<td class='bottomBarCell'>" . Today() . " | " . Now() . "</td>\n";
					$Ajax->addUpdate(true, 'hotkeyshelp', $phelp);
					echo "<td id='hotkeyshelp'>".$phelp."</td>";
				}
				echo "</tr></table>\n";
			}
			echo "</td></tr> </table>\n"; // 'callout_main'
			if ($no_menu == false)
				$this->site_footer($path_to_root, $SysPrefs);
		}

		// Reuses the knbgroup.in marketing site's own footer content/layout
		// (logo + tagline + social, Useful Links, Address) instead of FA's
		// plain version/theme line - the "Useful Links" point out to the
		// real marketing site since those pages (about/faq/products/etc.)
		// don't exist inside this admin ERP.
		function site_footer($path_to_root, $SysPrefs)
		{
			$year = date('Y');
			$site = 'https://www.knbgroup.in';
			$img = "$path_to_root/themes/knbgroup/images/brand";

			echo "<div class='knb-site-footer'>";
			echo "<div class='knb-site-footer-cols'>";

			// Column 1: logos, about blurb, social
			echo "<div class='knb-site-footer-col'>";
			echo "<div class='knb-site-footer-logos'>";
			echo "<img src='$img/godavari-logo.png' alt='Godavari'>";
			echo "<img src='$img/knb-logo.png' alt='KNB Group'>";
			echo "</div>";
			echo "<p class='knb-site-footer-about'>Godavari Pure and Fresh Ghee is specially manufactured from fresh butter collected across the famous green and fertile belt of twin Godavari districts of AP.</p>";
			echo "<div class='knb-site-footer-social'>";
			echo "<a href='https://www.facebook.com/knbgroup1951' target='_blank' title='Facebook'>" . svg_icon('facebook') . "</a>";
			echo "<a href='https://www.instagram.com/knbgroup1951/' target='_blank' title='Instagram'>" . svg_icon('instagram') . "</a>";
			echo "<a href='https://www.youtube.com/channel/UCyQQxedanTio5iagijPPgVQ' target='_blank' title='Youtube'>" . svg_icon('youtube') . "</a>";
			echo "</div>";
			echo "</div>";

			// Column 2: Useful Links (points at the real marketing site)
			echo "<div class='knb-site-footer-col'>";
			echo "<p class='knb-site-footer-title'>Useful Links</p>";
			echo "<div class='knb-site-footer-links'>";
			echo "<ul>";
			echo "<li><a href='$site/about.php' target='_blank'>About</a></li>";
			echo "<li><a href='$site/faq.php' target='_blank'>FAQ</a></li>";
			echo "<li><a href='$site/contact.php' target='_blank'>Contact us</a></li>";
			echo "</ul>";
			echo "<ul>";
			echo "<li><a href='$site/gallery.php' target='_blank'>Promotions</a></li>";
			echo "<li><a href='$site/products.php' target='_blank'>Products</a></li>";
			echo "<li><a href='$site/why-us.php' target='_blank'>Why us</a></li>";
			echo "</ul>";
			echo "</div>";
			echo "</div>";

			// Column 3: Address
			echo "<div class='knb-site-footer-col'>";
			echo "<p class='knb-site-footer-title'>Address:</p>";
			echo "<ul class='knb-site-footer-address'>";
			echo "<li><span class='knb-icon'>" . svg_icon('pin') . "</span><span>Rajaratna Bypass Road, Mandapeta, Andhra Pradesh 533308</span></li>";
			echo "<li><span class='knb-icon'>" . svg_icon('phone') . "</span><a href='tel:+919666755555'>+91 9666755555</a></li>";
			echo "<li><span class='knb-icon'>" . svg_icon('mail') . "</span><a href='mailto:Info@knbgroup.in'>Info@knbgroup.in</a></li>";
			echo "</ul>";
			echo "</div>";

			echo "</div>"; // knb-site-footer-cols
			echo "</div>"; // knb-site-footer

			echo "<div class='knb-site-footer-bottom'>";
			echo "All Rights Reserved &commat; KNB Group $year &nbsp;&middot;&nbsp; ";
			echo "<a target='_blank' href='".$SysPrefs->power_url."'>".$SysPrefs->power_by."</a>";
			echo "</div>";
		}

		/*
			Persistent left sidebar for the current app - same
			modules/lappfunctions/rappfunctions data display_applications()
			uses for the index-page menu, rendered as a single narrow column
			of module sections instead of two wide columns, and shown on
			every page (not just index.php).
		*/
		function render_sidebar($app)
		{
			if (!$_SESSION["wa_current_user"]->check_application_access($app))
				return;

			echo "<div class='knb-sidebar-inner'>";
			foreach ($app->modules as $module)
			{
				if (!$_SESSION["wa_current_user"]->check_module_access($module))
					continue;

				echo "<div class='knb-sidebar-heading'>".$module->name."</div>";
				echo "<div class='knb-sidebar-items'>";
				foreach (array_merge($module->lappfunctions, $module->rappfunctions) as $appfunction)
				{
					if ($appfunction->label == "")
						continue;
					if ($_SESSION["wa_current_user"]->can_access_page($appfunction->access))
						echo "<div class='knb-sidebar-link'>".menu_link($appfunction->link, $appfunction->label)."</div>\n";
					elseif (!$_SESSION["wa_current_user"]->hide_inaccessible_menu_items())
						echo "<div class='knb-sidebar-link inactive'>".access_string($appfunction->label, true)."</div>\n";
				}
				echo "</div>";
			}
			echo "</div>";
		}

		function display_applications(&$waapp)
		{
			global $path_to_root;

			$selected_app = $waapp->get_selected_application();
			if (!$_SESSION["wa_current_user"]->check_application_access($selected_app))
				return;

			if (method_exists($selected_app, 'render_index'))
			{
				$selected_app->render_index();
				return;
			}

			echo "<table width='100%' cellpadding='0' cellspacing='0'>";
			foreach ($selected_app->modules as $module)
			{
        		if (!$_SESSION["wa_current_user"]->check_module_access($module))
        			continue;
				// image
				echo "<tr>";
				// values
				echo "<td valign='top' class='menu_group'>";
				echo "<table border=0 width='100%'>";
				echo "<tr><td class='menu_group'>";
				echo $module->name;
				echo "</td></tr><tr>";
				echo "<td class='menu_group_items'>";

				foreach ($module->lappfunctions as $appfunction)
				{
					$img = $this->get_icon($appfunction->category);
					if ($appfunction->label == "")
						echo "&nbsp;<br>";
					elseif ($_SESSION["wa_current_user"]->can_access_page($appfunction->access)) 
					{
							echo $img.menu_link($appfunction->link, $appfunction->label)."<br>\n";
					}
					elseif (!$_SESSION["wa_current_user"]->hide_inaccessible_menu_items())
					{
							echo $img.'<span class="inactive">'
								.access_string($appfunction->label, true)
								."</span><br>\n";
					}
				}
				echo "</td>";
				if (sizeof($module->rappfunctions) > 0)
				{
					echo "<td width='50%' class='menu_group_items'>";
					foreach ($module->rappfunctions as $appfunction)
					{
						$img = $this->get_icon($appfunction->category);
						if ($appfunction->label == "")
							echo "&nbsp;<br>";
						elseif ($_SESSION["wa_current_user"]->can_access_page($appfunction->access)) 
						{
								echo $img.menu_link($appfunction->link, $appfunction->label)."<br>\n";
						}
						elseif (!$_SESSION["wa_current_user"]->hide_inaccessible_menu_items())
						{
								echo $img.'<span class="inactive">'
									.access_string($appfunction->label, true)
									."</span><br>\n";
						}
					}
					echo "</td>";
				}

				echo "</tr></table></td></tr>";
			}
			echo "</table>";
  		}
	}
