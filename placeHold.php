<?php
/*
ini_set('display_errors', 1);
error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set('display_errors', '1');
*/
//place the hold and indicate pickup location
//we must have a session to store to
if (!isset($_SESSION)) {
	session_start();
} //this has to stay or CC/grad/senate, etc won't load right
//not a hotspot request, include the login info
include_once('privateFunctions.php'); //privateFunctions is a poorly named file that includes variables for our APIkey, wskey, and URL for verification of patron hotspot eligibility
include_once('functions.php');
if (empty($_REQUEST['req_type']) || $_REQUEST['req_type'] != 'hotspot') {
	include_once($_SERVER['DOCUMENT_ROOT'] . '/webFiles/login/top.php');
	if (isset($_REQUEST['viewas'])) {
		include($_SERVER['DOCUMENT_ROOT'] . '/webFiles/login/viewas.php');
	}
?>
	<html>

	<head>
		<style>
			body,
			p,
			div {
				font-family: Arial, Helvetica, sans-serif;
				font-family: 'Open Sans', sans-serif;
				background-color: transparent;
			}
		</style>
	</head>

	<body>
	<?php
	print '<div style="padding:2em;">';
} else {
	print "<html><body>";
}
//variables that are pulled from the URL sent by the General Electronic Service are in the parameters file along with other non-secret params
include_once('parameters.php');
if (!empty($pickupLibrary)) {
	$pickupLocation = $_REQUEST['pickupLibrary'];
	if (preg_match('/salem/i', $pickupLibrary)) {
		$pickupLibrary = 'WOU_Salem';
	} else {
		$pickupLibrary = 'WOU';
	}
}

//This is not a summit or hotspot request and includes a barcode (item-specific request). Go get the details (item id, holiding id, etc.) from the record so we can place the request correctly. 
// We DO NOT WANTt item level holds on hotspots or tablets.//
if (empty($_REQUEST['req_type']) || (!preg_match('/summit/i', $_REQUEST['req_type']) && !preg_match('/hotspot/i', $_REQUEST['req_type'])) && !empty($_REQUEST['barcode'])) {
	$service_url   = 'https://api-na.hosted.exlibrisgroup.com/almaws/v1/items';
	$curl_response = getAlmaRecord($_REQUEST['pid'], $service_url, $queryParams); //keep $_REQUEST['pid] - I think it is actually the piece, not patron, id in this instance
	//print $curl_response;
	$bib           = json_decode($curl_response, true);
	//print_r($bib);
	$mmsid = !empty($bib['bib_data']['mms_id']) ? $bib['bib_data']['mms_id'] : $mmsid;
	$hid   = $bib['holding_data']['holding_id'];
	$iid   = $bib['item_data']['pid'];
	$itemDescription   = $bib['item_data']['description'];
}
$sendToAddress = '';
$makeRequest = new stdClass();
$makeRequest->request_type = "HOLD";
$makeRequest->description = $itemDescription;
$makeRequest->pickup_location_type = "LIBRARY";
$makeRequest->pickup_location_library = $pickupLibrary;
$makeRequest->pickup_location_circulation_desk = null;
$makeRequest->pickup_location_institution = null;
$makeRequest->material_type->value = null;
$makeRequest->comment = "Item to be picked up at {$pickupLocation}";

//make an array to update the address/phone. Still hacky, but whatever.
if (!empty($_REQUEST['line1']) || !empty($_REQUEST['mailTo'])) {
	$makeRequest->pickup_location_type = 'USER_HOME_ADDRESS';
	$addressNo                           = !empty($_REQUEST['mailTo']) ? str_replace('Use Address ', '', $_REQUEST['mailTo']) : 1;
	$addressNo                           = $addressNo - 1;
	$makeRequest->comment             = 'Send to this address ';
	$makeRequest->comment .= !empty($_REQUEST['mailTo']) ? '(Patron ' . str_replace('Use ', '', $_REQUEST['mailTo']) . '): ' : ': ';
	if (!empty($_REQUEST['line1'])) {
		$sendToAddress = $_REQUEST['line1'] . ' ';
		$sendToAddress .= !empty($_REQUEST['line2']) ? $_REQUEST['line2'] . ' ' : '';
		$sendToAddress .= $_REQUEST['city'] . ', ';
		$sendToAddress .= $_REQUEST['state'] . ' ';
		$sendToAddress .= $_REQUEST['zip'] . ' ';
	} elseif (!empty($_REQUEST['mailTo'])) {
		$sendToAddress = $_SESSION['woulib']['addresses'][$addressNo]['line'][0] . ' ';
		$sendToAddress .= !empty($_SESSION['woulib']['addresses'][$addressNo]['line'][1]) ? $_SESSION['woulib']['addresses'][$addressNo]['line'][1] . ' ' : '';
		$sendToAddress .= $_SESSION['woulib']['addresses'][$addressNo]['city'];
		$sendToAddress .= ', ' . $_SESSION['woulib']['addresses'][$addressNo]['state'];
		$sendToAddress .= ' ' . $_SESSION['woulib']['addresses'][$addressNo]['zip'];
	}
	$makeRequest->comment .= $sendToAddress;
}
$makeRequest->mms_id = $mmsid;

//reset $queryParams to default
$queryParams = '?' . urlencode('apikey') . '=' . urlencode($apikey);
//non-summit requests, create a hold
if (empty($_REQUEST['req_type']) || (!preg_match('/summit/i', $_REQUEST['req_type']) && !preg_match('/Interlibrary/i', $_REQUEST['req_type']) && $_REQUEST['req_type'] != 'ill')) {
	//print '<p>Hold request</p>';
	//we have a holding Id and it isn't a hotspot, the URL should go to the item level
	if ((empty($_REQUEST['req_type']) || $_REQUEST['req_type'] != 'hotspot' || $mmsid == '99900391873201856') && !empty($hid) && !empty($itemDescription)) {
		//print "ITEM";
		$queryParams .= '&' . urlencode('user_id') . '=' . urlencode($_REQUEST['pid']);
		$service_url = 'https://api-na.hosted.exlibrisgroup.com/almaws/v1/bibs/' . $mmsid . '/holdings/' . $hid . '/items/' . $iid;
	} else {
		//print "BIB";
		//we know who the patron is and are placing a bib-level request
		$service_url = 'https://api-na.hosted.exlibrisgroup.com/almaws/v1/users/' . $pid; //$_REQUEST['viewas'];
		$queryParams .= '&user_id_type=all_unique&mms_id=' . $mmsid;
	}
	$service_url .= '/requests';
	$makeRequest   = json_encode($makeRequest, JSON_PRETTY_PRINT);
	//user CURL to place hold
	$curl_response = postAlmaHold($makeRequest, $service_url, $queryParams);
}
//summit request, create a resource sharing request
elseif (!empty($_REQUEST['req_type']) && (preg_match('/summit/i', $_REQUEST['req_type']) || preg_match('/Interlibrary/i', $_REQUEST['req_type']) || $_REQUEST['req_type'] == 'ill')) {
	print '<p>Resouce Sharing Request</p>';
	if ($req_type != 'hold' && $req_type != 'digitize' && (empty($author) || empty($oclc_num) || empty($isbn) || empty($title))) {
		//if we are missing citation information, go get it from OCLC. We really should have an OCLC number for this to work correctly.
		include('oclc.php');
		if (empty($oclc_num)) {
			$oclc_num = !empty($oclcnum) ? $oclcnum : '';
		}
	}

	//create the container for our request
	$thisRequest = new ResourceSharingRequestObject();
	$thisRequest->set_format((!empty($reqFormat) && $reqFormat == 'copy') ? 'Digital' : 'Physical');
	$thisRequest->set_citation_type($thisRequest->format->desc);
	$thisRequest->set_param('title', $title);
	$thisRequest->set_param('chapter_title', !empty($_REQUEST['atitle']) ? trim($_REQUEST['atitle']) : null);
	$thisRequest->set_param('oclc_number', $oclc_num);
	$thisRequest->set_param('volume', $itemDescription);
	$thisRequest->set_param('issue', $issue);
	$thisRequest->set_param('isbn', $isbn);
	$thisRequest->set_param('issn', $issn);
	$thisRequest->set_param('pmid', $pmid);
	$thisRequest->set_param('doi', $doi);
	$thisRequest->set_param('author', $author);
	$thisRequest->set_param('source', $sid);
	$thisRequest->set_param('year', $date);
	$thisRequest->set_param('pages', $pages);
	$thisRequest->set_value('level_of_service', (!empty($_REQUEST['lolr']) && $_REQUEST['lolr'] == 'Yes') ? 'WHEN_CONVINIENT' : null);
	$thisRequest->set_pickup_location($pickupLibrary);
	$thisRequest->set_preferred_send_method((!empty($reqFormat) && $reqFormat == 'copy') ? 'EMAIL' : 'MAIL');
	$thisRequest->last_interest_date = date('Y-m-d', strtotime('18 days')) . 'Z'; // or form value
	if (preg_match('/Interlibrary/i', $_REQUEST['req_type']) || $_REQUEST['req_type'] == 'ill' || $reqFormat == 'copy') {
		$thisRequest->set_value('partner', 'OCLC');
	}
	if (!empty($_REQUEST['line1']) || !empty($_REQUEST['mailTo'])) {
		$addressNo       = !empty($_REQUEST['mailTo']) ? str_replace('Use Address ', '', $_REQUEST['mailTo']) : 1;
		$addressNo       = $addressNo - 1;
		$thisRequest->note = 'Upon receipt, send to: ';
		$thisRequest->note .= !empty($_REQUEST['mailTo']) ? '(Patron ' . str_replace('Use ', '', $_REQUEST['mailTo']) . '): ' : ': ';
		if (!empty($_REQUEST['line1'])) {
			$sendToAddress = $_REQUEST['line1'] . ' ';
			$sendToAddress .= !empty($_REQUEST['line2']) ? $_REQUEST['line2'] . ' ' : '';
			$sendToAddress .= $_REQUEST['city'] . ', ';
			$sendToAddress .= $_REQUEST['state'] . ' ';
			$sendToAddress .= $_REQUEST['zip'] . ' ';
		} elseif (!empty($_REQUEST['mailTo'])) {
			$sendToAddress = $_SESSION['woulib']['addresses'][$addressNo]['line'][0] . ' ';
			$sendToAddress .= !empty($_SESSION['woulib']['addresses'][$addressNo]['line'][1]) ? $_SESSION['woulib']['addresses'][$addressNo]['line'][1] . ' ' : '';
			$sendToAddress .= $_SESSION['woulib']['addresses'][$addressNo]['city'];
			$sendToAddress .= ', ' . $_SESSION['woulib']['addresses'][$addressNo]['state'];
			$sendToAddress .= ' ' . $_SESSION['woulib']['addresses'][$addressNo]['zip'];
		}
		$thisRequest->note .= $sendToAddress;
	}
	$thisRequest->note .= " Request submitted via API.";
	if (empty($thisRequest->level_of_service->value)) {
		unset($thisRequest->level_of_service);
	}
	$request = json_encode($thisRequest, JSON_PRETTY_PRINT);
	$queryParams .= '&' . urlencode('override_blocks') . '=' . urlencode('true') . '&format=json&user_id_type=all_unique';
	// if ($pid != 'V00016181') {
	$service_url   = "https://api-na.hosted.exlibrisgroup.com/almaws/v1/users/$pid/resource-sharing-requests";
	$curl_response = postAlmaHold($request, $service_url, $queryParams);
	/* } else {
        print '<p>REQUEST NOT PLACED FOR TESTING!!!</p>';
        $curl_response = $request;
        print "<p>NEW REQUEST</p><pre>{$request}</pre>";
    }*/
}
$result = json_decode($curl_response, true);
if (isset($result['title']) && trim($result['title']) == 'Tablet:') {
	$result['title'] = 'Tablet: iPad';
}
//depending on what we're doing here, or success message varies
$successMessage = "<p><strong>Request placed for:</strong> <em>" . $result['title'] . "</em></p>";
if (preg_match('/summit/i', $_REQUEST['req_type'])) {
	$successMessage .= 'You will receive an email and/or text notification when your item arrives. ';
}
if (!empty($_REQUEST['line1']) || !empty($_REQUEST['mailTo'])) {
	$successMessage .= "<p>We will send it to you at: " . $sendToAddress . "</p><p>Our onsite staff will check out the materials to your library account and put them in the mail to you within the next few business days.</p>";
} else {
	$successMessage .= '<p>It will be available for pickup at ' . $pickupLibraries[$pickupLibrary] . '</p><p></p>';
	if (!preg_match('/summit/i', $_REQUEST['req_type']) && $pickupLibrary == 'WOU') {
		// $successMessage .= 'We pull materials at 9 a.m. and 1 p.m. daily, so you can come by after the next scheduled pull time.';
	}
}
if (empty($_REQUEST['usePhone']) && !empty($_REQUEST['smsNumber'])) {
	$successMessage .= '<p>You will receive messages about this and other requests at the number:<strong>' . $_REQUEST['smsNumber'] . '</strong></p>';
}
$requestPlaced = false;
//if the hold placed successfully
if (!empty($result) && (empty($result['errorsExist']) || $result['errorsExist'] != true) && empty($result['web_service_result']['errorsExist'])) {
	//Wahoo!
	print $successMessage;
	$requestPlaced = true;
} else {
	print_r($result);
	//wah-wah. We weren't able to place a hold
	//we also don't have  barcode or description. We're done.
	if (empty($_REQUEST['barcode']) || empty($itemDescription)) {
		print '<strong>There was an error placing a hold on this item:</strong><br>';
		//print $curl_response;
		//print all the errors
		if (isset($result['errorList'])) {
			foreach ($result['errorList']['error'] as $k => $v) {
				print $v['errorMessage'] . '<br>';
				unset($k, $v);
			}
			//display the response and request that was sent
			if (isset($_REQUEST['debug'])) {
				print $curl_response . '<br>';
				print $makeRequest . '<br>';
			}
		}
	}
	//we do have a description or barcode, let's try another route to get the hold placed
	else {
		//I don't actually remember why I am placing the item hold in a loop here. It seems like a bad idea. But, it works and I'm not feeling the urge to break it right now.
		//I'm guessing maybe this was an early attempt on my part to place multiple holds at the same time.
		//also, for some inexplicable (to me) reason, sometimes the JSON returned by the API has the error within the web_service_result and sometimes not, so we have to account for both when trying to place the hold
		//no web_service_result
		if (isset($result['errorList']['error'])) {
			foreach ($result['errorList']['error'] as $k => $v) {
				//place a second request, this time at the bib level
				$secondResult = bibLevelHold($pid, $mmsid, $makeRequest, $queryParams);
				if (!empty($secondResult) && (empty($secondResult['errorsExist']) || $secondResult['errorsExist'] != true) && empty($jsonResult['web_service_result']['errorsExist']) && empty($secondResult['web_service_result']['errorsExist'])) {
					//it worked!
					print $successMessage;
				} else {
					//no dice
					print '<strong>There was an error placing a hold on this:</strong><br>';
					/*foreach ($secondResult['errorList']['error'] as $k2 => $v2) {
						print $v2['errorMessage'] . '<br>';
						unset($k2, $v2);
					}*/
					print_r($secondResult['errorList']);
					if (isset($_REQUEST['debug'])) {
						print $curl_response . '<br>';
						print $makeRequest . '<br>';
						print $secondResult . '<br>';
					}
				}
			}
		}
		//web_service_result
		elseif (!empty($result['web_service_result']['errorList']['error'])) {
			foreach ($result['web_service_result']['errorList']['error'] as $k => $v) {
				$secondResult = bibLevelHold($pid, $mmsid, $makeRequest, $queryParams);
				if (!empty($secondResult) && (empty($secondResult['errorsExist']) || $secondResult['errorsExist'] != true) && empty($jsonResult['web_service_result']['errorsExist']) && empty($secondResult['web_service_result']['errorsExist'])) {
					//let the code angels sing
					print $successMessage;
				} else {
					//bummer, dude
					print '<strong>There was an error placing this hold:</strong><br>';
					/*foreach ($secondResult['errorList']['error'] as $k2 => $v2) {
						print $v2['errorMessage'] . '<br>';
						unset($k2, $v2);
					}*/
					print_r($secondResult['errorList']);
					if (isset($_REQUEST['debug'])) {
						print $curl_response . '<br>';
						print $makeRequest . '<br>';
						print $secondResult . '<br>';
					}
				}
			}
		} else {
			print $curl_response;
		}
		print '<br>';
	}
}
//if we haven't alredy included this, make sure our page has the styles, etc.
if (empty($_REQUEST['req_type']) || $_REQUEST['req_type'] != 'hotspot') {
	?>
		<style>
			/*Chat CSS*/
			/* Button used to open the chat form - fixed at the bottom of the page */
			.open-button {
				background-color: #555;
				color: white !important;
				padding: 16px 20px;
				border: none;
				cursor: pointer;
				opacity: 0.8;
				/* position: fixed;
	  bottom: 60px;
	  right: 0px;*/
				width: 280px;
				font-size: large;
				max-height: 4em;
				z-index: 8;
			}

			/* The popup chat - hidden by default */
			.chat-popup {
				display: none;
				/* position: fixed;
	  bottom: 60px;
	  right: 15px; */
				border: 3px solid #f1f1f1;
				height: 325px;
				width: 350px;
				z-index: 9;
				background-color: white;
				overflow: visible;
				opacity: .95;
			}

			.topBar {
				width: 100%;
				font-size: larger;
				font-weight: bold;
			}

			.chat-popup button {
				background: #dbdbdb !important;
				margin: .25em !important;
			}

			@media (max-device-width:480px) {

				.chat-popup,
				.open-button {
					bottom: 0px;
				}

				.open-button {
					font-size: normal;
				}

				.open-button img {
					width: auto !important;
					height: 1em !important;
				}
			}

			/*end chat css*/
		</style>
		<p style="padding-top:1em;">To view and manage your requests, visit the <a href="https://library.wou.edu/my-library/">My Library</a> page.</p>
		<h3>Questions?</h3>
		<div class="open-button" onclick="openForm()" id="chatButton"><img align="top" alt="Click to chat with WOULibrary" border="0" class="photo_noborder" id="wou_chat" src="//library.wou.edu/webFiles/images/buttons/bubble.png" style="max-height:2em;vertical-align: middle;margin-right:.5em;" />Ask WOU Library</div>
		<div class="chat-popup" id="myForm">
			<div class="topBar"><button type="button" class="btn cancel" onclick="closeForm()">X</button> Ask WOU</div>
			<div class="needs-js"><span style="font-size:small;" id="chatAway"></span><a href="mailto:libweb@wou.edu"><img alt="Click to email WOULibrary" border="0" id="wou_chat_away" src="//library.wou.edu/webFiles/images/buttons/envelope.png" style="max-height:2em;float:right;" /></a> We're offline, but you can email us (<a href="mailto:libweb@wou.edu">libweb@wou.edu</a>) and we'll get back to you as soon as we can. In the meantime, check our <strong><a href="https://woulibrary.ask.libraryh3lp.com/" target="_blank">FAQ</a></strong> to see it the answer to your question is there.</div>
		</div>
		<?php
		print '</div>';
		?>
		<script>
			/*jQuery(document).ready(function(){
	jQuery(".chat-popup").show(0).delay(2500).fadeOut(3000);
	jQuery(".open-button").hide(0).delay(2500).fadeIn(3000);
	*/
			/*jQuery(".open-button").animate({
				opacity: 0.8
			  }, 1500 );
			jQuery(".chat-popup").hide(0).delay(4000).fadeIn(3000);*/
			/*jQuery('.chat-popup').draggable();
			jQuery('.open-button').draggable();
			jQuery( ".chat-popup" ).resizable({
				  helper: "ui-resizable-helper"
				});
			});*/
			function openForm() {
				document.getElementById("myForm").style.display = "block";
				document.getElementById("chatButton").style.display = "none";
			}

			function closeForm() {
				document.getElementById("myForm").style.display = "none";
				document.getElementById("chatButton").style.display = "block";
			}
		</script>
		<script src="https://woulibrary.ask.libraryh3lp.com/js/faq-embeddable/embed.js"></script>
		<script src="https://libraryh3lp.com/js/libraryh3lp.js?15276"></script>
	<?php
}
	?>
	</body>

	</html>