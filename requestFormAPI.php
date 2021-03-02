<?php
//the initial authentication and pull of data
include_once('privateFunctions.php');
//SSO integration
//sets $_SESSION['libSession] based on SAML and Alma attributes
//our SAML data does not return an expiration date, so I have used the API to gather that as part of the login process for this and EZProxy
include_once('functions.php');
include_once('authenticate.php');
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
$details         = !empty($_REQUEST['details']) ? $_REQUEST['details'] : ''; //for reserves/booking form text field
//&issn=$issn&issue=$issue&volume=$volume&ericdoc=$ed&WOUOwns=Yes&sid=$sid&
//base URLs to your form(s) should be set here. 
//$digitizationURL = "https://library.school.edu/form1/?";
//$holdURL         = "https://library.school.edu/form2/?";
//$requestFormURL  = "https://library.school.edu/form3/?";
//$reserveFormURL  = "https://library.school.edu/form3/?";
//get expiration from session variable
$now             = new DateTime();
$expires         = DateTime::createFromFormat('m-d-Y', $_SESSION['libSession']['expiration']); // our expiration is saved in m-d-Y format, yours may be different
//if the are a current patron
if (isset($expires) && $expires > $now) {
	
    if (empty($_REQUEST['formType'])){
		$message = '<style>body {padding:1em;}</style><form action="'.$_SERVER["SELF"].'" style="padding:1em;">';
		$message .= 'I am requesting:<br>';
		$message .= '<input type="radio" name="formType" value="mailform"> This be mailed to me in a physical format or held at the library for pick up<br>';
		$message .= '<input type="radio" name="formType" value="scananddeliv"> A portion of this be digitized and sent directly to me<br>';
		if(!preg_match('/student/i', $status)) {
		$message .= '<input type="radio" name="formType" value="reserves"> This be digitized and made available to my students through the library\'s digital reserves system (print items only)<br>';
		$message .= '<input type="radio" name="formType" value="videoDigitization"> This be digitized and made embeddable in my Canvas or Moodle shell (videos only)';
		}
		foreach($_REQUEST as $k => $v){
			$message .=  "<input type=\"hidden\" name=\"{$k}\" value=\"{$v}\">";
			unset($k,$v);
		}
		$message .= '<br><input type="submit"></form>';
		if (!empty($isbn)){
			$message .= "<p>Alternately, <a href=\"https://library.wou.edu/webFiles/scripts/NEL.php?title={$title}&isbn={$isbn}&primo=yes\">Check the free Internet Archive</a> for immediate online availability.</p>";
		}
		redirectToForm($message, null);
		}
	else
	{
		$formType = !empty($_REQUEST['formType']) ? $_REQUEST['formType'] : '';
		//for human readability and ease in locating, add the location to the call number, if that has been provided
		if (!empty($location)) {
			$callNumber = $callNumber . ' (' . $location . ')';
		}
		if (!empty($formType)){$$formType = 'yes';}
		
		// for digitization form
		$_REQUEST['request_type'] = !empty($_REQUEST['genre']) ? $_REQUEST['genre'] : '';
		$genre                    = $_REQUEST['request_type'];
		//why am I wasting resources doing this?
		$firstname                = !empty($_SESSION['libSession']['firstname']) ? $_SESSION['libSession']['firstname'] : '';
		$lastname                 = !empty($_SESSION['libSession']['lastname']) ? $_SESSION['libSession']['lastname'] : '';
		$email                    = !empty($_SESSION['libSession']['email']) ? $_SESSION['libSession']['email'] : '';
		$IDnumber                  = !empty($_SESSION['libSession']['IDnumber']) ? $_SESSION['libSession']['IDnumber'] : '';
		$status                   = !empty($_SESSION['libSession']['status']) ? trim(stripslashes($_SESSION['libSession']['status'])) : '';
		$patronParams    = "first=$firstname&last=$lastname&email=$email&vnumber=$IDnumber&status=$status&";//patron paramaters to fill form - really should be sent as a post, but I'm working with what I've got
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
		} elseif (($genre == 'av' && !empty($scananddeliv) && $scananddeliv == 'yes' && empty($_REQUEST['mailform']) && empty($_mailform)) || $videoDigitization == 'yes') {
			$url = $digitizationURL . $patronParams . "title=$title&date=$date&isbn=$isbn&oclcnum=$oclcnum&authors=$authors&callNumber=$callNumber&description=$description&WOUOwns=Yes&format=Video";
			redirectToForm(null, $url);
		} elseif (!empty($scananddeliv) && $scananddeliv == 'yes' && empty($_REQUEST['mailform']) && empty($mailform)) {
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
			elseif (!empty($_REQUEST['mailform']) || !empty($mailform)) {
			$requestType = !empty($_REQUEST['req_type']) ? $_REQUEST['req_type'] : 'hold';
			$eligibility = 'ELIGIBLE';
			//we have two MMSIDs that our hotspot checkout program is attached to. If that is one of the records we are looking for, we need to verify teh student is eligible to check a hotspot out
			if ($_REQUEST['mms_id'] == '99900371275501856' || $_REQUEST['mms_id'] == '99900364672801856') {
				$eligibility = getHotSpotList($IDnumber);
				if (empty($eligibility)) {
					$eligibility = 'ELIGIBILE';
				}
			}
			if ($eligibility != 'NOT ELIGIBLE') {
				$url = $holdURL . $patronParams . "title=$title&date=$date&isbn=$isbn&authors=$authors&callnumber=$callNumber&description=$description&barcode=$barcode&mmsid=$mms_id&requestType=$requestType";
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
		} 
		elseif (!empty($reserves)) {
			$detailsStr = "Title:%20{$title}%0ADate:%20{$date}%0AISBN:%20{$isbn}%0AVolume:%20{$volume}%0AMMSID:%20{$mmsid}%0AType:%20{$genre}%0AcallNumber:%20{$callNumber}%0Adescription:%20{$description}%0Alocation:%20{$location}";
			$url = $reserveFormURL . $patronParams . "&details={$detailsStr}";
			redirectToForm(null, $url);
		}else {
			$url = $requestFormURL . $patronParams . "&title={$title}&date={$date}&isbn={$isbn}&authors={$authors}";
			redirectToForm(null, $url);
		}
	}
} //end of not expired
else {
    $message = 'Requesting of materials is only available to current WOU students, faculty, and staff. Please contact the Reference desk if you feel you have gotten this message in error.';
    redirectToForm($message, null);
}
