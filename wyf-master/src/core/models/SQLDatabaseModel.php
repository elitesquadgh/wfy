<?php
class SQLDatabaseModel extends Model
{    
    public function __construct()
    {
        global $packageSchema;
        $this->database = (substr($this->database, 0, 1) == "."?$packageSchema: "") . $this->database;
    }
    
    /**
     * Returns a DataStore object which is based on the settings provided for
     * the default datastore.
     * 
     * @return DataStore
     */
    public static function getDatastoreInstance() 
    {
        $class = new ReflectionClass(SQLDBDataStore::$activeDriverClass);
        return $class->newInstance();
    }
    
    protected function connect()
    {
        $this->datastore = self::getDatastoreInstance();
        $this->datastore->modelName = $this->package;
    }
    
    public function getDatabase()
    {
        return $this->datastore->getDatabase();
    }
    
    public function getSearch($searchValue,$field)
    {
        return $this->datastore->getSearch($searchValue,$field);
    }
}
