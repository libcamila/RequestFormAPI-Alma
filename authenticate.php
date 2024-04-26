<?php
///error logging for troubelshooting
ini_set('display_errors', 1);
error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set('display_errors', '1');
//we must have a session to store to
if (!isset($_SESSION)) {
    session_start();
}
$from_proxy = !empty($_REQUEST['from_proxy']) ? $_REQUEST['from_proxy'] : '';
$login = (isset($_REQUEST['login'])) ? $_REQUEST['login'] : (!empty($_SESSION['libSession']['id']) ? $_SESSION['libSession']['id'] : null); // && !empty($_REQUEST['from_proxy'])
$authstatus    = !empty($authstatus) ? $authstatus : (!empty($_SESSION['libSession']['id']) ? 'yes' : null);
$staff_request = !empty($staff_request) ? $staff_request : '';
//if we are logging off, get rid of it all
if (isset($_REQUEST['state']) && $_REQUEST['state'] == 'logoff') {
    unset($IDnumber, $email, $_SESSION['woulib']);
    $authstatus = 'none';
    if (!empty($_SERVER['HTTP_REFERER'])) {
        header('location: ' . $_SERVER['HTTP_REFERER']);
    } else {
        header('location: ' . $portalURL);
    }
} else {

    if (isset($_SESSION['wou_committee']) && !empty($_SESSION['wou_committee']['IDnumber']) && !empty($_SESSION['wou_committee']['id']) && empty($login)) {
        $login = !empty($_SESSION['wou_committee']['id']) ? $_SESSION['wou_committee']['id'] : '';
    }
    //if we are not logging out, check to see if we already have an active session with the info we need
    //check for a re-direct location
    $goto = isset($_REQUEST['goto']) ? trim(stripslashes($_REQUEST['goto'])) : ''; //$goto varies depending on the form we are headed to
    $uri = htmlspecialchars($_SERVER["REQUEST_URI"]);
    /*--------End optional------*/
    if ((empty($authstatus) || $authstatus != 'yes') && $authstatus != 'error') {
        //Your authentication method may vary. We supplement our
        $token = !empty($_REQUEST['token']) ? $_REQUEST['token'] : ''; //if we have a token, we have already used EZProxy to authenticate (based on LDAP OR SAML authentication there).
        if (!empty($token)) { //we have gotten the token back and need to get the userobject to fill in some details
            $authstatus = authenticatePerson($login, $from_proxy, $token);
        } else {
            $authstatus = authenticatePerson($login, null, null);
            //something isn't right with our authstatus. Go get the right one.
            if (empty($authstatus) || $authstatus == 'none') {
                $locationURL = "https://{$proxyAddress}/userObject?service=getToken&returnURL=";
                $locationURL .= urlencode("https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . $request_str);
                header("location: $locationURL");
                $authstatus = authenticatePerson($login, $from_proxy, $token);
            }
        }
    }
}
