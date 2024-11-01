<?php
/*
Plugin Name: WP Realty Lisings
Plugin URI: http://www.wprealty.org/
Description: <strong>WP Realty v-1.0.6</strong> -- This plugin was designed to provide real estate and automotive professionals with a way to publish listings within their WordPress blog.  Our belief is that by adding listings directly into your WordPress posts you increase the relevance of the content associated with your listings and provide a way for search engines to index content relative to your listings.  The WP Realty Listing plugin requires either <a href="http://www.open-realty.org" title="Open Realty" target="_blank">Open Realty</a> or <strong><a href="http://www.wprealty.org" title="WPRealty" target="_blank">Upgrade to WP Realty 2.0</a></strong> to function, this plugin was created
Author: Jared Ritchey and Chad Broussard
Version: 1.0.6
Author URI: http://www.wprealty.org/
*/

/*
tag = {wp-realty}
PRODUCT LICENSE: This product, WP Listings Manager Plugin, was created to be released as open source and is the product of JaredRitchey.com  This product may be used, modified and redistributed providing this section of the copyright and license remain in tact and the above plugin details remain in place.  A private lable license is available and branding changes can be made by special request.
*/

/*
TAGS - insert it to Page content (one tag for one page!)
Open-Realty Front Page - {wp-realty index}
Search Page - {wp-realty searchpage}
Search All Property Classes - {wp-realty search_all}
About Us - {wp-realty about_us}
Contact Us - {wp-realty contact_us}
View All Listings - {wp-realty searchresults}
Saved Searches - {wp-realty view_saved_searches}
Loan Calculators - {wp-realty calculator}
Signup - Agent - {wp-realty signup_agent}
Signup - Member - {wp-realty signup_member}
Member Login - {wp-realty member_login}
Logout - {wp-realty logout}
View Agents - {wp-realty view_users}
View Favourites - {wp-realty view_favorites}
Custom page (i.e. with ID 4) - {wp-realty PageID=4}
Custom Listing (i.e. with ID 2) - {wp-realty listingID=2}
*/

@session_start();
@ob_start();
?>
<?php
	/////////////////////////////////////////////
	/// Added in Version 1.0.4 December 2nd 2008
	/// for compatibility
	/////////////////////////////////////////////	
	// we restore $_POST['name'] before call OpenRealty
if (isset($_POST['openrealty_form']) && isset($_POST['name'])) {
	$_POST['openrealty_form_name'] = $_POST['name'];
	unset($_POST['name']);
}

if (isset($_GET['openrealty_form']) && isset($_GET['name'])) {
	$_GET['openrealty_form_name'] = $_GET['name'];
	unset($_GET['name']);
}

//////////////////////////////
/* admin_menu hook function */
//////////////////////////////
add_action('admin_menu', 'show_listings_option');
function show_listings_option() {
	add_management_page("edit.php","WP Realty Administration", 8, "wp_realty_admin" ,'listings_admin_option1');
	if (function_exists('add_options_page')) {
		$Title = "WP Realty Display v1.0.6 || Beta Version";
		add_options_page("$Title","WP Realty Configuration", 8, "wp_realty_admin", 'listings_admin_option2');
	}
}

/* ////////////////////////////////////////////////////////////////////
wp_template_show_listings_listings...
use this in template to show the listings listings
param def_action ... it's the action from tag
//////////////////////////////////////////////////////////////////// */
function wp_template_show_listings($def_action = '') {
	global $wpdb;

	$page_id = $wpdb->get_results("SELECT `ID` FROM `".$wpdb->posts."` WHERE `post_type` = 'page' AND (`post_content` LIKE '%{wp-realty index}%' OR `post_content` LIKE '%{wp-realty}%') LIMIT 1;",ARRAY_A);
	if( !isset($page_id[0]['ID']) || (int)$page_id[0]['ID'] < 1) {
		$page_id = $wpdb->get_results("SELECT `ID` FROM `".$wpdb->posts."` WHERE `post_type` = 'page' AND `post_content` LIKE '%{wp-realty%' ORDER BY `ID` LIMIT 1;",ARRAY_A);
	}
	$page_id = (int)$page_id[0]['ID'];
	$page_permalink = get_permalink($page_id);
	$page_path =  str_replace('http://','', $page_permalink);
	$page_path =  str_replace($_SERVER['SERVER_NAME'],'', $page_path);
	$page_path =  str_replace('www.','', $page_path);

	if ( substr($page_permalink, strlen($page_permalink)-1, 1) == '/')
		$page_permalink = substr($page_permalink, 0, strlen($page_permalink)-1);
	$permalink_structure = get_option('permalink_structure');
	$request_uri = $_SERVER['REQUEST_URI'];

	if (!isset($_POST['referer']) || strlen($_POST['referer']) < 6) {	
		if ($permalink_structure == '')
			$_POST['referer'] = $page_permalink;
		else
			$_POST['referer'] = $page_permalink;
	}

	if ($permalink_structure == '') {
		$path = $page_path.'&';
	} else {
		if (substr($page_path, strlen($page_path)-1, 1) != '/')
			$path = $page_path.'/';
		else
			$path = $page_path;
	}

	//array with SEO patterns (based on .htaccess of Open-Realty )
	// Due to change in version 1.1 as per the new listings manager
	$seo_patterns = array(
		array(2, '/listing-(.*?)-([0-9]*).html/i', 'action=listingview&listingID=%s', $path.'listing-\\1-\\2.html'),
		array(2, '/listing-(.*?)\/([0-9]*).html/i', 'action=listingview&listingID=%s', $path.'listing-\\1/\\2.html'),
		array(2, '/page-(.*?)-([0-9]*).html/i', 'action=page_display&PageID=%s', $path.'page-\\1-\\2.html'),
		array(2, '/page-(.*?)\/([0-9]*).html/i', 'action=page_display&PageID=%s', $path.'page-\\1/\\2.html'),
		array(0, '/search.html/i', 'action=searchpage', $path.'search.html'),
		array(0, '/searchresults.html/i', 'action=searchresults', $path.'searchresults.html'),
		array(0, '/agents.html/i', 'action=view_users', $path.'agents.html'),
		array(0, '/view_favorites.html/i', 'action=view_favorites', $path.'view_favorites.html'),
		array(0, '/calculator.html/i', 'action=calculator&popup=yes', $path.'calculator.html'),
		array(0, '/saved_searches.html/i', 'action=view_saved_searches', $path.'saved_searches.html'),
		array(1, '/listing_image_([0-9]*).html/i', 'action=view_listing_image&image_id=%s', $path.'listing_image_\\1.html'),
		array(0, '/logout.html/i', 'action=logout', $path.'logout.html'),
		array(0, '/member_signup.html/i', 'action=signup&type=member', $path.'member_signup.html'),
		array(0, '/agent_signup.html/i', 'action=signup&type=agent', $path.'agent_signup.html'),
		array(0, '/member_login.html/i', 'action=member_login', $path.'member_login.html'),
		array(1, '/edit_profile_([0-9]*).html/i', 'action=edit_profile&user_id=%s', $path.'edit_profile_\\1.html'),
		array(2, '/agent-(.*?)-([0-9]*).html/i', 'action=view_user&user=%s', $path.'agent-\\1-\\2.html'),
		array(0, '/agent-(.*?)\/([0-9]*).html/i', 'action=searchresults', $path.'agent-\\1/\\2.html'),
		array(0, '/(.*?)-searchresults-([0-9]*).html/i', 'action=searchresults', $path.'\\1-searchresults-\\2).html'),
		array(0, '/(.*?)-searchresults\/([0-9]*).html/i', 'action=searchresults', $path.'\\1-searchresults/\\2).html')
		);

	if(isset($_GET['action']))
		$action = $_GET['action'];
	else {
		$action = '';
		
		//probably SEF enabled	
		foreach ($seo_patterns as $seo_pattern){
			preg_match($seo_pattern[1], $request_uri, $matches);

			if (count($matches) < 1) {
				continue;
			}
			if (isset($matches[$seo_pattern[0]])) {
				if ($seo_pattern[0] > 0 )
					$uri = sprintf($seo_pattern[2], $matches[$seo_pattern[0]]);
				else 
				$uri = $seo_pattern[2];
				$uri_array = explode('&', $uri);
				if (count($uri_array) > 0) {
					foreach($uri_array as $item) {
						$req = explode('=', $item);
						if (count($req) == 2)
							$_GET[$req[0]] = $req[1];
					}
				}
			}			
		}
	}

	if ($def_action != '' && !isset($_GET['action'])) {
		$def_action = trim($def_action);
		switch ($def_action) {			
			case 'about_us':
				$_GET['action'] = 'page_display';
				$_GET['PageID'] = '3';
			break;
			
			case 'contact_us':
				$_GET['action'] = 'page_display';
				$_GET['PageID'] = '2';
			break;
			
			case 'signup_agent':
				$_GET['action'] = 'signup';
				$_GET['type'] = 'agent';
			break;

			case 'signup_member':
				$_GET['action'] = 'signup';
				$_GET['type'] = 'member';
			break;

			case 'search_all':
				$_GET['action'] = 'search_step_2';
				$_GET['pclass'] = array('0' => null);
			break;

			default:
				$_GET['action'] = $def_action;
			break;
		}

		if (strpos('  '.$def_action, 'listingID=') !== false){
			$_GET['action'] = 'listingview';
			$_GET['listingID'] = (int)str_replace('listingID=', '', $def_action);			
		} elseif (strpos('  '.$def_action, 'PageID=') !== false) {
			$_GET['action'] = 'page_display';
			$_GET['PageID'] = (int)str_replace('PageID=', '', $def_action);
		}		
	}

	if ( isset($_GET['action']) && $_GET['action']=='logout') {
		unset($_SESSION['username']);
		unset($_SESSION['userpassword']);
		unset($_SESSION['userID']);
		unset($_SESSION['featureListings']);
		unset($_SESSION['viewLogs']);
		unset($_SESSION['admin_privs']);
		unset($_SESSION['active']);
		unset($_SESSION['isAgent']);
		unset($_SESSION['moderator']);
		unset($_SESSION['editpages']);
		unset($_SESSION['havevtours']);
		unset($_SESSION['is_member']);
		unset($_SESSION['edit_site_config']);
		unset($_SESSION['edit_member_template']);
		unset($_SESSION['edit_agent_template']);
		unset($_SESSION['edit_listing_template']);
		unset($_SESSION['export_listings']);
		unset($_SESSION['edit_all_listings']);
		unset($_SESSION['edit_all_users']);
		unset($_SESSION['edit_property_classes']);
		unset($_SESSION['edit_expiration']);
		// Destroy Cookie
		setcookie("cookname", "", time() - 3600, "/");
		setcookie("cookpass", "", time() - 3600, "/");
		
		// Refresh the screen
		header('Location:' . $page_permalink);
		die();
		$_GET['action'] = 'index';
	}

	$parts = explode('&', str_replace('&amp;', '&', $GLOBALS['query_string']));
	$good_vars = array('action', 'listingID', 'PageID', 'popup', 'image_id', 'type', 'user_id', 'user', 'pclass');
	$hiddens = '<input type="hidden" name="page_id" value="'.$page_id.'" />';
	if (is_array($parts) && count($parts) > 0)
	foreach($parts as $part) {
		$tmp = explode ('=', $part);
		if (count($tmp) > 1) {
			if (isset($tmp[0]) && isset($_GET[$tmp[0]]) && !in_array($tmp[0], $good_vars))
				unset($_GET[$tmp[0]]);
		}
	}
	/////////////////////////////////////////////
	/// Added in Version 1.0.4 December 2nd 2008
	/////////////////////////////////////////////
	//restore $_POST['name']
	$openrealty_form = false;
	if (isset($_POST['openrealty_form']) && isset($_POST['openrealty_form_name'])) {
		$_POST['name'] = $_POST['openrealty_form_name'];
		unset($_POST['openrealty_form_name']);
		unset($_POST['openrealty_form']);
		$openrealty_form = true;
	}
	else {
		unset($_POST['openrealty_form']);
	}

	if (isset($_GET['openrealty_form']) && isset($_GET['openrealty_form_name'])) {
		$_GET['name'] = $_GET['openrealty_form_name'];
		unset($_GET['openrealty_form_name']);
		unset($_GET['openrealty_form']);
		$openrealty_form = true;
	} else {
		unset($_GET['openrealty_form']);
	}

	@ob_start();
	$folder_to_include = get_option('folder_to_include');

	if (isset($_POST['user_name']) && isset($_POST['user_pass'])) {
		$_POST['referer'] = $page_permalink;
		unset($_POST['page_id']);	
		$_GET['action'] = 'member_login';
	}

	$content = '';
	if (strpos('  '.$def_action, 'featuredID=') !== false){
		$f_id  = (int)str_replace('featuredID=', '', $def_action);
		$layout = substr($def_action, strpos($def_action, 'layout=') + 7);
		$content = wp_get_featured($f_id, $layout);
	} else {
		//set open-realty global vars
		global $conn, $lang, $config, $db_type;
		include($folder_to_include."include/common.php");
		$GLOBALS['conn'] = $conn;
		$GLOBALS['lang'] = $lang;
		$GLOBALS['config'] = $config;
		$GLOBALS['db_type'] = $db_type;
		include($folder_to_include."index.php");
		$content = @ob_get_contents();
		@ob_end_clean(); 

///////////////////////////////////
// EZProRealty Branding Starts Here
// Updated by Jared on May 22 2009
///////////////////////////////////
$licence_url="http://www.ezprorealty.com/license/index.php?action=get_licence&url=".$_SERVER["SERVER_NAME"]."&version=5&current_time=".date("H:i:s")."&licence_display=horizontal";
$template=@file_get_contents($licence_url);
$content = $content. "<br/><table width=\"100%\"><tr><td style=\"text-align:center; vertical-align:middle;\">" . $template . "</td></tr></table><br/>";
}
/////////////////////////////////
// EZProRealty Branding Ends Here
/////////////////////////////////

	/////////////////////////////////////////////
	/// Added in Version 1.0.4 December 2nd 2008
	/// for compatibility
	/////////////////////////////////////////////
	$content = str_replace('</form>', $hiddens.'<input type="hidden" name="openrealty_form" value="1" /></form>', $content);
	foreach ($seo_patterns as $seo_pattern) {
		$content = preg_replace ($seo_pattern[1], $seo_pattern[3], $content);
	}
	if (substr($_SERVER['SERVER_NAME'], strlen($_SERVER['SERVER_NAME'])-1, 1) != '/')
		$live_site = $_SERVER['SERVER_NAME'].'/';
		else
		$live_site = $_SERVER['SERVER_NAME'];
	if ($permalink_structure == '') {
		$content = preg_replace('/http\:\/\/.*(index\.php)/i', 'http://'.$live_site.'index.php', $content);
		$content = str_replace('index.php?', $path, $content);
		$content = str_replace('index.php', $path, $content);
	} else {
		$content = preg_replace('/http\:\/\/.*(index\.php)/i', 'http://'.$live_site.'index.php', $content);
		$content = str_replace('index.php?', $path.'?', $content);
		$content = str_replace('index.php', $path.'?', $content);
	}
	$content = utf8_encode($content);
	//unset $_POST['name']
	if ($openrealty_form && isset($_POST['name'])) {
		unset($_POST['name']);
	}
	if ($openrealty_form && isset($_GET['name'])) {
		unset($_GET['name']);
	}
	// End Version 1.0.4 update
	return $content;
}

function wp_get_featured($f_id = 0, $template_name = '', $num_of_listings = 1){
	$random = FALSE;
	$pclass = '';
	$folder_to_include = get_option('folder_to_include');
	//set open-realty global vars
	global $conn, $lang, $config, $db_type;
	include($folder_to_include."include/common.php");
	$GLOBALS['conn'] = $conn;
	$GLOBALS['lang'] = $lang;
	$GLOBALS['config'] = $config;
	$GLOBALS['db_type'] = $db_type;
	require_once($folder_to_include.'include/class/template/core.inc.php');
	require_once($folder_to_include.'include/misc.inc.php');
	require_once($folder_to_include."include/listing.inc.php");
	$listing_pages = new listing_pages();
	if (method_exists($listing_pages, 'renderFeaturedListingsID')) {
		$display = $listing_pages->renderFeaturedListingsID($num_of_listings, $template_name, $f_id);
	}
	else {		
		$page = new page_user();
		$misc = new misc();
		//Declare an empty display variable to hold all output from function.
		$display = '';
		//Get the number of listing to display by default, unless user specified an override in the template file.
		if ($num_of_listings == 0) {
			$num_of_listings = $config['num_featured_listings'];
		}
		//Load a Random set of featured listings
		if ($db_type == 'mysql') {
			$rand = 'RAND()';
		}else {
			$rand = 'RANDOM()';
		}
		if($random == TRUE){
			$sql_rand = '';
		}else{
			$sql_rand = "(listingsdb_featured = 'yes') AND";
		}
		if ($config['use_expiration'] === "1") {
			if ($pclass != '') {
			$sql = "SELECT " . $config['table_prefix'] . "listingsdb.listingsdb_id, listingsdb_title FROM " . $config['table_prefix'] . "listingsdb," . $config['table_prefix_no_lang'] . "classlistingsdb WHERe $sql_rand (listingsdb_active = 'yes') AND (listingsdb_expiration > " . $conn->DBDate(time()) . ") AND (" . $config['table_prefix'] . "listingsdb.listingsdb_id = " . $config['table_prefix_no_lang'] . "classlistingsdb.listingsdb_id) AND class_id = " . $pclass . ( $f_id > 0 ? " AND `listingsdb_id` = '{$f_id}' ": '') ." ORDER BY $rand";
			} else {
			$sql = "SELECT " . $config['table_prefix'] . "listingsdb.listingsdb_id, listingsdb_title FROM " . $config['table_prefix'] . "listingsdb WHERE $sql_rand (listingsdb_active = 'yes') AND (listingsdb_expiration > " . $conn->DBDate(time()) . ") ". ( $f_id > 0 ? " AND `listingsdb_id` = '{$f_id}' ": '') ."ORDER BY $rand";
			}
		}else {
			if ($pclass != '') {
			$sql = "SELECT " . $config['table_prefix'] . "listingsdb.listingsdb_id, listingsdb_title FROM " . $config['table_prefix'] . "listingsdb," . $config['table_prefix_no_lang'] . "classlistingsdb WHERE $sql_rand (listingsdb_active = 'yes') AND (" . $config['table_prefix'] . "listingsdb.listingsdb_id = " . $config['table_prefix_no_lang'] . "classlistingsdb.listingsdb_id) AND class_id = " . $pclass . ( $f_id > 0 ? " AND `listingsdb_id` = '{$f_id}' ": '')." ORDER BY $rand";
			} else {
			$sql = "SELECT " . $config['table_prefix'] . "listingsdb.listingsdb_id, listingsdb_title FROM " . $config['table_prefix'] . "listingsdb WHERE $sql_rand (listingsdb_active = 'yes')  ".( $f_id > 0 ? " AND `listingsdb_id` = '{$f_id}' ": '')." ORDER BY $rand";
			}
		}
		$recordSet = $conn->SelectLimit($sql, $num_of_listings, 0);
		if ($recordSet === false) {
			$misc->log_error($sql);
		}
		//Find out how many listing were returned
		$returned_num_listings = $recordSet->RecordCount();
		if ($returned_num_listings >= 1) {
	//Load the Featured Listing Template specified in the Site Config unless a template was specified in the calling template tag.
			if ($template_name == '') {
				$page->load_page($config['template_path'] . '/' . $config['featured_listing_template']);
			} else {
				$page->load_page($config['template_path'] . '/featured_listing_' . $template_name . '.html');
			}
	// Determine if the template uses rows.
	// First item in array is the row conent second item is the number of block per block row
			$featured_template_row = $page->get_template_section_row('featured_listing_block_row');
			if (is_array($featured_template_row)) {
				$row = $featured_template_row[0];
				$col_count = $featured_template_row[1];
				$user_rows = true;
				$x = 1;
	//Create an empty array to hold the row conents
				$new_row_data = array();
			}else {
				$user_rows = false;
			}
			$featured_template_section = '';
			while (!$recordSet->EOF) {
				if ($user_rows == true && $x > $col_count) {
	//We are at then end of a row. Save the template section as a new row.
					$new_row_data[] = $page->replace_template_section('featured_listing_block', $featured_template_section,$row);
	//$new_row_data[] = $featured_template_section;
					$featured_template_section = $page->get_template_section('featured_listing_block');
					$x=1;
				}else {
					$featured_template_section .= $page->get_template_section('featured_listing_block');
				}
				$listing_title = $misc->make_db_unsafe ($recordSet->fields['listingsdb_title']);
				$ID = $misc->make_db_unsafe ($recordSet->fields['listingsdb_id']);
				if ($config['url_style'] == '1') {
					$featured_url = 'index.php?action=listingview&amp;listingID=' . $ID;
				}else {
					$url_title = str_replace("/", "", $listing_title);
					$url_title = strtolower(str_replace(" ", $config['seo_url_seperator'], $url_title));
					$featured_url = 'listing-' . urlencode($url_title) . '-' . $ID . '.html';
				}
				$sql3 = "SELECT listingsdbelements_field_name, listingsdbelements_field_value FROM " . $config['table_prefix'] . "listingsdbelements WHERE (listingsdb_id = $ID)";
				$recordSet3 = $conn->SelectLimit($sql3, 4, 0 );
				if ($recordSet3 === false) {
					$misc->log_error($sql3);
				}
			
				if ($recordSet3->RecordCount() > 0) {
					while (!$recordSet3->EOF) { 	
						$name = $misc->make_db_unsafe ($recordSet3->fields['listingsdbelements_field_name']);
						$value = $misc->make_db_unsafe ($recordSet3->fields['listingsdbelements_field_value']);
						$featured_template_section = $page->parse_template_section($featured_template_section,'listing_field_'.strtolower($name), $value);					
						$recordSet3->MoveNext();
					}
				}
				$featured_template_section = $page->replace_listing_field_tags($ID, $featured_template_section);
				$featured_template_section = $page->replace_listing_field_tags($ID, $featured_template_section);
				$featured_template_section = $page->parse_template_section($featured_template_section, 'featured_url', $featured_url);
	// Setup Image Tags
				$sql2 = "SELECT listingsimages_thumb_file_name,listingsimages_file_name FROM " . $config['table_prefix'] . "listingsimages WHERE (listingsdb_id = $ID) ORDER BY listingsimages_rank";
				$recordSet2 = $conn->SelectLimit($sql2, 1, 0);
				if ($recordSet2 === false) {
					$misc->log_error($sql2);
				}
				if ($recordSet2->RecordCount() > 0) {
					$thumb_file_name = $misc->make_db_unsafe ($recordSet2->fields['listingsimages_thumb_file_name']);
					$file_name = $misc->make_db_unsafe($recordSet2->fields['listingsimages_file_name']);
	// gotta grab the thumbnail image size
					$imagedata = GetImageSize("$config[listings_upload_path]/$thumb_file_name");
					$imagewidth = $imagedata[0];
					$imageheight = $imagedata[1];
					$shrinkage = $config['thumbnail_width'] / $imagewidth;
					$featured_thumb_width = $imagewidth * $shrinkage;
					$featured_thumb_height = $imageheight * $shrinkage;
					$featured_thumb_src = $config['listings_view_images_path'] . '/' . $thumb_file_name;
	
	// gotta grab the thumbnail image size
					$imagedata = GetImageSize("$config[listings_upload_path]/$file_name");
					$imagewidth = $imagedata[0];
					$imageheight = $imagedata[1];
					$featured_width = $imagewidth;
					$featured_height = $imageheight;
					$featured_src = $config['listings_view_images_path'] . '/' . $file_name;
				}else {
					if ($config['show_no_photo'] == 1) {
						$imagedata = GetImageSize("images/nophoto.gif");
						$imagewidth = $imagedata[0];
						$imageheight = $imagedata[1];
						$shrinkage = $config['thumbnail_width'] / $imagewidth;
						$featured_thumb_width = $imagewidth * $shrinkage;
						$featured_thumb_height = $imageheight * $shrinkage;
						$featured_thumb_src = "images/nophoto.gif";
						$featured_width = $featured_thumb_width;
						$featured_height = $featured_thumb_height;
						$featured_src = "images/nophoto.gif";
					}else {
						$featured_thumb_width = '';
						$featured_thumb_height = '';
						$featured_thumb_src = '';
						$featured_width = '';
						$featured_height = '';
						$featured_src = '';
					}
				}
				if (!empty($featured_thumb_src)) {
					$featured_template_section = $page->parse_template_section($featured_template_section, 'featured_thumb_src', $featured_thumb_src);
					$featured_template_section = $page->parse_template_section($featured_template_section, 'featured_thumb_height', $featured_thumb_height);
					$featured_template_section = $page->parse_template_section($featured_template_section, 'featured_thumb_width', $featured_thumb_width);
					$featured_template_section = $page->cleanup_template_block('featured_img', $featured_template_section);
				}else {
					$featured_template_section = $page->remove_template_block('featured_img', $featured_template_section);
				}
				if (!empty($featured_src)) {
					$featured_template_section = $page->parse_template_section($featured_template_section, 'featured_large_src', $featured_src);
					$featured_template_section = $page->parse_template_section($featured_template_section, 'featured_large_height', $featured_height);
					$featured_template_section = $page->parse_template_section($featured_template_section, 'featured_large_width', $featured_width);
					$featured_template_section = $page->cleanup_template_block('featured_img_large', $featured_template_section);
				}else {
					$featured_template_section = $page->remove_template_block('featured_img_large', $featured_template_section);
				}
				$recordSet->MoveNext();
				if ($user_rows == true) {
					$x++;
				}
			}
			if ($user_rows == true) {
				$featured_template_section = $page->cleanup_template_block('featured_listing', $featured_template_section);
				$new_row_data[] = $page->replace_template_section('featured_listing_block', $featured_template_section,$row);
				$replace_row = '';
				foreach ($new_row_data as $rows){
				$replace_row .= $rows;
				}
				$page->replace_template_section_row('featured_listing_block_row', $replace_row);
			}else {
				$page->replace_template_section('featured_listing_block', $featured_template_section);
			}
			$page->replace_permission_tags();
			$display .= $page->return_page();
		}
	}
	return $display;
}

////////////////////////////////
/* used when displaying admin */
////////////////////////////////
function listings_admin_option1() {
	listings_listings_options_form1(0);
}

// this is the form we need to do something when it is submitted
function listings_admin_option2() {
	if (isset($_POST['submit'])) {
		update_form();
	}
    listings_listings_options_form($_POST['tag_selected']);
}

if(isset($_GET['activate']) && ($_GET['activate'] == 'true')) {	
  	add_action('init', 'wp_realty_install');
}

function wp_realty_install() {
	global $wpdb, $user_level, $wp_rewrite, $wp_version; 
	$post_date =date("Y-m-d H:i:s");
	$post_date_gmt =gmdate("Y-m-d H:i:s");

	$num=0;
	$pages[$num]['name'] = 'wp-realty';
	$pages[$num]['title'] = 'Open-Realty Front Page';
	$pages[$num]['tag'] = '{wp-realty index}';
	
	$num++;
	$pages[$num]['name'] = 'search_page';
	$pages[$num]['title'] = 'Search Page';
	$pages[$num]['tag'] = '{wp-realty searchpage}';

	$num++;
	$pages[$num]['name'] = 'search_all';
	$pages[$num]['title'] = 'Search All Property Classes';
	$pages[$num]['tag'] = '{wp-realty search_all}';

	$num++;
	$pages[$num]['name'] = 'about_us';
	$pages[$num]['title'] = 'About Us';
	$pages[$num]['tag'] = '{wp-realty about_us}';

	$num++;
	$pages[$num]['name'] = 'contact_us';
	$pages[$num]['title'] = 'Contact Us';
	$pages[$num]['tag'] = '{wp-realty contact_us}';

	$num++;
	$pages[$num]['name'] = 'view_all_listings';
	$pages[$num]['title'] = 'View All Listings';
	$pages[$num]['tag'] = '{wp-realty searchresults}';

	$num++;
	$pages[$num]['name'] = 'saved_searches';
	$pages[$num]['title'] = 'Saved Searches';
	$pages[$num]['tag'] = '{wp-realty view_saved_searches}';

	$num++;
	$pages[$num]['name'] = 'loan_calculators';
	$pages[$num]['title'] = 'Loan Calculators';
	$pages[$num]['tag'] = '{wp-realty calculator}';

	$num++;
	$pages[$num]['name'] = 'signup_agent';
	$pages[$num]['title'] = 'Signup - Agent';
	$pages[$num]['tag'] = '{wp-realty signup_agent}';

	$num++;
	$pages[$num]['name'] = 'signup_member';
	$pages[$num]['title'] = 'Signup - Member';
	$pages[$num]['tag'] = '{wp-realty signup_member}';

	$num++;
	$pages[$num]['name'] = 'member_login';
	$pages[$num]['title'] = 'Member Login';
	$pages[$num]['tag'] = '{wp-realty member_login}';

	$num++;
	$pages[$num]['name'] = 'logout';
	$pages[$num]['title'] = 'Logout';
	$pages[$num]['tag'] = '{wp-realty logout}';

	$num++;
	$pages[$num]['name'] = 'view_agents';
	$pages[$num]['title'] = 'View Agents';
	$pages[$num]['tag'] = '{wp-realty view_users}';

	$num++;
	$pages[$num]['name'] = 'view_favourites';
	$pages[$num]['title'] = 'View Favourites';
	$pages[$num]['tag'] = '{wp-realty view_favorites}';	
	
	$newpages = false;
	$i = 0;
	$post_parent = 0;
	foreach($pages as $page) {
	$check_page = $wpdb->get_row("SELECT * FROM `".$wpdb->posts."` WHERE `post_content` LIKE '%".$page['tag']."%' LIMIT 1",ARRAY_A);
	if($check_page == null) {
		if($i == 0) {
			$post_parent = 0;
		} else {
			$post_parent = $first_id;
		}	  

		$sql ="INSERT INTO ".$wpdb->posts."
		(post_author, post_date, post_date_gmt, post_content, post_content_filtered, post_title, post_excerpt,  post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_parent, menu_order, post_type)
		VALUES
		('1', '$post_date', '$post_date_gmt', '".$page['tag']."', '', '".$page['title']."', '', 'publish', 'closed', 'closed', '', '".$page['name']."', '', '', '$post_date', '$post_date_gmt', '$post_parent', '0', 'page')";
	
		$wpdb->query($sql);
		$post_id = $wpdb->insert_id;
		if($i == 0) {
			$first_id = $post_id;
		}
		$wpdb->query("UPDATE $wpdb->posts SET guid = '" . get_permalink($post_id) . "' WHERE ID = '$post_id'");
		$newpages = true;
		$i++;
		}
	}
	if($newpages == true) {
		wp_cache_delete('all_page_ids', 'pages');
		$wp_rewrite->flush_rules();
	} 
	$wp_rewrite->flush_rules();
}

add_filter('rewrite_rules_array', 'wp_realty_rewrite_rules');
function wp_realty_rewrite_rules($rules) {
	global $wpdb;
	$page_id = $wpdb->get_results("SELECT `ID` FROM `".$wpdb->posts."` WHERE `post_type` = 'page' AND (`post_content` LIKE '%{wp-realty index}%' OR `post_content` LIKE '%{wp-realty}%') LIMIT 1;",ARRAY_A);
	if( !isset($page_id[0]['ID']) || (int)$page_id[0]['ID'] < 1) {
		$page_id = $wpdb->get_results("SELECT `ID` FROM `".$wpdb->posts."` WHERE `post_type` = 'page' AND `post_content` LIKE '%{wp-realty%' ORDER BY `ID` LIMIT 1;",ARRAY_A);
	}
	$page_id = (int)$page_id[0]['ID'];
	$page_permalink = get_permalink($page_id);
	$newrules = array();
	$newrules['(.*)listing-(.*)?-([0-9]*).html$']= 'index.php?page_id='.$page_id.'&action=listingview&listingID=$matches[3]';
	$newrules['(.*)page-(.*)?-([0-9]*).html$']= 'index.php?page_id='.$page_id.'&action=page_display&PageID=$matches[3]';	
	$newrules['(.*)search.html$']= 'index.php?page_id='.$page_id.'&action=searchpage';
	$newrules['(.*)searchresults.html$']= 'index.php?page_id='.$page_id.'&action=searchresults';
	$newrules['(.*)agents.html$']= 'index.php?page_id='.$page_id.'&action=view_users';	
	$newrules['(.*)view_favorites.html$']= 'index.php?page_id='.$page_id.'&action=view_favorites';
	$newrules['(.*)calculator.html$']= 'index.php?page_id='.$page_id.'&action=calculator&popup=yes';
	$newrules['(.*)saved_searches.html$']= 'index.php?page_id='.$page_id.'&action=view_saved_searches';
	$newrules['(.*)listing_image_([0-9]*).html$']= 'index.php?page_id='.$page_id.'&action=view_listing_image&image_id=$matches[2]';
	$newrules['(.*)logout.html$']= 'index.php?page_id='.$page_id.'&action=logout';	
	$newrules['(.*)member_signup.html$']= 'index.php?page_id='.$page_id.'&action=signup&type=member';
	$newrules['(.*)agent_signup.html$']= 'index.php?page_id='.$page_id.'&action=signup&type=agent';
	$newrules['(.*)member_login.html$']= 'index.php?page_id='.$page_id.'&action=member_login';
	$newrules['(.*)edit_profile_([0-9]*).html$']= 'index.php?page_id='.$page_id.'&action=edit_profile&user_id=$matches[2]';
	$newrules['(.*)agent-(.*)?-([0-9]*).html$']= 'index.php?page_id='.$page_id.'&action=view_user&user=$matches[3]';
	$newrules['(.*)page-(.*)?-([0-9]*).html$']= 'index.php?page_id='.$page_id.'&action=page_display&PageID=$matches[3]';
	$newrules['(.*)page-(.*)?-([0-9]*).html$']= 'index.php?page_id='.$page_id.'&action=page_display&PageID=$matches[3]';
	$newrules['(.*)?-searchresults-([0-9]*).html$']= 'index.php?page_id='.$page_id.'&action=searchresults';
	return $newrules + $rules;
}

//////////////////////////////////////////
/* Look for trigger text in page */
//////////////////////////////////////////

//check the contents for our tags which we want
add_filter('the_content', 'listings_check_content');
function listings_check_content($content) {
	preg_match_all('/{wp-realty([^{}]*?)}/', $content, $tags_found);
    $tags_found = $tags_found[1];
	foreach($tags_found as $tag)
	$content = str_replace("{wp-realty".$tag."}", wp_template_show_listings($tag), $content);
	return $content;
}

///////////////////////////////////
/* SHOW THE ADMIN FROM OR form for the search criterias */
///////////////////////////////////
function listings_listings_options_form($tag_selected) {
	$template = '';
	//$folder_to_include ... Open Realty folder to include
	$folder_to_include = get_option('folder_to_include');
	//$filepath_to_controlpanel = __FILE__;
	echo '<div class="wrap">';
	echo '<style type="text/css" media="all">
	span.green-notice{
background:#DAFDDF url(http://www.wprealty.org/images/statusok.gif) no-repeat scroll left center;
border-bottom:2px solid #8DC745;
border-top:2px solid #8DC745;
display:block;
font-weight:700;
height:24px;
line-height:24px;
margin:8px 0 20px;
padding:3px 0;
text-indent:30px;
}</style>
	';
	echo '<h2>WP Realty</h2>';
	echo '<span class="green-notice"><a href="http://www.wprealty.org" title="WPRealty" target="_blank">Upgrade to WP Realty 2.0 Today!</a></span>';
	echo '<form method="post">
	<fieldset class="options">
	<legend>Listings Manager Settings</legend>
	<p>WordPress\'s true power is in its ability to allow custom links urls or permalinks within the blog.  For instance you could set your blog pages and posts to follow 2008/10/07/sample-post/ or 2008/10/sample-post/ or even something custom like postname.html  By setting up your permalinks structure it is important to specify a page name that follows reasonable and easy to use structure such as /realestate/ or /listings/ for example. If you setup your page name to be realestate or listings for example your installation of OpenRealty should NOT be in a similarly named folder.</p>
	<p>This plugin will use first page with {wp-realty index} or {wp-realty} tag  as a default page.</p>
	<p>Some other tags (place only one tag on a page!):<br /><br />
	<ul id="awprealty">
	<li>Open-Realty Front Page - {wp-realty index}</li>
	<li>Search Page - {wp-realty searchpage}</li>
	<li>Search All Property Classes - {wp-realty search_all}</li>
	<li>About Us - {wp-realty about_us}</li>
	<li>Contact Us - {wp-realty contact_us}</li>
	<li>View All Listings - {wp-realty searchresults}</li>
	<li>Saved Searches - {wp-realty view_saved_searches}</li>
	<li>Loan Calculators - {wp-realty calculator}</li>
	<li>Signup - Agent - {wp-realty signup_agent}</li>
	<li>Signup - Member - {wp-realty signup_member}</li>
	<li>Member Login - {wp-realty member_login}</li>
	<li>Logout - {wp-realty logout}</li>
	<li>View Agents - {wp-realty view_users}</li>
	<li>View Favourites - {wp-realty view_favorites}</li>
	<li>Custom page (i.e. with ID 4) - {wp-realty PageID=4}</li>
	<li>Custom Listing (i.e. with ID 2) - {wp-realty listingID=2}</li></ul>
</p>
	<table width="100%" border="0" cellspacing="0" cellpadding="0" class="form-table">	
	<tr>
	<td>
	<strong>Open Realty Folder:</strong>&nbsp;<input type="text" size="40" name="folder_to_include" value="'.$folder_to_include.'" />
	<br />
	<span style="display:block; border:1px solid #333; background:#eee; padding:5px;">This field value is a absolute value. This should match the value of your Open Realty [basepath] installation. (NOTE: add a trailing slash)</span>
		</td>
		</tr>
		<tr>
		<td align="left" valign="top">&nbsp; <input type="submit" name="submit" value="Submit" /></td>
		</tr>
		</table>
	</fieldset>
	</form>';
	echo '</div>';
}

// The Open-Realty Admin Screens
function listings_listings_options_form1 ($tag_selected) {
	$template = '';
	$folder_to_include = get_option('folder_to_include');
	//include the include/common.php config file
	global $config;
	require_once($folder_to_include.'include/common.php');
	@session_start();
	echo '<div class="wrap">';
	echo '<style type="text/css" media="all">
	span.green-notice{
background:#DAFDDF url(http://www.wprealty.org/images/statusok.gif) no-repeat scroll left center;
border-bottom:2px solid #8DC745;
border-top:2px solid #8DC745;
display:block;
font-weight:700;
height:24px;
line-height:24px;
margin:8px 0 20px;
padding:3px 0;
text-indent:30px;
}</style>
	';
	echo '<span class="green-notice"><a href="http://www.wprealty.org" title="WPRealty" target="_blank">Upgrade to WP Realty 2.0 Today!</a></span>';
	echo '<h2>WP Realty v-1.0.6 - <a href="'.$config[baseurl].'/admin/index.php" onclick="return dw_loadExternal(this.href)">Admin</a> ';
	echo '| <a href="'.$config[baseurl].'/admin/index.php?action=log_out" onclick="return dw_loadExternal(this.href)">Log Out</a>';
	echo '</h2>';
// Get the Current WordPress login information then login to OR with the same
	global $userdata;
	get_currentuserinfo();
	//getlogin($userdata->user_login);
	echo '<script type="text/javascript" src="/wp-content/plugins/wp-realty/dw_loader.js"></script>';
	echo '<div id="wprealty"></div>';
	echo '<iframe id="buffer" name="buffer" src="'.$config[baseurl].'/admin/index.php" onload="dw_displayExternal()"></iframe>';
	/*	
	echo '<iframe name="myframe" id="myframe" src="'.$config[baseurl].'/admin/index.php" scrolling="no" marginwidth="0" marginheight="0" frameborder="0" vspace="0" hspace="0" style="overflow:visible; width:100%; display:none"></iframe>';
	*/
    echo '</div>';
}

function update_form() {
	$updated = false;
	if (isset($_POST['folder_to_include'])) {
		update_option('folder_to_include', $_POST['folder_to_include']);
		$updated = true;
	}
	if ($updated) {
		echo '<div id="message" class="updated fade">';
		echo '<p>Options Updated</p>';
		echo '</div>';
	} else {
		echo '<div id="message" class="error fade">';
		echo '<p>Unable to update options</p>';
		echo '</div>';
	}
}

function widget_realty_init() {
	if (!function_exists('register_sidebar_widget')) {return;}
	function widget_realty($args) {
		global $wpdb;
		$page_id = $wpdb->get_results("SELECT `ID` FROM `".$wpdb->posts."` WHERE `post_type` = 'page' AND (`post_content` LIKE '%{wp-realty index}%' OR `post_content` LIKE '%{wp-realty}%') LIMIT 1;",ARRAY_A);
		if( !isset($page_id[0]['ID']) || (int)$page_id[0]['ID'] < 1) {
			$page_id = $wpdb->get_results("SELECT `ID` FROM `".$wpdb->posts."` WHERE `post_type` = 'page' AND `post_content` LIKE '%{wp-realty%' ORDER BY `ID` LIMIT 1;",ARRAY_A);
		}
		$page_id = (int)$page_id[0]['ID'];
		$page_permalink = get_permalink($page_id);
		$page_path =  str_replace('http://','', $page_permalink);
		$page_path =  str_replace($_SERVER['SERVER_NAME'],'', $page_path);
		$page_path =  str_replace('www.','', $page_path);

		if ( substr($page_permalink, strlen($page_permalink)-1, 1) == '/')
			$page_permalink = substr($page_permalink, 0, strlen($page_permalink)-1);

		$permalink_structure = get_option('permalink_structure');
		
		if ($permalink_structure == '') {
			$path = $page_path.'&';
		} else {
			if (substr($page_path, strlen($page_path)-1, 1) != '/')
				$path = $page_path.'/';
			else
				$path = $page_path;
		}
		
		extract($args);
		$options = get_realty_settings();
		$title = $options['title'];
		$template = $options['template'];
		$num_of_listings = $options['number'];
		echo $before_widget . $before_title . $title . $after_title;
		$content = wp_get_featured( 0, $template, $num_of_listings);
		$content = str_replace('</form>', $hiddens.'</form>', $content);
		$permalink_structure = get_option('permalink_structure');

		//array with SEO pattens (based on .htaccess of Open-Realty )	
		$seo_patterns = array(
				array(2, '/listing-(.*?)-([0-9]*).html/i', 'action=listingview&listingID=%s', $path.'listing-\\1-\\2.html'),
				array(2, '/listing-(.*?)\/([0-9]*).html/i', 'action=listingview&listingID=%s', $path.'listing-\\1/\\2.html'),
				array(2, '/page-(.*?)-([0-9]*).html/i', 'action=page_display&PageID=%s', $path.'page-\\1-\\2.html'),
				array(2, '/page-(.*?)\/([0-9]*).html/i', 'action=page_display&PageID=%s', $path.'page-\\1/\\2.html'),
				array(0, '/search.html/i', 'action=searchpage', $path.'search.html'),
				array(0, '/searchresults.html/i', 'action=searchresults', $path.'searchresults.html'),
				array(0, '/agents.html/i', 'action=view_users', $path.'agents.html'),
				array(0, '/view_favorites.html/i', 'action=view_favorites', $path.'view_favorites.html'),
				array(0, '/calculator.html/i', 'action=calculator&popup=yes', $path.'calculator.html'),
				array(0, '/saved_searches.html/i', 'action=view_saved_searches', $path.'saved_searches.html'),
				array(1, '/listing_image_([0-9]*).html/i', 'action=view_listing_image&image_id=%s', $path.'listing_image_\\1.html'),
				array(0, '/logout.html/i', 'action=logout', $path.'logout.html'),
				array(0, '/member_signup.html/i', 'action=signup&type=member', $path.'member_signup.html'),
				array(0, '/agent_signup.html/i', 'action=signup&type=agent', $path.'agent_signup.html'),
				array(0, '/member_login.html/i', 'action=member_login', $path.'member_login.html'),
				array(1, '/edit_profile_([0-9]*).html/i', 'action=edit_profile&user_id=%s', $path.'edit_profile_\\1.html'),
				array(2, '/agent-(.*?)-([0-9]*).html/i', 'action=view_user&user=%s', $path.'agent-\\1-\\2.html'),
				array(0, '/agent-(.*?)\/([0-9]*).html/i', 'action=searchresults', $path.'agent-\\1/\\2.html'),
				array(0, '/(.*?)-searchresults-([0-9]*).html/i', 'action=searchresults', $path.'\\1-searchresults-\\2).html'),
				array(0, '/(.*?)-searchresults\/([0-9]*).html/i', 'action=searchresults', $path.'\\1-searchresults/\\2).html')
			);
	
		foreach ($seo_patterns as $seo_pattern) {
			$content = preg_replace ($seo_pattern[1], $seo_pattern[3], $content);
		}

		if ($permalink_structure == '') {
			$content = str_replace('index.php?', $path, $content);
			$content = str_replace('index.php', $path, $content);
		} else {
			$content = str_replace('index.php?', $path.'?', $content);
			$content = str_replace('index.php', $path.'?', $content);
		}
		$content = utf8_encode($content);
		echo $content;
		echo $after_widget;
	}

	function widget_realty_control() {
		$options = get_realty_settings();
		if ( $_POST['realty-submit'] ) {
			// Remember to sanitize and format use input appropriately.
			$options['title'] = strip_tags(stripslashes($_POST['realty-title']));
			$options['template'] = strip_tags(stripslashes($_POST['realty-template']));
			$options['number'] = strip_tags(stripslashes($_POST['realty-number']));
			$options['buildID'] = "47A90AB340844C93AB8812A564ED0000";
			update_option('widget_realty', $options);
			$options = get_realty_settings();
		}
?>
<table align="center">
<tr><td align="right">Title:</td><td><input id="realty-title" name="realty-title" type="text" value="<?php echo htmlspecialchars($options['title']); ?>" /></td></tr>
<tr><td align="right" style="white-space:nowrap;">Template:</td><td><input id="realty-template" name="realty-template" type="text" value="<?php echo htmlspecialchars($options['template']); ?>" /></td></tr>
<tr><td align="right" style="white-space:nowrap;">No listings:</td><td><input id="realty-number" name="realty-number" type="text" value="<?php echo htmlspecialchars($options['number']); ?>" /></td></tr>
<tr><td></td><td><input type="hidden" id="realty-submit" name="realty-submit" value="1" /><input type="submit" value="Save" /></td></tr>
</table>
<?php
	}
	function get_realty_settings () {
	$gbsopt = get_option('widget_realty');
	if ($gbsopt['buildID'] != "47A90AB340844C93AB8812A564ED0000") {
		$gbsopt["title"] = "";
		$gbsopt["template"] = "";
		$gbsopt["number"] = "";
	}

		if ($gbsopt["title"] == "") {$gbsopt["title"] = "WP Realty";}
		if ($gbsopt["template"] == "") {$gbsopt["template"] = "vertical";}
		if ($gbsopt["number"] == "") {$gbsopt["number"] = "2";}
		return($gbsopt);
	}

	function bboptrow ($bborv, $bborl, $bborm) {
		$tmp = '<option value="' . $bborv . '"';
		if ($bborv == $bborm) {$tmp .= " selected";}
		$tmp .= '>' . $bborl . '</option>';
		echo $tmp;
	}
	register_sidebar_widget('WP Realty', 'widget_realty');
	register_widget_control('WP Realty', 'widget_realty_control', 400, 300);
}
add_action('plugins_loaded', 'widget_realty_init');
?>