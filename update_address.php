<?php
//set new address/phone in Alma
//we must have a session to store to
if (!isset($_SESSION)) {
    session_start();
} //this has to stay or CC/grad/senate, etc won't load right
include_once('functions.php');
include_once('privateFunctions.php'); //privateFunctions is a poorly named file that includes our APIkey, wskey, and URL for verification of patron hotspot eligibility
//if we don't have a request type already, or we are reqyesting something that is not a hotspot (Google Form get data via apps script), include the login stuff
if (empty($_REQUEST['req_type']) || $_REQUEST['req_type'] != 'hotspot') {
	//authenticate
    include_once($_SERVER['DOCUMENT_ROOT'] . '/webFiles/login/top.php');
} else {
    $_REQUEST['pid'] = !empty($_REQUEST['viewas']) ? $_REQUEST['viewas'] : '';
?>
   <html><head>
   <style>
   body, p, div {
    font-family: Arial, Helvetica, sans-serif;
    font-family: 'Open Sans',sans-serif;
    background-color: transparent;
	}
	</style>
	</head>
	<body>
    <?php
}
if (!empty($_REQUEST['state']) && !empty($states[$_REQUEST['state']])) {
    $_REQUEST['state'] = $states[$_REQUEST['state']];
}
$queryParams = '?' . urlencode('apikey') . '=' . urlencode($apikey);
//user CURL to get record
$service_url = 'https://api-eu.hosted.exlibrisgroup.com/almaws/v1/users/' . $_REQUEST['pid'];
$curl_response = getAlmaRecord($_REQUEST['pid'], $service_url, $queryParams);
$patronRecord  = json_decode($curl_response);
unset($curl_response);
//this is all very ugly (done in a hurry). It should be cleaned up.
//we have a new address sent from the form.
if (!empty($_REQUEST['line1'])) {
    $newAddress['preferred']      = true;
    $newAddress['segment_type']   = "Internal";
    $newAddress['line1']          = !empty($_REQUEST['line1']) ? $_REQUEST['line1'] : null;
    $newAddress['line2']          = !empty($_REQUEST['line2']) ? $_REQUEST['line2'] : null;
    $newAddress['line3']          = null;
    $newAddress['line4']          = null;
    $newAddress['line5']          = null;
    $newAddress['city']           = !empty($_REQUEST['city']) ? $_REQUEST['city'] : null;
    $newAddress['state_province'] = !empty($_REQUEST['state']) ? $_REQUEST['state'] : null;
    $newAddress['postal_code']    = !empty($_REQUEST['zip']) ? $_REQUEST['zip'] : null;
    $addressType['value']         = 'home';
    $addressType['desc']          = 'Home';
    $newAddress['address_type'][] = $addressType;
    $newAddress['address_note']   = 'Added by request form.';
    $addresses                    = $patronRecord->contact_info->address;
    //$addresses                    = (array) $addresses;
    $addressExists                = 'no';
    //convert our array to an object
    $newAddress = (object) $newAddress;
    if (isset($addresses) && is_array($addresses)) {
        foreach ($addresses as $k => $v) {
            //our Banner data is ...messy. It is quite possible we have a variation of their current address in there. I don't, however, want my match to be too fuzzy, as if they've just changed units within a complex, the 98% match might show as exisiting. Better too many addresses than not enough, right?
            if ((strtoupper(preg_replace("/[^A-Za-z0-9 ]/", '', $v->line1)) == strtoupper(preg_replace("/[^A-Za-z0-9 ]/", '', $newAddress->line1))) && (empty($v->line2) || (strtoupper(preg_replace("/[^A-Za-z0-9 ]/", '', $v->line2)) == strtoupper(preg_replace("/[^A-Za-z0-9 ]/", '', $newAddress->line2)))) && (strtoupper(preg_replace("/[^A-Za-z0-9 ]/", '', $newAddress->city)) == strtoupper(preg_replace("/[^A-Za-z0-9 ]/", '', $v->city))) && (strtoupper(preg_replace("/[^A-Za-z0-9 ]/", '', $newAddress->state_province)) == strtoupper(preg_replace("/[^A-Za-z0-9 ]/", '', $v->state_province))) && (strtoupper(preg_replace("/[^A-Za-z0-9 ]/", '', $newAddress->postal_code)) == strtoupper(preg_replace("/[^A-Za-z0-9 ]/", '', $v->postal_code)))) {
                unset($addresses[$k]);
                $addresses[$k]            = $newAddress;
                $addressExists            = 'yes';
            } else {
                if ($v->preferred == 1) {
                    $addresses[$k]->preferred = false;
                }
            }
            unset($v);
        }
    if ($addressExists == 'no') {
		$k = !empty($k) ? $k+1 : 0;
         //add it to the object
        $addresses[$k] = $newAddress;
         unset($k);
    }
    } else {
        //we didn't have an address for the patron. Better add one.
        $addresses[0] = $newAddress;
    }
    //get rid of the numeric keys on addresses
    //$addresses                               = array_values($addresses);
    // add it to the patronRecord object
    $patronRecord->contact_info->{"address"} = $addresses;
}
if (!empty($_REQUEST['usePhone'])) {
    $newNumber['preferred']     = true;
    $newNumber['preferred_sms'] = true;
    $newNumber['segment_type']  = "Internal";
    $newNumber['phone_number']  = !empty($_REQUEST['newPhone']) ? preg_replace('/[^0-9]/i', '', $_REQUEST['newPhone']) : null;
    if (empty($newNumber['phone_number'])) {
        $usePhoneNo                = preg_replace('/[A-za-z ]/i', '', $_REQUEST['usePhone']);
        $usePhoneNo                = $usePhoneNo - 1;
        $newNumber['phone_number'] = $_SESSION['woulib']['phones'][$usePhoneNo]['phone_number'];
    }
    $phoneType['value']        = 'mobile';
    $phoneType['desc']         = 'Mobile';
    $newNumber['phone_type'][] = $phoneType;
    $numberExists              = 'no';
    $phNumbers                 = $patronRecord->contact_info->phone;
    $phones                    = (array) $phNumbers;
    //convert our array to an object
    $newNumber = (object) $newNumber;
    if (!empty($phNumbers)) {
        foreach ($phNumbers as $k => $v) {
			if ($v->phone_number == $newNumber->phone_number) {
                $phNumbers[$k]  = $newNumber;
                $numberExists          = 'yes';
            } else {
                if ($v->preferred == 1) {
                    $phNumbers[$k]->preferred = false;
                }
            }
            unset($v);
		}
	}
	if ($numberExists == 'no') {
		$k = !empty($k) ? $k+1 : 0;
		$phNumbers[$k]  = $newNumber;
		unset($k);
		}
}
else {
        //we didn't have an address for the patron. Better add one.
        $phNumbers[0] = $newNumber;
    }
     $patronRecord->contact_info->{"phone"} = $phNumbers;
     //let's just not mess with user roles. By not including them in what we send back, we don't accidentally overwrite something
unset($patronRecord->user_role);
$patronRecord  = json_encode($patronRecord);
//now update the record
$curl_response = putPatronRecord($patronRecord, $service_url, $queryParams);
$jsonResult    = json_decode($curl_response, true);
unset($curl_response);
print '<div style="padding:2em;padding-bottom:0;">';
if ((empty($jsonResult['errorsExist']) || $jsonResult['errorsExist'] != true) && empty($jsonResult['web_service_result']['errorsExist'])) {
    if (!empty($_REQUEST['line1'])) {
        print "<strong>Preferred address updated to:</strong><br>";
        print $newAddress->line1 . "<br>";
        print !empty($newAddress->lline2) ? $newAddress->lline2 . "<br>" : '';
        print $newAddress->city . ", " . $newAddress->state_province . " " . $newAddress->postal_code . "<br>";
    }
    if (!empty($_REQUEST['usePhone'])) {
        print '<p>You will receive messages about this and other requests at the number:<strong>' . $newNumber->phone_number . '</strong></p>';
    }
} else {
    //bother.
    print '<strong>There was an error updating your address or phone number:</strong><br> ';
    foreach ($jsonResult['errorList']['error'] as $k => $v) {
        print $v['errorMessage'] . '<br>';
        unset($k, $v);
    }
    foreach ($jsonResult['web_service_result']['errorList'] as $k => $v) {
        print $v['errorMessage'] . '<br>';
        print $v['errorMessage'] . '<br>';
        unset($k, $v);
    }
}
print '</div>';
//now place the hold - we can do this even if the address update fails
include('placeHold.php');
