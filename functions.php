<?php
//functions called by the pages/scripts
$queryParams = '?' . urlencode('apikey') . '=' . urlencode($apikey);
function getAlmaRecord($pid, $service_url, $queryParams)
{
    $curl        = curl_init();
    curl_setopt($curl, CURLOPT_URL, $service_url . $queryParams);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'Content-Type: application/json'
    ));
    $curl_response = curl_exec($curl);
    curl_close($curl);
    return $curl_response;
}
function putPatronRecord($patronRecord, $service_url, $queryParams)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $service_url . $queryParams);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'Content-Type: application/json'
    ));
    curl_setopt($curl, CURLOPT_POSTFIELDS, $patronRecord);
    $curl_response = curl_exec($curl);
    curl_close($curl);
    return ($curl_response);
}
function postAlmaHold($makeRequest, $service_url, $queryParams)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $service_url . $queryParams);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($curl, CURLOPT_POSTFIELDS, $makeRequest);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($curl, CURLOPT_HEADER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'Content-Type: application/json'
    ));
    //curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/xml'));
    $curl_response = curl_exec($curl);
    curl_close($curl);
    return ($curl_response);
}
function bibLevelHold($pid, $mmsid, $makeRequest, $queryParams)
{
    //we know who the patron is and are placing a bib-level request, because item level requests on things like tablets and hotspots are ridiculous
    $service_url = 'https://api-na.hosted.exlibrisgroup.com/almaws/v1/users/' . $pid;
    $queryParams .= '&user_id_type=all_unique&mms_id=' . $mmsid;
    $service_url .= '/requests';
    $makeRequest = json_encode($makeRequest);
    //user CURL
    $curl        = curl_init();
    curl_setopt($curl, CURLOPT_URL, $service_url . $queryParams);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($curl, CURLOPT_POSTFIELDS, $makeRequest);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'Content-Type: application/json'
    ));
    $curl_response = curl_exec($curl);
    curl_close($curl);
    return $curl_response;
}
//We have a call to housing's system to verify whether someone is a resident or not (residents are not eligible for hotspots)
function getHotSpotList($vnumber, $eligibilityURL)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $eligibilityURL . $vnumber);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    $eligibility = curl_exec($ch);
    curl_close($ch);
    return $eligibility;
}
//function to either display a wah-wah message or send patron to correct form
function redirectToForm($message,$url){
	if(!empty($message)){
		include($GLOBALS['header']);
          print $message; 
          if(!empty($url)){
			  print '<p><a href="'.$url.'">Continue to request form</a></p>';
		  }
		include($GLOBALS['footer']); 
	}
	else {
		 header('location:'.$url);   
	}	
}
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
?>
