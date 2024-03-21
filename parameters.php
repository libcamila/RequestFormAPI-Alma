<?php
class ResourceSharingRequestObject
{
    public $owner, $agree_to_copyright_terms;
    public $format, $citation_type, $title, $chapter_title, $oclc_number, $volume, $issue, $issn, $isbn, $pmid, $doi, $author, $source, $year, $pages, $allow_other_formats, $pickup_location, $level_of_service, $preferred_send_method, $last_interest_date, $note;
    public function __construct()
    {
        $this->note = '';
        $this->owner = "WOU";
        $this->agree_to_copyright_terms = true;
        $this->allow_other_formats = true;
    }
    /*
        $this->material_type = $material_type;
        $this->comment = $comment;*/
    function set_param($name, $value)
    {
        $this->{$name} = $value;
    }
    function set_value($name, $value)
    {
        $this->{$name}->value =  $value;
    }
    function set_format($format)
    {
        $this->format->desc = $format;
        $this->format->value =  strtoupper($format);
    }
    function set_citation_type($format)
    {
        $this->citation_type->desc = $format == 'Digital' ? 'Article' : 'Book';
        $this->citation_type->value = $format == 'Digital' ? 'CR' : 'BK';
    }
    function set_preferred_send_method($method)
    {
        $this->preferred_send_method->desc = true;
        $this->preferred_send_method->value =  $method;
    }
    function set_pickup_location($pickupLibrary)
    {
        global $pickupLibraries;
        $this->pickup_location->desc = $pickupLibraries[$pickupLibrary];
        $this->pickup_location->value =  $pickupLibrary;
    }
    function set_level_of_service($level_of_service)
    {
        $this->level_of_service->value =  $level_of_service;
    }
}
foreach ($_REQUEST as $k => $v) {
    if (!empty($v)) {
        $$k = $v;
    }
    unset($k, $v);
}

$portalURL = 'https://wou.edu/portal';
$forcePortalURL = 'https://library.wou.edu/webFiles/login/portal_redirect.php';
$digitizationURL = "https://library.wou.edu/digitization-request-form/?";
$holdURL         = "https://library.wou.edu/request-pickup-or-mailing-of-hamersly-library-materials/?";
$requestFormURL  = "https://library.wou.edu/request-form/?";
$reserveFormURL  = "https://library.wou.edu/reserves/?";
$pickupLibraries['WOU_Salem'] = 'WOU Salem';
$pickupLibraries['WOU'] = 'Hamersly Library';
$callNumber      = !empty($_REQUEST['callNumber']) ? $_REQUEST['callNumber'] : null;
$location        = !empty($_REQUEST['location']) ? $_REQUEST['location'] : null;
$description     = !empty($_REQUEST['description']) ? $_REQUEST['description'] : null;
$atitle           = !empty($_REQUEST['atitle']) ? $_REQUEST['atitle'] : null;
$sid           = !empty($_REQUEST['sid']) ? $_REQUEST['sid'] : (!empty($_REQUEST['source']) ? $_REQUEST['source'] : null);
$doi           = !empty($_REQUEST['doi']) ? $_REQUEST['doi'] : null;
$pmid           = !empty($_REQUEST['pmid']) ? $_REQUEST['pmid'] : null;
$format           = !empty($_REQUEST['format']) ? $_REQUEST['format'] : null;
$date            = !empty($_REQUEST['date']) ? $_REQUEST['date'] : null;
$date            = (!empty($date) && $date != '19691231' && $date != '1969-12-31') ? str_replace('0101', '', $date) : null;
$isbn            = !empty($_REQUEST['isbn']) ? urldecode(trim($_REQUEST['isbn'])) : (!empty($_REQUEST['isn']) && empty($_REQUEST['issn']) ? urldecode(trim($_REQUEST['isn'])) : null);
$issn            = !empty($_REQUEST['issn']) ? trim($_REQUEST['issn']) : (!empty($_REQUEST['isn']) && empty($_REQUEST['isbn']) ? urldecode(trim($_REQUEST['isn'])) : null);
$pages            = !empty($_REQUEST['pages']) ? $_REQUEST['pages'] : null;
$barcode         = !empty($_REQUEST['barcode']) ? $_REQUEST['barcode'] : null;
$mmsid           = !empty($_REQUEST['mms_id']) ? $_REQUEST['mms_id'] : null;
$authors         = !empty($_REQUEST['authors']) ? $_REQUEST['authors'] : null;
$author          = !empty($_REQUEST['author']) ? preg_replace('/\W/i', ' ', trim($_REQUEST['author'])) : $authors;
$oclcnum         = !empty($_REQUEST['oclcnum']) ? $_REQUEST['oclcnum'] : null;
$req_type    = !empty($_REQUEST['type']) ? $_REQUEST['type'] : (!empty($_REQUEST['req_type']) ? $_REQUEST['req_type'] : 'loan');
$loan_id     = !empty($_REQUEST['loan_id']) ? trim($_REQUEST['loan_id']) : null;
$reqFormat     = !empty($_REQUEST['format']) ? strtolower(trim($_REQUEST['format'])) : (!empty($issn) && (preg_match('/interlibrary/i', $req_type) || preg_match('/ill/i', $req_type)) ? 'copy' : 'loan');
$title =  !empty($_REQUEST['title']) ? stripslashes(trim(urldecode($_REQUEST['title']))) : (!empty($_REQUEST['rft.title']) ? $_REQUEST['rft.title'] : null);
$title       = trim(rtrim($title, '/'));
$oclc_num = !empty($_REQUEST['oclcnum']) ? trim($_REQUEST['oclcnum']) : (!empty($_REQUEST['oclc']) ? trim($_REQUEST['oclc']) : null);
$volume            = !empty($_REQUEST['volume']) ? $_REQUEST['volume'] : null;
$issue           = !empty($_REQUEST['issue']) ? $_REQUEST['issue'] : null;
$details         = !empty($_REQUEST['details']) ? $_REQUEST['details'] : ''; //for reserves/booking form text field
$formType = !empty($_REQUEST['formType']) ? $_REQUEST['formType'] : null;

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
$firstname                = !empty($_SESSION['libSession']['firstname']) ? $_SESSION['libSession']['firstname'] : null;
$lastname                 = !empty($_SESSION['libSession']['lastname']) ? $_SESSION['libSession']['lastname'] : null;
$email                    = !empty($_SESSION['libSession']['email']) ? $_SESSION['libSession']['email'] : null;
$IDnumber                  = !empty($_SESSION['libSession']['IDnumber']) ? $_SESSION['libSession']['IDnumber'] : null;
$status                   = !empty($_SESSION['libSession']['status']) ? trim(stripslashes($_SESSION['libSession']['status'])) : null;
$pid           = !empty($_REQUEST['vnumber']) ? trim($_REQUEST['vnumber']) : (!empty($_REQUEST['pid']) ? trim($_REQUEST['pid']) : $IDnumber); //get our patron identifier

//If pickup was selected, where are they picking it up?
$pickupLocation = '';
$pickupLibrary = !empty($_REQUEST['pickupLibrary']) ? $_REQUEST['pickupLibrary'] : 'WOU';
//if we have a description, we're going to need it to place the hold correctly
$itemDescription = !empty($_REQUEST['description']) ? $_REQUEST['description'] : '';
//encode the barcode, if applicable
$queryParams .= !empty($_REQUEST['barcode']) ? '&' . urlencode('item_barcode') . '=' . urlencode($_REQUEST['barcode']) : null;
