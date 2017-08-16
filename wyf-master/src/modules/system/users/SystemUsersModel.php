<?php
class SystemUsersModel extends ORMSQLDatabaseModel
{
    public $database = '.users';
    
    public function preValidateHook()
    {
        if($this->datastore->data["password"]=="")
        {
            $this->datastore->data["password"] = md5($this->datastore->data["user_name"]);
        }
        unset($this->datastore->data['user_id']);
    }
    
    public function preAddHook()
    {
        $this->datastore->data["user_status"] = 2;
    }    
}
