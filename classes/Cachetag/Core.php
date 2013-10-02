<?php defined('SYSPATH') or die('No direct script access.');

class Cachetag_Core {
    const DEFAULT_CONFIG_PATH ='cachetag';
    protected static $default_group = 'default';
    
    protected $_config = null;
    protected $_tag = null;
    
    /**
     * sets tag for the token.
     *
     * @param  mixed  $group  configurations
     * return Cache
     */
    public function __construct($group = null)
    {
        if (empty($group))
        {
            $group = Cachetag_Tag::$default_config_group;
        }
        
        if (is_string($group))
        {
            $config = Kohana::$config->load(self::DEFAULT_CONFIG_PATH)->as_array();
            
            // load configuration from kohana config file.
            if (empty($config[$group]))
            {
                throw new Kohana_Exception('Cannot find :class configuration group `:group` on file `:file`',
                    array(':class' => __CLASS__, ':group' => $group, ':file' => self::DEFAULT_CONFIG_PATH));
                
                return false;
            }
            
            $group = $config[$group];
        }
        
        $this->_cache = Cache::instance($group['local']);
        $this->_tag = new Cachetag_Tag($group['tag']);
    }
    
    /**
     * sets tag for the token.
     *
     * return Cache
     */
    public function tags($tags = array())
    {
        $this->tag()->set($tags);
        
        return $this;
    }
    
    /**
     * sets tag for the token.
     *
     * return Cache
     */
    public function reset()
    {
        $this->tag()->set(array());
        
        return $this;
    }
    
    /**
     * get the cache instance being used
     *
     * return Cache
     */
    public function cache()
    {
        return $this->_cache;
    }
    
    /**
     * get the cache instance being used
     *
     * return Cache
     */
    public function tag()
    {
        return $this->_tag;
    }
    
    /**
     * create the proper token id
     *
     * @param  string  $id  name of the token
     * return string
     */
    public function get_id($id)
    {
        return md5($this->tag() . $id);
    }
    
    /**
     * get the cache token
     *
     * @param  string  $id       name of the token
     * @param  string  $default  return value if token does not exist
     * return string
     */
    public function get($id, $default = NULL)
    {
        return $this->cache()->get($this->get_id($id), $default);
    }
    
    /**
     * get the cache token
     *
     * @param  string   $id        name of the token
     * @param  string   $data      return value if token does not exist
     * @param  integer  $lifetime  cache lifetime
     * return string
     */
    public function set($id, $data, $lifetime = Date::DAY)
    {
        $this->cache()->set($this->get_id($id), $data, $lifetime);
        
        return $this;
    }
    
    /**
     * sets tag for the token.
     *
     * return Cache
     */
    public function flush()
    {
        $this->tag()->flush();
        
        return $this;
    }
}