<?php

class MCDataExporterJob extends ajumamoro\Ajuma
{
    public function run()
    {
        $this->go();
    }
    
    private function getData($fieldNames)
    {
        $this->model->setQueryResolve(false);        
        $data = $this->model->get(array("fields"=>$fieldNames));        
        foreach($data as $j => $row)
        {
            for($i = 0; $i < count($row); $i++)
            {
                $this->fields[$i]->setValue($row[$fieldNames[$i]]);
                $data[$j][$fieldNames[$i]] = strip_tags($this->fields[$i]->getDisplayValue());
            }
        }        
        return $data;
    }
    
    public function go()
    {
        $fieldNames = array();
        $headers = array();
        foreach($this->fields as $field)
        {
            $fieldNames[] = $field->getName();
            $headers[] = $field->getLabel();
        }
        
        $report = new Report($this->format);
                
        if(!$this->exportOnlyHeaders)
        {
            $title = new TextContent($this->label, array('bold' => true));
            $report->add($title);
            $data = $this->getData($fieldNames);
        }
        else
        {
            $data = array();
        }
        
        $table = new TableContent($headers,$data);

        $report->add($table);
        $report->output();
    }
}
