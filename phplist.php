<?php
/*
Plugin Name: PHPlist
Plugin URI: http://www.jesseheap.com/projects/wordpress-phplist-plugin.php?ver=1.0
Description: Allows you to easily integrate the PHPList subscribe form in your blog
Version: 1.3
License: GPL
Author: Jesse Heap
Author URI: http://www.jesseheap.com
Contributors:
 ==============================================================================
   Rich Cowan 							Initial idea for PHPList API		

Thanks to Ryan Duff, creator of the Wordpress Contact form plug-in (http://ryanduff.net), for the
Spam Handling Feature, CSS, and flexible design idea for dropping the subscriber form on any wordpress page.
*/
/*Globals*/
$admin_phplist_config_page = get_settings('siteurl') . '/wp-admin/options-general.php?page=phplist.php"';
$ver='1.3';
$o = phplist_get_options();
$phplist_strings = array(
	'field1' => '<input type="text" name="'. $o['php_list_txt_id'] . '" id="'. $o['php_list_txt_id'] .'" size="'. $o['php_list_txt_size'] .'" maxlength="'. $o['php_list_txt_max'] .'" value="' . $_POST[$o['php_list_txt_id']] . '" /> '. (strlen($o['php_list_txt_req'])>0 ? '(required)':  '') . '<br/>',
	'email' => '<input type="text" name="email" id="email" size="'. $o['php_list_email_size'] .'" maxlength="'. $o['php_list_email_max'] .'" value="'. $_POST['email'] .'" /> (' . __('required', 'phplist') . ')' .'<br/>',
	'error' => '');

function phplist_subscribe($input_data) {
    global $admin_phplist_config_page;
	$o=phplist_get_options();
  	$domain = $o['php_list_uri'];
	$lid =$o['php_list_listid'];      // lid is the default PHPlist List ID to use 
	$login =  $o['php_list_login'];
	$pass = $o['php_list_pass'];
	$skipConfirmationEmail = $o['php_list_skip_confirm'];               // Set to 0 if you require a confirmation email to be sent.  

	if (!phplist_check_curl()) {
		echo 'CURL library not detected on system.  Need to compile php with cURL in order to use this plug-in';
	    return(0);
		}
//   $post_data = array(); 
	foreach ($input_data as $varname => $varvalue) {
     $post_data[$varname] = $varvalue;
   }    
   // Ensure email is provided 
   $email = $post_data['email']; 
 //  $tmp = $_POST['lid']; 
 //  if ($tmp != '') {$lid = $tmp; }   //user may override default list ID 
   if ($email == '') { 
         echo('You must supply an email address'); 
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
   print '<h3 style="color:red">Error: ' . curl_error($ch) . ' Check <a href=" '. $admin_phplist_config_page .'"> admin config options page.</a> </h3>';
   return(0);
	} 

// 3) Now simulate post to subscriber form.  
   $post_data["emailconfirm"] = $email; 
   $post_data["htmlemail"] = "1"; 
   $post_data["list[$lid]"] = "signup"; 
   $post_data["subscribe"] = "Subscribe"; 
   $post_data["makeconfirmed"] = $skipConfirmationEmail;  //If set to 1 it will confirm user bypassing confirmation email 
   $url = $domain . "?p=subscribe"; 

   curl_setopt($ch, CURLOPT_POST, 1); 
   curl_setopt($ch, CURLOPT_URL, $url);    
   curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data); 
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
   $result = curl_exec($ch); 
 //  echo('Result was: ' .$result); 
   if (curl_errno($ch)) {
   print '<h3 style="color:red">Error: ' . curl_error($ch) . ' Check <a href=" '. $admin_phplist_config_page .'"> admin config options page.</a> </h3>';
   return(0);
	} 
  echo ('<h3>Thank you for subscribing to our email list</h3>');
//) Clean up 
   curl_close($ch);

 }  // end of function

function phplist_get_options(){
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
		$defaults['php_list_listid']= '';
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
		if ($_POST['phplist']){
		    //Check for trailing slash
			  $option_phplisturi = $_POST['phplist']['php_list_uri'];
			  if  (strcmp($option_phplisturi[strlen($option_phplisturi)-1], '/') !=0) {
	 				$_POST['phplist']['php_list_uri'] .= '/';
	 			}
			update_option('phplistsettings', $_POST['phplist']);
			$message = '<div class="updated"><p><strong>Options saved.</strong></p></div>';
		}
		
	if (!phplist_check_curl()) {
		echo 'CURL library not detected on system.  Need to compile php with cURL in order to use this plug-in';
	    return(0);
		}  
	
	$o = phplist_get_options();
	$skip_confirmation_chkbox = ($o['php_list_skip_confirm'] == 1) ? ' checked="checked"' : '';
	$show_optional_field = ($o['php_list_txt_show'] == 1) ? ' checked="checked"' : '';
    $optional_field_req = ($o['php_list_txt_req'] == 1) ? ' checked="checked"' : '';
	$show_css = ($o['php_list_show_css'] == 1) ? ' checked="checked"' : '';
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
          <td><p><strong><label for="php_list_listid">PHPList List ID</label></strong></td>
          <td><input name="phplist[php_list_listid]" type="text" id="php_list_listid" value="{$o['php_list_listid']}" size="50" />
          <em>Enter Number of list you want to subscribe to. <a href="http://www.jesseheap.com/projects/wordpress-phplist-plugin.php#ListID"><strong>See 
          help</strong></a> for more info.</em></p></td>
         </tr>
		 <tr>
		  <td></td>
		  <td><p><input name="phplist[php_list_skip_confirm]" type="checkbox" id="php_list_skip_confirm" value="1" {$skip_confirmation_chkbox}/>  <label for="php_list_skip_confirm"><strong>Skip Confirmation Email</strong> (Check to bypass confirmation email)</label></p></td>
		 </tr>
        </table>
        </fieldset>
        <div class="submit">
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

	$_POST['email'] = stripslashes(trim($_POST['email']));

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
	
    if (strlen($required)>0 && $txt_show) {
			if(empty($_POST[$txt_id]))  {
				$ok = false; $reason = 'empty';
				$phplist_strings['field1'] = '<input class="requiredOutline" type="text" name="'. $txt_id .'" id="' . $txt_id .'" size="' . $txt_size . '" maxlength="'. $txt_max . '" value="' . $_POST[$txt_id] . '"  /> (' . __('required', 'phplist') . ')';
			}
	}
    if(!is_email($_POST['email']))
    {
	    $ok = false; $reason = 'Invalid Email';
	    $phplist_strings['email'] = '<input class="requiredOutline" type="text" name="email" id="email" size="'. $email_size .'" maxlength="'. $email_max .'" value="' . $_POST['email'] . '"  /> (' . __('required', 'phplist') . ')';
	}

	if( phplist_is_malicious($_POST['email'])) {
		$ok = false; $reason = 'malicious';
	}

	if($ok == true)
	{
		return true;
	}
	else {
		if($reason == 'malicious') {
			$phplist_strings['error'] = "<div class='required'>You can not use any of the following in the Name or Email fields: a linebreak, or the phrases 'mime-version', 'content-type', 'cc:' or 'to:'.</div>";
			}
		elseif($reason == 'empty') {
			$phplist_strings['error'] = '<label for "Error"></label><div class="required">Missing Required Field </div>';
			}
		 elseif($reason == 'Invalid Email')  {
			$phplist_strings['error'] = '<label for "Error"></label><div class="required">Invalid Email</div>';
			}
	   return false;
	}
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

function phplist_construct_form() {
	global $phplist_strings, $admin_phplist_config_page;
	$o = phplist_get_options();
       if (strlen($o['php_list_uri'])>0) {
			$label_name = $o['php_list_txt_label'];
			$txt_id = $o['php_list_txt_id'];
			$email_name = $o['php_list_email'];
			$txt_show = $o['php_list_txt_show'];			

			if ($txt_show==true)  {
				$txt_optional_field = '<label for="'. $txt_id . '">' . __($label_name, 'phplist') . '</label>'. $phplist_strings['field1'];
			}
			else { 
				$txt_optional_field = '';
			}

			$form = '       	
				<form action="" method="post" class="phplist">
			' . $phplist_strings['error'] . '
			
				' . $txt_optional_field . '
			    <label for="email">' . __($email_name, 'phplist') . '</label>'. $phplist_strings['email'] . '
					<label for="kludge"></label><input type="submit" name="Submit" value="' . __('Submit', 'phplist') . '" id="contactsubmit" />
					<input type="hidden" name="phplist_submit" value="process" />
				</form>
			<div style="clear:both; height:1px;">&nbsp;</div>'; 
			}
		else { //Key configuration option has not been set - alert user.
			$form = '<h3>Must <a href= "' . $admin_phplist_config_page . '"> configure options</a> under admin panel before using form</h3>';
			}
		return $form;
}
/*CSS Styling*/
function phplist_css()
	{
	global $ver;
	$phplist_wp_url = get_bloginfo('wpurl') . "/";
	echo "\n\t<!-- CSS Added By PhpList Plugin. Version {$ver} -->\n";
    echo "\n\t<link href='{$phplist_wp_url}wp-content/plugins/phplist.css' rel='stylesheet' type='text/css' />";
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