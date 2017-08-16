<?php
class PasswordField extends TextField
{  
    public function __construct($label="",$name="",$description="")
    {
        parent::__construct($label,$name,$description);
        $this->setAttribute("type","password");
    }
    
    public function getDisplayValue()
    {
        return "******";
    }   
}

