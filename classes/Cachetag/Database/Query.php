<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Database query wrapper.  See [Parameterized Statements](database/query/parameterized) for usage and examples.
 * Overwrites the Kohana's query builder
 * NOTE! Default will be $_lifetime = Date::DAY
 * 
 * @package    Kohana/Database
 * @category   Query
 * @author     Gian Carlo Val Ebao <gianebao@gmail.com>
 */
class Cachetag_Database_Query extends Kohana_Database_Query {
    
    // Cache object
    protected static $_cache = null;
    
    // Cache lifetime. NOTE! Default will be cache ON
    protected $_lifetime = Date::DAY;
    
    /**
     * Enables the query to be cached for a specified amount of time.
     *
     * @param   integer  $lifetime  number of seconds to cache, 0 deletes it from the cache
     * @param   boolean  whether or not to execute the query during a cache hit
     * @return  $this
     * @uses    Kohana::$cache_life
     */
    public function cached($lifetime = NULL, $force = FALSE)
    {
        $this->_force_execute = $force;
        $this->_lifetime = $lifetime;

        return $this;
    }
    
    /**
     * Fetches the tables being used and to be tagged to the token.
     * return Array
     */
    private function get_tags()
    {
        $data = empty($this->_table) ? $this->_from: $this->_table;
        
        if (!is_array($data))
        {
            $data = array($data);
        }
        
        $response = new RecursiveIteratorIterator(new RecursiveArrayIterator($data));
        
        return iterator_to_array($response, false);
    }
    
    /**
     * Execute the current query on the given database.
     *
     * @param   mixed    $db  Database instance or name of instance
     * @param   string   result object classname, TRUE for stdClass or FALSE for array
     * @param   array    result object constructor arguments
     * @return  object   Database_Result for SELECT queries
     * @return  mixed    the insert id for INSERT queries
     * @return  integer  number of affected rows for all other queries
     */
    public function execute($db = NULL, $as_object = NULL, $object_params = NULL)
    {
        if ( ! is_object($db))
        {
            // Get the database instance
            $db = Database::instance($db);
        }

        if ($as_object === NULL)
        {
            $as_object = $this->_as_object;
        }

        if ($object_params === NULL)
        {
            $object_params = $this->_object_params;
        }

        // Compile the SQL query
        $sql = $this->compile($db);

        if ($this->_lifetime !== NULL && $this->_lifetime > 0)
        {
            if (empty(Database_Query::$_cache))
            {
                Database_Query::$_cache = new Cachetag();
            }
            
            Database_Query::$_cache->tags($this->get_tags());
        }

        if ($this->_lifetime > 0 && $this->_type === Database::SELECT)
        {
            if (Kohana::$profiling === TRUE)
            {
                $benchmark = Profiler::start('Cache_Tag', 'DB: "' . $db . '", Tags: "' . json_encode($this->get_tags()) .  '", Query: "' . $sql . '")');
            }
            
            // Set the cache key based on the database instance name and SQL
            $cache_key = 'Database::query("'.$db.'", "'.$sql.'")';

            // Read the cache first to delete a possible hit with lifetime <= 0
            if (($result = Database_Query::$_cache->get($cache_key, NULL)) !== NULL && ! $this->_force_execute)
            {
                if (isset($benchmark))
                {
                    Profiler::stop($benchmark);
                }
                
                Database_Query::$_cache->reset();
                // Return a cached result
                return new Database_Result_Cached($result, $sql, $as_object, $object_params);
            }
        }
        
        // Execute the query
        $result = $db->query($this->_type, $sql, $as_object, $object_params);
        
        if ($this->_type !== Database::SELECT)
        {
            Database_Query::$_cache->flush();
        }
        elseif (isset($cache_key) && $this->_lifetime > 0)
        {
            if (isset($benchmark))
            {
                Profiler::stop($benchmark);
            }
            // Cache the result array
            Database_Query::$_cache->set($cache_key, $result->as_array(), $this->_lifetime);
            Database_Query::$_cache->reset();
        }
        
        return $result;
    }

} // End Database_Query
