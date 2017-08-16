<?php
class LinkButton extends ToolbarItem
{
    protected $label;
    protected $link;
    protected $linkAttributes;

    public function __construct($label,$link,$icon=null)
    {
        $this->label = $label;
        $this->link = $link;
        $this->icon = $icon;
    }

    public function render()
    {
        return "<a href='{$this->link}' $this->linkAttributes >{$this->label}</a>";
    }

    public function getCssClasses()
    {
        return array(
            "toolbar-linkbutton-".strtolower($this->label),
            "toolbar-toolitem-button"
        );
    }
    
    public function setLinkAttributes($linkAttributes)
    {
        $this->linkAttributes = $linkAttributes;
    }
}
