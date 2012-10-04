<?php

/**
 * I should have made this with node.js, but in a pinch gotta fall back to what I know best, PHP
 */

class StackExchange
{
	public static $site = 'askubuntu';
	public static $api_ver = '2.1';
	public static $api_key = '';

	public static function reputation($users, $opts = array())
	{
		//$default_opts = array('filter' => '!T6PWqr371XrnzOwG2A');
		//$opts = array_merge($default_opts, $opts);
		$users = (is_array($users)) ? implode(';', $users) : $users;

		return static::fetch('users/' . $users . '/reputation', $opts);
	}

	public static function users($users, $opts = array())
	{
		$default_opts = array('filter' => '!T6PWqr371XrnzOwG2A');
		$opts = array_merge($default_opts, $opts);
		$users = (is_array($users)) ? implode(';', $users) : $users;

		return static::fetch('users/' . $users, $opts);
	}

	protected static function fetch($url, $options = array())
	{
		$default_opts = array('pagesize' => 100, 'site' => static::$site, 'page' => 1);
		$opts = array_merge($default_opts, $options);
		$r = array();
		$data = array('has_more' => true);

		while( $data['has_more'] )
		{
			$query_url = http_build_query(array_merge($default_opts, $opts));
			$data_raw = file_get_contents('http://api.stackexchange.com/' . static::$api_ver . '/' . $url . '?' . $query_url);
			$data = json_decode(gzinflate(substr($data_raw, 10, -8)), true);
			$r = array_merge($r, $data['items']);

			if( array_key_exists('backoff', $data) )
			{
				sleep($data['backoff'] + 2);
			}

			$opts['page']++;
		}

		return $r;
	}
}

