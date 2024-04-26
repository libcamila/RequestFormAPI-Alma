<?php
ini_set('display_errors', 1);
error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set('display_errors', '1');
include_once('vars/privateVar.php'); //includes variables for our APIkey, wskey, and URL for verification of patron hotspot eligibility
include_once('functions/functions.php');
include_once('vars/parameters.php');
header("Content-Type:text/json");
$service_url   = 'https://api-na.hosted.exlibrisgroup.com/almaws/v1/items';
$result = json_decode(getAlmaRecord($pid, $service_url, $queryParams));
$url = $result->holding_data->link . '/items';
$available = getAlmaAvailability($url, $queryParams);
print_r($available);
