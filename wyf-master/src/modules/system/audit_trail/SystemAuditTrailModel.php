<?php
class SystemAuditTrailModel extends ORMSQLDatabaseModel
{
    const AUDIT_TYPE_ADDED_DATA = 0;
    const AUDIT_TYPE_UPDATED_DATA = 1;
    const AUDIT_TYPE_DELETED_DATA = 2;
    const AUDIT_TYPE_ROUTING = 4;
    const AUDIT_TYPE_SYSTEM = 5;
    
    private static $instance = false;
    private static $dataModel;
    
    public $database = '.audit_trail';
    private $auditTrailData;
    
    public function update()
    {
        throw new Exception('Cannot update audit trail');
    }
    
    public function delete()
    {
        throw new Exception('Cannot delete audit trail');
    }
    
    private function getInstance()
    {
        if(self::$instance === false)
        {
            self::$instance = Model::load('system.audit_trail');
            self::$dataModel = Model::load('system.audit_trail_data');
        }
        return self::$instance;
    }
    
    public static function log($params)
    {
        if($params['item_type'] != 'system.audit_trail')
        {
            $model = self::getInstance();
            $params['user_id'] = $_SESSION['user_id'];
            $params['audit_date'] = time();
            $model->setData($params);
            $model->save();
        }
    }
    
    public static function logAdd($model, $id)
    {
        if(ENABLE_AUDIT_TRAILS === true && $model->disableAuditTrails === false)
        {
            if($id === null)
            {
                $id = $this->datastore->data[$this->getKeyField()];
            }
            
            @SystemAuditTrailModel::log(
                array(
                    'item_id' => $id,
                    'item_type' => $model->package,
                    'description' => 'Added item',
                    'type' => SystemAuditTrailModel::AUDIT_TYPE_ADDED_DATA,
                    'data' => json_encode($model->datastore->data)
                )
            );
        }        
    }
    
    public static function getPreUpdateData($model, $field, $value)
    {
        if(ENABLE_AUDIT_TRAILS === true && $model->disableAuditTrails === false)
        {
            $before = reset($model->getWithField2($field, $value));
        }    
        return $before;
    }
    
    public static function logUpdate($model, $before)
    {
        if(ENABLE_AUDIT_TRAILS === true && $model->disableAuditTrails === false)
        {
            $data = json_encode(
                array(
                    "after"=>$model->datastore->data ,
                    "before"=>$before
                )
            );
                        
            if($model->datastore->tempData[0][$model->getKeyField()] == null)
            {
                $id = $before[$model->getKeyField()];
            }
            else
            {
                $id = $model->datastore->tempData[0][$model->getKeyField()];
            }
            
            SystemAuditTrailModel::log(
                array(
                    'item_id' => $id,
                    'item_type' => $model->package,
                    'description' => 'Updated item',
                    'type' => SystemAuditTrailModel::AUDIT_TYPE_UPDATED_DATA,
                    'data' => $data
                )
            );
        }        
    }
    
    public static function logDelete($model, $field, $value)
    {
        if(ENABLE_AUDIT_TRAILS === true  && $model->disableAuditTrails === false)
        {        
            if($value === null)
            {
                $data = reset($model->get(array('conditions' => $field)));
            }
            else
            {
                $data = reset($model->getWithField2($field, $value));
            }

            SystemAuditTrailModel::log(
                array(
                    'item_id' => $data[$model->getKeyField()],
                    'item_type' => $model->package,
                    'description' => 'Deleted item',
                    'type' => SystemAuditTrailModel::AUDIT_TYPE_DELETED_DATA,
                    'data' => json_encode($data)
                )
            );
        }
    }
    
    public function preAddHook()
    {
        $this->auditTrailData = $this->datastore->data['data'];
        unset($this->datastore->data['data']);
    }
    
    public function postAddHook($id, $data)
    {
        self::$dataModel->setData(
            array(
                'audit_trail_id' => $id,
                'data' => $this->auditTrailData,
            )
        );
        self::$dataModel->save();
    }
}
