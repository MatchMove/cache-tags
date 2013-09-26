<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * MySQL database connection.
 * Overwrites the Kohana's query builder
 * NOTE! Default will be $_lifetime = Date::DAY
 * 
 * @package    Kohana/Database
 * @category   Drivers
 * @author     Gian Carlo Val Ebao <gianebao@gmail.com>
 */
class Cachetag_Database_MySQL extends Kohana_Database_MySQL {
    
    // Cachetag instance
    protected static $_cache = null;
    
    // Cache lifetime
    protected $_lifetime = Date::DAY;
    
    protected function schema_cache($tags, $query)
    {
        if (empty(Database_Query::$cache))
        {
            Database_MySQL::$_cache = new Cachetag();
        }
        
        Database_MySQL::$_cache->tags($tags);
        
        if (false !== ($result = Database_MySQL::$_cache->get($query, false)))
        {
            Database_MySQL::$_cache->reset();
            return new Database_Result_Cached($result, $query, false);
        }
        
        // Search for column names
        $result = $this->query(Database::SELECT, $query, FALSE);
        
        Database_MySQL::$_cache->set($query, $result->as_array(), $this->_lifetime);
        Database_MySQL::$_cache->reset();
        
        return $result;
    }
    
	public function list_tables($like = NULL)
	{
        $query = 'SHOW TABLES';
            
        if (is_string($like))
        {
            $query .= ' LIKE '.$this->quote($like);
        }
        
        $result = $this->schema_cache(array('schema_all'), $query);
        
		$tables = array();
		foreach ($result as $row)
		{
			$tables[] = reset($row);
		}

		return $tables;
	}

	public function list_columns($table, $like = NULL, $add_prefix = TRUE)
	{
		// Quote the table name
		$table = ($add_prefix === TRUE) ? $this->quote_table($table) : $table;
        
        $query = 'SHOW FULL COLUMNS FROM '.$table;
            
        if (is_string($like))
        {
            $query .= ' LIKE '.$this->quote($like);
        }
        
        $result = $this->schema_cache(array('schema_' . $table), $query);
        
		$count = 0;
		$columns = array();
		foreach ($result as $row)
		{
			list($type, $length) = $this->_parse_type($row['Type']);

			$column = $this->datatype($type);

			$column['column_name']      = $row['Field'];
			$column['column_default']   = $row['Default'];
			$column['data_type']        = $type;
			$column['is_nullable']      = ($row['Null'] == 'YES');
			$column['ordinal_position'] = ++$count;

			switch ($column['type'])
			{
				case 'float':
					if (isset($length))
					{
						list($column['numeric_precision'], $column['numeric_scale']) = explode(',', $length);
					}
				break;
				case 'int':
					if (isset($length))
					{
						// MySQL attribute
						$column['display'] = $length;
					}
				break;
				case 'string':
					switch ($column['data_type'])
					{
						case 'binary':
						case 'varbinary':
							$column['character_maximum_length'] = $length;
						break;
						case 'char':
						case 'varchar':
							$column['character_maximum_length'] = $length;
						case 'text':
						case 'tinytext':
						case 'mediumtext':
						case 'longtext':
							$column['collation_name'] = $row['Collation'];
						break;
						case 'enum':
						case 'set':
							$column['collation_name'] = $row['Collation'];
							$column['options'] = explode('\',\'', substr($length, 1, -1));
						break;
					}
				break;
			}

			// MySQL attributes
			$column['comment']      = $row['Comment'];
			$column['extra']        = $row['Extra'];
			$column['key']          = $row['Key'];
			$column['privileges']   = $row['Privileges'];

			$columns[$row['Field']] = $column;
		}

		return $columns;
	}

} // End Database_MySQL
