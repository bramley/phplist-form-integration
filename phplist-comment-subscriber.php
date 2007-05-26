<?php
/*
Plugin Name: Phplist-Comment-Subscriber
Plugin URI: http://projects.jesseheap.com/all-projects/wordpress-plugin-phplist-comment-subscriber/
Description: This plugin gives users the option to be automatically added to your phplist mailing list after 
leaving a comment on your blog
Version: .2
Author: Jesse Heap
Author URI: http://projects.jesseheap.com
*/
$phplist_sub_ver = .2;
class comment_sub_phplist {
	var $domain;
	var $lid;   // lid is the default PHPlist List ID to use 
	var $login;
	var $pass;
	var $skipConfirmationEmail;     // Set to 0 if you require a confirmation email to be sent  
    var $default_subscribed;
	var $subscriber_text;
    var $name_id;
	var $email_id;
	function comment_sub_phplist ($domain,  $lid, $login, $pass, $skipConfirmationEmail, $email, $name, $chkboxtxt, $showchkbox) {
			$this->domain = $domain;
			$this->lid = $lid;
			$this->login = $login;
			$this->pass = $pass;
			if ($skipConfirmationEmail=='true') $this->skipConfirmationEmail = 1;
			else $this->skipConfirmationEmail = 0;
			if ($showchkbox=='true') $this->default_subscribed = 1;
			else $this->default_subscribed = 0;			
			$this->name_id = $name;
			$this->email_id = $email;
			$this->subscriber_text = $chkboxtxt;
	}
	function subscribe($input_data) {
			
			if (!phplist_check_curl()) {
				echo 'CURL library not detected on system.  Need to compile php with cURL in order to use this plug-in';
				return(0);
				}
		//   $post_data = array(); 
			foreach ($input_data as $varname => $varvalue) {
			 $post_data[$varname] = $varvalue;
		   }   
		   // Ensure email is provided 
		   $email = $post_data[$this->email_id]; 
		 //  $tmp = $_POST['lid']; 
		 //  if ($tmp != '') {$lid = $tmp; }   //user may override default list ID 
		   if ($email == '') { 
		    //echo('You must supply an email address'); 
			return(0); 
		   } 
		
		// 3) Login to phplist as admin and save cookie using CURLOPT_COOKIEFILE 
		// NOTE: Must log in as admin in order to bypass email confirmation
		   $url = $this->domain . "admin/?"; 
		   $ch = curl_init(); 
		   $login_data = array(); 
		   $login_data["login"] = $this->login; 
		   $login_data["password"] = $this->pass; 
		   curl_setopt($ch, CURLOPT_POST, 1); 
		   curl_setopt($ch, CURLOPT_URL, $url);    
		   curl_setopt($ch, CURLOPT_POSTFIELDS, $login_data); 
		   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		   curl_setopt($ch, CURLOPT_COOKIEFILE, ""); //Enable Cookie Parser.  
		   //File does not need to exist - http://curl.netmirror.org/libcurl/c/libcurl-tutorial.html for more info 
		   $result = curl_exec($ch); 
		  // echo("Result was: $result"); //debug 
		   if (curl_errno($ch)) {
		   //print '<h3 style="color:red">Error: ' . curl_error($ch) . ' </h3>';
		   return(0);
			} 
		
		// 3) Now simulate post to subscriber form.  
		   $post_data["emailconfirm"] = $email; 
		   $post_data["htmlemail"] = "1"; 
		   $post_data["list[$this->lid]"] = "signup"; 
		   $post_data["subscribe"] = "Subscribe"; 
		   $post_data["makeconfirmed"] = $this->skipConfirmationEmail;  //If set to 1 it will confirm user bypassing confirmation email 
		   $url = $this->domain . "?p=subscribe"; 
		
		   curl_setopt($ch, CURLOPT_POST, 1); 
		   curl_setopt($ch, CURLOPT_URL, $url);    
		   curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data); 
		   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		   $result = curl_exec($ch); 
		//   echo('Result was: ' .$result); 
		   if (curl_errno($ch)) {
		   print '<h3 style="color:red">Error: ' . curl_error($ch) . ' </h3>';
		   return(0);
			} 
		  //echo ('<h3>Thank you for subscribing to our email list</h3>');
		//) Clean up 
		   curl_close($ch);

 }  // end of function
 
 function show_sub_checkbox()	{ 
 		$set_subscriber_chkbox = ($this->default_subscribed == 'true') ? ' ' : ' checked="checked"';
   echo <<<EOT
         <input type="checkbox" name="subscribe" id="subscribe" value="subscribe" style="width: auto;" {$set_subscriber_chkbox} />
         <label for="subscribe"><small>{$this->subscriber_text}</small></label>
EOT;
}
 function add_subscriber($cid) {
 		global $wpdb;
		$id = (int) $id;
//    	$email = $wpdb->escape(strtolower($wpdb->get_var("SELECT comment_author_email FROM $wpdb->comments WHERE comment_ID = '$cid'")));
//	    $name = $wpdb->escape(strtolower($wpdb->get_var("SELECT comment_author FROM $wpdb->comments WHERE comment_ID = '$cid'")));
		
		foreach ($_POST as $varname => $varvalue) {
		    if ($varname=='author') $varname=$this->name_id;
			$post_data[$varname] = $varvalue;
		}
		// If user wants to subscribe and is a valid email and not spam
		if (($_POST['subscribe'] == 'subscribe' && is_email($_POST[$this->email_id]) && (!$this->is_spam($cid)))) {
		   $this->subscribe($post_data);	
		   }   
		return $cid;
	}
  function is_spam($cid) {
  	global $wpdb;
	$comment = $wpdb->get_row("SELECT * FROM $wpdb->comments WHERE comment_ID = '$cid'");
	if ($comment->comment_approved == 'spam')
		return true;
	else
		return false;
	}
} //End Class
/* Main Entry Point */
$o = phplist_comments_get_options();
$subscriber = new comment_sub_phplist($o['php_list_uri'],  $o['php_list_listid'], $o['php_list_login'], $o['php_list_pass'],$o['php_list_skip_confirm'], $o['php_list_email_id'], $o['php_list_name_id'], $o['php_list_chkbx_txt'], $o['php_list_chkbx']);

function phplist_comments_get_options(){
		$defaults = array();
		$defaults['php_list_uri'] = 'http://www.yourphplisturl.com/lists/';
		$defaults['php_list_login'] = 'admin';
		$defaults['php_list_pass'] = 'Enter Password';
		$defaults['php_list_listid'] = 'Enter List ID';
		$defaults['php_list_skip_confirm'] = '';
		$defaults['php_list_email_id']='email';
		$defaults['php_list_name_id']='attribute1';
		$defaults['php_list_chkbx_txt']= 'Check this box to subscribe to our newsletter.';
		$defaults['php_list_chkbx']='';
				
		$options = get_option('phplistCommentssettings');

		if (!is_array($options)){
			$options = $defaults;
			update_option('phplistCommentssettings', $options);
		}
		return $options;
	}

function phplist_comments_check_curl() {
 if (function_exists('curl_exec')) return(1);
	else return (0);
}

function phplist_comments_subpanel() {
	global $phplist_sub_ver;
//     if (isset($_POST['save_settings'])) {
//      	phplist_save_general_settings();
		//If the txt label isn't set, then set all the options to their default settings
		//if (strlen(get_option('php_list_txt_label')) ==0) {
		//phplist_save_default_form_settings();
	    //  }
//	  }
       
     if (isset($_POST['phplist'])) {
		update_option('phplistCommentssettings', $_POST['phplist']);
		$message = '<div class="updated"><p><strong>Options saved.</strong></p></div>';
	}

	if (!phplist_comments_check_curl()) {
		echo 'CURL library not detected on system.  Need to compile php with cURL in order to use this plug-in';
	    return(0);
		}  
		$o = phplist_comments_get_options();
		$skip_confirmation_chkbox = ($o['php_list_skip_confirm'] == 'true') ? ' checked="checked"' : '';
		$set_subscriber_chkbox = ($o['php_list_chkbx'] == 'true') ? ' checked="checked"' : '';
  echo <<<EOT
    <div class="wrap">   
		{$message}
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
		  <td><p><input name="phplist[php_list_skip_confirm]" type="checkbox" id="php_list_skip_confirm" value="true" {$skip_confirmation_chkbox}/>  <label for="php_list_skip_confirm"><strong>Skip Confirmation Email</strong> (Check to bypass confirmation email)</label></p></td>
		 </tr>
        </table>
        </fieldset>
    <h2>Form Settings</h2>
<legend>Use these settings to ensure the comment email and name fields are
captured in the correct phplist fields.  <strong>See <a href="http://projects.jesseheap.com/all-projects/wordpress-plugin-phplist-comment-subscriber/#FAQ">FAQ</a> for help</strong></legend>
<fieldset class="form">
    <table>
      <tr> 
        <td width="20%">Email
        </td>
        <td><input name="phplist[php_list_email_id]"  type="text" id="php_list_email_id" value="{$o['php_list_email_id']}" size="20" /> <label for="php_list_email_id"><em> PHPList ALWAYS uses email. This should not change</em></label>
        </td>
     </tr>
	 <tr> 
        <td width="20%">Name
        </td>
        <td><input name="phplist[php_list_name_id]" type="text" id="php_list_name_id" value="{$o['php_list_name_id']}" size="20" /> <label for="php_list_email_id"><em> Enter the ID phplist uses to store your NAME Field</em></label>
        </td>
     </tr>
	 </table>
	 <table>
	 <tr>
      <td width="30%">
	 <label for="php_list_chkbx_txt">Add Text for Subscriber Checkbox</label>	 
	  <td> <p><input name="phplist[php_list_chkbx_txt]" type="text" id="php_list_chkbx_txt" value="{$o['php_list_chkbx_txt']}" size="50" /> </p></td>	 </td>
	 </tr>
     <tr>
      <td colspan="2"><p><input name="phplist[php_list_chkbx]" type="checkbox" id="php_list_chkbx" value="true" {$set_subscriber_chkbox}/>  <label for="php_list_chkbx"><strong>Subscriber Checkbox Default </strong><em>If checked the subscriber checkbox will be checked by default</em></label></p></td>	 
	 </tr>
	</table>
        </fieldset>
        <p>
		<div class="submit">
           <input type="submit" name="save_settings" value="Update Options &raquo;" style="font-weight:bold;" /></div>
		   
        </p>
        </form>
    </div>
	
	<div class="wrap">
	<h2>Information & Support</h2>
	
  <p>Visit <a href="http://projects.jesseheap.com/all-projects/wordpress-plugin-phplist-comment-subscriber//?ver={$phplist_sub_ver}" target="_blank">our 
    help section</a> for installation and help</p>
  <p><strong>Like this script?</strong> Show your support by linking to <a href="http://www.jesseheap.com">our 
    site</a> - www.jesseheap.com.</p>
	</div>
EOT;
} // end phplist_subpanel()

function phplist_comments_admin_menu() {
   if (function_exists('add_options_page')) {
        add_options_page('phplist Comments Options Page', 'PHPlist Comments', 8, basename(__FILE__), 'phplist_comments_subpanel');
        }
}
/*Hooks */
add_action('admin_menu', 'phplist_comments_admin_menu');
add_action('comment_post', array(&$subscriber,'add_subscriber'), 50);
add_action('comment_form', array(&$subscriber, 'show_sub_checkbox'));
?>