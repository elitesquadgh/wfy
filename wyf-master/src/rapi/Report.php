<?php
class Report
{
    private $contents = array();
    private $generator;

    public function __construct($format, $parameters = array())
    {
        $generatorClass = ucfirst($format) . 'Renderer';
        $this->generator = new $generatorClass();
        $this->generator->setParameters($parameters);
    }
        
    public function add()
    {
        $this->contents = array_merge($this->contents,func_get_args());
        return $this;
    }
    
    public function output()
    {
        foreach($this->contents as $content)
        {
            if($this->filterContent($content)) continue;
            $contentType = $content->getType();
            $method = "render{$contentType}";
            $this->generator->$method($content);
        }
        
        $httpContentType = $this->generator->getContentType();
        if($httpContentType != '')
        {
            header("Content-Type: $httpContentType");
        }
        
        echo $this->generator->output();
        die();
    }
    
    private function filterContent($content)
    {
        $filter = false;
        if($_REQUEST['logo'] === 'no' && $content->getType() === 'logo')
        {
            $filter = true;
        }
        else if($_REQUEST['title'] === 'no' && $content->getType() === 'text' && $content->getStyle() === 'title')
        {
            $filter = true;
        }
        
        return $filter;
    }
}

