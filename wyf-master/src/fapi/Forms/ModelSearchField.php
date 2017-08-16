<?php
/**
 * Works just like the ModelField but presents the user with a suggestion of
 * correct values as the user types in. Provides an interface which is much like
 * a search box.
 * 
 * @author James Ekow Abaka Ainooson <jainooson@gmail.com>
 * @ingroup Forms
 */

class ModelSearchField extends Field
{
    protected $searchFields = array();
    protected $model;
    protected $storedField;
    public $boldFirst = true;
    private $storedFieldSet = false;
    private $andConditions;
    private $onChangeAttribute;
    private $textField;
    
    public function __construct($path=null,$value=null)
    {
        if($path!=null)
        {
            $info = Model::resolvePath($path);
            if ($value=="") $value = $info["field"];
            $this->model = model::load($info["model"]);
            $field = $this->model->getFields(array($value));

            $this->setLabel($field[0]["label"]);
            $this->setDescription($field[0]["description"]);
            $this->setName($info["field"]);

            $this->addSearchField($value);
            $this->storedField = $info["field"];
        }
        $this->textField = new TextField();
    }
    
    public function setAndConditions($andConditions)
    {
        $this->andConditions = $andConditions;
        return $this;
    }
    
    public function setStoredField($field)
    {
    	$this->storedField = $field;
    	return $this;
    }
    
    /**
     * 
     * @param $model
     * @param $value
     * @return ModelSearchField
     */
    public function setModel($model,$value="")
    {
        $this->model = $model;
        $this->storedField = $value==""?$this->model->getKeyField():$value;
        return $this;
    }
    
    public function addSearchField($field)
    {
        $this->searchFields[] = $field;
        return $this;
    }
    
    public function onChangeJsFunction($params) 
    {
        $this->onChangeAttribute = $params;
        return $this;
    }
    
    public function render()
    {
        global $redirectedPackage;
        global $packageSchema;
        
        $name = $this->getName();
        $hidden = new HiddenField($name,$this->getValue());
        $id = $this->getId();
        $hidden->addAttribute("id", $id);        
        $ret = $hidden->render();
                
        if($this->storedFieldSet === false)
        {
            $this->addSearchField($this->storedField);
            $this->storedFieldSet = true;
        }
        
        $object = array
        (
            "model"=>$this->model->package,
            "format"=>"json",
            "fields"=>$this->searchFields,
            "limit"=>20,
            "conditions"=>"",
            "and_conditions"=>$this->andConditions,
            'redirected_package' => $redirectedPackage,
            'package_schema' => $packageSchema
        );
        $jsonSearchFields = array_reverse($this->searchFields);
        $object = base64_encode(serialize($object));
        $path = Application::$prefix."/system/api/query?object=$object";
        $fields = urlencode(json_encode($jsonSearchFields));
        
        $this->textField->addAttribute("onkeyup","fapiUpdateSearchField('$id','$path','$fields',this,".($this->boldFirst?"true":"false").",'{$this->onChangeAttribute}')");
        $this->textField->addAttribute("autocomplete","off");
        
        foreach($this->attributes as $attribute)
        {
            $this->textField->addAttributeObject($attribute);
        }
        
        if($this->getValue()!="")
        {
            $data = $this->model[$this->getValue()];
            for($i=2;$i<count($jsonSearchFields);$i++)
            {
                $val .= $data[0][$jsonSearchFields[$i]]." ";
            }
            $this->textField->setValue($val);
        }
        else
        {
            $this->textField->setValue('');
        }
        
        $this->textField->setId($id."_search_entry");        
        $ret .= $this->textField->render();
        $ret .= "<div class='fapi-popup' id='{$id}_search_area'></div>";
        return $ret;
    }
    
    public function setWithDisplayValue($value) 
    {
        $conditions = array();
        foreach($this->searchFields as $searchField)
        {
            $conditions[] = "{$searchField} = '{$value}'";
        }
        
        $item = $this->model->get(
            array(
                'fields' => array($this->getName()),
                'conditions' => implode(" OR ", $conditions)
            )
        );
        
        if(count($item) > 0)
        {
            $this->setValue($item[0][$this->getName()]);
        }
        else
        {
            throw new Exception("Invalid option $value for {$this->label} field.");
        }
    }

    public function getDisplayValue()
    {
        $jsonSearchFields = array_reverse($this->searchFields);
        $data = $this->model[$this->getValue()];
        $val = "<b>".$data[0][$jsonSearchFields[0]]."</b> ";
        for($i=1;$i<count($jsonSearchFields);$i++)
        {
            $val .= $data[0][$jsonSearchFields[$i]]." ";
        }
        return $val;
    }
    
    public function addCSSClass($class) 
    {
        $this->textField->addCSSClass($class);
    }
}
