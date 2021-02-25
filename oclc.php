<?php
//this page's sole purpose is completing citation data by geting the record from OCLC.
if (!empty($oclcnum) || !empty($_REQUEST['oclcnum'])) {
    if (empty($oclcnum)) {
        $oclcnum = $_REQUEST['oclcnum'];
    }
    $isn = $oclcnum;
} elseif (!empty($isbn) || !empty($_REQUEST['isbn'])) {
    if (empty($isbn)) {
        $isbn = $_REQUEST['isbn'];
    }
    $isn = 'isbn/' . $isbn;
} elseif (!empty($issn) || !empty($_REQUEST['issn'])) {
    if (empty($issn)) {
        $issn = $_REQUEST['issn'];
    }
    $isn = 'issn/' . $issn;
}
$oclc_url   = "https://www.worldcat.org/webservices/catalog/content/$isn?wskey=$wskey"; //wskey is unique to the institution
//&format=json';
//I hate XML with a firey passion. Please give me JSON.
$oclc_xml   = file_get_contents($oclc_url);
$xml        = simplexml_load_string($oclc_xml);
$oclc_json  = json_encode($xml, JSON_PRETTY_PRINT);
$oclc_array = json_decode($oclc_json, TRUE);
//the most important piece of info we need if we don't already have it is the OCLC#
if (empty($oclcnum)) {
    $oclcnum = $oclc_array['controlfield'][0];
}
//if we need data and have an array, let's go get it
if ((empty($issn) && empty($isbn) || empty($title) || empty($date)) && isset($oclc_array)) {
    foreach ($oclc_array['datafield'] as $l => $q) {
        if ($q['@attributes']['tag'] == '022') {
            if (is_array($q['subfield'])) {
                $issn = $q['subfield'][0];
            } else {
                $issn = $q['subfield'];
            }
        }
        if ($q['@attributes']['tag'] == '020' && empty($isbn)) {
			//just get the first one, if it is an array of isbns
            if (is_array($q['subfield'])) {
                $isbn = $q['subfield'][0];
            } else {
                $isbn = $q['subfield'];
            }
        }
        if ($q['@attributes']['tag'] == '245') {
            $title = trim($q['subfield'][0] . ' ' . $q['subfield'][1]);
        }
        if ($q['@attributes']['tag'] == '260' && empty($date) && empty($issn)) {
            foreach ($q['subfield'] as $k => $v) {
                if (preg_match('/.*?[0-9]{3,4}.*?/', $v)) {
                    $date = $v;
                }
                unset($k, $v);
            }
            //clean the sloppy datefield up
            $date = str_replace('&#xA9;', '', $date);
            $date = str_replace('©', '', $date);
            $date = str_replace('.', '', $date);
        }
        if ($q['@attributes']['tag'] == '264' && empty($date) && empty($issn)) {
            $date = $q['subfield'][2];
            //clean the sloppy datefield up
            $date = trim(rtrim($date, '.'));
            $date = str_replace('&#xA9;', '', $date);
            $date = str_replace('©', '', $date);
        }
    }
}
?>
