<?php
class Table
{
    protected $data = array();
    protected $headers = array();
    protected $cellRenderers = array();
    protected $operations = array();
    protected $prefix;
    public $name = "defaultTable";
    public $headerParams;
    public $useInlineOperations = false;
    protected $renderedOperations;
    
    public function __construct($prefix,$headers=null, $data=null, $operations=null,$headerParams=null)
    {
        if($GLOBALS['fapi_stylesheet'] === false)
        {
            Application::preAddStylesheet("css/tapi.css", Application::getWyfHome("tapi/"));
        }
        else
        {
            Application::preAddStylesheet($GLOBALS['tapi_stylesheet']);
        }

        $this->prefix = $prefix;
        $this->headers = $headers;
        $this->data = $data;
        $this->operations = $operations;
    }
    
    public function setOperations($operations)
    {
        $this->operations = $operations;
    }
    
    public function addOperation($link,$label=null,$action=null)
    {
        $this->operations[] = 
        array
        (
            "link"=>$link,
            "label"=>$label==null?$link:$label,
            "action"=>$action==null?$this->prefix.$link."/%key%":$action
        );
    }
    
    public function getOperations()
    {
        return $this->renderedOperations;
    }
    
    protected function renderHeader()
    {
        $table = "<table class='tapi-table'>";
         
        //Render Headers
        $table .= "<thead><tr>";
        $table .= implode("</td><td>",$this->headers);
        
        if($this->useInlineOperations)
        {
            $table .= "</td><td align='right'>Operations</td></tr></thead>";
        }
         
        //Render Data
        $table .= "<tbody id='tbody'>";
        return $table;
    }
    
    protected function renderFooter()
    {
        $table .= "</tbody>";
        $table .= "</table>";
        return $table;
    }
    
    public function render($renderHeaders=true)
    {
        if($renderHeaders) {
            $table = $this->renderHeader();
        }
        
        if(isset($this->data[0]))
        {
            reset($this->data[0]);
            next($this->data[0]);
            $firstColumnKey = key($this->data[0]);
        }
        
        foreach($this->data as $i => $row)
        {
            $key = array_shift($row);
            $table .= "<tr id='{$this->name}-operations-row-$i' onmouseover='wyf.tapi.showOperations(\"{$this->name}\", $i)' >";
            //$table .= "<td><input type='checkbox' class='$this->name-checkbox' value='$key' ></td>";
            
            foreach($row as $name=>$value)
            {
                $params="";
                if($this->headerParams[$name]["type"]=="number")
                {
                    $params = "align='right'";
                }
                $table .= "<td $params >" . 
                    ($name == $firstColumnKey ? "<input type='checkbox' class='table-checkbox {$this->name}-checkbox' value='$key' > " : '') . 
                    strip_tags($value) . "</td>";
            }
            
            if($this->operations!=null)
            {
                $rowOperations = '';
                foreach($this->operations as $operation)
                {
                    $rowOperations .=
                        sprintf(
                            '<a class="tapi-operation tapi-operation-%s" href="%s">%s</a>',
                            $operation["link"],
                            str_replace(array("%key%","%path%"),array($key,$this->prefix.$operation["link"]),$operation["action"]),
                            $operation["label"]
                        );
                }
            }
            
            if($this->useInlineOperations)
            {
                $table .= "<td id='{$this->name}-operations-cell-$i' align='right' >$rowOperations</td>";
            }
            else
            {
                $this->renderedOperations .= "<div class='operations-box' id='{$this->name}-operations-box-$i'>$rowOperations</div>";
            }
            
            $table .= "</tr>";
        }
        
        if($renderHeaders) $table .= $this->renderFooter();
        
        if(!$this->useInlineOperations)
        {
            $table .= $operations;
        }
        
        return $table;
    }
}

