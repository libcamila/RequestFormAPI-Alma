# almaAPIRequestForm
PHP code for using the Alma API to create a request form that allows for multiple delivery options and updating of patron information, including SMS number.

File flow works like this:
1. top.php & authenticate.php
	- top.php includes authenticate.php
	- top.php and authenticate.php are how we authenticate and get our patron variables. You may use a different method. Includes an initial call to Alma to get the patron information.
2. oclc.php
	- oclc.php allows us to flesh out item details that may not have come from our general electronic service.
3. functions.php
	- this file is included in all of the files and is has the functions to make most of our Alma calls. I also store a list of states and abbreviations, which is not actually a function.
4. privateFunctions.php
	- an example file that should be renamed is in this repository. It stores our private variables and functions.
5. requestFormAPI.php 
	- the rest of this page is about determining which form and/or messages the patron should receive, then redirecting them appropriately.
6. update_address.php
	- if a patron has indicated they want to update their address or receive SMS messages about thier requests, this file is called and then includes the place_hold.php file
7. place_hold.php
	- This is where we do the actual placing of the request for Summit or Hold requests. The patron is notified of the success and where the item will be available or delivered to them.
