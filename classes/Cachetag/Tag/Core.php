<?php defined('SYSPATH') or die('No direct script access.');

class Cachetag_Tag_Core {
    /**
     * Upper limit of the random range
     */
    protected $key_range = 10000;
    protected $cache = null;
    
    public function __construct($cache)
    {
        $this->cache = $cache;
    }
    
    /**
     * Get a cache tag
     *
     * @param  string  $name  name of the tag
     * @return string
     */
    public function get($name)
    {
        $cache = $this->cache;
        $min_key_range = 1;
        
        if(false === ($value = $cache->get(self::name($name), false)))
        {
            $value = rand($min_key_range, $this->key_range);
            $cache->set(self::name($name), $value);
        }
        
        return strtoupper($name) . $value;
    }
    
    /**
     * Reset the tag
     *
     * @param  string  $name  name of the tag
     */
    public function flush($name)
    {
        $this->cache->increment(self::prefix($name));
    }
    
    /**
     * Creates a tagtoken
     *
     * @param  string  $name  name of the tag
     * @return string
     */
    protected static function name($name)
    {
        return 'TAG_' . hash('crc32b', strtoupper($name));
    }
}