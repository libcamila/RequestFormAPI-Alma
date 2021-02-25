<?php
//place the hold and indicate pickup location
//we must have a session to store to
if (!isset($_SESSION)) {
    session_start();
} //this has to stay or CC/grad/senate, etc won't load right
//not a hotspot request, include the login info
if (empty($_REQUEST['req_type']) || $_REQUEST['req_type'] != 'hotspot') {
    include_once('functions.php');
    include_once('privateFunctions.php'); //privateFunctions is a poorly named file that includes variables for our APIkey, wskey, and URL for verification of patron hotspot eligibility
    include_once($_SERVER['DOCUMENT_ROOT'] . '/webFiles/login/top.php');
    if (isset($_REQUEST['viewas'])) {
        include($_SERVER['DOCUMENT_ROOT'] . '/webFiles/login/viewas.php');
    }
?>
   <html><head>
   <style>
   body, p, div {
    font-family: Arial, Helvetica, sans-serif;
    font-family: 'Open Sans',sans-serif;
    background-color: transparent;
    }
</style>
</head><body>
    <?php
    print '<div style="padding:2em;">';
}
?>
 <html><body>
    <?php
//if we have a description, we're going to need it to place the hold correctly
$itemDescription = !empty($_REQUEST['description']) ? $_REQUEST['description'] : '';
//encode the barcode, if applicable
$queryParams .= !empty($_REQUEST['barcode']) ? '&' . urlencode('item_barcode') . '=' . urlencode($_REQUEST['barcode']) : '';
//create the container for our request
$makeRequest = json_decode('{"request_type":"HOLD","description":"' . $itemDescription . '","pickup_location_type":"LIBRARY","pickup_location_library":"WOU","pickup_location_circulation_desk":"","pickup_location_institution":"","material_type":{"value":""},"comment":"Item to be picked up at Hamersly Library."}', true);
//get the mmsid from the request
$mmsid       = !empty($_REQUEST['mms_id']) ? $_REQUEST['mms_id'] : '';
//not a summit or hotspot request and includes a barcode (item-specific request). Go get the details (item id, holiding id, etc.) from the record so we can place the request correctly.
//We DO NOT want item level holds on hotspots or tablets.
if (empty($_REQUEST['req_type']) || (!preg_match('/summit/i', $_REQUEST['req_type']) && !preg_match('/hotspot/i', $_REQUEST['req_type'])) && !empty($_REQUEST['barcode'])) {
    $service_url   = 'https://api-na.hosted.exlibrisgroup.com/almaws/v1/items';
    $curl_response = getAlmaRecord($_REQUEST['pid'], $service_url, $queryParams);
    $bib           = json_decode($curl_response, true);
    unset($curl_response);
    $mmsid = $bib['bib_data']['mms_id'];
    $hid   = $bib['holding_data']['holding_id'];
    $iid   = $bib['item_data']['pid'];
}
$sendToAddress = '';
$pid           = !empty($_REQUEST['vnumber']) ? trim($_REQUEST['vnumber']) : trim($_REQUEST['pid']); //get our patron identifier
//make an array to update the address/phone. Still hacky, but whatever.
if (!empty($_REQUEST['line1']) || !empty($_REQUEST['mailTo'])) {
    $makeRequest['pickup_location_type'] = 'USER_HOME_ADDRESS';
    $addressNo                           = !empty($_REQUEST['mailTo']) ? str_replace('Use Address ', '', $_REQUEST['mailTo']) : 1;
    $addressNo                           = $addressNo - 1;
    $makeRequest['comment']              = 'Send to this address ';
    $makeRequest['comment'] .= !empty($_REQUEST['mailTo']) ? '(Patron ' . str_replace('Use ', '', $_REQUEST['mailTo']) . '): ' : ': ';
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
    $makeRequest['comment'] .= $sendToAddress;
}
//non-summit requests, create a hold
if (empty($_REQUEST['req_type']) || !preg_match('/summit/i', $_REQUEST['req_type'])) {
    //print '<p>Hold request</p>';
    //reset $queryParams to default
    $queryParams = '?' . urlencode('apikey') . '=' . urlencode($apikey);
    //we have a holding Id and it isn't a hotspot, the URL should go to the item level
    if ((empty($_REQUEST['req_type']) || $_REQUEST['req_type'] != 'hotspot' || $mmsid == '99900391873201856') && !empty($hid) && !empty($itemDescription)) {
        $queryParams .= '&' . urlencode('user_id') . '=' . urlencode($_REQUEST['pid']);
        $service_url = 'https://api-na.hosted.exlibrisgroup.com/almaws/v1/bibs/' . $mmsid . '/holdings/' . $hid . '/items/' . $iid;
    } else {
        //we know who the patron is and are placing a bib-level request
        $service_url = 'https://api-na.hosted.exlibrisgroup.com/almaws/v1/users/' . $pid; //$_REQUEST['viewas'];
        $queryParams .= '&user_id_type=all_unique&mms_id=' . $mmsid;
    }
    $service_url .= '/requests';
    $makeRequest   = json_encode($makeRequest);
    //user CURL to place hold
    $curl_response = postAlmaHold($makeRequest, $service_url, $queryParams);
}
//summit request, create a resource sharing request
elseif (!empty($_REQUEST['req_type']) && preg_match('/summit/i', $_REQUEST['req_type'])) {
    print '<p>Summit request</p>';
    $req_id      = !empty($_REQUEST['mmsid']) ? trim($_REQUEST['mmsid']) : '';
    $title       = !empty($_REQUEST['title']) ? stripslashes(trim(str_replace('Add to e-Shelf', '', urldecode($_REQUEST['title'])))) : '';
    $isn         = !empty($_REQUEST['title']) ? urldecode(trim($_REQUEST['isn'])) : '';
    $req_type    = !empty($_REQUEST['type']) ? $_REQUEST['type'] : '';
    $oclc_num    = !empty($_REQUEST['oclcnum']) ? trim($_REQUEST['oclcnum']) : '';
    $oclcnum     = $oclc_num;
    $description = !empty($_REQUEST['description']) ? trim($_REQUEST['description']) : '';
    $loan_id     = !empty($_REQUEST['loan_id']) ? trim($_REQUEST['loan_id']) : '';
    $isbn        = !empty($_REQUEST['isbn']) ? urldecode(trim($_REQUEST['isbn'])) : '';
    $issn        = !empty($_REQUEST['issn']) ? trim($_REQUEST['issn']) : '';
    $issue       = !empty($_REQUEST['issue']) ? trim($_REQUEST['issue']) : '';
    $pages       = !empty($_REQUEST['pages']) ? trim($_REQUEST['pages']) : '';
    $author      = !empty($_REQUEST['author']) ? preg_replace('/\W/i', ' ', trim($_REQUEST['author'])) : '';
    $date        = !empty($_REQUEST['date']) ? trim($_REQUEST['date']) : '';
    $atitle      = !empty($_REQUEST['atitle']) ? trim($_REQUEST['atitle']) : '';
    $title       = trim(rtrim($title, '/'));
    if (empty($isbn) && empty($issn)) {
        $isbn = !empty($isn) ? $isn : '';
    }
    if ($req_type != 'hold' and $req_type != 'digitize' && (empty($author) || empty($oclc_num) || empty($isbn) || empty($title))) {
        //if we are missing citation information, go get it from OCLC. We really should have an OCLC number for this to work correctly.
        include('oclc.php');
        if (empty($oclc_num)) {
            $oclc_num = !empty($oclcnum) ? $oclcnum : '';
        }
    }
    $pickup_location['value']            = 'WOU';
    $pickup_location['desc']             = 'Hamersly Library';
    $citation_type['value']              = 'BK';
    $citation_type['desc']               = 'Physical Book';
    $preferred_send_method['value']      = 'MAIL';
    $preferred_send_method['desc']       = 'true';
    $request['owner']                    = 'WOU';
    $format['value']                     = 'PHYSICAL';
    $format['desc']                      = 'Physical';
    $request['format']                   = $format;
    $request['title']                    = $title;
    $request['oclc_number']              = $oclcnum;
    $request['volume']                   = $description;
    $request['isbn']                     = $isbn;
    $request['author']                   = $author;
    $request['year']                     = $date;
    $request['request_id']               = $req_id;
    $request['allow_other_formats']      = 'true';
    $request['agree_to_copyright_terms'] = 'true';
    $request['citation_type']            = $citation_type;
    $request['preferred_send_method']    = $preferred_send_method;
    $request['pickup_location']          = $pickup_location;
    if (!empty($req_id)) {
        $request['mms_id'] = $req_id;
    }
    $request['last_interest_date'] = date('Y-m-d', strtotime('18 days')) . 'Z';
    if (!empty($_REQUEST['line1']) || !empty($_REQUEST['mailTo'])) {
        $addressNo       = !empty($_REQUEST['mailTo']) ? str_replace('Use Address ', '', $_REQUEST['mailTo']) : 1;
        $addressNo       = $addressNo - 1;
        $request['note'] = 'Upon receipt, send to: ';
        $request['note'] .= !empty($_REQUEST['mailTo']) ? '(Patron ' . str_replace('Use ', '', $_REQUEST['mailTo']) . '): ' : ': ';
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
        $request['note'] .= $sendToAddress;
    }
    $request = json_encode($request);
    $queryParams .= '&' . urlencode('override_blocks') . '=' . urlencode('true') . '&format=json&user_id_type=all_unique';
    $service_url   = "https://api-na.hosted.exlibrisgroup.com/almaws/v1/users/$pid/resource-sharing-requests?";
    $curl_response = postAlmaHold($request, $service_url, $queryParams);
}
$result = json_decode($curl_response, true);
if (isset($result['title']) && trim($result['title']) == 'Tablet:') {
    $result['title'] = 'Tablet: iPad';
}
//depending on what we're doing here, or success message varies
$successMessage = "<p><strong>Request placed for:</strong> <em>" . $result['title'] . "</em></p>";
if (preg_match('/summit/i', $_REQUEST['req_type'])) {
    $successMessage .= 'Summit items are delivered twice a week. You will receive an email and/or text notification when your item arrives at Hamersly Library. ';
}
if (!empty($_REQUEST['line1']) || !empty($_REQUEST['mailTo'])) {
    $successMessage .= "<p>We will send it to you at: " . $sendToAddress . "</p><p>Our onsite staff will check out the materials to your library account and put them in the mail to you within the next few business days.</p>";
} else {
    $successMessage .= '<p>It will be available for pickup at Hamersly Library.</p><p></p>';
    if (!preg_match('/summit/i', $_REQUEST['req_type'])) {
        $successMessage .= 'We pull materials at 9 a.m. and 1 p.m. daily, so you can come by after the next scheduled pull time.';
    }
}
$requestPlaced = false;
//if the hold placed successfully
if (!empty($result) && (empty($result['errorsExist']) || $result['errorsExist'] != true) && empty($result['web_service_result']['errorsExist'])) {
    //Wahoo!
    print $successMessage;
    $requestPlaced = true;
} else {
    //wah-wah. We weren't able to place a hold
    //we also don't have  barcode or description. We're done.
    if (empty($_REQUEST['barcode']) || empty($itemDescription)) {
        print '<strong>There was an error placing a hold on this item:</strong><br>';
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
                    print '<strong>There was an error placing a hold on this item:</strong><br>';
                    foreach ($secondResult['errorList']['error'] as $k2 => $v2) {
                        print $v2['errorMessage'] . '<br>';
                        unset($k2, $v2);
                    }
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
                    print '<strong>There was an error placing a hold on this item:</strong><br>';
                    foreach ($secondResult['errorList']['error'] as $k2 => $v2) {
                        print $v2['errorMessage'] . '<br>';
                        unset($k2, $v2);
                    }
                    if (isset($_REQUEST['debug'])) {
                        print $curl_response . '<br>';
                        print $makeRequest . '<br>';
                        print $secondResult . '<br>';
                    }
                }
            }
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
      font-size:large;
    max-height:4em;
      z-index: 8;
    }

    /* The popup chat - hidden by default */
    .chat-popup {
      display: none;
     /* position: fixed;
      bottom: 60px;
      right: 15px; */
      border: 3px solid #f1f1f1;
        height:325px;
        width:350px;
      z-index: 9;
        background-color: white;
        overflow:visible;
    opacity:.95;
    }
     .topBar
        {width:100%
        font-size:larger;
        font-weight: bold;}
        .chat-popup button {background: #dbdbdb !important;
    margin: .25em !important;
    }
    @media (max-device-width:480px){
    .chat-popup,.open-button {
      bottom: 0px;
    }
    .open-button {
    font-size:normal;
    }
    .open-button img {width:auto !important; height:1em !important;}
    }
    /*end chat css*/
    </style>
    <p style="padding-top:1em;">To view and manage your requests, visit the <a href="https://library.wou.edu/my-library/">My Library</a> page.</p>
    <h3>Questions?</h3>
    <div class="open-button" onclick="openForm()" id="chatButton"><img align="top" alt="Click to chat with WOULibrary" border="0" class="photo_noborder" id="wou_chat" src="//library.wou.edu/webFiles/images/buttons/bubble.png"  style="max-height:2em;vertical-align: middle;margin-right:.5em;"/>Ask WOU Library</div>
    <div class="chat-popup" id="myForm"><div class="topBar"><button type="button" class="btn cancel" onclick="closeForm()">X</button> Ask WOU</div>
    <div class="needs-js"><span style="font-size:small;" id="chatAway"></span><a href="mailto:libweb@wou.edu"><img  alt="Click to email WOULibrary" border="0" id="wou_chat_away" src="//library.wou.edu/webFiles/images/buttons/envelope.png"  style="max-height:2em;style:float:right;"/></a> We're offline, but you can email us (<a href="mailto:libweb@wou.edu">libweb@wou.edu</a>) and we'll get back to you as soon as we can. In the meantime, check our <strong><a href="https://woulibrary.ask.libraryh3lp.com/" target="_blank">FAQ</a></strong> to see it the answer to your question is there.</div>
    </div>
    <?php
    print '</div>';
?>
 <script>
    jQuery(document).ready(function(){
    jQuery(".chat-popup").show(0).delay(2500).fadeOut(3000);
    jQuery(".open-button").hide(0).delay(2500).fadeIn(3000);
    /*jQuery(".open-button").animate({
        opacity: 0.8
      }, 1500 );
    jQuery(".chat-popup").hide(0).delay(4000).fadeIn(3000);*/
    jQuery('.chat-popup').draggable();
    jQuery('.open-button').draggable();
    jQuery( ".chat-popup" ).resizable({
          helper: "ui-resizable-helper"
        });
    });
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
