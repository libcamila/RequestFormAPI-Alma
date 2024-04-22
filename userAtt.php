<?php
include_once('privateFunctions.php'); //privateFunctions is a poorly named file that includes variables for our APIkey, wskey, and URL for verification of patron hotspot eligibility
/*include_once('functions.php');
include_once('authenticationFunctions.php');
$token = !empty($_REQUEST['token']) ? $_REQUEST['token'] : '';
if (empty($token)) {
    header('location: https://wou.idm.oclc.org/login?from_proxy=yes&url=https://wou.idm.oclc.org/userObject?service=getToken&returnURL=' . urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . $request_str));
} else {
    //print $token;
    $url = "{$token}&service=getUserObject&wskey={$OCLCwskey}&from_proxy=yes";
    //print $url;
    viewUserObject($url);
}*/
$info = getLDAP('V00016181', 'employeeNumber');
print_r($info);
//main authentication function
function getLDAP($attValue, $attName)
{
    global $searchId, $WOUsearchId, $searchPassword, $ldap_server, $basedn;
    $ds = ldap_connect($ldap_server);
    if ($ds) {
        $login = 'gabaldoc';
        $uidPath       = "uid=$searchId,ou=People,dc=wou,dc=edu";
        $filter    = "(&({$attName}={$attValue})(objectclass=wouPerson))";
        //$filter    = "(&(uid=$login)(objectclass=wouPerson))";
        $justthese = array(
            "sn",
            "givenname",
            "mail",
            "surname",
            "cn",
            "gn",
            "telephonenumber",
            "usertype",
            "employeenumber",
            "department",
            "title"
        );
        $r         = ldap_bind($ds, $uidPath, $searchPassword);
        if ($r) {
            // search for the user's info
            //$sr = ldap_search($ds, $basedn, $filter); // for testing the whole array - not very efficient
            $sr = ldap_search($ds, $basedn, $filter, $justthese);
            if ($sr) {
                // we got a match, so get the user's attributes (we may have them already thanks to "justthese" array in the ldap_search above)
                $info = ldap_get_entries($ds, $sr);
            } //end if-sr
        } //end if-r
        else {
            print "no r";
        }
        //the $r bind didn't work at this point, but we are still in the zone where the $ds connection did work
        ldap_close($ds);
    } //end of if-ds worked
    else {
        print "no ds";
    }
    return $info;
} //end of function "authenticate"