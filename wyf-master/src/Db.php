<?php
/*
 * WYF Framework
 * Copyright (c) 2011 James Ekow Abaka Ainooson
 * 
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 * 
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

/**
 * A database access class. This class allows the application to access
 * the postgresql database directly without going through the framework.
 * Through this class connections could be established with multiple databases.
 * The framework uses this class under the hood to perform its database
 * access too.
 */
class Db
{
    const MODE_ASSOC = "assoc";
    const MODE_ARRAY = "array";
    
    /**
     * The instances of the various databases
     * @var array
     */
    private static $instances = array();
    
    public static $defaultDatabase;
    public static $error;
    private static $lastQuery;
    
    /**
     * The last instance of the database
     * @var type 
     */
    private static $lastConnection;
    
    private static function resolveConnection($requested, $default = false)
    {
        if($requested == null)
        {
            return $default === false ? Db::$lastConnection : $default;
        }
        else
        {
            return $requested;
        }
    }
    
    public static function escape($string, $connection = null)
    {
        $quoted = self::getCachedInstance(self::resolveConnection($connection))->quote($string);
        return substr($quoted, 1, strlen($quoted) - 2);
    }
    
    public static function query($query, $connection = null, $mode = null)
    {
        $connection = self::resolveConnection($connection);
        $instance = Db::getCachedInstance($connection);
        
        try{
            $result = $instance->query($query);
        }
        catch(PDOException $e)
        {
            throw new Exception($e->getMessage() . " Query [ $query ]");
        }
        
        self::$lastQuery = $query;
                
        if($result->rowCount() > 0)
        {
            if($mode == Db::MODE_ARRAY)
            {
                return $result->fetchAll(PDO::FETCH_NUM);
            }
            else
            {
                return $result->fetchAll(PDO::FETCH_ASSOC);
            }
        }
        else
        {
            return array();
        }
    }
    
    public static function close($connection = null)
    {
        $connection = self::resolveConnection($connection);
        if(is_object(Db::$instances[$connection])) 
        {
            Db::$instances[$connection] = null;
            unset(Db::$instances[$connection]);
        }
        else
        {
            Db::$instances = null;
        }
        
    }
    
    public static function reset($db = null, $atAllCost = false)
    {
        Db::close($db);
        Db::get($db, $atAllCost);
    }
    
    public static function getCachedInstance($connection = null)
    {
        if(isset(Db::$instances[$connection]))
        {
            return Db::$instances[$connection];
        }
        else
        {
            return Db::get($connection, false);
        }
    }
    
    /**
     * Returns an instance of a named database. All the database configurations
     * are stored in the app/config.php
     * @param string $db The tag of the database in the config file
     * @param boolean $atAllCost When set to true this function will block till a valid db is found.
     * @return resource
     */
    public static function get($connection = null, $atAllCost = false)
    {
        if(is_array(Application::$config))
        {
            $database = Application::$config['db'];
            $connection = self::resolveConnection($connection, self::$defaultDatabase);
        }
        else
        {
            require "app/config.php";
            $connection = self::resolveConnection($connection, $selected);
            $database = $config['db'];            
        }
        
        unset(Db::$instances[$connection]);
        
        while(!is_object(Db::$instances[$connection]))
        {
            $connection_host = $database[$connection]["host"];
            $connection_port = $database[$connection]["port"];
            $connection_name = $database[$connection]["name"];
            $connection_user = $database[$connection]["user"];
            $connection_password = $database[$connection]["password"];
            
            Db::$instances[$connection] = new PDO(
                "pgsql:host=$connection_host;port=$connection_port;dbname=$connection_name;user=$connection_user;password=$connection_password"
            );
            Db::$instances[$connection]->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        Db::$lastConnection = $connection;
        return Db::$instances[$connection];
    }
    
    public function getLastQuery()
    {
        return self::$lastQuery;
    }
}
