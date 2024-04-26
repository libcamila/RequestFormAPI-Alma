<?php
foreach ($_REQUEST as $k => $v) {
    if (!empty($v)) {
        $$k = $v;
    }
    unset($k, $v);
}
//URLS
$digitizationURL = "https://library.wou.edu/digitization-request-form/?";
$holdURL = "https://library.wou.edu/request-pickup-or-mailing-of-hamersly-library-materials/?";
$portalURL = 'https://wou.edu/portal';
$requestFormURL = "https://library.wou.edu/request-form/?";
$reserveFormURL = "https://library.wou.edu/reserves/?";

//pickup library Array
$pickupLibraries['WOU_Salem'] = 'WOU Salem';
$pickupLibraries['WOU'] = 'Hamersly Library';

//parameters to send
$atitle = !empty($_REQUEST['atitle']) ? $_REQUEST['atitle'] : (!empty($_REQUEST['rft.atitle']) ? $_REQUEST['rft.atitle'] : null);
$atitle = $atitle;
$authors = !empty($_REQUEST['authors']) ? $_REQUEST['authors'] : (!empty($_REQUEST['rft.authors']) ? $_REQUEST['rft.authors'] : null);
$author = !empty($_REQUEST['author']) ? preg_replace('/\W/i', ' ', trim($_REQUEST['author'])) : (!empty($_REQUEST['rft.au']) ? $_REQUEST['rft.au'] : (!empty($_REQUEST['rft.aulast']) ? $_REQUEST['rft.aulast'] : (!empty($_REQUEST['rft.author']) ? $_REQUEST['rft.author'] : $authors)));
$barcode = !empty($_REQUEST['barcode']) ? $_REQUEST['barcode'] : null;
$mmsid = !empty($_REQUEST['mms_id']) ? $_REQUEST['mms_id'] : (!empty($_REQUEST['mmsid']) ? $_REQUEST['mmsid'] : null);
$mms_id = $mmsid;
$req_type = !empty($_REQUEST['type']) ? $_REQUEST['type'] : (!empty($_REQUEST['req_type']) ? strtolower($_REQUEST['req_type']) : (!empty($barcode) ? 'hold' : ((!empty($mms_id) && empty($atitle)) ? 'summit' : 'ill')));
$callNumber = !empty($_REQUEST['callNumber']) ? $_REQUEST['callNumber'] : null;
$city = !empty($_REQUEST['city']) ? $_REQUEST['city'] : null;
$date = !empty($_REQUEST['date']) ? $_REQUEST['date'] : (!empty($_REQUEST['rft.date']) ? $_REQUEST['rft.date'] : null);
$date = (!empty($date) && $date != '19691231' && $date != '1969-12-31') ? str_replace('0101', '', $date) : null;
$description = !empty($_REQUEST['description']) ? $_REQUEST['description'] : null;
$details = !empty($_REQUEST['details']) ? $_REQUEST['details'] : ''; //for reserves/booking form text field
$doi = !empty($_REQUEST['doi']) ? $_REQUEST['doi'] : (!empty($_REQUEST['rft.doi']) ? $_REQUEST['rft.doi'] : null);
$specific_edition = !empty($_REQUEST['editionType'] && $_REQUEST['editionType'] == 'any') ? false : true;
$edition = !empty($_REQUEST['edition']) ? $_REQUEST['edition'] : (!empty($_REQUEST['rft.edition']) ? $_REQUEST['rft.edition'] : null);
$format = $req_type != 'ill' ? 'loan' : (!empty($_REQUEST['format']) ? strtolower($_REQUEST['format']) : (!empty($_REQUEST['genre']) ? strtolower($_REQUEST['genre']) : (!empty($_REQUEST['rft.genre']) ? strtolower($_REQUEST['rft.genre']) : (!empty($_REQUEST['rft_val_fmt']) ? $_REQUEST['rft_val_fmt'] : null))));
if ($format == 'loan' && $req_type == 'ill' && (!empty($atitle) || !empty($issn))) {
    $format = 'copy';
}
$formType = !empty($_REQUEST['formType']) ? $_REQUEST['formType'] : null;
$isbn = !empty($_REQUEST['isbn']) ? urldecode(trim($_REQUEST['isbn'])) : (!empty($_REQUEST['rft.isbn']) ? urldecode(trim($_REQUEST['rft.isbn'])) : (!empty($_REQUEST['isn']) && empty($_REQUEST['issn']) ? urldecode(trim($_REQUEST['isn'])) : null));
$issn = !empty($_REQUEST['issn']) ? trim($_REQUEST['issn']) : (!empty($_REQUEST['rft.issn']) ? trim($_REQUEST['rft.issn']) : (!empty($_REQUEST['isn']) && empty($_REQUEST['isbn']) ? urldecode(trim($_REQUEST['isn'])) : null));
$issue = !empty($_REQUEST['issue']) ? $_REQUEST['issue'] : (!empty($_REQUEST['rft.issue']) ? $_REQUEST['rft.issue'] : null);
$loan_id = !empty($_REQUEST['loan_id']) ? trim($_REQUEST['loan_id']) : null;
$location = !empty($_REQUEST['location']) ? $_REQUEST['location'] : null;
$lolr = !empty($_REQUEST['lolr']) ? $_REQUEST['lolr'] : null;
$mailTo = !empty($_REQUEST['mailTo']) ? $_REQUEST['mailTo'] : null;
$needby = !empty($_REQUEST['need_by']) ? date('Y-m-d', strtotime($_REQUEST['need_by'])) . 'Z' : date('Y-m-d', strtotime('18 days')) . 'Z';
$newPhone = !empty($_REQUEST['newPhone']) ? $_REQUEST['newPhone'] : null;
$oclc_num = !empty($_REQUEST['oclcnum']) ? trim($_REQUEST['oclcnum']) : (!empty($_REQUEST['rft.oclcnum']) ? trim($_REQUEST['rft.oclcnum']) : (!empty($_REQUEST['oclc']) ? trim($_REQUEST['oclc']) : null));
$oclcnum = $oclc_num;
$pages = !empty($_REQUEST['pages']) ? $_REQUEST['pages'] : (!empty($_REQUEST['rft.pages']) ? $_REQUEST['rft.pages'] : null);
$pickupLibrary = !empty($_REQUEST['pickupLibrary']) ? $_REQUEST['pickupLibrary'] : null;
$pmid = !empty($_REQUEST['pmid']) ? $_REQUEST['pmid'] : null;
$reqFormat = !empty($_REQUEST['format']) ? strtolower(trim($_REQUEST['format'])) : (!empty($issn) && (preg_match('/interlibrary/i', $req_type) || preg_match('/ill/i', $req_type)) ? 'copy' : 'loan');
$sid = !empty($_REQUEST['sid']) ? $_REQUEST['sid'] : (!empty($_REQUEST['rft.sid']) ? $_REQUEST['rft.sid'] : (!empty($_REQUEST['source']) ? $_REQUEST['source'] : (!empty($mmsid) ? 'Primo VE' : null)));
$smsNumber = !empty($_REQUEST['smsNumber']) ? $_REQUEST['smsNumber'] : null;
$state = !empty($_REQUEST['state']) ? $_REQUEST['state'] : null;
$title = !empty($_REQUEST['title']) ? stripslashes(trim(urldecode($_REQUEST['title']))) : (!empty($_REQUEST['rft.title']) ? stripslashes(trim($_REQUEST['rft.title'])) : null);
$title = trim(rtrim($title, '/'));
$usePhone = !empty($_REQUEST['usePhone']) ? $_REQUEST['usePhone'] : null;
$volume = !empty($_REQUEST['volume']) ? $_REQUEST['volume'] : (!empty($_REQUEST['rft.volume']) ? $_REQUEST['rft.volume'] : null);
$zip = !empty($_REQUEST['zip']) ? $_REQUEST['zip'] : null;
for ($i = 1; $i <= 5; $i++) {
    $tmpName = 'line' . $i;
    $$tmpName = !empty($_REQUEST['line' . $i]) ? $_REQUEST['line' . $i] : null;
}
// for digitization form
$_REQUEST['request_type'] = !empty($_REQUEST['genre']) ? $_REQUEST['genre'] : null;
$genre                    = $_REQUEST['request_type'];

//base URLs to your form(s) should be set here.
//$digitizationURL = "https://library.school.edu/form1/?";
//$holdURL         = "https://library.school.edu/form2/?";
//$requestFormURL  = "https://library.school.edu/form3/?";
//$reserveFormURL  = "https://library.school.edu/form3/?";
$hotspotURL = "https://library.wou.edu/hotspots/";
$top_html             = '<style>input[type=text] {width:auto;}</style><div style="padding:1em;">
<div style="width:100%;min-height:80px;text-align:left;margin-bottom:2em;" id="toplogo"><a href="https://library.wou.edu/" target="_blank"><img src="/webFiles/images/logos/woulib_logos/HL_logo_2Color_on_transparent.png" alt="WOU Library logo space" style="max-height:130px;"></a></div>';
//get expiration from session variable
$now             = new DateTime();
//print_r($_SESSION['libSession']);
$expires         = DateTime::createFromFormat('m-d-Y', $_SESSION['libSession']['expiration']); // our expiration is saved in m-d-Y format, yours may be different

//patron dets
$firstname  = !empty($_SESSION['libSession']['firstname']) ? $_SESSION['libSession']['firstname'] : null;
$lastname  = !empty($_SESSION['libSession']['lastname']) ? $_SESSION['libSession']['lastname'] : null;
$email = !empty($_SESSION['libSession']['email']) ? $_SESSION['libSession']['email'] : null;
$IDnumber = !empty($_SESSION['libSession']['IDnumber']) ? $_SESSION['libSession']['IDnumber'] : null;
$status = !empty($_SESSION['libSession']['status']) ? trim(stripslashes($_SESSION['libSession']['status'])) : null;
$pid = !empty($IDnumber) ? $IDnumber : (!empty($_SESSION['libSession']['univID']) ? $_SESSION['libSession']['univID'] : null); //this is the primary ID


//If pickup was selected, where are they picking it up?
$pickupLocation = '';
$pickupLibrary = !empty($_REQUEST['pickupLibrary']) ? $_REQUEST['pickupLibrary'] : 'WOU';
//if we have a description, we're going to need it to place the hold correctly
$itemDescription = !empty($_REQUEST['description']) ? $_REQUEST['description'] : '';
$states      = array(
    'Alabama' => 'AL',
    'Alaska' => 'AK',
    'Arizona' => 'AZ',
    'Arkansas' => 'AR',
    'California' => 'CA',
    'Colorado' => 'CO',
    'Connecticut' => 'CT',
    'Delaware' => 'DE',
    'Florida' => 'FL',
    'Georgia' => 'GA',
    'Hawaii' => 'HI',
    'Idaho' => 'ID',
    'Illinois' => 'IL',
    'Indiana' => 'IN',
    'Iowa' => 'IA',
    'Kansas' => 'KS',
    'Kentucky' => 'KY',
    'Louisiana' => 'LA',
    'Maine' => 'ME',
    'Maryland' => 'MD',
    'Massachusetts' => 'MA',
    'Michigan' => 'MI',
    'Minnesota' => 'MN',
    'Mississippi' => 'MS',
    'Missouri' => 'MO',
    'Montana' => 'MT',
    'Nebraska' => 'NE',
    'Nevada' => 'NV',
    'New Hampshire' => 'NH',
    'New Jersey' => 'NJ',
    'New Mexico' => 'NM',
    'New York' => 'NY',
    'North Carolina' => 'NC',
    'North Dakota' => 'ND',
    'Ohio' => 'OH',
    'Oklahoma' => 'OK',
    'Oregon' => 'OR',
    'Pennsylvania' => 'PA',
    'Rhode Island' => 'RI',
    'South Carolina' => 'SC',
    'South Dakota' => 'SD',
    'Tennessee' => 'TN',
    'Texas' => 'TX',
    'Utah' => 'UT',
    'Vermont' => 'VT',
    'Virginia' => 'VA',
    'Washington' => 'WA',
    'West Virginia' => 'WV',
    'Wisconsin' => 'WI',
    'Wyoming' => 'WY'
);
