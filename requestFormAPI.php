<?php
//the initial authentication and pull of data
include_once('privateFunctions.php');
//SSO integration
//sets $_SESSION['libSession] based on SAML and Alma attributes
//our SAML data does not return an expiration date, so I have used the API to gather that as part of the login process for this and EZProxy
include_once('top.php');
include_once('functions.php');
//variables that are pulled from the URL sent by the General Electronic Service
$callNumber      = !empty($_REQUEST['callNumber']) ? $_REQUEST['callNumber'] : '';
$location        = !empty($_REQUEST['location']) ? $_REQUEST['location'] : '';
$description     = !empty($_REQUEST['description']) ? $_REQUEST['description'] : '';
$title           = !empty($_REQUEST['title']) ? $_REQUEST['title'] : '';
$date            = !empty($_REQUEST['date']) ? $_REQUEST['date'] : '';
$date            = (!empty($date) && $date != '19691231' && $date != '1969-12-31') ? str_replace('0101', '', $date) : '';
$isbn            = !empty($_REQUEST['isbn']) ? $_REQUEST['isbn'] : '';
$barcode         = !empty($_REQUEST['barcode']) ? $_REQUEST['barcode'] : '';
$mmsid           = !empty($_REQUEST['mmsid']) ? $_REQUEST['mmsid'] : '';
$authors         = !empty($_REQUEST['authors']) ? $_REQUEST['authors'] : '';
$oclcnum         = !empty($_REQUEST['oclcnum']) ? $_REQUEST['oclcnum'] : '';
//&issn=$issn&issue=$issue&volume=$volume&ericdoc=$ed&WOUOwns=Yes&sid=$sid&
//base URL
$digitizationURL = "https://library.wou.edu/digitization-request-form/?";
$holdURL         = "https://library.wou.edu/request-pickup-or-mailing-of-hamersly-library-materials/?";
$requestFormURL  = "https://library.wou.edu/request-form/?";
//get expiration from session variable
$now             = new DateTime();
$expires         = DateTime::createFromFormat('m-d-Y', $_SESSION['libSession']['expiration']); // our expiration is saved in m-d-Y format, yours may be different
//if the are a current patron
if (isset($expires) && $expires > $now) {
	print '1';
    //for human readability and ease in locating, add the location to the call number, if that has been provided
    if (!empty($location)) {
        $callNumber = $callNumber . ' (' . $location . ')';
    }
    // for digitization form
    $_REQUEST['request_type'] = !empty($_REQUEST['genre']) ? $_REQUEST['genre'] : '';
    $genre                    = $_REQUEST['request_type'];
    //why am I wasting resources doing this?
    $firstname                = !empty($_SESSION['libSession']['firstname']) ? $_SESSION['libSession']['firstname'] : '';
    $lastname                 = !empty($_SESSION['libSession']['lastname']) ? $_SESSION['libSession']['lastname'] : '';
    $email                    = !empty($_SESSION['libSession']['email']) ? $_SESSION['libSession']['email'] : '';
    $vnumber                  = !empty($_SESSION['libSession']['vnumber']) ? $_SESSION['libSession']['vnumber'] : '';
    $status                   = !empty($_SESSION['libSession']['status']) ? trim(stripslashes($_SESSION['libSession']['status'])) : '';
    $patronParams    = "first=$firstname&last=$lastname&email=$email&vnumber=$vnumber&status=$status&";
    //&requestType=" . $requestType
    if (!empty($_SESSION['libSession']['addresses'])) {
        foreach ($_SESSION['libSession']['addresses'] as $k => $v) {
            $lines = '';
            foreach ($v['line'] as $k2 => $v2) {
                if (!empty($v2)) {
                    $x                    = $k2 + 1;
                    $label                = 'line' . $x;
                    $this_address[$label] = $v2;
                }
                unset($k2, $v2);
            }
            $this_address['city']  = $v['city'];
            $this_address['state'] = $v['state'];
            $this_address['zip']   = $v['zip'];
            //$address[] = $lines.$v['city'].', '.$v['state'].' '.$v['zip'];
            $address[]             = $this_address;
            unset($k, $v, $this_address);
        }
    }
    if (!empty($_SESSION['libSession']['phones'])) {
        foreach ($_SESSION['libSession']['phones'] as $k => $v) {
            $thisPhone['number'] = $v['phone_number'];
            if (!empty($v['preferred_sms'])) {
                $thisPhone['sms'] = ($v['preferred_sms'] == 1) ? $v['preferred_sms'] : '';
            } else {
                $thisPhone['sms'] = '';
            }
            $phoneNo[] = $thisPhone;
            unset($thisPhone, $k, $v['preferred_sms'], $v);
        }
    }
    if (!empty($description)) {
        $title .= " ($description)";
    }
    /*This form (video digitization) is only available to faculty. Give a message stating this to others.*/
    if (!preg_match('/faculty/i', strtolower($_SESSION['libSession']['status'])) && $genre == 'av' && !empty($scananddeliv) && $scananddeliv == 'yes') {
        $message = '<p>Video digitization is only available to faculty. If you are a faculty member and are seeing this message, please contact the library (libweb@wou.edu)</p>';
        $message .= 'Your status is: ' . $_SESSION['libSession']['status'];
        redirectToForm($message, null);
    } elseif ($genre == 'av' && !empty($scananddeliv) && $scananddeliv == 'yes' && empty($_REQUEST['mailform'])) {
        $url = $digitizationURL . $patronParams . "title=$title&date=$date&isbn=$isbn&oclcnum=$oclcnum&authors=$authors&callNumber=$callNumber&description=$description&WOUOwns=Yes&format=Video";
        redirectToForm(null, $url);
    } elseif (!empty($scananddeliv) && $scananddeliv == 'yes' && empty($_REQUEST['mailform'])) {
        $message = '';
        $url     = $digitizationURL . $patronParams . "title=$title&date=$date&isbn=$isbn&oclcnum=$oclcnum&authors=$authors&callNumber=$callNumber&description=$description&atitle=$atitle&issn=$issn&issue=$issue&volume=$volume&ericdoc=$ed&WOUOwns=Yes&sid=$sid&format=Print";
        //this code is left over from using the National Emergency Library at the start of COVID
        /*
        if (!empty($isbn)){
        $emergencyAvailibilityJSON = file_get_contents('https://archive.org/services/book/v1/do_we_have_it/?isbn='.$isbn.'&debug=false&include_unscanned_books=false');
        $emergencyAvailibility = json_decode($emergencyAvailibilityJSON, true);
        if ($emergencyAvailibility['status'] == 'ok' && $emergencyAvailibility['message'] == 'we have this book')
        {
        $message =  '<div style="background:yellow;width:100%;padding:1em;"><p><strong>Wait!</strong> This item appears to be <a href="https://archive.org/details/nationalemergencylibrary?and%5B%5D='.$isbn.'&sin=">available digitially via the National Emergency Library</a> from Internet Archive. Internet Archive has suspended waitlists through June 30, 2020, or the end of the US national emergency, whichever is later and the contents are free to read for anyone.</p><p>We are happy to digitize items for you, but scanning of physical materials takes longer and provides potential exposure for our employees. Please verify that it is not available before continuing with this form.</p><p>Books are free to read and download, but do require a free account and <a href="https://www.adobe.com/solutions/ebook/digital-editions.html">Adobe Digital Editions</a> (also free). Use on a tablet or phone may require an additional free app, such as Bluefire Reader.</p></div>';
        }
        }
        */
        //if (empty($isbn) || !isset($emergencyAvailibility) || $emergencyAvailibility['status'] != 'ok' || $emergencyAvailibility['message'] != 'we have this book') {
        redirectToForm($message, $url);
        //}
    }
    //delivery form
        elseif (!empty($_REQUEST['mailform'])) {
        $requestType = !empty($_REQUEST['req_type']) ? $_REQUEST['req_type'] : 'hold';
        $eligibility = 'ELIGIBLE';
        if ($_REQUEST['mms_id'] == '99900371275501856' || $_REQUEST['mms_id'] == '99900364672801856') {
            $eligibility = getHotSpotList($vnumber);
            if (empty($eligibility)) {
                $eligibility = 'ELIGIBILE';
            }
        }
        if ($eligibility != 'NOT ELIGIBLE') {
            $url = $holdURL . $patronParams . "title=$title&date=$date&isbn=$isbn&authors=$authors&callnumber=$callNumber&description=$description&barcode=$barcode&mmsid=$mms_id&requestType=$requestType";
            //$url .= http_build_query($address, 'flags_');
            $x   = 1;
            foreach ($address as $k => $v) {
                foreach ($v as $k2 => $v2) {
                    $url .= '&address' . $x . '_' . $k2 . '=' . $v2;
                    unset($k2, $v2);
                }
                unset($k, $v);
                $x++;
            }
            $x = 1;
            foreach ($phoneNo as $k => $v) {
                $url .= '&phone' . $x . '=' . $v['number'];
                if (!empty($v['sms'])) {
                    $url .= '* This is currently selected as your SMS number';
                    $url .= '&smsPhone=phone' . $x . '&wantSMS=Yes';
                    $url .= '&sms=' . $v['sms'];
                }
                unset($k, $v);
                $x++;
            }
            if (!empty($_REQUEST['req_type']) && $_REQUEST['req_type'] == 'summit') {
                $url .= '&req_type=summit';
            }
            redirectToForm(null, $url);
        } else {
            $message = '<p>&nbsp;</p><p>iPad requesting is limited to students enrolled in specific courses. You do not appear to be enrolled in one of those courses.</p><p>If you believe this is incorrect, please <a href="https://library.wou.edu">contact the library</a> (libweb@wou.edu)</p>';
            redirectToForm($message, null);
        }
    } else {
        $url = $requestFormURL . $patronParams . "&title=$title&date=$date&isbn=$isbn&authors=$authors";
        redirectToForm(null, $url);
    }
} //end of not expired
else {
    $message = 'Requesting of materials is only available to current WOU students, faculty, and staff. Please contact the Reference desk if you feel you have gotten this message in error.';
    redirectToForm($message, null);
}
