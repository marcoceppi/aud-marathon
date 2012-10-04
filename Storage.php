<?php
/**
 * Storage Engine Components
 *
 * @author Marco Ceppi <marco@ceppi.net>
 * @package Amulet
 * @subpackage Model
 */

/**
 * Storage Engine Exception
 *
 * To be used when the Storage Engine can not recover from an error.
 * 
 * @package Amulet
 * @subpackage Model
 */
class StorageException extends Exception {}

/**
 * Storage Engine
 *
 * Currently uses Redis, but will eventually be configurable to Memcache, etc
 * 
 * @package Amulet
 * @subpackage Model
 */
class Storage
{
	/**
	 * Redis Object
	 */
	protected static $Redis = NULL;

	/**
	 * Init
	 * 
	 * Instaniate the storage engine
	 *
	 * @param string $prefix Storage prefix for Redis
	 *
	 * @return null
	 * @throws StorageException
	 */
	public static function init($prefix = '')
	{
		if( is_null(static::$Redis) )
		{
			if( !static::connect(TRUE) )
			{
				// @codeCoverageIgnoreStart
				throw new StorageException('Can not connect to Redis');
				// @codeCoverageIgnoreEnd
			}
		}
		else
		{
			try
			{
				static::$Redis->ping();
			}
			catch (RedisException $e)
			{
				// @codeCoverageIgnoreStart
				if( !static::connect() )
				{
					throw new StorageException('Could not re-connect to Redis: ' . $e);
				}
				// @codeCoverageIgnoreEnd
			}
		}

		static::prefix($prefix);
	}

	/**
	 * Connect
	 * 
	 * Connect to the storage engine
	 *
	 * @param bool $create the Redis variable
	 *
	 * @return null
	 * @throws StorageException
	 */
	protected static function connect($create = FALSE)
	{
		if( $create )
		{
			static::$Redis = new Redis();
		}

		return static::$Redis->connect(REDIS_HOST, REDIS_PORT);
	}

	/**
	 * Storage Prefix
	 *
	 * What each record should be ammended with. This is a hard requirement
	 * for Storage until we move the db switching. Each set of related
	 * records should be denoted with a prefix.
	 *
	 * @param string|false $prefix
	 *
	 * @return bool|string It's a double edged method. if $prefix is FALSE you'll get the current prefix
	 */
	public static function prefix($prefix = FALSE)
	{
		if( $prefix === FALSE )
		{
			return static::$Redis->getOption(Redis::OPT_PREFIX);
		}
		else
		{
			return static::$Redis->setOption(Redis::OPT_PREFIX, $prefix . ':');
		}
	}

	/**
	 * Set
	 *
	 * Update or create a new record
	 *
	 * @param string $key
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public static function set($key, $value)
	{
		// This is a pretty heavy handed command. It'll over-write whatever
		// is there, even it it's not a Redis::REDIS_STRING. This will likely
		// need some form of validation.

		return static::$Redis->set($key, $value);
	}
	
	/**
	 * Hash
	 * 
	 * Either create or ammend a member to a hash
	 * 
	 * @param string $key
	 * @param string|array $hash Either the member to create or an array of member => value pairs
	 * @param string $value optional If $hash is a string, then the value of that member
	 * 
	 * @return bool
	 */
	public static function hash($key, $hash, $value = NULL)
	{		
		if( is_null($value) )
		{
			if( !is_array($hash) )
			{
				throw new StorageException('$hash must be an array');
			}

			// $key, (array) $hash->members
			return static::$Redis->hMset($key, $hash);
		}
		else
		{
			if( is_array($hash) || is_array($value) )
			{
				throw new StorageException('$hash and $value must be strings');
			}

			// $key, $hash_key, $value
			return static::$Redis->hSet($key, $hash, $value);
		}
	}

	/**
	 * Add
	 *
	 * Add or create a new set item
	 *
	 * @param string $key
	 * @param string $value
	 *
	 * @return bool
	 * @throws StorageException
	 */
	public static function add($set, $item)
	{
		return static::$Redis->sAdd($set, $item);
	}

	/**
	 * Push
	 * 
	 * Perform a RPUSH on a list
	 * 
	 * @param string $list
	 * @param mixed $data
	 * 
	 * @return bool
	 */
	public static function push($list, $data)
	{
		if( static::$Redis->type($list) !== Redis::REDIS_LIST
		 || static::$Redis->type($list) !== Redis::REDIS_NOT_FOUND )
		{
			throw new StorageException('Not a list');
		}

		if( is_array($data) )
		{
			$data = json_encode($data);
		}
		else if( is_object($data) )
		{
			$data = serialize($data);
		}

		return static::$Redis->rPush($list, $data);
	}

	/**
	 * Pop
	 * 
	 * Perform an LPOP on $list
	 * 
	 * @param string $list
	 * 
	 * @return mixed 
	 */
	public static function pop($list)
	{
		if( static::$Redis->type($list) !== Redis::REDIS_LIST )
		{
			throw new StorageException('Not a list');
		}

		
	}

	/**
	 * Remove
	 *
	 * Delete the $item from the $set
	 *
	 * @param string $set
	 * @param string $item
	 *
	 * @return bool
	 * @throws StorageException
	 */
	public static function remove($set, $item)
	{
		if( static::$Redis->type($set) === Redis::REDIS_SET )
		{
			// sRem returns int(1) or bool(FALSE)
			return (static::$Redis->sRem($set, $item)) ? TRUE : FALSE;
		}
		else if( static::$Redis->type($set) === Redis::REDIS_HASH )
		{
			return (static::$Redis->hDel($set, $item)) ? TRUE : FALSE;
		}
		else
		{
			throw new StorageException('Not a valid set');
		}
	}

	/**
	 * Get
	 *
	 * Retrieve the set or value of the key
	 *
	 * @param string $key
	 * @param array $member_keys Option, only used for getting specific keys from Hashes
	 *
	 * @return mixed
	 *
	 * @todo Maybe throw an exception if indeed the key does not exist?
	 */
	public static function get($key, $members = array())
	{
		if( static::$Redis->exists($key) )
		{
			switch(static::$Redis->type($key))
			{
				case Redis::REDIS_STRING:
					return static::$Redis->get($key);
				break;
				case Redis::REDIS_SET:
					return static::$Redis->sMembers($key);
				break;
				case Redis::REDIS_HASH:
					return (!empty($members) && is_array($members)) ? static::$Redis->hMGet($key, $members) : static::$Redis->hGetAll($key);
				break;
				// @codeCoverageIgnoreStart
				default:
					return FALSE;
				break;
			}
			// @codeCoverageIgnoreEnd
		}
		else
		{
			// Maybe throw an exception? E: Key not found?
			return FALSE;
		}
	}
	
	/**
	 * Hash Keys
	 * 
	 * Get all keys for the provided hash
	 * 
	 * @param string $hash
	 * 
	 * @return array|false
	 */
	public static function keys($hash)
	{
		return static::$Radis->hKeys($hash);
	}

	/**
	 * Contains
	 *
	 * Check if a key is contained within a Redis set
	 *
	 * @param string $key
	 * @param string $value
	 *
	 * @return bool
	 * @throws StorageException
	 */
	public static function contains($set, $item)
	{
		if( static::$Redis->type($set) === Redis::REDIS_SET )
		{
			return static::$Redis->sIsMember($set, $item);
		}
		else
		{
			throw new StorageException('Not a valid set');
		}
	}

	/**
	 * Cache
	 *
	 * Create a temporary item for $ttl seconds
	 *
	 * @param string $key
	 * @param string $value
	 * @param int $ttl
	 *
	 * @return bool
	 */
	public static function cache($key, $value, $ttl = 300)
	{
		if( static::set($key, $value) )
		{
			return static::$Redis->expire($key, $ttl);
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Delete
	 *
	 * Delete N total keys
	 *
	 * @param mixed $key,... The key to be deleted
	 *
	 * @return int Number of deleted keys
	 */
	public static function delete()
	{
		$keys = func_get_args();

		if( is_array($keys) && !empty($keys) )
		{
			return static::$Redis->delete($keys);
		}
		else
		{
			throw new StorageException('Delete key(s) not valid');
		}
	}

	/**
	 * Save
	 *
	 * Create a persistent store for the running data set.
	 *
	 * This is vital and should be run whenver data is changed. If I were
	 * smart and used a Singleton pattern I could have this as part of the
	 * __destruct magic so it just saved at the end of each session. Maybe
	 * if this is refactored it will have something like that.
	 *
	 * @param bool $bg optional
	 * 
	 * @return void
	 */
	public static function save($bg = TRUE)
	{
		return ($bg) ? static::$Redis->bgsave() : static::$Redis->save();
	}
}

