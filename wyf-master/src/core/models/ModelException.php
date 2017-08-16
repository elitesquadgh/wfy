<?php
class ModelException extends Exception{
    public $object;
    
    public function __construct($message, $object)
    {
        parent::__construct($message);
        $this->object = $object;
    }
}