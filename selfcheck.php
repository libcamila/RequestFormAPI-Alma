<?php
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
//the initial authentication and pull of data
if (empty(session_id()) && !isset($_SESSION)  && session_status() == PHP_SESSION_NONE) {
    session_start();
}
//include($_SERVER['DOCUMENT_ROOT'].'/webFiles/login/top.php');
$SETTINGS['header']   = $_SERVER['DOCUMENT_ROOT'] . '/webFiles/fs_header.php';
$SETTINGS['footer']   = $_SERVER['DOCUMENT_ROOT'] . '/webFiles/fs_footer.php';
include_once('privateFunctions.php');
include_once('functions.php');
//unset($_SESSION['libSession']['selfCheck']);
$top_html             = '<style>input[type=text] {width:auto;}</style><div style="padding:1em;"> 
<div style="width:100%;min-height:80px;text-align:left;margin-bottom:2em;" id="toplogo"><a href="https://library.wou.edu/" target="_blank"><img src="/webFiles/images/logos/woulib_logos/HL_logo_2Color_on_transparent.png" alt="WOU Library logo space" style="max-height:130px;"></a></div>';
//get expiration from session variable
$now             = new DateTime();
include $SETTINGS['header'];
print $top_html; 
$selfCheck = 'yes';
if (isset($_SESSION['libSession']['selfCheck'])){
 showForms();
} 
else {
	if(empty($_POST['login'])){
		loginForm(); 
		}
	else{
		if (isset($_POST['login']) && isset($_POST['password']) && strtolower($_POST['login']) == $secretName && $_POST['password'] == $secretCode){
		$_SESSION['libSession']['selfCheck'] = 'yes';
		showForms();
		}	
		else
		{
			print '<h3>Incorrect username/password</h3>';
			loginForm();
		}
	}
}
?>
  </body>
</html>
<?php
function loginForm(){
	print '<h3>This system is logged out. Please contact the Checkout Desk for assistance.</h3>';
	print '<form action="" method="post" id="loginForm">';
	print '<div style="width:5em;">Staff Username:</div><input name="login"></br>';
	print '<div style="width:5em;">Staff Password:</div><input type="password" name="password">';
	print '</br><div style="width:5em;"></div><input type="submit">';
	print '</form>';
}
function showForms(){
	global $apikey;
	?>
	<h2>Self Checkout</h2>
<script>
// Set the date we're counting down to
var countDownDate = new Date();
countDownDate.setMinutes( countDownDate.getMinutes() + 1 );
function focusAndOpenKeyboard(el, timeout) {
  if(!timeout) {
    timeout = 100;
  }
  if(el) {
    // Align temp input element approximately where the input element is
    // so the cursor doesn't jump around
    var __tempEl__ = document.createElement('input');
    __tempEl__.style.position = 'absolute';
    __tempEl__.style.top = (el.offsetTop + 7) + 'px';
    __tempEl__.style.left = el.offsetLeft + 'px';
    __tempEl__.style.height = 0;
    __tempEl__.style.opacity = 0;
    // Put this temp element as a child of the page <body> and focus on it
    document.body.appendChild(__tempEl__);
    __tempEl__.focus();

    // The keyboard is open. Now do a delayed focus on the target element
    setTimeout(function() {
      el.focus();
      el.click();
      // Remove the temp element
      document.body.removeChild(__tempEl__);
    }, timeout);
  }
}
</script>
    <noscript>You need to enable JavaScript to run this app.</noscript>
    <?php
if(!empty($_REQUEST['IDnumber']) && preg_match('/v0/i',$_REQUEST['IDnumber'])){
	$IDnumber = preg_replace('/2v/i', 'V', $_REQUEST['IDnumber']);
	//set a session variable to unset at the end of cko
	
	    $queryParams   = '?' . urlencode('apikey') . '=' . urlencode($apikey);
        $service_url   = 'https://api-na.hosted.exlibrisgroup.com/almaws/v1/users/' . $IDnumber;
        //user CURL to get record
        $curl_response = getAlmaRecord($IDnumber, $service_url, $queryParams);
        $patronRecord  = json_decode($curl_response);
        $ptype   = !empty($patronRecord->user_group->value) ? urlencode(trim(stripslashes($patronRecord->user_group->value))) : '';
        //I don't think this is working
        $expires = !empty($patronRecord->expiry_date) ? urlencode(trim(stripslashes($patronRecord->expiry_date))) : '';
        $expires = str_replace('Z', '', $expires);
        if (empty($expires) && !empty($ptype)) {
            $expires = date('m-d-Y', strtotime('3 years'));
        } elseif (empty($expires) && empty($ptype)) {
            $expires = date('m-d-Y', strtotime('6 months ago'));
        } elseif ($expires == strtotime('December 31, 1969')) {
            $expires = date('m-d-Y', strtotime('3 years'));
        } else {
            $expires = date('m-d-Y', strtotime($expires . ' +2 weeks'));
        }
        print !empty($patronRecord->pref_first_name) ? $patronRecord->pref_first_name : $patronRecord->first_name;
        print ' ';
        print !empty($patronRecord->pref_middle_name) ? $patronRecord->pref_middle_name : $patronRecord->middle_name;
        print ' ';
        print !empty($patronRecord->pref_last_name) ? $patronRecord->pref_last_name : $patronRecord->last_name;
        //pref_middle_name//pref_last_name
        if ($expires > date('m-d-Y', strtotime('now'))){
        ?>
        <p>Scan the barcode of the items you want to check out</p>
     <form action="" method="post" id="checkoutForm">
		<input name="barcode" id="barcode">
		<!--<input type="submit">-->
		<div id="loading"></div>
			</form>
			
     <div id="ItemsOut"></div>
     <div id="FailedCKO"></div> <!-- Display the countdown timer in an element -->
     <?php 
     
		}
		else
		{
			print '<p>Your account is expired. Please see the Checkout Desk.</p>';
		}
		?>
     <hr>
     <p>Session will automatically time out if there is no activity for 1 minute. Remaining time: <span id="count"></span></p>
	<input name="logout" id="logout" type="button" value="End Session">

<script>
	var x  = 0;
	$(document).ready(function() {
countDown(countDownDate);
		$('#barcode').trigger('click');
        $("#barcode").select();    
        $('#barcode').focus();
        $('#barcode').get(0).focus();
		$('#barcode').trigger('touchstart');
		var myElement = $('#barcode');
		var modalFadeInDuration = 300;
		focusAndOpenKeyboard(myElement, modalFadeInDuration); // or without the second argument
	});
	
function countDown(countDownDate){
// Update the count down every 1 second
	x = setInterval(function() {

  // Get today's date and time
  var now = new Date().getTime();

  // Find the distance between now and the count down date
  var distance = countDownDate - now;

  // Time calculations for days, hours, minutes and seconds
  //var days = Math.floor(distance / (1000 * 60 * 60 * 24));
  //var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
  var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
  var seconds = Math.floor((distance % (1000 * 60)) / 1000);

  // Display the result in the element with id="demo"
  $("#count").html( minutes + "m " + seconds + "s ");
	  // If the count down is finished, write some text
	  if (distance < 0) {
		clearInterval(x);
		$("#count").html('Expired');
		window.location.href = location.href;
	  }
	}, 1000);
}
</script>
			<?php
}
else
{
	 ?>
	 <p>Scan your WOU ID/barcode</p>
	<form action="" method="post" id="patronForm">
		<input name="IDnumber" id="IDnumber">
		<!--<input type="submit">-->
		<div id="loading"></div>
	</form>
		<script>
			$(document).ready(function() {
        $("#IDnumber").select();    
        $('#IDnumber').focus();
        $('#IDnumber').get(0).focus();
		$('#IDnumber').trigger('click');
		$('#IDnumber').trigger('touchstart');
		// Usage example
		var myElement = $('#IDnumber');
		var modalFadeInDuration = 300;
		focusAndOpenKeyboard(myElement, modalFadeInDuration); // or without the second argument
	});
        </script>
  
	<?php
	
}
    ?>
    <script>
		$('.input').keypress(function (e) {
		if (e.which == 13) {
			$('form').submit();
		return false;    //<---- Add this line
		}
	});
				$('#checkoutForm').submit(function (evt) {
				evt.preventDefault();
				var barcode = $('#barcode').val();
				createLoan(barcode);
			});
			$('#logout').click(function(){ window.location.href = location.href;});
		function createLoan(barcode) {
        event.preventDefault();
        //reset the timer
		countDownDate = new Date();
		countDownDate.setMinutes( countDownDate.getMinutes() + 1 );
        clearInterval(x);
        countDown(countDownDate);
		 $('#loading').html('<img src="/webFiles/resolver/ajax-loader.gif" border="0">');
		 $.getJSON("/webFiles/login/alma/alma_processor.php?mylib=yes&type=checkout&pid= <?php print $IDnumber; ?>&barcode=" + barcode).done(function(result) {
		countDownDate = new Date();
		countDownDate.setMinutes( countDownDate.getMinutes() + 1 );
		//console.log(result);
		if ((result['web_service_result'] && result['web_service_result']['errorsExist'] && result['web_service_result']['errorsExist'] === true) ||(result['errorsExist'] && result['errorsExist'] === true))
			 { 
            alert ('This item was NOT checked out. Please contact the Checkout Desk for assistance.');
            var errorText = '<p><strong>';
            $.each(result['errorList']['error'], function (key, val){
				errorText += val['errorMessage']+'. ';
			});
			$('#FailedCKO').append(errorText+barcode+' NOT checked out. Please contact the Checkout Desk for assistance.</strong></p>'); 
			
			 $('#barcode').val('');
			 }
			 else
			 {
			var due_date = result['due_date'];
			var d = new Date(due_date);
    		due_date = d.toLocaleString(); 
    		// alert ('result['title'] Due Date: ' + due_date);
    		if ($('#ItemsOut').is(':empty')){
				$('#ItemsOut').append('<p><strong>The following have been checked out to you:</strong></p>');
			}
			$('#ItemsOut').append('<p>'+result['title']+' Due Date: ' + due_date+'</p>');
			 }
         $('#loading').html('');
         $('#barcode').val('');
	}).fail(function( jqxhr, textStatus, error ) {
    var err = textStatus + ", " + error;
    alert( "Request Failed: " + err );
         $('#barcode').val('');
         $('#FailedCKO').append('<p>'+barcode+' NOT checked out. Please contact the Checkout Desk for assistance.</p>'); 
});
	}
	function focusAndOpenKeyboard(el, timeout) {
  if(!timeout) {
    timeout = 100;
  }
  if(el) {
    // Align temp input element approximately where the input element is
    // so the cursor doesn't jump around
    var __tempEl__ = document.createElement('input');
    __tempEl__.style.position = 'absolute';
    __tempEl__.style.top = (el.offsetTop + 7) + 'px';
    __tempEl__.style.left = el.offsetLeft + 'px';
    __tempEl__.style.height = 0;
    __tempEl__.style.opacity = 0;
    // Put this temp element as a child of the page <body> and focus on it
    document.body.appendChild(__tempEl__);
    __tempEl__.focus();

    // The keyboard is open. Now do a delayed focus on the target element
    setTimeout(function() {
      el.focus();
      el.click();
      // Remove the temp element
      document.body.removeChild(__tempEl__);
    }, timeout);
  }
}
</script>
<?php
}
?>
