<?php

class NestedModelController extends ModelController
{
    public $_showInMenu = false;
    protected $parentItemId;
    private $methodName;

    /**
     * 
     * @var ModelController
     */
    protected $parentController;
    
    public function getLabel()
    {
        if($this->parentController)
        {
            $parentEntity = Utils::singular($this->parentController->model->getEntity());
            $entity = $this->model->getEntity();
            $data = reset($this->parentController->model[$this->parentItemId]);
            $this->parentController->model->setData($data);
            return $entity . ($entity == '' ? '' : ' of ') .  "$parentEntity ({$this->parentController->model})";
        }
        else
        {
            return parent::getLabel();
        }
    }
    
    public function setLabel($label)
    {
        if($this->parentController)
        {
            $this->parentController->setLabel($label);
        }
        else
        {
            parent::setLabel($label);
        }
    }
    
    public function setupListView()
    {
        $this->listView->setListConditions(
            "{$this->parentController->model->getKeyField()} = '{$this->parentItemId}'"
        );
    }
    
    public function setMethodName($methodName)
    {
        $this->methodName = $methodName;
    }
    
    public function setParent(ModelController $parent)
    {
        $this->parentController = $parent;
    }
    
    public function setParentItemId($parentItemId)
    {
        $this->parentItemId = $parentItemId;
    }
    
    public function getParentItemId()
    {
        return $this->parentItemId;
    }
    
    public function getParentController()
    {
        return $this->parentController;
    }
    
    public function getForm() 
    {    
        $form = parent::getForm();
        $form->add(Element::create(
                'HiddenField', 
                $this->parentController->model->getKeyField(), 
                $this->parentItemId
            )
        );
        return $form;
    }
    
    public function getImporterForm() 
    {
        $key = $this->parentController->model->getKeyField();
        $form = parent::getForm();
        $list = Element::create(
            'SelectionList',
            Utils::singular($this->parentController->model->getEntity()),
            $key
        );
        $items = $this->parentController->model->get();
        foreach($items as $item)
        {
            $this->parentController->model->setData($item);
            $list->addOption((string)$this->parentController->model, $item[$key]);
        }
        $form->add($list);
        return $form;
    }
}
