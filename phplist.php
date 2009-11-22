<?php
/*
Plugin Name: PHPlist
Plugin URI: http://www.jesseheap.com/projects/wordpress-phplist-plugin.php?ver=1.0
Description: Allows you to easily integrate the PHPList subscribe form in your blog
Version: 1.7
License: GPL
Author: Jesse Heap
Author URI: http://www.jesseheap.com
Contributors:
 ==============================================================================
   Rich Cowan 							Initial idea for PHPList API
   Rob Z at Web Geek Blog				Widget Support

Thanks to Ryan Duff, creator of the Wordpress Contact form plug-in (http://ryanduff.net), for the
Spam Handling Feature, CSS, and flexible design idea for dropping the subscriber form on any wordpress page.
*/
$plugin_dir = basename(dirname(__FILE__)) . '/languages/';
load_plugin_textdomain( 'phplist', 'wp-content/plugins/' . $plugin_dir, $plugin_dir );
/*Globals*/
$admin_phplist_config_page = get_settings('siteurl') . '/wp-admin/options-general.php?page=phplist.php"';
$ver='1.6';
$default_list_size = 2;
$o = phplist_get_options();

$phplist_strings = array(
	'field1' => '<input type="text" name="'. $o['php_list_txt_id'] . '" id="'. $o['php_list_txt_id'] .'" size="'. $o['php_list_txt_size'] .'" maxlength="'. $o['php_list_txt_max'] .'" value="' . paranoid($_POST[$o['php_list_txt_id']], array('-', ' ')) . '"   /> <br/>',
	'email' => '<input type="text" name="email" id="email" size="'. $o['php_list_email_size'] .'" maxlength="'. $o['php_list_email_max'] .'" value="'. paranoid($_POST['email'], array('@', '.', '_', '-')) .'"  /> <br/>',
	'error' => '');


add_action('widgets_init', 'phplist_widget_init');

function phplist_widget_init() {

	if (!function_exists('register_sidebar_widget')) {
		return;
	}

	function phplist_widget($args) {
		extract($args);
		$options = get_option('phplist_widget');
		$title = $options['title'];
		if (empty($title)) {
		}
		echo $before_widget . $before_title . $title . $after_title;
		if(phplist_check_input()) // If the input check returns true (ie. there has been a subscriber & input is ok)
		{
			   if(phplist_subscribe($_POST, true) === true) {
			   		echo("<h3>" . __('Thank You For Subscribing','phplist') . "</h3>");
			   } else {
			   		echo("<h3>" . __('An Error Occurred','phplist') . "</h3>");
			   		echo("<p>" . __("We were unable to add your email to our list. Please send us an email at <a href=\"mailto:support@getoutbayarea.com\">support@getoutbayarea.com</a> letting us know what happened and we'll make sure you get on the list.",'phplist'));
			   }
		}
		else // Else show the form. If there are errors the strings will have updated during running the inputcheck.
		{
			echo phplist_construct_form(true);
		}
		echo $after_widget;
	}
	register_sidebar_widget(array(__('PHPList Integration', 'phplist'), 'widgets'), 'phplist_widget');

	function phplist_widget_control() {
		$options = get_option('phplist_widget');
		if (!is_array($options)) {
			$options = array(
				'title' => __("Sign Up For Our Newsletter", 'phplist')
			);
		}
		if (isset($_POST['pl_action']) && $_POST['pl_action'] == 'phplist_update_widget_options') {
			$options['title'] = strip_tags(stripslashes($_POST['phplist_widget_title']));
			update_option('phplist_widget', $options);
		}

		// Be sure you format your options to be valid HTML attributes.
		$title = htmlspecialchars($options['title'], ENT_QUOTES);

		// Here is our little form segment. Notice that we don't need a
		// complete form. This will be embedded into the existing form.
		print('
			<p style="text-align:right;"><label for="phplist_widget_title">' . __('Title:') . ' <input style="width: 200px;" id="phplist_widget_title" name="phplist_widget_title" type="text" value="'.$title.'" /></label></p>
			<p><input type="hidden" id="pl_action" name="pl_action" value="phplist_update_widget_options" />
		');
	}
	register_widget_control(array(__('PHPList Integration', 'phplist'), 'widgets'), 'phplist_widget_control', 300, 100);
}


function phplist_subscribe($input_data, $widget=false) {
    global $admin_phplist_config_page;
	$o=phplist_get_options();
  	$domain = $o['php_list_uri'];
	$lid =$o['php_list_listid'];      // lid is the default PHPlist List ID to use
	$login =  $o['php_list_login'];
	$pass = $o['php_list_pass'];
	$skipConfirmationEmail = $o['php_list_skip_confirm'];               // Set to 0 if you require a confirmation email to be sent.

	if (!phplist_check_curl()) {
		_e('CURL library not detected on system.  Need to compile php with cURL in order to use this plug-in','phplist');
	    return(0);
		}
//   $post_data = array();

	foreach ($input_data as $varname => $varvalue) {
      // Handle Arrays (i.e Checkgroups)
     if (is_array($varvalue) && (strcasecmp($varname, 'list')!==0)) {
		 foreach ($varvalue as $varname1 => $varvalue1) {
			if (empty($var))
			 $var = $varvalue1;
			else  $var = $var . ',' . $varvalue1 ;
		 }
		 $post_data[$varname] = $var;
	 }
	 // Handle Multiple Lists
	 else if (is_array($varvalue) && strcasecmp($varname, 'list')==0){
		foreach ($varvalue as $varname1 => $varvalue1) {
				$varname = 'list['. $varname1 .']';
				$post_data[$varname] = $varvalue1;
			 }
	 }
	 else if (strcasecmp($varname, 'email') == 0)
	    $post_data[$varname] = paranoid($varvalue,  array('@', '.', '_', '-'));
     else
	    $post_data[$varname] = paranoid($varvalue, array('-', ' '));
   /* echo 'Post_Data['.$varname.'] = ' . $post_data[$varname] . '<br>';*/
   }

	// Ensure email is provided
	$email = $post_data['email'];//
   if ($email == '') {
         printf(__("You must supply an email address %s",'phplist'), $email);
    return(0);
   }

// 3) Login to phplist as admin and save cookie using CURLOPT_COOKIEFILE
// NOTE: Must log in as admin in order to bypass email confirmation
   $url = $domain . "admin/?";
   $ch = curl_init();
   $login_data = array();
   $login_data["login"] = $login;
   $login_data["password"] = $pass;
   curl_setopt($ch, CURLOPT_POST, 1);
   curl_setopt($ch, CURLOPT_URL, $url);
   curl_setopt($ch, CURLOPT_POSTFIELDS, $login_data);
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
   curl_setopt($ch, CURLOPT_COOKIEFILE, ""); //Enable Cookie Parser.
   //File does not need to exist - http://curl.netmirror.org/libcurl/c/libcurl-tutorial.html for more info
   $result = curl_exec($ch);
   //echo("Result was: $result"); //debug
   if (curl_errno($ch)) {
   printf('<h3 style="color:red">'. __('Error: %1$s .Check <a href="%2$s"> admin config options page.</a>','phplist'). ' </h3>', curl_error($ch) ,$admin_phplist_config_page );
   return(0);
	}

// 3) Now simulate post to subscriber form.
   $post_data["emailconfirm"] = $email;
   $post_data["htmlemail"] = "1";
 // No longer required  $post_data["list[$lid]"] = "signup";
   $post_data["subscribe"] = "Subscribe";
   $post_data["makeconfirmed"] = $skipConfirmationEmail;  //If set to 1 it will confirm user bypassing confirmation email
   $url = $domain . "?p=subscribe";

   curl_setopt($ch, CURLOPT_POST, 1);
   curl_setopt($ch, CURLOPT_URL, $url);
   curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
   $result = curl_exec($ch);

   //echo('Result was: ' .$result);
   if (curl_errno($ch)) {
   printf('<h3 style="color:red">'. __('Error: %1$s .Check <a href="%2$s"> admin config options page.</a>','phplist'). ' </h3>', curl_error($ch) ,$admin_phplist_config_page );
   return(0);
	}
	if(!$widget){
 		echo ('<h3>' . __('Thank you for subscribing to our email list') .'</h3>');
 	} else {
		curl_close($ch);
		return true;
	}
//) Clean up
   curl_close($ch);

 }  // end of function

function phplist_get_options(){
		global $default_list_size;
		$defaults = array();
		$defaults['php_list_txt_label'] = 'Name';
		$defaults['php_list_txt_id'] = 'attribute1';
		$defaults['php_list_txt_size'] = '30';
		$defaults['php_list_txt_max'] = '50';
		$defaults['php_list_txt_show'] = FALSE;
		$defaults['php_list_txt_req'] = FALSE;
		$defaults['php_list_email'] = 'Email';
		$defaults['php_list_email_size'] ='20';
		$defaults['php_list_email_max'] = '50';
		$defaults['php_list_show_css'] = TRUE;
		$defaults['php_list_login']='admin';
		$defaults['php_list_uri']='http://yourdomain.com/lists/';
		$defaults['php_list_pass']='';
		$defaults['php_list_maxLists']=$default_list_size;
		$counter=1;
		for ($counter; $counter <$defaults['php_list_maxLists']; $counter += 1) {
			$defaults['php_list_listid' . $counter]= '';
			$defaults['php_list_listname' . $counter]= '';
		}
		$defaults['php_list_skip_confirm']= 0;

		$options = get_option('phplistsettings');

		if (!is_array($options)){
			$options = $defaults;
			update_option('phplistsettings', $options);
		}

		return $options;
	}
function phplist_check_curl() {
 if (function_exists('curl_exec')) {

	return(1);

  } else {

   return (0);

 }
}
function phplist_subpanel() {
	global $ver;
	global $default_list_size;
		if ($_POST['phplist']){
		    //Check for trailing slash
			  $option_phplisturi = $_POST['phplist']['php_list_uri'];
			  if  (strcmp($option_phplisturi[strlen($option_phplisturi)-1], '/') !=0) {
	 				$_POST['phplist']['php_list_uri'] .= '/';
	 			}
			if ($_POST['add_more']) {
				$_POST['phplist']['php_list_maxLists'] = $_POST['phplist']['php_list_maxLists'] + 1;
				$sub_mesg = __(' and added additional line for Lists.  Make sure to enter List ID.','phplist');
			}
			if ($_POST['reset']) {
				$counter = $default_list_size;
				$max_lists = (empty($o['php_list_maxLists'])) ? $default_list_size : $o['php_list_maxLists'];
				for ($counter; $counter <$max_lists; $counter += 1) {
					 $o['php_list_listid' . $counter] = '';
					 $o['php_list_listname'. $counter ] = '';
				}
				$_POST['phplist']['php_list_maxLists'] = $default_list_size;
				$sub_mesg = __('and Reset List Count','phplist');
			}
			update_option('phplistsettings', $_POST['phplist']);
			$message = '<div class="updated"><p><strong>' . __('Options saved','phplist') .' ' . $sub_mesg . '</strong></p></div>';
			echo $message;
		}

	if (!phplist_check_curl()) {
		_e('CURL library not detected on system.  Need to compile php with cURL in order to use this plug-in','phplist');
	    return(0);
		}

	$o = phplist_get_options();
	$skip_confirmation_chkbox = ($o['php_list_skip_confirm'] == 1) ? ' checked="checked"' : '';
	$show_optional_field = ($o['php_list_txt_show'] == 1) ? ' checked="checked"' : '';
    $optional_field_req = ($o['php_list_txt_req'] == 1) ? ' checked="checked"' : '';
	$show_css = ($o['php_list_show_css'] == 1) ? ' checked="checked"' : '';
	$max_lists = (empty($o['php_list_maxLists'])) ? $default_list_size : $o['php_list_maxLists'];
    $counter=1;
	for ($counter; $counter <$max_lists; $counter += 1) {
		$lists = $lists . '<tr>
              <td><input name="phplist[php_list_listid' . $counter . ']" type="text" id="phplist[php_list_listid' . $counter . ']" value="' . $o['php_list_listid' . $counter] .'" size="50" /></td>
              <td><input name="phplist[php_list_listname' . $counter .']" type="text" id="phplist[php_list_listname' . $counter . ']" value="' . $o['php_list_listname'. $counter ] .'" size="50"/></td></tr>';
	}
  echo <<<EOT
    <div class="wrap">
         <h2>General Settings</h2>
		<form method="post">
        <fieldset class="options">
		<table>
         <tr>
          <td><p><strong><label for="php_list_uri">PHPList URL:</label></strong></td>
          <td><input name="phplist[php_list_uri]" type="text" id="php_list_uri" value="{$o['php_list_uri']}" size="50" /> <em>http://www.yoursite.com/lists/</em></p></td>
         </tr>
         <tr>
          <td><p><strong><label for="php_list_login">PHPList Admin Login</label></strong></td>
          <td><input name="phplist[php_list_login]" type="text" id="php_list_login" value="{$o['php_list_login']}" size="50" /> <em>Enter PHPList Admin Login</em></p></td>
         </tr>
		 <tr>
          <td><p><strong><label for="php_list_pass">PHPList Admin Password</label></strong></td>
          <td><input name="phplist[php_list_pass]" type="password" id="php_list_pass" value="{$o['php_list_pass']}" size="50" /> <em>Enter PHPList Admin Password</em></p></td>
         </tr>
		  <tr>
          <td valign="top"><p><strong><label for="php_list_listid">PHPList List Information</label></strong></td>
          <td>
		  <table width="100%" border="0">
            <tr>
              <td><em><b>Enter PHPList ID</b></em></td>
              <td><em><b>Enter Name of List</b></em></td>
            </tr>
				{$lists}
          </table>
		<input type="submit" name="reset" value="Reset &raquo;" style="font-weight:bold;float:right" />
		  <input type="submit" name="add_more" value="Add Another List &raquo;" style="font-weight:bold;float:right" />
			<div style="clear:both"></div>
            <p><em>For each list enter the list number and name of the list in the above table.  <a href="http://www.jesseheap.com/projects/wordpress-phplist-plugin.php#ListID"><strong>See
                help</strong></a> for more info.</em></p>
            </td>
         </tr>
		 <tr>
		  <td></td>
		  <td><p><input name="phplist[php_list_skip_confirm]" type="checkbox" id="php_list_skip_confirm" value="1" {$skip_confirmation_chkbox}/>  <label for="php_list_skip_confirm"><strong>Skip Confirmation Email</strong> (Check to bypass confirmation email)</label></p></td>
		 </tr>
        </table>
        </fieldset>
        <div class="submit">
			  <input type="hidden" name="phplist[php_list_maxLists]" value="{$max_lists}" />
           <input type="submit" name="save_settings" value="Update Options &raquo;" style="font-weight:bold;" />
		</div>
    </div>


	    <div class="wrap">
        <h2>Form Settings</h2>

		<p>Use these settings to <strong>OPTIONALLY</strong> configure your form.  Currently the form supports two text fields.
		The first field supports any text label and should correspond to a text field you capture in PHPList.  Most people capture NAME in this field.  The second field is the <strong>required</strong> email field.</p>
        <fieldset class="options">

    <table>
      <tr>
        <td align="left" ><p><strong>
            <label for "php_list_txt_show">Show on Form?</label>
            </strong></p></td>
        <td align="left" ><p><strong>
            <label for "php_list_txt_label">Text Field Label Name</label>
            </strong></p></td>
        <td align="left"><p><strong>
            <label for "php_list_txt_id">Text Field ID</label>
            </strong></p></td>
        <td align="left"><p><strong>
            <label for="php_list_txt_size">Text Field Size</label>
            </strong></td>
        <td align="left"><p><strong>
            <label for="php_list_txt_max">Text Field Max Size</label>
            </strong></td>
        <td align="left"><p><strong>
            <label for="php_list_txt_req">Required Field?</label>
            </strong></td>
      </tr>
      <tr>
        <td><input name="phplist[php_list_txt_show]" type="checkbox" id="php_list_txt_show" value="1" {$show_optional_field} />
        </td>
        <td><input name="phplist[php_list_txt_label]" type="text" id="php_list_txt_label" value="{$o['php_list_txt_label']}" size="20" />
        </td>
        <td><input name="phplist[php_list_txt_id]" type="text" id="php_list_txt_id" value="{$o['php_list_txt_id']}" size="20" />
        </td>
        <td><input name="phplist[php_list_txt_size]" type="text" id="php_list_txt_size" value="{$o['php_list_txt_size']}" size="5" /></td>
        <td><input name="phplist[php_list_txt_max]" type="text" id="php_list_txt_max" value="{$o['php_list_txt_max']}" size="5" /></td>
        <td><input name="phplist[php_list_txt_req]" type="checkbox" id="php_list_txt_req" value="1"  {$optional_field_req} /></td>
      </tr>
      <tr>
        <td><em>Check to show field on form</em></em></td>
        <td><em>Enter Text Label (i.e. Name)</em></td>
        <td><em>Enter Text ID - <a href="http://www.jesseheap.com/projects/wordpress-phplist-plugin.php#FieldID" target="_blank">see
          help </a>for more information</em></td>
        <td><em>Enter size of text field</em></td>
        <td><em>Enter max size of text field</em></td>
        <td><em>Check to make field required</em></td>
      </tr>
      <tr>
        <td><input name="phplist[php_list_email_show]" type="checkbox" id="php_list_email_show" disabled value="1" checked="checked">
        </td>
        <td ><input name="phplist[php_list_email]" type="text" id="php_list_email"  value="{$o['php_list_email']}" size="20" /> </p>
        </td>
        <td ><input name="phplist[php_list_email_id]" type="text" id="php_list_email_id" disabled value="email" size="20" /></p>
        </td>
        <td ><input name="phplist[php_list_email_size]" type="text" id="php_list_email_size"  value="{$o['php_list_email_size']}" size="5" /> </p>
        </td>
        <td ><input name="phplist[php_list_email_max]" type="text" id="php_list_email_max"  value="{$o['php_list_email_max']}" size="5" /> </p>
        </td>
        <td ><input name="phplist[php_list_email_req]" type="checkbox" id="php_list_email_req" disabled value ="1" checked="checked"/> </p>
        </td>
      </tr>
      <tr>
        <td colspan="6">
          <input name="phplist[php_list_show_css]" id="php_list_show_css" type="checkbox" value="1" {$show_css}>
          Use default CSS for subscriber form. (Uncheck to use your own CSS for the form)</td>
      </tr>
    </table>
        </fieldset>
        <div class="submit">
           <input type="submit" name="save_form_settings" value="Update Options &raquo;" style="font-weight:bold;" /></div>
        </form>
    </div>

	<div class="wrap">
	<h2>Information & Support</h2>

  <p>Visit <a href="http://www.jesseheap.com/projects/wordpress-phplist-plugin.php?ver=<?php echo $ver ?>" target="_blank">our
    help section</a> for installation and help</p>
  <p><strong>Like this script?</strong> Show your support by linking to <a href="http://www.jesseheap.com">our
    site</a> - www.jesseheap.com.</p>
	</div>
EOT;
} // end phplist_subpanel()

/* Original Author: Ryan Duff */
function phplist_is_malicious($input) {
	$is_malicious = false;
	$bad_inputs = array("\r", "\n", "mime-version", "content-type", "cc:", "to:");
	foreach($bad_inputs as $bad_input) {
		if(strpos(strtolower($input), strtolower($bad_input)) !== false) {
			$is_malicious = true; break;
		}
	}
	return $is_malicious;
}


/* This function checks for errors on input and changes $phplist_strings if there are any errors.  Returns false if there has not been a submission */
/* Original Author: Ryan Duff */
function phplist_check_input()
{
	if(!(isset($_POST['phplist_submit']))) {return false;} // Exit returning false.
    $email = $_POST['email'];
	$email = stripslashes(trim($email));
    $email = paranoid($email, array('@', '.', '_', '-'));
	$optionvalue = paranoid($_POST[$txt_id], array('-', '_', ' '));
	global $phplist_strings, $admin_phplist_config_page;
	$ok = true;


	/*See if custom field is required */
	$o = phplist_get_options();
    $required = $o['php_list_txt_req'];
	$txt_id = $o['php_list_txt_id'];
	$txt_size = $o['php_list_txt_size'];
	$txt_max = $o['php_list_txt_max'];
	$txt_show = $o['php_list_txt_show'];
    $email_size = $o['php_list_email_size'];
	$email_max = $o['php_list_email_max'];

	if (!(phplist_using_single_list($o))) {
		$max_lists = (empty($o['php_list_maxLists'])) ? 4 : $o['php_list_maxLists'];
		if (!is_array($_POST['list'])) {
				$ok = false; $reason = __('No Mailing List','phplist');
		}

	}
	if (strlen($required)>0 && $txt_show) {
			if(empty($_POST[$txt_id]))  {
				$ok = false; $reason = 'empty';
				$phplist_strings['field1'] = '<input class="requiredOutline" type="text" name="'. $txt_id .'" id="' . $txt_id .'" size="' . $txt_size . '" maxlength="'. $txt_max . '" value="' .$optionvalue . '"  /> <br class="br" /> ';
			}
	}
    if(!is_email($email))
    {
	    $ok = false; $reason = 'Invalid Email';
	    $phplist_strings['email'] = '<input class="requiredOutline" type="text" name="email" id="email" size="'. $email_size .'" maxlength="'. $email_max .'" value="' . $email . '"  /> <br class="br" />';
	}

	if( phplist_is_malicious($email)) {
		$ok = false; $reason = 'malicious';
	}

	if($ok == true)
	{
		return true;
	}
	else {
		if($reason == 'malicious') {
			$phplist_strings['error'] = "<div class='required'>". __("You can not use any of the following in the Name or Email fields: a linebreak, or the phrases 'mime-version', 'content-type', 'cc:' or 'to:'.",'phplist') . "</div>";
			}
		elseif($reason == 'empty') {
			$phplist_strings['error'] = '<label for="Error"></label><div class="required">'. __('Missing Required Field','phplist') . '</div>';
			}
		 elseif($reason == 'Invalid Email')  {
			$phplist_strings['error'] = '<label for="Error"></label><div class="required">' . __('Invalid Email','phplist') . '</div>';
			}
		 elseif($reason == 'No Mailing List')  {
			$phplist_strings['error'] = '<label for="Error"></label><div class="required">' . __('You Must Check a Mailing List','phplist') . '</div>';
			}
	   return false;
	}
}


/* From http://api.cakephp.org/sanitize_8php-source.html#l00046 */
/* Thanks to Gordon */
function paranoid($string, $allowed = array()) {
        $allow = null;
         if (!empty($allowed)) {
             foreach ($allowed as $value) {
                 $allow .= "\\$value";
             }
         }

         if (is_array($string)) {
             foreach ($string as $key => $clean) {
                 $cleaned[$key] = preg_replace("/[^{$allow}a-zA-Z0-9]/", "", $clean);
             }
         } else {
             $cleaned = preg_replace("/[^{$allow}a-zA-Z0-9]/", "", $string);
         }
         return $cleaned;
     }

/*Wrapper function which calls the form.*/
/* Original Author: Ryan Duff */
function phplist_callback( $content )
{

	/* Check to see if the phplist form comment is found in the incoming content
	   If no match then return the content as is */
		if(! preg_match('|<!--phplist form-->|', $content)) {
		return $content;
		}

    if(phplist_check_input()) // If the input check returns true (ie. there has been a subscriber & input is ok)
    {
           phplist_subscribe($_POST);
    }
    else // Else show the form. If there are errors the strings will have updated during running the inputcheck.
    {
 		$form = phplist_construct_form();

        return str_replace('<!--phplist form-->', $form, $content);
    }
}

function phplist_using_single_list($options) {
	return (empty($options["php_list_listid2"]));
	}
function phplist_generate_list_elements () {
	$o = phplist_get_options();
	$max_lists = (empty($o['php_list_maxLists'])) ? 4 : $o['php_list_maxLists'];
	if (phplist_using_single_list($o)) {
		$lists_form = '<input type="hidden" name="list['. $o["php_list_listid1"] . ']" value="signup">';
	}
	else
	{
		$lists_mes = '<br><span style="caption">' . __('Please select the newsletters you want to sign up for:','phplist') . '</span><br>';
		$lists_ul = '<ul class="list">';
		$lists_li = '<li class="list">';
		$lists_form = $lists_mes . $lists_ul;
		$counter=1;
		for ($counter; $counter <$max_lists; $counter += 1) {
			if (!(empty($o["php_list_listid" . $counter]))) {
				$lists_form = $lists_form . $lists_li . ' <input type="checkbox" name="list[' . $o["php_list_listid". $counter ] . ']" value=signup  />
				<b>' . $o['php_list_listname'. $counter ] .'</b></li>';
			}
		}
		$lists_form = $lists_form . '</ul>';
	}
	return $lists_form;
}
function phplist_construct_form($widget = false) {
	global $phplist_strings, $admin_phplist_config_page;
	if($widget) {
		$labelBreak = '<br />';
	} else {
		$labelBreak = '';
	}
	$o = phplist_get_options();
       if (strlen($o['php_list_uri'])>0) {
			$label_name = $o['php_list_txt_label'];
			$txt_id = $o['php_list_txt_id'];
			$email_name = $o['php_list_email'];
			$txt_show = $o['php_list_txt_show'];

			if ($txt_show==true)  {
				$txt_optional_field = '<label for="'. $txt_id . '" '. (strlen($o['php_list_txt_req'])>0 ? 'class="required"':  '') . '>' . __($label_name, 'phplist') . '</label>'.$labelBreak.$phplist_strings['field1'];
			}
			else {
				$txt_optional_field = '';
			}

			$form = '
				<form action="" method="post" class="phplist">
			' . $phplist_strings['error'] . '
				' . $txt_optional_field . '
			    <label for="email" class="required">' . __($email_name, 'phplist') . '</label>'.$labelBreak. $phplist_strings['email'] . '
					'. phplist_generate_list_elements() . '
					<label for="kludge"></label>
					<input type="submit" name="Submit" value="' . __('Submit', 'phplist') . '" id="contactsubmit" />
					<input type="hidden" name="phplist_submit" value="process" />
				</form>
			<div style="clear:both; height:1px;">&nbsp;</div>';
			}
		else { //Key configuration option has not been set - alert user.
			$form = '<h3>' . sprintf(__('Must <a href= "%s"> configure options</a> under admin panel before using form','phplist'), $admin_phplist_config_page ). '</h3>';
			}
		return $form;
}
/*CSS Styling*/
function phplist_css()
	{
	global $ver;
	$phplist_wp_url = get_bloginfo('wpurl') . "/";
	$installpath  =  plugin_basename(dirname(__FILE__));
	if (strcmp($installpath, '.') == 0 ) {
		$installpath=''; }
	else {
		$installpath=$installpath . "/"; }
	
	echo "\n\t<!-- CSS Added By PhpList Plugin. Version {$ver} -->\n";
    echo "\n\t<link href='{$phplist_wp_url}wp-content/plugins/{$installpath}phplist.css' rel='stylesheet' type='text/css' />";
	}


function phplist_admin_menu() {
   if (function_exists('add_options_page')) {
        add_options_page('phplist Options Page', 'PHPlist', 8, basename(__FILE__), 'phplist_subpanel');
        }
}

add_action('admin_menu', 'phplist_admin_menu');
add_filter('the_content', 'phplist_callback', 99);
$o = phplist_get_options();
if  ($o['php_list_show_css'] ==TRUE)  {
	add_filter('wp_head', 'phplist_css');
	}
?>