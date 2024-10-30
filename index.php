<?php
/*
  Plugin Name: Lanoba Social Plugin
  Plugin URI: http://www.lanoba.com/
  Description: Provides authentication, registration and social sharing capabilities through the Lanoba Social API.
  Version: 1.2
  Author: Lanoba
  Author URI: http://www.lanoba.com/
  License: GPL2
 */
error_reporting(0);

ob_start();

session_set_cookie_params(10 * 60);

if (!session_id())
    session_start();

//---------------------------------------------------------------------------------
function lb_InitScripts() {

    include_once('LanobaSDK.class.php');

    wp_deregister_script('lanobajs');
    wp_register_script('lanobajs', 'http://social.' . get_option('socialDomain') . '/js');
    wp_enqueue_script('lanobajs');
}

//------------------------------------------------------------------------------
function lb_RegisterPost() {

    global $lb_error;

    lb_LoginPage();

    if (isset($_POST['user_login']))
        $_SESSION['lb_username'] = $_POST['user_login'];

    if (isset($_POST['user_email']))
        $_SESSION['lb_email'] = $_POST['user_email'];

    if (lb_ValidateData()) {
        
        lb_RegisterUser();
        
    } else {
        
        $lb_error = "<b>Lanoba Registration API</b><br></br><p>A valid username and/or email were not retrieved from your social account.</p></br> 
            <p>Please fill them up in the fields below to proceed with the registration process.</p><br>";
    }
}

//------------------------------------------------------------------------------
function lb_LoginErrors($error) {
    
    global $lb_error;

    return $lb_error . $error;
}

// ------------------------------------------------------------------------------

function lb_LoginWidget($type="login") {
    ?>	
    <div id="login_el"></div>
    <script type="text/javascript">
        social.widgets.<?php echo $type; ?>({ container: "login_el" });
    </script>
    <br />

    <?php
}

//-------------------------------------------------------------------------------
function SharingHeader() {
    ?>
    <script type="text/javascript">         
        function ShareIt(title,description,link,photo)
        {social.widgets.share({"title":title,"description":description,"link":link,"photo":photo});}
    </script>

    <?php
}

//-------------------------------------------------------------------------------
function lb_ShareWidget($content) {
    try {

        if (!class_exists("DOMDocument"))
            return $content;

        $doc = new DOMDocument();

        if (!$doc)
            return $content;

        if (!$doc->loadHTML($content))
            return $content;

        $imageTags = $doc->getElementsByTagName('img');

        if (count($imageTags))
            foreach ($imageTags as $tag) {
                $photo = $tag->getAttribute('src');
                break;
            }
        else
            $photo="http://www.lanoba.com/images/logo.png";

        $protocol = "http" . (($_SERVER['SERVER_PORT'] == '443') ? "s" : "") . "://";

        if (strlen($photo) && !preg_match("/^(.*[:])?\/\//", $photo)) {

            if (preg_match("/^\//i", $photo)) {
                $photo = $_SERVER['HTTP_HOST'] . $photo;
            } else {
                $photo = $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . "/" . $photo;
                $photo = $protocol . str_replace("//", "/", $photo);
            }
        }

        $stripped_content = preg_replace('/\s+/', ' ', preg_replace('/[\x00-\x1F\x80-\xFF]/', ' ', strip_tags($content)));

        $title = rawurlencode(get_the_title($post->post_parent));
        $link = rawurlencode(get_permalink($post->post_parent));
        $description = rawurlencode(substr($stripped_content, 0, 200) . (strlen($stripped_content) > 200 ? '...' : ''));
        $photo = rawurlencode($photo);

        $widget = "<div style='width: 100%; text-align: right;'>
            <a href='javascript:ShareIt(\"$title\",\"$description\",\"$link\",\"$photo\")'>
              <img src='http://social.lanoba.com/img/share_button.png' border=0/></a></div>";
        
        return $widget . $content . $widget;
        
    } catch (Exception $e) {
        
        return $content;
    }
}

// ------------------------------------------------------------------------------

function lb_SigninUser($user_id) {

    wp_set_auth_cookie($user_id);

    setcookie('LSESSID', $_SESSION['token'], time() + 1209600, '/', '.' . get_option('socialDomain'));

    $lb_uri = preg_replace('/wp-login\.php(.*)/', '', $_SERVER['REQUEST_URI']);

    session_unset();

    header("Location: $lb_uri"); exit;
}

// ---------------------------------------------------------------------------------

function lb_MapUser($user_id, $uid) {
    try {

        if (!class_exists(LanobaSDK))
            return;

        $sdk = new LanobaSDK(get_option('APISecret'));

        if (!$sdk)
            return;

        if (!empty($uid)) {
            $mapParams['uid'] = $uid;
        }

        $mapParams['mapped_uid'] = $user_id;

        $response = $sdk->post(empty($uid) ? "unmap" : "map", $mapParams);
    } catch (Exception $e) {
        echo "<div style='color: maroon'>Internal System Error! Error Message: " . $e->getMessage() . "</div>";
    }
}

//------------------------------------------------------------------------------
function lb_Redirect($errorCode) {

    if (!empty($errorCode))
        $errMsg = "&errorCode=$errorCode";

    header('Location: ' . $_SERVER['PHP_SELF'] . "?action=register{$errMsg}"); exit;

}

//------------------------------------------------------------------------------ 
function lb_RegisterUser() {

    try {
        if (!isset($_SESSION['lb_uid'])) {

            lb_Redirect(2);
        }

        $user_pass = wp_generate_password();
        $user_login = $_SESSION['lb_username'];

        $user_id = wp_insert_user(array('ID' => '',
            'user_pass' => $user_pass,
            'user_login' => $user_login,
            'user_email' => $_SESSION['lb_email'],
            'user_url' => $_SESSION['link'],
            'first_name' => $_SESSION['first_name'],
            'last_name' => $_SESSION['last_name'],
            'display_name' => $_SESSION['name']));

        if (is_int($user_id)) {
            // send user an email with username and password when the registration process is succesfull      
            $fromEmail = get_option('fromEmail');
            $fromName = get_option('fromName');

            $headers = "From: $fromName <$fromEmail>\r\n";

            $lb_uri = preg_replace('/\?.*/', '', ($_SERVER['SERVER_PORT'] == "443") ? "https" : "http" . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            ;

            lb_MapUser($user_id, $_SESSION['lb_uid']);

            update_usermeta($user_id, 'lanoba_id', $_SESSION['lb_uid']);
            update_usermeta($user_id, 'lb_map', "1");

            try {
                wp_mail($_SESSION['lb_email'], '[Lanoba Social API] New User Registration', "Username: $user_login\nPassword: $user_pass\n\n$lb_uri", $headers);
            } catch (Exception $e) {
                
            }

            lb_SigninUser($user_id);
        }
    } catch (Exception $e) {
        lb_Redirect(2);
    }
}

//------------------------------------------------------------------------------
function lb_Logout() {

    setcookie('LSESSID', '', time() - 3600, '/', '.' . get_option('socialDomain'));
    session_unset();
}

//-----------------------------------------------------------------    
function lb_SaveUserData() {

    global $sdk;

    $profiles = $sdk->get('profiles', array('uid' => $_POST['uid']));
    $lb_profile = $profiles['profiles'][0];

    $_SESSION['lb_uid'] = $_POST['uid'];
    $_SESSION['lb_email'] = $lb_profile['email'];
    $_SESSION['lb_username'] = $lb_profile['email'];
    $_SESSION['first_name'] = $lb_profile['first_name'];
    $_SESSION['last_name'] = $lb_profile['last_name'];
    $_SESSION['name'] = $lb_profile['name'];
    $_SESSION['link'] = $lb_profile['link'];
    $_SESSION['mapped_uid'] = $lb_profile['mapped_uid'];
    $_SESSION['lb_signature'] = hash_hmac('MD5', $_POST['uid'], get_option('APISecret'));
}

//-----------------------------------------------------------------------------------
function lb_RegisterForm() {

    global $lb_error;

    $errorCode = @$_GET['errorCode'];

    $messages = array(1 => "A valid username and/or email were not retrieved from your social account.</br> </br>
                            Please fill them up in the fields above to proceed with the registration process.<br>",
        2 => "<b>Registration failed!</b></br></br>
                            <p>This is either an invalid request, or your session expired, or sessions are not functioning properly in your system!</p></br>
                            <p>Please try again using the Lanoba Social Login widget below or contact your system administrator if the problem persists!</p>");
    if ($errorCode):
        $lb_error = $messages[1];

        if ((!@$_SESSION['lb_uid']))
            $lb_error = $messages[2];
        ?>

        <div style='border: 1px solid maroon; background-color: #FFF9F9; color: maroon;  padding: 2px;'>
            <div style='font-size: 12pt; color: black;'><b>Lanoba Registration API</b></br></br></div>

            <?php echo $lb_error; ?>

            </br></div>             
        <?php
    endif;
}

//---------------------------------------------------------------------------------

function lb_LoginPage() {

    global $sdk;

    if (!class_exists(LanobaSDK))
        return;

    $sdk = new LanobaSDK(get_option('APISecret'));

    if (!($sdk && isset($_POST['uid']) && $sdk->verify_signature($_POST)))
        return;

    $_SESSION['token'] = $_POST['token'];

    if (isset($_POST['mapped_uid'])) { //existing user signin
        $user_id = $_POST['mapped_uid'];
        $user = get_userdata($user_id);

        if ($user)
            lb_SigninUser($user_id);
    }

    if (get_option("users_can_register") && get_option("autoRegister")) {  // if auto-register enabled
        lb_SaveUserData();

        if (lb_ValidateData())
            lb_RegisterUser();

        lb_Redirect(1);
    }
}

//------------------------------------------------------------------------------
function ShowWidget() {
    
    echo "<br><p><b>Or use one of your social accounts:</b></p><br>";
    lb_LoginWidget();
}

//------------------------------------------------------------------------------
function lb_ValidateData() {

    if (!validate_username($_SESSION['lb_username'])) {
        return 0;
    }

    if (username_exists($_SESSION['lb_username'])) {
        return 0;
    }

    if (!is_email($_SESSION['lb_email'])) {
        return 0;
    }

    if (email_exists($_SESSION['lb_email'])) {
        return 0;
    }

    return 1;
}

//------------------------------------------------------------------------------
function lb_PluginMenu() {
    
    add_options_page('Lanoba Plugin Options', 'Lanoba Social Plugin', 'manage_options', 'LanobaWP', 'lb_PluginOptions');
}

//------------------------------------------------------------------------------
function lb_PluginOptions() {

    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    $fields = array('APISecret', 'socialDomain', 'autoRegister', 'fromEmail', 'fromName');

    $updateSettings = isset($_POST['APISecret']);

    foreach ($fields as $field) {
        $$field = get_option($field);

        if ($updateSettings):
            $$field = trim(@$_POST[$field]);
            update_option($field, $$field);
        endif;
    }
    ?> 

    <h2>Lanoba Wordpress Plugin Settings</h2> 	

    <div class="wrap"> 
        <form name="form1" method="post" action="">
            <table style='width: 550px'>                       
                <tr><td style='width: 260px'> <p><b>API Secret</b></td>
                    <td><input type="text" name="APISecret" value="<?php echo $APISecret ?>" size="30"></p></td></tr>
                <tr><td> <p><b>Domain Name</b> <br> without http or www; e.g. mydomain.com</td>
                    <td><input type="text" name="socialDomain" value="<?php echo $socialDomain ?>" size="30"></p></td></tr>
                <tr><td style='width: 260px'> <p><b>From Name</b></td>
                    <td><input type="text" name="fromName" value="<?php echo $fromName ?>" size="40"></p></td></tr>
                <tr><td style='width: 260px'> <p><b>From Email</b></td>
                    <td><input type="text" name="fromEmail" value="<?php echo $fromEmail ?>" size="40"></p></td></tr>
                <tr><td> <p><b>Auto-register Users</b></td>
                    <td><input type="checkbox" name="autoRegister" <?php echo $autoRegister ? 'checked="checked"' : "" ?>></p></td></tr>
                <tr><td colspan=2><p class="submit">
                            <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
                        </p></td></tr>
            </table>
        </form>
    </div>

    <?php
}

//------------------------------------------------------------------------------
function lb_SaveProfile($user_id) {
    
    $user = get_userdata($user_id);

    $lanoba_id = isset($_POST['lb_map']) ? $user->lanoba_id : "";

    update_usermeta($user_id, 'lb_map', $_POST['lb_map']);
    update_usermeta($user_id, 'lanoba_id', $lanoba_id);

    lb_MapUser($user_id, $lanoba_id);

    if (!$lanoba_id)
        setcookie('LSESSID', '', time() - 3600, '/', '.' . get_option('socialDomain'));
}

//------------------------------------------------------------------------------
function lb_ShowMapping() {

    $user = wp_get_current_user();

    if (isset($_POST['uid']) && isset($_POST['token']) && class_exists(LanobaSDK)) {

        $sdk = new LanobaSDK(get_option('APISecret'));

        if ($sdk && $sdk->verify_signature($_POST)) {
            $_SESSION['token'] = $_POST['token'];
            setcookie('LSESSID', $_SESSION['token'], time() + 1209600, '/', '.' . get_option('socialDomain'));

            lb_MapUser($user->id, $_POST['uid']);

            update_usermeta($user->id, 'lb_map', '1');
            update_usermeta($user->id, 'lanoba_id', $_POST['uid']);
        }
    }

    $user = get_userdata($user->id);

    $msg = "Your social account has been sucessfully linked to WordPress. You may now use it to access this site next time you login.";


    $lb_map = @$user->lb_map;
    ?>        
    <h3>Lanoba Social Account Mapping</h3> 

    <?php if ($lb_map) : ?>    
        <table  cellpadding='1' cellspacing='1' style="background-color: #e8e8e8; width: 100%"> 
            <tr><td style="width: 250px; background-color: #e8e8e8; height: 30px; padding: 2px;">Link this account to social networks</td>
                <td style=" background-color: white;  padding: 5px;">
                    <input name="lb_map" type="checkbox" id="lb_map" value="1" checked='checked'/></td></tr>  
        </table><br>
    <?php endif; ?>    

    <div style="color: #0b4f1d; font-size: 10pt;">
        Use the Lanoba widget below to link your social accounts (Facebook, Twitter, Yahoo!, etc...) with this WordPress account. 
        This will allow you to sign-in to this site in the future by simply signing in to any of these linked social networks. 
        <br>
    </div><br>
    <div style="font-size: 9pt; color: blue;"><?php echo $msg; ?></div><br>

    <?php
    lb_LoginWidget('link');
}

if (function_exists('add_action') && get_option('socialDomain') && get_option('APISecret')):
    add_action('init', 'lb_InitScripts');
    add_action('register_post', 'lb_RegisterPost');
    add_action('register_form', 'lb_RegisterForm');
    add_action('register_form', 'ShowWidget');
    add_action('login_form', 'lb_LoginPage');
    add_action('login_form', 'ShowWidget');
    add_action('show_user_profile', 'lb_ShowMapping');
    add_action('profile_update', 'lb_SaveProfile');
    add_action('wp_logout', 'lb_Logout');
    add_action('admin_menu', 'lb_PluginMenu');
    add_filter('login_errors', 'lb_LoginErrors');
    add_filter('the_content', 'lb_ShareWidget');
    add_action('wp_head', 'SharingHeader');
endif;
