<?php defined('SYSPATH') or die('No direct script access.');

return array(
    /**
     * Local cache storage. Prefered to be located together with application.
     */
	'local'      => array(
		'driver'             => 'apc',
		'default_expire'     => 3600,
	),
    
    /**
     * Global cache storage.
     */
	'tag' => array(
		'driver'             => 'memcache',
		'default_expire'     => 3600,
		'compression'        => FALSE,              // Use Zlib compression (can cause issues with integers)
		'servers'            => array(
			'local' => array(
				'host'             => 'localhost',  // Memcache Server
				'port'             => 11211,        // Memcache port number
				'persistent'       => FALSE,        // Persistent connection
				'weight'           => 1,
				'timeout'          => 1,
				'retry_interval'   => 15,
				'status'           => TRUE,
			),
		),
		'instant_death'      => TRUE,
	),
);