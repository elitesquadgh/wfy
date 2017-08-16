<?php

abstract class ReportRenderer
{
    protected $parameters;
    
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
    }
    
    abstract public function renderLogo(LogoContent $content);
    abstract public function renderText(TextContent $content);
    abstract public function renderTable(TableContent $content);
    abstract public function output();
    
    public function getContentType()
    {
        
    }
}