<?php
/**
 * 
 */
class TextContent extends ReportContent
{
    protected $text;
    protected $style = 'normal';
    
    public function __construct($text=null, $style=null)
    {
        $this->style = $style;
        $this->text = $text;
    }
    
    public function getType()
    {
        return "text";
    }
    
    public function getText()
    {
        return $this->text;
    }
    
    public function getStyle()
    {
        return $this->style;
    }
}
