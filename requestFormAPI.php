<?php
/*
ini_set('display_errors', 1);
error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set('display_errors', '1');
*/
//the initial authentication and pull of data
if (empty(session_id()) && !isset($_SESSION)  && session_status() == PHP_SESSION_NONE) {
	session_start();
}
//include($_SERVER['DOCUMENT_ROOT'].'/webFiles/login/top.php');
include_once('privateFunctions.php');
//SSO integration
//sets $_SESSION['libSession] based on SAML and Alma attributes
//our SAML data does not return an expiration date, so I have used the API to gather that as part of the login process for this and EZProxy
include_once('functions.php');
include_once('authenticate.php');
if (isset($_SESSION['woulib']) && !isset($_SESSION['libSession'])) {
	$_SESSION['libSession'] = $_SESSION['woulib'];
}
//I would like to eventually include this file, but it has a jquery dependency, so I'll need to add that first
//include('viewas.php');
//variables that are pulled from the URL sent by the General Electronic Service are in the parameters file along with other non-secret params
include_once('parameters.php');
//if the are a current patron
if (isset($expires) && $expires > $now) {
	//redirect to the Google form for Hotspots
	if (!empty($mmsid) && ($mmsid == '99900414776101856' || $mmsid == '99900391873201856')) {
		header("location: $hotspotURL;");
	}
	if (empty($formType)) {
		unset($_REQUEST['mailform']);
		$message = '<style>body {padding:1em;}</style><form action="' . $_SERVER["SELF"] . '" style="padding:1em;">';
		$message .= $top_html;
		$message .= '<p><strong>I am requesting:</strong></p>';
		$message .= '<p></p><input type="radio" name="formType" value="mailform"> This be mailed to me or held at the library for pick up<br>';
		$message .= '<input type="radio" name="formType" value="scananddeliv"> A portion of this be digitized and sent directly to me</p>';
		if (!preg_match('/student/i', $status)) {
			$message .= '<p><strong>Faculty/Staff Only:</strong></p>';
			$message .= '<p><input type="radio" name="formType" value="cdl"> This be digitized and made available to my students through the library\'s <a href="https://research.wou.edu/CDL" target="_blank">digital reserves system</a> (print items only)<br>';
			$message .= '<input type="radio" name="formType" value="reserves"> This be made available to my students through the library\'s print reserves system or be booked for a specific date for use in sthe classroom (physical items only)<br>';
			$message .= '<input type="radio" name="formType" value="videoDigitization"> This be digitized and made embeddable in my Canvas or Moodle shell (videos only)</p>';
		}
		foreach ($_REQUEST as $k => $v) {
			$message .=  "<input type=\"hidden\" name=\"{$k}\" value=\"{$v}\">";
			unset($k, $v);
		}
		$message .= '<br><input type="submit" value="Submit"></form>';
		if (!empty($isbn)) {
			$message .= "<p>Alternately, <a href=\"https://library.wou.edu/webFiles/scripts/NEL.php?title={$title}&isbn={$isbn}&primo=yes\">Check the free Internet Archive</a> for immediate online availability.</p>";
		}
		redirectToForm($message, null);
	} else {
		//for human readability and ease in locating, add the location to the call number, if that has been provided
		if (!empty($location)) {
			$callNumber = $callNumber . ' (' . $location . ')';
		}
		if (!empty($formType)) {
			$$formType = 'yes';
		}
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
		if (!preg_match('/faculty/i', strtolower($_SESSION['libSession']['status'])) && !preg_match('/staff/i', strtolower($_SESSION['libSession']['status'])) && (($genre == 'av' && !empty($scananddeliv) && $scananddeliv == 'yes') || (isset($cdl) && $cdl == 'yes') || (isset($reserves) && $reserves == 'yes'))) {
			if ($genre == 'av' && !empty($scananddeliv) && $scananddeliv == 'yes') {
				$message = '<p>Video digitization is only available to faculty. If you are a faculty member and are seeing this message, please contact the library (libweb@wou.edu)</p>';
			} else {
				$message = '<p>Only course instructors may place items on reserve.</p>';
			}
			$message .= 'Your status is: ' . $_SESSION['libSession']['status'];
			redirectToForm($message, null);
		} elseif (($genre == 'av' && !empty($scananddeliv) && $scananddeliv == 'yes' && empty($_REQUEST['mailform']) && empty($_mailform)) || $videoDigitization == 'yes') {
			$url = $digitizationURL . "title={$title}&date={$date}&isbn={$isbn}&oclcnum={$oclcnum}&authors={$authors}&callNumber={$callNumber}&description={$description}&WOUOwns=Yes&format=Video";
			redirectToForm(null, $url);
		} elseif (!empty($scananddeliv) && $scananddeliv == 'yes' && empty($_REQUEST['mailform']) && empty($mailform)) {
			$message = '';
			$url     = $digitizationURL . "title=$title&date=$date&isbn=$isbn&oclcnum=$oclcnum&authors=$authors&callNumber=$callNumber&description=$description&atitle=$atitle&issn=$issn&issue=$issue&volume=$volume&ericdoc=$ed&WOUOwns=Yes&sid=$sid&format=Print";
			redirectToForm($message, $url);
		}
		//delivery form
		elseif (!empty($_REQUEST['mailform']) || !empty($mailform)) {
			$requestType = !empty($_REQUEST['req_type']) ? $_REQUEST['req_type'] : 'hold';
			$eligibility = 'ELIGIBLE';
			//we have two MMSIDs that our hotspot checkout program is attached to. If that is one of the records we are looking for, we need to verify teh student is eligible to check a hotspot out
			if ($_REQUEST['mms_id'] == '99900371275501856' || $_REQUEST['mms_id'] == '99900364672801856') {
				$eligibility = getHotSpotList($IDnumber, $eligibilityURL);
				if (empty($eligibility)) {
					$eligibility = 'ELIGIBILE';
				}
			}
			if ($eligibility != 'NOT ELIGIBLE') {
				$url = $holdURL . "title={$title}&date={$date}&isbn={$isbn}&authors={$authors}&callnumber={$callNumber}&description={$description}&barcode={$barcode}&mmsid={$mms_id}&requestType={$requestType}&oclcnum={$oclcnum}&sid={$sid}";
				if (!empty($format) || (preg_match('/interlibrary/i', $_REQUEST['req_type']) || preg_match('/ill/i', $_REQUEST['req_type']))) {
					$url .= "&atitle={$atitle}&doi={$doi}&pmid={$pmid}&issn={$issn}&format={$format}&sid={$sid}&pages={$pages}&volume={$volume}&issue={$issue}";
				}
				$x = 1;
				$phoneNums = array();
				foreach ($phoneNo as $k => $v) {
					$v['number'] = preg_replace('/[^0-9]/i', '', $v['number']);
					if (!in_array($v['number'], $phoneNums)) {
						$phoneNums[] = $v['number'];
						$url .= '&phone' . $x . '=' . $v['number'];
						if (!empty($v['sms']) && !isset($smsPhone)) {
							$smsPhone = $x;
							//$url .= '* This is currently selected as your SMS number';
							$url .= '&smsPhone=phone' . $x . '&wantSMS=No';
							$url .= '&sms=' . $v['sms'];
							$url .= '&smsNumber=' . $v['number'];
						}
					}
					unset($k, $v);
					$x++;
				}
				if (empty($format) || $format != 'copy') {
					$x   = 1;
					foreach ($address as $k => $v) {
						foreach ($v as $k2 => $v2) {
							$url .= '&address' . $x . '_' . $k2 . '=' . $v2;
							unset($k2, $v2);
						}
						unset($k, $v);
						$x++;
					}
				}
				if ($requestType != 'hold') {
					$url .= "&req_type=";
					$url .= preg_match('/summit/i', $requestType) ? 'summit' : ((preg_match('/interlibrary/i', $requestType) || preg_match('/ill/i', $requestType)) ? 'ill' : $requestType);
				}
				redirectToForm(null, $url);
			} else {
				//print $eligibility;
				$message = '<p>&nbsp;</p><p>iPad requesting is limited to students enrolled in specific courses. You do not appear to be enrolled in one of those courses.</p><p>If you believe this is incorrect, please <a href="https://library.wou.edu">contact the library</a> (libweb@wou.edu)</p>';
				redirectToForm($message, null);
			}
		} elseif (!empty($cdl) || !empty($reserves)) {
			$detailsStr = "Title:%20{$title}%0ADate:%20{$date}%0AISBN:%20{$isbn}%0AVolume:%20{$volume}%0AMMSID:%20{$mmsid}%0AType:%20{$genre}%0AcallNumber:%20{$callNumber}%0Adescription:%20{$description}%0Alocation:%20{$location}";
			$url = $reserveFormURL . "details={$detailsStr}&WOUOwns=yes";
			$url .= !empty($cdl) ? "&reserveType=CDL" : "&reserveType=reserves";
			redirectToForm(null, $url);
		} else {
			$url = $requestFormURL . "&title={$title}&date={$date}&isbn={$isbn}&authors={$authors}";
			redirectToForm(null, $url);
		}
	}
} //end of not expired
else {
	$message = 'Requesting of materials is only available to current WOU students, faculty, and staff. Please contact the Reference desk if you feel you have gotten this message in error.';
	redirectToForm($message, null);
}
