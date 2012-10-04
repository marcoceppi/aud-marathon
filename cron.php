<?php

define('REDIS_HOST', '127.0.0.1');
define('REDIS_PORT', 6379);

require_once('Storage.php');
require_once('StackExchange.php');

Storage::init('aud:marathon');

StackExchange::$api_key = "rFNXbGBukKGSZMzFr3DovQ((";

$last_run = (Storage::get('last_run')) ? Storage::get('last_run') : 1349344800;
$participating_users = Storage::get('users');
$user = array();

// GET THE LATEST DETAILS!
$userstats = StackExchange::users($participating_users);

foreach( $userstats as $user_details )
{
	Storage::set('users:' . $user_details['user_id'] . ':info', json_encode($user_details));
	$deets = Storage::get('users:' . $user_details['user_id']);
	$original = json_decode(Storage::get('users:' . $user_details['user_id'] . ':start'), true);

	//$deets = array('reputation' => 0, 'question_count' => 0, 'answer_count' => 0, 'gold' => 0, 'silver' => 0, 'bronze' => 0);

	foreach($deets as $item => $val)
	{
		if( in_array($item, array('gold', 'silver', 'bronze')) )
		{
			$deets[$item] = ($user_details['badge_counts'][$item] - $original['badge_counts'][$item]);
		}
		else if(!in_array($item, array('up_votes', 'down_votes')))
		{
			$deets[$item] = ($user_details[$item] - $original[$item]);
		}
	}

	$user[$user_details['user_id']] = $deets;
}

// NOW WE UPDATE THE VOTE STATS

$userrep = StackExchange::reputation($participating_users, array('fromdate' => $last_run));
Storage::set('last_run', time());

foreach( $userrep as $reputation )
{
	if( $reputation['vote_type'] == 'up_votes' )
	{
		$user[$reputation['user_id']]['up_votes']++;
	}
	else if( $reputation['vote_type'] == 'down_votes' )
	{
		$user[$reputation['user_id']]['down_votes']++;
	}
}

foreach( $user as $id => $score_card )
{
	Storage::hash('users:' . $id, $score_card);
}
