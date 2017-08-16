<?php
class TableContent extends ReportContent
{
    protected $headers;
    protected $data;
    protected $dataParams = null;
    protected $autoTotals;
    private $totals = array();
    protected $totalsBox;
    protected $numColumns;
    
    public function __construct($headers, $data)
    {   
        $this->headers = $headers;
        $this->data = $data;
        $this->numColumns = count($headers);
    }
    
    public function setAsTotalsBox($totalsBox)
    {
        $this->totalsBox = $totalsBox;
    }
    
    public function getAsTotalsBox()
    {
        return $this->totalsBox;
    }
    
    public function setAutoTotals($autoTotals)
    {
        $this->autoTotals = $autoTotals;
    }
    
    public function getAutoTotals()
    {
        return $this->autoTotals;
    }
    
    public function getTableWidths()
    {
        if(isset($this->dataParams['widths']))
        {
            return $this->dataParams['widths'];
        }
        else
        {
            return $this->computeTableWidths();
        }
    }
    
    private function adjustMaxStringLenght($strings, $widths)
    {
        $i = 0;
        foreach($strings as $string)
        {
            $widths[$i] = strlen($string) > $widths[$i] ? strlen($string) : $widths[$i];
            $i++;
        }
        return $widths;
    }

    private function computeTableWidths()
    {
        $widths = array();
        foreach($this->headers as $header)
        {
            $lines = explode("\n",$header);
            $widths = $this->adjustMaxStringLenght($lines, $widths);
        }
        
        foreach($this->data as $row)
        {
            $widths = $this->adjustMaxStringLenght($row, $widths);
        }
        
        $totals = $this->getTotals();
        
        if(count($totals) > 0)
        {
            $widths = $this->adjustMaxStringLenght($totals, $widths);
        }
        
        return $this->normalizeWidths($widths);
    }
    
    private function normalizeWidths($widths)
    {
        $max = array_sum($widths);
        foreach($widths as $i => $width)
        {
            $widths[$i] = $width / $max;
        } 
        return $widths;
    }
    
    public function setTotals($totals)
    {
        $this->totals = $totals;
    }
    
    public function getTotals()
    {   
        foreach($this->data as $row)
        {
            foreach($row as $i => $field)
            {
                if($this->dataParams["total"][$i])
                {
                    $totals[$i] += $this->getFieldValue($field, $this->dataParams['type'][$i]);
                }
                else
                {
                    $totals[$i] = null;
                }
            }
        }

        return $totals;
    }
    
    private function getFieldValue($value, $type)
    {

        $field = str_replace(array(",", ' '), "", $value);

        switch($type)
        {
            case 'double':
                $field = round($field, 2);
                break;
            case 'number':
                $field = round($field, 0);
                break;
        }
        
        return $field;
    }
    
    public function getHeaders()
    {
        return $this->headers;
    }
    
    public function setDataTypes($types)
    {
    	$this->dataParams['type'] = $types;
    }
    
    public function getDataTypes()
    {
        return $this->dataParams['type'];
    }
    
    public function setTotalsFields($total)
    {
    	$this->dataParams['total'] = $total;
    	$this->setAutoTotals(true);
    }
    
    public function setIgnoredFields($ignore)
    {
        $this->dataParams['ignore'] = $ignore;
    }
    
    public function setWidths($widths)
    {
        $this->dataParams['widths'] = $widths;
    }
    
    public function getData()
    {
        return $this->data;
    }
    
    public function getType()
    {
        return "table";
    }
    
    public function getNumColumns()
    {
        return $this->numColumns;
    }
    
    public function setDataParams($dataParams)
    {
        if(isset($dataParams['widths']))
        {
            $dataParams['widths'] = $this->normalizeWidths($dataParams['widths']);
        }
        $this->dataParams = $dataParams;
    }
    
    public function getDataParams()
    {
        return $this->dataParams;
    }
}
