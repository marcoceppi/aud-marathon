<?php

/**
 * I should have made this with node.js, but in a pinch gotta fall back to what I know best, PHP
 */

class StackExchange
{
	public static $site = 'askubuntu';
	public static $api_ver = '2.1';

	public static function reputation($users, $opts)
	{
		$users = (is_array($users)) ? implode(';', $users) : $users;

		return static::fetch('users/' . $users . '/reputation', $opts);
	}


	protected static function fetch($url, $options)
	{
		$default_opts = array('pagesize' => 100, 'site' => static::$site);
	}
}

