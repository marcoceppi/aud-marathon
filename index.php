<?php

/**
 * I should have made this with node.js, but in a pinch gotta fall back to what I know best, PHP
 */

define('REDIS_HOST', '127.0.0.1');
define('REDIS_PORT', 6379);

require_once('Storage.php');

Storage::init('aud:marathon');

$participating_users = Storage::get('users');
$user = array();

foreach( $participating_users as $user_id )
{
	$user[$user_id] = array();
	$user[$user_id]['data'] = Storage::get('users:' . $user_id);
	$user[$user_id]['info'] = json_decode(Storage::get('users:' . $user_id . ':info'), true);
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>
    </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">
    <!-- Le styles -->
    <link href="assets/css/bootstrap.ubuntu.css" rel="stylesheet">
    <style>
      body { padding-top: 60px; /* 60px to make the container go all the way
      to the bottom of the topbar */ }
      hr { margin: 5px 0; }
    </style>
    <link href="assets/css/bootstrap-responsive.css" rel="stylesheet">
    <!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js">
      </script>
    <![endif]-->
    <!-- Le fav and touch icons -->
    <link rel="shortcut icon" href="assets/ico/favicon.ico">
    <link rel="apple-touch-icon-precomposed" sizes="144x144" href="assets/ico/apple-touch-icon-144-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="114x114" href="assets/ico/apple-touch-icon-114-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="72x72" href="assets/ico/apple-touch-icon-72-precomposed.png">
    <link rel="apple-touch-icon-precomposed" href="assets/ico/apple-touch-icon-57-precomposed.png">
    <style>
      .pull-left { margin-top:10px; } .names { margin-left:30px }
    </style>
  </head>
  <body>
    <div class="navbar navbar-fixed-top">
      <div class="navbar-inner">
        <div class="container">
          <a class="brand" href="#">
            Ask Ubuntu Data Portal
          </a>
        </div>
      </div>
    </div>
    <div class="container">
      <div class="hero-unit">
        <div>
          <h1>
            <img src="http://i.imgur.com/Asf2J.jpg" style="height: 100px;"> Ubuntu Community Marathon
          </h1>
        </div>
      </div>
      <div class="row">
<!-- GENERATE ME! -->
<?php
	$count = 1;
	foreach( $user as $id => $data )
	{
		if($count % 3 == 0)
		{
?>
      </div>
      <div class="row">
<?php } ?>
		<div class="span4">
          <div class="well">
            <img class="pull-left" src="<?php echo $data['info']['profile_image']; ?>" style=" width: 30px; height: 30px;">
            <div class="names">
              <h3>
                <?php echo $data['info']['display_name']; ?>
              </h3>
            </div>
			<hr>
			<ul class="pull-left" style="padding-right: 45px">
				<li>Gold: <?php echo $data['data']['gold']; ?></li>
				<li>Silver: <?php echo $data['data']['silver']; ?></li>
				<li>Bronze: <?php echo $data['data']['bronze']; ?></li>
			</ul>
			<ul class="pull-left">
				<li>Reputation: <?php echo $data['data']['reputation']; ?></li>
				<li>Up-votes: <?php echo $data['data']['up_votes']; ?></li>
				<li>Down-votes: <?php echo $data['data']['down_votes']; ?></li>
				<li>Questions: <?php echo $data['data']['question_count']; ?></li>
				<li>Answers: <?php echo $data['data']['answer_count']; ?></li>
			</ul>
			<br style="clear:both;">
          </div>
        </div>
<?php
} ?>
      </div>
      <hr>
      <div>
        Marco Ceppi made this while fully rested in a few hours. <a href=http://github.com/marcoceppi/aud-marathon>Github</a>
      </div>
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js">
    </script>
    <script src="assets/js/bootstrap.js">
    </script>
  </body>

</html>
