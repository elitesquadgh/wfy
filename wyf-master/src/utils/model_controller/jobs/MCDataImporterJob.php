<?php

class MCDataImporterJob extends ajumamoro\Ajuma
{
    private $fileFields = array();
    private $headers;
    private $displayData;
    private $modelData;
    /**
     *
     * @var Model
     */
    private $modelInstance;
    private $secondaryKey;
    private $tertiaryKey;
    private $statuses;
    private $line;
    private $added = 0;
    private $updated = 0;
    
    public function run()
    {
        try{
            $status = $this->go();
        }
        catch(Exception $e)
        {
            $status = $e->getMessage();
        }
        return $status;
    }
    
    private function setModelData($data, &$errors)
    {
        $hasValues = false;
        
        foreach($data as $i => $value)
        {
            $field = $this->fileFields[$i]->getName();
            if(trim($value) !== '') 
            {
                $hasValues = true;
            }
            
            try
            {
                $this->fields[$i]->setWithDisplayValue($value);
            }
            catch(Exception $e)
            {
                $hasValues = false;
                $errors[$field] = array($e->getMessage());
            }
            
            $this->displayData[$field] = $value;
            $this->modelData[$field] = $this->fields[$i]->getValue();
        } 
        return $hasValues;
    }
    
    /**
     * Maps the fields on the file to those on the form.
     */
    private function setupFileFields()
    {
        foreach($this->fields as $field)
        {
            $index = array_search($field->getLabel(), $this->headers);
            if($index !== false)
            {
                $this->fileFields[] = $field;
            }
            else
            {
                throw new Exception("Invalid file format could not find the {$field->getLabel()} column");
            }
        }        
    }
    
    private function updateData()
    {
        $tempData = reset(
            $this->modelInstance->getWithField2(
                $this->secondaryKey,
                $this->modelData[$this->secondaryKey]
            )
        );
        
        if($tempData !== false) 
        {
            if($this->tertiaryKey != "")
            {
                $this->modelData[$this->primaryKey] = $tempData[$this->primaryKey];
                $this->modelData[$this->tertiaryKey] = $tempData[$this->tertiaryKey];
            }
            

            $validated = $this->modelInstance->setData(
                $this->modelData,
                $this->primaryKey,
                $tempData[$this->primaryKey]
            );
            
            if($validated===true) 
            {
                $this->modelInstance->update(
                    $this->primaryKey,
                    $tempData[$this->primaryKey]
                );
                $this->updated++;
                return 'Updated';
            }
            else
            {
                return $validated;
            }
        }
        else
        {
            return $this->addData();
        }        
    }
    
    private function addData()
    {
        $validated = $this->modelInstance->setData($this->modelData);
        if($validated===true) 
        {
            $this->modelInstance->save();
            $this->added++;
            return 'Added';  
        }   
        else
        {
            return $validated;
        }
    }
    
    private function flattenErrors($errors)
    {
        $flatErrors = array();
        foreach($errors as $fieldErrors)
        {
            $flatErrors = array_merge($fieldErrors, $flatErrors);
        }
        return $flatErrors;
    }
    
    private function saveData()
    {
        if($this->secondaryKey!=null && $this->modelData[$this->secondaryKey] != '')
        {
            $validated = $this->updateData();
        }
        else
        {
            $validated = $this->addData();
        }

        if(isset($validated['errors']))
        {
            $this->statuses = array(
                    array(
                    'success' => false,
                    'data' => $this->displayData,
                    'errors' => $validated['errors'],
                )
            );
            return false;
        }
        else
        {
            $this->statuses[] = array(
                'success' => true,
                'data' => $this->displayData
            );
            return true;
        }        
    }

    public function go()
    {
        $file = fopen($this->file, "r");
        $this->headers = fgetcsv($file);
        $this->modelInstance = Model::load($this->model)->setQueryResolve(false)->setQueryExplicitRelations(false);
        
        
        $this->setupFileFields();
        
        $this->primaryKey = $this->modelInstance->getKeyField();
        $this->tertiaryKey = $this->modelInstance->getKeyField("tertiary");
        $this->secondaryKey = $this->modelInstance->getKeyField('secondary');
        

        $this->modelInstance->datastore->beginTransaction();
        $this->line = 1;

        while(!feof($file))
        {
            $this->line++;
            $data = fgetcsv($file);
            $this->modelData = array();
            $errors = array();
            
            if(!is_array($data))
            {
                continue;
            }
            
            if($this->setModelData($data, $errors)) 
            {
                if(!$this->saveData())
                {
                    $hasErrors = true;
                    break;
                }
            }
            else 
            {
                if(count($errors) > 0)
                {
                    $this->statuses = array(
                        array(
                            'success' => false,
                            'data' => $this->displayData,
                            'errors' => $errors,
                        )
                    );                    
                }
                $hasErrors = true;
                break;
            }
        }
        
        unlink($this->file);
        
        $return = array(
            'statuses' => $this->statuses,
            'headers' => $this->headers
        );
        
        if(!$hasErrors) 
        {
            $return['message'] = 'Succesfully Imported';
            $return['added'] = $this->added;
            $return['updated'] = $this->updated;
            $this->modelInstance->datastore->endTransaction();
        }
        else
        {
            $return['message'] = 'Failed to import data';
            $return['failed'] = true;
            $return['errors'] = $this->flattenErrors($this->statuses[0]['errors']);
            $return['line'] = $this->line;
        }
        
        return $return;       
    }
}
