<?php
//error logging for troubelshooting
error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set('display_errors', '1');

$from_proxy = !empty($_REQUEST['from_proxy']) ? $_REQUEST['from_proxy'] : '';
if (isset($_REQUEST['login']) && !empty($_REQUEST['from_proxy'])) {
    $login = $_REQUEST['login'];
}
$authstatus    = empty($authstatus) ? 'none' : $authstatus;
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
    //if we are not logging out, check to see if we already have an active session with the info we need
    //check for a re-direct location
    $goto = isset($_REQUEST['goto']) ? trim(stripslashes($_REQUEST['goto'])) : ''; //$goto varies depending on the form we are headed to
    /*-----This section optional. It pings our SSO---*/
    //if we don't have an authstatus, but we have a session with the info
    if ($authstatus != 'yes' && $staff_request != 'yes') {
        if (isset($_SESSION['libSession']) && !empty($_SESSION['libSession']['IDnumber']) && !empty($_SESSION['libSession']['id'])) {
            $login = !empty($_SESSION['libSession']['id']) ? $_SESSION['libSession']['id'] : '';
        } elseif (empty($_REQUEST['from_proxy'])) {
            //portalVerify is a private function that pings our portal. You may choose to exclude this and go straight to EZProxy or LDAP authentication.
            $portalVars  = portalVerify();
            $login       = $portalVars['login'];
            $from_portal = $portalVars['from_portal'];
        }
    }
    $uri = htmlspecialchars($_SERVER["REQUEST_URI"]);
    if (empty($login)) {
        //This is a force login to our portal. You may choose to exclude this and go straight to EZProxy or LDAP authentication.
        header("location: $forcePortalURL?portal_goto=" . urlencode(htmlspecialchars("https://" . $_SERVER["HTTP_HOST"] . $uri)));
    }
    /*--------End optional------*/
    if ((empty($authstatus) || $authstatus != 'yes') && $authstatus != 'error') {
        include_once('authenticationFunctions.php'); // this login process broke when I included this file outside of this if statement. I'm probably using a global in there that I forgot to account for
        //Your authentication method may vary. We supplement our
        $token = !empty($_REQUEST['token']) ? $_REQUEST['token'] : ''; //if we have a token, we have already used EZProxy to authenticate (based on LDAP OR SAML authentication there). 
        if (!empty($token)) { //we have gotten the token back and need to get the userobject to fill in some details
            //print $from_proxy.$token;
            $authstatus = authenticate(null, $from_proxy, $token);
        } else {
            //something isn't right with our authstatus. Go get the right one.
            if (empty($authstatus) || $authstatus == 'none') {
                $request_str = '';
                if (!empty($goto)) {
                    $request_str .= '?goto=' . $goto;
                    if ($_REQUEST['goto'] == 'reqform') {
                        include($_SERVER['DOCUMENT_ROOT'] . '/webFiles/delivery/sess_var.php');
                        $request_str .= '&' . $citation_data;
                    }
                }
                $locationURL = "https://{$proxyAddress}/login?from_proxy=yes&url=https://{$proxyAddress}/userObject?service=getToken&returnURL=";
                $locationURL .= urlencode("https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . $request_str);
                header("location: $locationURL");
                $authstatus = authenticate($login, $from_proxy, $token);
            }
        }
    }
}
?>
