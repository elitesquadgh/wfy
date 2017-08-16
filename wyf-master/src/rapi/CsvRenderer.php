<?php

class CsvRenderer extends ReportRenderer
{
    private $csv = '';
    
    public function output() 
    {
        return $this->csv;
    }

    public function renderLogo(\LogoContent $content) 
    {
        
    }

    public function renderTable(\TableContent $content) 
    {
        $this->csv .= '"'.implode('","',$content->getHeaders()).'"'."\n";
        foreach($content->getData() as $data)
        {
            $this->csv .= '"'.implode('","',$data).'"'."\n";
        }        
    }

    public function renderText(\TextContent $content) 
    {
        
    }
    
    public function getContentType() 
    {
        return "text/csv";
    }
}
