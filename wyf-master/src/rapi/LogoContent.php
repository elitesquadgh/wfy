<?php
class LogoContent extends ReportContent
{
    public $image;
    public $address = array();
    public $title;
    
    private function setAddress($address)
    {
        if(is_array($address))
        {
            $this->address = $address;
        }
        else
        {
            $this->address = explode("\n", $address);
        }
    }
    
    public function __set($name, $value)
    {
        switch($name)
        {
            case 'address': $this->setAddress($address);
        }
    }
    
    public function getType()
    {
        return "logo";
    }
}
