<?php
if ($_SESSION['libSession']['email'] == 'gabaldoc@wou.edu' || $savelogin == 'gabaldoc' || $_SESSION['libSession']['savelogin'] == 'gabaldoc' || (($savelogin == 'bakersc' || $_SESSION['libSession']['savelogin'] == 'bakersc' || $_SESSION['libSession']['id'] == 'bakersc')) || (preg_match('/placeHold/', $_SERVER['REQUEST_URI']) && !empty($_REQUEST['req_type']) && $_REQUEST['req_type'] == 'hotspot')){
	if(empty($forJSON)){
?>
<script>
    function showButton(){$('.openbtn').show();}
    console.log('in viewas file');
    $('body').prepend('<button class="openbtn" id="adminButton">Toggle Admin Options</button>');
    $('.openbtn').click(function(){
    $('.openbtn').hide();
    console.log('in da script');
       $.when($('body').prepend("<div id=\"adminBanner\" class=\"alert alert-dismissible alert-info\" style=\"margin-top:-2em;margin-left:-2em;margin-right:-2em;\"><button onclick=\"showButton()\" type=\"button\" class=\"close\" data-dismiss=\"alert\">&times;</button><strong>Welcome to Admin Version 1.0!</strong><div id=\"adminContent\"><h3>Permissions</h3><div><form method=\"post\" action=\"<?php print $_SERVER['PHP_SELF']; ?>\">View as: &nbsp;<input type=\"text\" name=\"viewas\" size=\"30\" style=\"max-width:10em;\" value=\"<?php if (!empty($_REQUEST['viewas'])) {trim($_REQUEST['viewas']);} ?>\"><?php if (!empty($_REQUEST)) {foreach ($_REQUEST as $k => $v) {if ($k != 'viewas' && $k != 'assoc_prog_name'){print '<input type=\"hidden\" name=\"'.$k.'\" value=\"'.$v.'\">';} unset($k,$v);}} if (preg_match('/view[a-zA-Z]*.php/i', $_SERVER['PHP_SELF'])){?>&nbsp;Edit request? &nbsp;Yes&nbsp;<input type=\"radio\" name=\"edit\" value=\"yes\">&nbsp;&nbsp;No&nbsp;<input type=\"radio\" name=\"edit\" value=\"no\" checked><?php } ?>&nbsp; &nbsp;<input type=\"submit\" name=\"Submit\"></form></div></div></div>")).done(function(){
           $( "#adminContent" ).accordion({
               heightStyle: "content"
           });
       });
    });

</script>

<?php
	}
    if (!empty($_POST['viewas']) ) {$_POST['viewas'] = trim($_POST['viewas']);
                                    $_REQUEST['viewas'] = trim($_REQUEST['viewas']);}
if (!empty($_REQUEST['viewas']) && $_SESSION['libSession']['id'] != $_REQUEST['viewas']) {
// but are we testing the system as someone else? If so we need to set permissions as them
if(!empty($login)){
		$savelogin                       =  $login;
}
elseif(!empty($_SESSION['libSession']['id'])){

		$savelogin                       =  $_SESSION['libSession']['id'];
}
		$viewas = !empty($_REQUEST['viewas']) ? $_REQUEST['viewas'] : '';
		// || (!empty($_REQUEST['userid']) && $_SESSION['libSession']['id'] != $_REQUEST['userid'])
			if ((!empty($_REQUEST['viewas']) && $_SESSION['libSession']['id'] != $_REQUEST['viewas'])) {
                unset($_SESSION['libSession']);
				//if (!empty($_REQUEST['viewas'])) {
				$login                      = $_REQUEST['viewas'];
				$view_as                         = $_REQUEST['viewas'];
				/*}
				elseif (!empty($_REQUEST['userid']))
				{$login                      = $_REQUEST['userid'];
				$view_as                         = $_REQUEST['userid'];}*/
            $staff_request                   = 'yes';
            $id                              = $login;
            //run the authenticate script. This also happened as part of the login, but if we're testing, we want to authenticate against the test credentials, not neccessarily the loggedin user
            authenticate($login, $from_proxy, $token);
            $_SESSION['libSession']['savelogin'] = $savelogin;
            if(empty($forJSON)){
                ?>
<script>
    $(document).ready(function(){
        $('body').prepend('<p><strong>Admin viewing with rights of:</strong>&nbsp;<span style="background:yellow;" class="header"><?php print $_SESSION['libSession']['name']; ?></span></p>');
    });
</script>
<?php
	}
        } // end of stuff for testing as another user
        else {
            $view_as = '';
        }
		}
}
		?>
