<?php
/**
 * Toolbars
 * @defgroup Toolbars
 */

/**
 * A toolbar for orgarnizing tools within the application.
 * @ingroup Toolbars
 */
class Toolbar
{
    /**
     * The buttons found in the array
     * @var array
     */
    protected $buttons = array();

    public function __construct($buttons=array())
    {
        if($GLOBALS['toolbar_stylesheet'] === false)
        {
            Application::preAddStylesheet("css/toolbar.css", Application::getWyfHome("toolbar/"));
        }
        else
        {
            Application::preAddStylesheet($GLOBALS['toolbar_stylesheet']);
        }

        $this->buttons = $buttons;
    }

    public function add($button)
    {
        $this->buttons[] = $button;
    }

    public function addLinkButton($label,$link,$icon=null)
    {
        $button = new LinkButton($label,$link,$icon);
        $this->buttons[] = $button;
        return $button;
    }

    public function render()
    {
        $ret = "<ul class='toolbar'>";
        foreach($this->buttons as $button)
        {
            $ret .= "<li class='toolbar-toolitem ".implode(" ",$button->getCssClasses())."'>".$button->render()."</li>";
        }
        $ret .= "</ul>";
        return $ret;
    }
}