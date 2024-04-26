<?php
//main authentication function
function authenticatePerson($login, $from_proxy, $token)
{
    global $OCLCwskey, $apikey, $emailDomain;
    $neededVars = array('name' => 'name', 'lastname' => 'surname', 'firstname' => 'forename', 'status' => 'univStatus', 'IDnumber' => 'IDnumber', 'vnumber' => 'IDnumber', 'id' => 'univID', 'expires' => 'expires', 'category' => 'category',  'email' => 'email', 'phone' => 'phone', 'emails' => 'emails', 'phones' => 'phones', 'addresses' => 'addresses');
    if (!empty($token) || (!empty($from_proxy) || $from_proxy == 'yes')) {

        //authenticate and get the base info from EZPROXY. This is based on the great post by Brice Stacey at: http://bricestacey.com/2009/07/21/Single-Sign-On-Authentication-Using-EZProxy-UserObjects.html
        $url = "{$token}&service=getUserObject&wskey={$OCLCwskey}";
        $person = (object) getUserObject($url);
        $person->emails = !empty($person->emails) ? $person->emails : new stdClass();
        $person->emails->{0} = $person->email;
    } else {
        //LDAP - last resort. Try not to do this unless absolutely neccessary
        $person = getLDAP($login);
    }
    //go finish populating the record in Alma
    if (!empty($person->IDnumber) || !empty($person->id)) {
        $identifier = !empty($person->IDnumber) ? $person->IDnumber : $person->id;
        $queryParams   = "?apikey={$apikey}";
        $service_url   = "https://api-na.hosted.exlibrisgroup.com/almaws/v1/users/{$identifier}";
        //user CURL to get record
        $curl_response = getAlmaPatronRecord($service_url, $queryParams);
        $patronRecord  = json_decode($curl_response);
        $almaData = setFromAlma($patronRecord);
        foreach ($almaData as $k => $v) {
            if (!empty($v)) {
                $person->{$k} = $v;
            }
            unset($k, $v);
        }
        $univStatus = getPatronCategory($person->category);
    }
    $person->category = !empty($univStatus) ? $univStatus : (!empty($person->wou_status) ? $person->wou_status : $person->category);
    //if we have the info, set the session
    if ((is_array($person->emails) && sizeof($person->emails) > 0) || !empty($person->IDnumber)) {
        if (empty($person->email) && !empty($person->emails)) {
            foreach ($person->emails as $k => $v) {
                if ($v['preferred'] == true || $v['preferred'] == 1 || (preg_match("/{$emailDomain}/i", $v['email_address']) && empty($email))) {
                    $person->email = $v['email_address'];
                }
                unset($k, $v);
            }
        }
        if (empty($person->phone) && !empty($person->phones)) {
            foreach ($person->phones as $k => $v) {
                if ($v['preferred'] == true || $v['preferred'] == 1) {
                    $person->phone = $v['phone_number'];
                }
                unset($k, $v);
            }
        }
        $person->univID       = preg_replace('/@(.*.)?wou\.edu/i', '', $person->email);
        foreach ($neededVars as $k => $v) {
            $_SESSION['libSession'][$k] = !empty($person->{$v}) ? $person->{$v} : (($v == 'phones' || $v == 'emails' || $v == 'addresses') ? array() : '');
        }
        return "yes";
    } elseif (empty($person->univID)) {
        return "none";
    } else {
        return "error";
    }
} //end of function "authenticate"

//function to set stuff from the ALma record
function setFromAlma($patronRecord)
{
    $primaryId = $patronRecord->primary_id;
    $firstName = !empty($patronRecord->pref_first_name) ? $patronRecord->pref_first_name : $patronRecord->first_name;
    $lastName = !empty($patronRecord->pref_last_name) ? $patronRecord->pref_last_name : $patronRecord->last_name;
    foreach ($patronRecord->contact_info->address as $key => $mailing) {
        $mailing->state = $mailing->state_province;
        unset($mailing->state_province);
        $mailing->zip = $mailing->postal_code;
        unset($mailing->postal_code);
        for ($x = 1; $x <= 5; $x++) {
            $lineName = "line{$x}";
            $mailing->{$lineName}    = (!empty($mailing->{$lineName}) && $mailing->{$lineName} != "{$mailing->city}, {$mailing->state} {$mailing->zip}") ? str_replace("{$mailing->city}, {$mailing->state} {$mailing->zip}", "", $mailing->{$lineName}) : null;
        }
        $addresses[$key] = (array) $mailing;
    }
    $emails = (array) $patronRecord->contact_info->email;
    /*foreach ($patronRecord->contact_info->email as $key => $emailAdd) {
        $emails[$key] = (array) $emailAdd;
    }*/
    $phones = array();
    foreach ($patronRecord->contact_info->phone as $key => $phNo) {
        $phones[$key] = (array) $phNo;
    }
    $ptype   = !empty($patronRecord->user_group->value) ? urlencode(trim(stripslashes($patronRecord->user_group->value))) : '';
    //expiration is not always set for faculty/library staff. If it is blank, they are good
    $facExpire = new DateTime();
    $facExpire->add(new DateInterval('P3Y'));
    $expiredPatron = new DateTime();
    $expiredPatron->modify('-6 months');
    $expiration = !empty($patronRecord->expiry_date) ? new DateTime($patronRecord->expiry_date) : (!empty($ptype) ? $facExpire  : $expiredPatron);
    $expiration->add(new DateInterval('P2W'));
    $expires = $expiration->format('m-d-Y');
    $category = !empty($category) ? $category : $ptype;
    if (empty($category)) {
        $category = 'none';
    }
    $returnObj = new stdClass();
    $returnObj->IDnumber = $primaryId;
    $returnObj->forename = $firstName;
    $returnObj->surname = $lastName;
    $returnObj->name = $firstName . " " . $lastName;
    $returnObj->emails = $emails;
    $returnObj->addresses = $addresses;
    $returnObj->phones = $phones;
    $returnObj->expires = $expires;
    $returnObj->category = $category;
    return $returnObj;
}
//function to get user object form EZproxy
function getUserObject($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $xml = curl_exec($ch);
    curl_close($ch);
    $obj = new SimpleXmlElement($xml);
    $person = $obj->userDocument;
    $person->email = $person->emailAddress;
    unset($person->emailAddress);
    $person->IDnumber = $person->uid;
    unset($person->uid);
    $person->name = $person->forename . " " . $person->surname;
    $person = json_decode(json_encode($person));
    return $person;
}
//set a text patron status based on numerical return
function getPatronCategory($category)
{
    switch ($category) {
        case "1":
            $univStatus = 'Faculty';
            break;
        case "2":
            $univStatus = 'Faculty (NTT)';
            break;
        case "3":
            $univStatus = 'Emeritus or Visiting Faculty';
            break;
        case "4":
            $univStatus = 'Library Staff';
            break;
        case "5":
            $univStatus = 'Staff';
            break;
        case "6":
            $univStatus = 'Independent Scholar';
            break;
        case "8":
            $univStatus = 'Undergraduate Student';
            break;
        case "9":
            $univStatus = 'Graduate Student';
            break;
        case "10":
        case "13":
            $univStatus = 'Upward Bound';
            break;
        case "11":
        case "230":
            $univStatus = 'Summit Excluded';
            break;
        case "12":
            $univStatus = 'Special borrower';
            break;
        case "14":
        case "15":
        case "24":
            $univStatus = 'Library unit';
            break;
        case "17":
        case "28":
            $univStatus = 'Summit Library';
            break;
        case "21":
            $univStatus = 'ILL Library';
            break;
        case "211":
            $univStatus = 'Summit Visiting Patron';
            break;
        default:
            $univStatus = $category;
            break;
    }
    return $univStatus;
}
function getLDAP($login)
{
    global $ldap_server, $searchId, $searchPassword, $basedn;
    $ds          = ldap_connect($ldap_server);
    $infoArray = new stdClass();
    if ($ds) {
        $uid      = "uid=$searchId,ou=People,dc=wou,dc=edu";
        $filter      = "(&(uid=$login)(objectclass=wouPerson))";
        $justthese   = array("sn", "givenname", "mail", "surname", "cn", "gn", "telephonenumber", "usertype", "employeenumber", "department", "title");
        //bind as the users themselves to see if their id/password combo is valid
        $r        = ldap_bind($ds, $uid, $searchPassword);
        if ($r) {
            // search for the user's info
            //$sr = ldap_search($ds, $basedn, $filter); // for testing the whole array - not very efficient
            $sr = ldap_search($ds, $basedn, $filter, $justthese);
            if ($sr) {
                // we got a match, so get the user's attributes (we may have them already thanks to "justthese" array in the ldap_search above) 
                $info = ldap_get_entries($ds, $sr);
            } //end if-sr
        } //end if-r
        //the $r bind didn't work at this point, but we are still in the zone where the $ds connection did work
        ldap_close($ds);
        $infoArray->name       = isset($info[0]['cn'][0]) ? $info[0]['cn'][0] : '';
        $infoArray->surname    = isset($info[0]['sn'][0]) ? $info[0]['sn'][0] : '';
        $infoArray->forename   = isset($info[0]['givenname'][0]) ? $info[0]['givenname'][0] : '';
        $infoArray->phone      = isset($info[0]['telephonenumber'][0]) ? $info[0]['telephonenumber'][0] : '';
        $infoArray->vnumber    = strtoupper($info[0]['employeenumber'][0]);
        $infoArray->IDnumber   = $infoArray->vnumber;
        if (!is_array($info[0]['usertype']) || !isset($info[0]['usertype'])) {
            $infoArray->wou_status = isset($info[0]['usertype']) ? $info[0]['usertype'] : '';
        } else {
            if (in_array('Faculty', $info[0]['usertype'])) {
                $infoArray->wou_status = 'Faculty';
            } elseif (in_array('Staff', $info[0]['usertype'])) {
                $infoArray->wou_status = 'Staff';
            } elseif (in_array('Student', $info[0]['usertype'])) {
                $infoArray->wou_status = 'Student';
            } else {
                $infoArray->wou_status = !empty($info[0]['usertype'][0]) ? ucfirst($info[0]['usertype'][0]) : '';
            }
        }
        foreach ($info[0]['mail'] as $k => $v) {
            if (is_numeric($k)) {
                if (($infoArray->wou_status == 'Faculty' || $infoArray->wou_status == 'Staff') && !preg_match('/[0-9]/i', $v)) {
                    $infoArray->emails[$k]['preferred'] = true;
                } elseif ($infoArray->wou_status == 'Faculty' || $infoArray->wou_status == 'Staff') {
                    $infoArray->emails[$k]['preferred'] = true;
                } else {
                    $infoArray->emails[$k]['preferred'] = true;
                }
                $infoArray->emails[$k]['email_address'] = $v;
            }
        }
    } //end of if-ds worked
    return $infoArray;
}
