<?php defined('SYSPATH') or die('No direct script access.');

class Cachetag_Tag_Core {
    const DEFAULT_CONFIG_PATH = 'cachetag';
    public static $default_config_group = 'default';
    
    protected $_tags = array();
    
    /**
     * Upper limit of the random range
     */
    protected $_key_range = 10000;
    protected $_lifetime  = Date::DAY;
    protected $_cache = null;
    
    public function __construct($group)
    {
        $this->_cache = Cache::instance($group);
    }
    
    public function __toString()
    {
        return $this->get($this->_tags);
    }
    
    /**
     * Lazily sets the value of the tag.
     * Can be use in tandem with `__toString` magic method
     *
     * @param  mixed  $name  name of the tag/s
     * @return string
     */
    public function set($name)
    {
        if (!is_array($name))
        {
            $name = array($name);
        }
        
        $this->_tags = $name;
        
        return $this;
    }

    /**
     * Get a cache tag/s
     *
     * @param  mixed  $name  name of the tag/s
     * @return string
     */
    public function get($name)
    {
        if (!is_array($name))
        {
            $name = array($name);
        }
        
        $key = '';
        
        foreach ($name as $item)
        {
            $key .= $this->_get($item);
        }
        
        return $key;
    }
    
    /**
     * Get a cache tag
     *
     * @param  string  $name  name of the tag
     * @return string
     */
    protected function _get($name)
    {
        $cache = $this->_cache;
        $min_key_range = 1;
        
        if(false === ($value = $cache->get(self::_name($name), false)))
        {
            $value = rand($min_key_range, $this->_key_range);
            $cache->set(self::_name($name), $value, $this->_lifetime);
        }
        
        return strtoupper($name) . $value;
    }
    
    /**
     * Reset the tag/s
     *
     * @param  mixed  $name  name of the tag
     */
    public function flush($name = array())
    {
        if (empty($name))
        {
            $name = $this->_tags;
        }
        
        if (!is_array($name))
        {
            $name = array($name);
        }
        
        foreach ($name as $item)
        {
            $this->_cache->increment(self::_name($item));
        }
    }
    
    /**
     * Creates a token tag
     *
     * @param  string  $name  name of the tag
     * @return string
     */
    protected static function _name($name)
    {
        return 'TAG_' . hash('crc32b', strtoupper($name));
    }
}