# almaAPIRequestForm

PHP code for using the Alma API to create a request form that allows for multiple delivery options and updating of patron information, including SMS number.

Files included (except form example):

1. functions/authenticateFunctions.php
   - includes functions that are specific to the authenticate file
2. functions/functions.php
   - includes functions for getting item/patron information and placing requests. It is included in all of the files and is has the functions to make most of our Alma calls.
3. vars/privateVar_example.php
   - an example file that stores our private variables.
4. vars/parameters.php
   - contains classes for holds and resource sharing requests
5. vars/resourceSharingClass.php
   - parameters that are stored and used throughout the code.1.
6. authenticate.php
   - authenticate.php is how we authenticate and get our patron variables. You may use a different method. Includes an initial call to Alma to get the patron information.
7. oclc.php
   - oclc.php allows us to flesh out item details that may not have come from our general electronic service.
8. place_hold.php
   - This is where we do the actual placing of the request for Summit or Hold requests. The patron is notified of the success and where the item will be available or delivered to them.
9. requestFormAPI.php
   - the rest of this page is about determining which form and/or messages the patron should receive, then redirecting them appropriately.
10. update_address.php

- if a patron has indicated they want to update their address or receive SMS messages about thier requests, this file is called and then includes the place_hold.php file

/** MISSING: viewas.php -- this file can be commented out until released **/

General Electronic Service in Alma (item level) uses this string:
callNumber={call_number}&description={description}&barcode={barcode}&mms_id={rft.mms_id}&location={location}&isbn={rft.isbn}&oclcnum={rft.oclcnum}&title={rft.title}&atitle={rft.atitle}
&issn={rft.issn}&mmsid={rfr_dat}&authors={rft.au}&pages={rft.pages}&issue={rft.issue}&volume={rft.volume}&date={rft.date}&mailform=yes

Form flow is:
General Electronic Service from Alma -> requestFormAPI (determine which form to redirect to)
-> External form (we use Gravity Forms in Wordpress, with a redirect confirmation to either update address or place hold)
-> update address (if applicable) -> place hold
