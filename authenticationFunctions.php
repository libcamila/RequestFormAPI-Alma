<?php
//main authentication function
function authenticate($login, $from_proxy, $token)
{
	//error_reporting(E_ERROR | E_WARNING | E_PARSE);
	//ini_set('display_errors', '1');
    global $OCLCwskey, $apikey, $emailDomain, $viewas;
    if(empty($viewas)){
    //authenticate and get the base info from EZPROXY. This is based on the great post by Brice Stacey at: http://bricestacey.com/2009/07/21/Single-Sign-On-Authentication-Using-EZProxy-UserObjects.html
    $url = $token . '&service=getUserObject&wskey=' . $OCLCwskey;
    $obj   = getUserObject($url);
    $xml = new SimpleXmlElement($obj); //, LIBXML_NOCDATA
    $userJSON = json_encode($xml);
    $userObject = json_decode($userJSON ,true);
    //print(json_encode($xml));
    //$xml = $xml->asXML();
    //this is all very ugly. I hate XML.
    foreach($userObject['userDocument'] as $k => $v){
		//print $k;
		$$k = $v;
		unset($k,$v);
	}
    /*foreach ($xml->children() as $child) {

        $iVarName  =  $child->getName();
        $$iVarName = $child;
        foreach ($child->children() as $session_var) {
			//print_r($session_var);
            $iVarName              = (string) $session_var->getName();
            $$iVarName             = $session_var;
            $info_array[$iVarName] = $session_var;
        }
    }*/
    unset($xml);
    $IDnumber = !empty($uid) ? $uid : '';
    $univID   = !empty($note1) ? $note1 : $IDnumber;
	}
	else {

		$IDnumber = $viewas;
	}
    //go finish populating the record in Alma
    if (!empty($IDnumber)) {
        $queryParams   = '?' . urlencode('apikey') . '=' . urlencode($apikey);
        $service_url   = 'https://api-na.hosted.exlibrisgroup.com/almaws/v1/users/' . $IDnumber;
        //user CURL to get record
        $curl_response = getAlmaRecord($IDnumber, $service_url, $queryParams);
        $patronRecord  = json_decode($curl_response);
        //print_r($patronRecord);
        if(!empty($viewas)){$IDnumber = $patronRecord->primary_id ? $patronRecord->primary_id : $IDnumber;}
        $surname = !empty($patronRecord->pref_last_name) ? $patronRecord->pref_last_name : (!empty($patronRecord->last_name) ? $patronRecord->last_name : $surname); //pref_last_name
        $forename = !empty($patronRecord->pref_first_name) ? $patronRecord->pref_first_name : (!empty($patronRecord->first_name) ? $patronRecord->first_name : $forename);
        //utf-8 encoding is choking some forms. Taking it off for now.
        $surname = iconv("UTF-8", "ASCII//TRANSLIT", $surname);
        $forename = iconv("UTF-8", "ASCII//TRANSLIT", $forename);
        foreach ($patronRecord->contact_info->address as $key => $mailing) {
            $addresses[$key]['preferred'] = !empty($mailing->preferred) ? $mailing->preferred : '';
            $addresses[$key]['city']      = !empty($mailing->city) ? $mailing->city : '';
            $addresses[$key]['state']     = !empty($mailing->state_province) ? $mailing->state_province : '';
            $addresses[$key]['zip']       = !empty($mailing->postal_code) ? $mailing->postal_code : '';
            $addresses[$key]['line'][]    = (!empty($mailing->line1) && $mailing->line1 != $addresses[$key]['city'] . ', ' . $addresses[$key]['state'] . ' ' . $addresses[$key]['zip']) ? str_replace($addresses[$key]['city'] . ', ' . $addresses[$key]['state'] . ' ' . $addresses[$key]['zip'], '', $mailing->line1) : '';
            $addresses[$key]['line'][]    = (!empty($mailing->line2) && $mailing->line2 != $addresses[$key]['city'] . ', ' . $addresses[$key]['state'] . ' ' . $addresses[$key]['zip']) ? str_replace($addresses[$key]['city'] . ', ' . $addresses[$key]['state'] . ' ' . $addresses[$key]['zip'], '', $mailing->line2) : '';
            $addresses[$key]['line'][]    = (!empty($mailing->line3) && $mailing->line3 != $addresses[$key]['city'] . ', ' . $addresses[$key]['state'] . ' ' . $addresses[$key]['zip']) ? str_replace($addresses[$key]['city'] . ', ' . $addresses[$key]['state'] . ' ' . $addresses[$key]['zip'], '', $mailing->line3) : '';
            $addresses[$key]['line'][]    = (!empty($mailing->line4) && $mailing->line4 != $addresses[$key]['city'] . ', ' . $addresses[$key]['state'] . ' ' . $addresses[$key]['zip']) ? str_replace($addresses[$key]['city'] . ', ' . $addresses[$key]['state'] . ' ' . $addresses[$key]['zip'], '', $mailing->line4) : '';
            $addresses[$key]['line'][]    = (!empty($mailing->line5) && $mailing->line5 != $addresses[$key]['city'] . ', ' . $addresses[$key]['state'] . ' ' . $addresses[$key]['zip']) ? str_replace($addresses[$key]['city'] . ', ' . $addresses[$key]['state'] . ' ' . $addresses[$key]['zip'], '', $mailing->line5) : '';
        }
        $emails = array();
        foreach ($patronRecord->contact_info->email as $key => $emailAdd) {
            $emails[$key]['preferred']           = !empty($emailAdd->preferred) ? $emailAdd->preferred : '';
            $emails[$key]['email_address']       = !empty($emailAdd->email_address) ? $emailAdd->email_address : '';
            $emails[$key]['email_type']['desc']  = !empty($emailAdd->email_type->desc) ? $emailAdd->email_type->desc : '';
            $emails[$key]['email_type']['value'] = !empty($emailAdd->email_type->value) ? $emailAdd->email_type->value : '';
        }
        $phones = array();
        foreach ($patronRecord->contact_info->phone as $key => $phNo) {
            $phones[$key]['preferred']            = !empty($phNo->preferred) ? $phNo->preferred : '';
            $phones[$key]['preferred_sms']        = !empty($phNo->preferred_sms) ? $phNo->preferred_sms : '';
            $phones[$key]['phone_number']         = !empty($phNo->phone_number) ? $phNo->phone_number : '';
            $phones[$key]['phone_types']['desc']  = !empty($phNo->phone_type->desc) ? $phNo->phone_type->desc : '';
            $phones[$key]['phone_types']['value'] = !empty($phNo->phone_type->value) ? $phNo->phone_type->value : '';
        }
        $ptype   = !empty($patronRecord->user_group->value) ? urlencode(trim(stripslashes($patronRecord->user_group->value))) : '';
        //I don't think this is working
        $expires = !empty($patronRecord->expiry_date) ? urlencode(trim(stripslashes($patronRecord->expiry_date))) : '';
        $expires = str_replace('Z', '', $expires);
        if (empty($expires) && !empty($ptype)) {
            $expires = date('m-d-Y', strtotime('3 years'));
        } elseif (empty($expires) && empty($ptype)) {
            $expires = date('m-d-Y', strtotime('6 months ago'));
        } elseif ($expires == strtotime('December 31, 1969')) {
            $expires = date('m-d-Y', strtotime('3 years'));
        } else {
            $expires = date('m-d-Y', strtotime($expires . ' +2 weeks'));
        }
    }
    $name = !empty($forename) ? $forename . ' ' : '';
    $name .= !empty($surname) ? $surname : '';
    $category = !empty($category) ? $category : $ptype;
    if (empty($category)) {
        $category = 'none';
    }
    $univStatus = getPatronCategory($category);
    $expires    = !empty($expires) ? $expires : date('m-d-Y', strtotime('yesterday'));
    //if we have the info, set the session
    if (!empty($emailAddress) || !empty($IDnumber)) {
        $_SESSION['libSession']['name']      = $name;
        $_SESSION['libSession']['lastname']  = $surname;
        $_SESSION['libSession']['firstname'] = $forename;
        $_SESSION['libSession']['status']    = $univStatus;
        $_SESSION['libSession']['IDnumber']  = $IDnumber;
        $_SESSION['libSession']['number']    = $uid;
        $_SESSION['libSession']['ptype']     = $category;
        if (empty($email) && !empty($emails)) {
            foreach ($emails as $k => $v) {
                if ($v['preferred'] == true || $v['preferred'] == 1 || (preg_match('/'.$emailDomain.'/i', $v['email_address']) && empty($email))) {
                    $email = $v['email_address'];
                    if(!empty($viewas)){
                    $univID   = preg_replace('/@(mail.)?wou.edu/i', '', $email);
					}
                }
                unset($k, $v);
            }
        }
        if (empty($phone) && !empty($phones)) {
            foreach ($phones as $k => $v) {
                if ($v['preferred'] == true || $v['preferred'] == 1) {
                    $phone = $v['phone_number'];
                }
                unset($k, $v);
            }
        }
        $_SESSION['libSession']['id']        = $univID;
        $_SESSION['libSession']['email']      = !empty($email) ? $email : '';
        $_SESSION['libSession']['phone']      = !empty($phone) ? $phone : '';
        $_SESSION['libSession']['info_array'] = $info_array;
        $_SESSION['libSession']['expiration'] = $expires;
        $_SESSION['libSession']['addresses']  = $addresses;
        $_SESSION['libSession']['phones']     = $phones;
        return "yes";
    } elseif (empty($univID)) {
        return "none";
    } else {
        return "error";
    }
} //end of function "authenticate"
//function to get user object form EZproxy
function getUserObject($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $o = curl_exec($ch);
    curl_close($ch);
    return $o;
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
            $univStatus = 'Upward Bound';
            break;
        case "11":
            $univStatus = 'Summit Excluded';
            break;
        case "12":
            $univStatus = 'Special borrower';
            break;
        case "13":
            $univStatus = 'Upward Bound';
            break;
        case "14":
            $univStatus = 'Library unit';
            break;
        case "15":
            $univStatus = 'Library unit';
            break;
        case "17":
            $univStatus = 'Summit Library';
            break;
        case "21":
            $univStatus = 'ILL Library';
            break;
        case "24":
            $univStatus = 'Library unit';
            break;
        case "28":
            $univStatus = 'Summit Library';
            break;
        case "211":
            $univStatus = 'Summit Visiting Patron';
            break;
        case "230":
            $univStatus = 'Summit Excluded';
            break;
        default:
            $univStatus = $category;
            break;
    }
    return $univStatus;
}
?>
