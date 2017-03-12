<?php
if($_SERVER['SERVER_NAME'] == '108.174.147.46') die();
include "helper.inc.php";
?>
<!DOCTYPE html>
<html lang="en">
    <head>
		<meta charset="UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"> 
		<meta name="viewport" content="width=device-width, initial-scale=1.0"> 
        <title>Demo SaaS</title>
        <meta name="description" content="Custom Login Form Styling with CSS3" />
        <meta name="keywords" content="css3, login, form, custom, input, submit, button, html5, placeholder" />
        <meta name="author" content="Codrops" />
        <link rel="stylesheet" type="text/css" href="css/style.css" />
	    <script src="http://code.jquery.com/jquery-1.10.1.min.js"></script>
	    <script src="http://code.jquery.com/jquery-migrate-1.2.1.min.js"></script>
		<script src="js/modernizr.custom.63321.js"></script>
		<script src="js/jquery.blockUI.js"></script>
	    <script type="text/javascript">

		    // unblock when ajax activity stops
		    $(document).ajaxStop($.unblockUI);

		    function test() {
			    $.ajax({ url: 'wait.php', cache: false });
		    }

		    $(document).ready(function() {
			    $('#submit').click(function() {
				    $.blockUI({ message: '<strong>Just a moment...</strong>' });
			    });
		    });

	    </script>
	    <!--[if lte IE 7]><style>.main{display:none;} .support-note .note-ie{display:block;}</style><![endif]-->
		<style>
			@import url(http://fonts.googleapis.com/css?family=Ubuntu:400,700);
			body {
				background: #563c55 url(images/blurred.jpg) no-repeat center top;
				-webkit-background-size: cover;
				-moz-background-size: cover;
				background-size: cover;
			}
			.container > header h1,
			.container > header h2 {
				color: #fff;
				text-shadow: 0 1px 1px rgba(0,0,0,0.7);
			}
		</style>
    </head>
    <body>
        <div class="container">
			<header>
			
				<h1>SaaS Store Application <strong>Register Form</strong></h1>
				<h2>
					This is sample register form<br />
					Please do not input special character (only use : a-z)
				</h2>
				<div class="support-note">
					<span class="note-ie">Sorry, only modern browsers.</span>
					<span><?php echo $data;?></span>
				</div>

				<div class="support-nofity">
					<?php echo $data;?>
				</div>

			</header>
			
			<section class="main">
				<?php if($form) { ?>
				<form class="form-3" action="index.php" method="post">
				    <p class="clearfix">
				        <label for="domain">Store Name</label>
				        <input type="text" name="domain" id="domain" placeholder="mystore">
				    </p>
					<p class="clearfix">
						<label for="title">Web Title</label>
						<input type="text" name="title" id="title" placeholder="Web Title">
					</p>
					<p class="clearfix">
						<label for="username">Username</label>
						<input type="text" name="username" id="username" placeholder="Username">
					</p>
					<p class="clearfix">
						<label for="password">Password</label>
						<input type="password" name="password" id="password" placeholder="Password">
					</p>
					<p class="clearfix">
						<label for="email">Email</label>
						<input type="text" name="email" id="email" placeholder="Email">
					</p>
				    <p class="clearfix">
				        <input id="submit" type="submit" name="submit" value="Sign up">
				    </p>
				</form>​
				<?php } ?>
			</section>
        </div>
    </body>
</html>