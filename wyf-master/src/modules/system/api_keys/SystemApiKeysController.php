<?php
class SystemApiKeysController extends ModelController
{
    public $modelName = '.api_keys';
    
    public $listFields = array(
        '.api_keys.api_key_id',
        '.users.user_name',
        '.api_keys.key'
    );
}
