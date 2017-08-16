<?php
/*
 * Copyright (c) 2011 James Ekow Abaka Ainooson
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 * 
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 * 
 */


/**
 * The Controller class represents the base class for all controllers that are
 * built for the WYF framework. Controllers are used to direct the flow of
 * your application. They are stored in modules and they contain methods which
 * are called from the url. Parameters to the methods are also passed through the
 * URL. If no method is specified, the Controller:getContents() method is called.
 * The methods called by the controllers are expected to either generate HTML output
 * which should be directly embeded into the app or they can return an array containing
 * data and a path to an appropriate smarty template to be used for the rendering
 * of the output.
 *
 * All the controllers you build must extend this class.
 *
 * @author James Ekow Abaka Ainooson <jainooson@gmail.com>
 *
 */
class Controller
{
    /**
     * Check if this controller is supposed to be shown in any menus that are
     * created. This property is usually false for modules which are built for
     * internal use within the application.
     * @var boolean
     */
    protected $_showInMenu = false;

    /**
     * A piece of text which briefly describes this controller. These 
     * descriptions are normally rendered as part of the views. 
     * @var string
     */
    public $description;

    /**
     * A variable which contains the contents of a given controller after a
     * particular method has been called. This property is what different
     * controllers use when interfacing with each other.
     * 
     * @var string
     */
    public $content;

    /**
     * This constant represents controllers that are loaded from modules
     * @var string
     */
    const TYPE_MODULE = "module";
    
    /**
     * The constant represents controllers that are loaded from raw classes.
     * @var string
     */
    const TYPE_CLASS = "class";

    /**
     * This constant represents controllers that are loaded from models.
     * @var string
     */
    const TYPE_MODEL = "model";

    /**
     * This constant represents controllers that are loaded from report.xml
     * files
     * @var string
     */
    const TYPE_REPORT = "report";

    /**
     * A copy of the path that was used to load this controller in an array
     * form.
     * @var Array
     */
    public $path;

    /**
     * A short machine readable name for this label.
     * @var string
     */
    public $name;
    
    /**
     * Tells whether the model has been redirected or not.
     * @var boolean
     */
    public $redirected;
    
    /**
     * A path to redirect controllers to. 
     * @warning This value is automatically set when
     *      redirections are done. They should never be modified unless the
     *      developpers realy know what they are doing.
     * @var string
     */
    public $redirectPath;
    
    /**
     * The new package path to use for redirected packages.
     * @var string
     */
    public $redirectedPackage;
    
    public $mainRedirectedPackage;
    
    public $redirectedPackageName;
    
    private static $templateEngine;
    
    protected $actionMethod;
    
    /**
     * A utility method to load a controller. This method loads the controller
     * and fetches the contents of the controller into the Controller::$contents
     * variable if the get_contents parameter is set to true on call. If a controller
     * doesn't exist in the module path, a ModelController is loaded to help
     * manipulate the contents of the model. If no model exists in that location,
     * it is asumed to be a package and a package controller is loaded.
     *
     * @param $path         The path for the model to be loaded.
     * @param $getContents A flag which determines whether the contents of the
     *                        controller should be displayed.
     * @return Controller
     */
    public static function load($path,$getContents=true, $actionMethod = null)
    {
        global $redirectedPackage;
        global $redirectPath;
        global $packageSchema;
        
        $controller_path = "";
        $controller_name = "";
        $redirected = false;
        $redirect_path = "";
        $package_name = "";
        $package_main = "";

        //Go through the whole path and build the folder location of the system
        $pathLenghtj = count($path);
        for($i = 0; $i<count($path); $i++)
        {
            $p = $path[$i];
            $baseClassName = $package_name . Application::camelize("$controller_path/$p", "/");
            
            if(file_exists(SOFTWARE_HOME . "app/modules/$controller_path/$p/{$baseClassName}Controller.php"))
            {
                $controller_class_name = $baseClassName . "Controller";
                $controller_name = $p;
                $controller_path .= "/$p";
                $controller_type = Controller::TYPE_MODULE;
                add_include_path("app/modules/$controller_path/");
                break;
            }
            else if(file_exists(SOFTWARE_HOME . "app/modules/$controller_path/$p/{$baseClassName}Model.php"))
            {
                $controller_name = $p;
                $controller_path .= "/$p";
                $controller_type = Controller::TYPE_MODEL;
                break;
            }
            else if(file_exists(SOFTWARE_HOME . "app/modules/$controller_path/$p/model.xml"))
            {
                $controller_name = $p;
                $controller_path .= "/$p";
                $controller_type = Controller::TYPE_MODEL;
                break;
            }
            else if(file_exists(SOFTWARE_HOME . "app/modules/$controller_path/$p/report.xml"))
            {
                $controller_name = $p;
                $controller_path .= "/$p";
                $controller_type = Controller::TYPE_REPORT;
                break;
            }
            else if(file_exists(SOFTWARE_HOME . "app/modules/$controller_path/$p/package_redirect.php"))
            {
                include(SOFTWARE_HOME . "app/modules/$controller_path/$p/package_redirect.php");
                $redirected = true;
                $previousControllerPath = $controller_path . "/$p"; 
                $controller_path = "";
                $redirectedPackage = $package_path;
                $packageSchema = $package_schema;
                $redirectPath = $redirect_path;
            }
            else if($redirected === true && file_exists(SOFTWARE_HOME . "$redirect_path/$controller_path/$p/{$baseClassName}Controller.php"))
            {
                $controller_class_name = $baseClassName . "Controller";
                $controller_name = $p;
                $controller_path .= "/$p";
                $controller_type = Controller::TYPE_MODULE;
                $package_main .= $p;
                add_include_path("$redirect_path/$controller_path/");
                break;
            }
            else if($redirected === true && file_exists(SOFTWARE_HOME . "$redirect_path/$controller_path/$p/report.xml"))
            {
                $controller_name = $p;
                $controller_path .= "/$p";
                $controller_type = Controller::TYPE_REPORT;
                break;
            }            
            else
            {
                $controller_path .= "/$p";
                if($redirected) 
                {
                    $package_main .= "$p."; 
                }
            }
        }

        // Check the type of controller and load it.
        switch($controller_type)
        {
            case Controller::TYPE_MODULE:
                // Load a module controller which would be a subclass of this
                // class
                $controller_name = $controller_class_name;
                $controller = new $controller_class_name();
                $controller->redirected = $redirected;
                $controller->redirectPath = $redirect_path;
                $controller->redirectedPackage = $package_path;
                $controller->mainRedirectedPackage = $package_main;
                $controller->redirectedPackageName = $package_name;
                break;

            case Controller::TYPE_MODEL;
                // Load the ModelController wrapper around an existing model class.
                $model = substr(str_replace("/",".",$controller_path),1);
                $controller_name = "ModelController";
                $controller = new ModelController($model, $package_path);
                break;
                
            case Controller::TYPE_REPORT:
                $controller = new XmlDefinedReportController($redirect_path . $controller_path."/report.xml", $redirected);
                $controller_name = "XmlDefinedReportController";
                break;

            default:
                // Load a directory handler class for directories
                if(is_dir("app/modules$controller_path"))
                {
                    $directoryHandlerClass = Application::getDirectoryHandler();
                    $controller = new $directoryHandlerClass($path);
                }
                else if($redirected === true && is_dir(SOFTWARE_HOME . "$redirect_path/$controller_path"))
                {
                    $directoryHandlerClass = Application::getDirectoryHandler();                    
                    $controller = new $directoryHandlerClass($path);
                }
                else
                {
                    $controller = new ErrorController();
                }
                $controller->actionMethod = 'getContents';
        }

        // If the get contents flag has been set return all the contents of this
        // controller.
        $controller->path = $previousControllerPath . $controller_path;
        
        if($getContents)
        {
            if($i == count($path)-1)
            {
                $controller->actionMethod = 'getContents';
            }
            else if($controller->actionMethod === null)
            {
                $controller->actionMethod = $path[$i+1];
            }
            
            if(method_exists($controller, $controller->actionMethod))
            {
                $controller_class = new ReflectionClass($controller->getClassName());
                $method = $controller_class->GetMethod($controller->actionMethod);
                $ret = $method->invoke($controller,array_slice($path,$i+2));
            }
            else
            {
                $ret = "<h2>Error</h2> Method does not exist. [" . $controller->actionMethod . "]";
            }
            
            
            if(is_array($ret))
            {
                $t = self::getTemplateEngine();
                $t->assign('controller_path', $controller_path);
                $t->assign($ret["data"]);
                $controller->content = $t->fetch(
                    isset($ret["template"]) ? $ret["template"] : $this->actionMethod . ".tpl"
                );
            }
            else if(is_string($ret))
            {
                $controller->content = $ret;
            }
        }
        else if($actionMethod !== null)
        {
            $controller->actionMethod = $actionMethod;
        }
        
        return $controller;
    }
    
    public static function getTemplateEngine()
    {
        if(!is_object(self::$templateEngine))
        {
            self::$templateEngine = new TemplateEngine();
        }
        return self::$templateEngine;
    }

    /**
     * The getContents method is the default point of call for any controller.
     * Every controller should override this method. The default method just
     * returns the string "No Content"
     *
     * @return string
     */
    public function getContents()
    {
        return "<h2>Ooops! No Content</h2><p>Create a <b><code>" . $this->getClassName() . ".getContents()</code></b> method to provide default content for this controller.";
    }

    /**
     * Getter for the Controller::_showInMenu method
     * @return boolean
     */
    public function setShowInMenu($value)
    {
        return $this->_showInMenu = $value;
    }
    
    public function getShowInMenu()
    {
        return $this->_showInMenu;
    }
    
    /**
     * An empty implementation of the getPermissions method
     */
    public function getPermissions()
    {

    }
    
    /**
     * Returns an array description to be used for rendering the smarty template.
     * This method expects the template file to exist in the same directory
     * as the controller class. Also note that when specifying the name of the
     * template file the .tpl extension should not be specified. For exampple to
     * load a template called send_mail.tpl from a particular controller...
     * 
     * @code
     * ..
     * $this->template('send_mail', $mailData);
     * ..
     * @endcode
     * 
     * @param string $template The name of the template file which exists in the
     *      same directory as the controller. The file name must have a .tpl
     *      extension which should not be specified.
     * @param array $data A structured array which contains the data to be
     *      sent to the view.
     */
    public function template($template, $data)
    {
        return array(
           "template"=>"file:/" . getcwd() . "/app/modules/{$this->path}/{$template}.tpl", 
           "data"=>$data
        );
    }
    
    /**
     * 
     * @param type $arbitraryTemplate
     * @param type $data
     * @return type
     */
    public function arbitraryTemplate($arbitraryTemplate, $data, $absolute = false)
    {
        return array(
            'template' => "file:/".($absolute ? '' : SOFTWARE_HOME . '/')."$arbitraryTemplate",
            'data' => $data        
        );
    }
    
    /**
     * A utility method which draws an attribute table.
     * @param unknown_type $attributes
     */
    public function getAttributeTable($attributes)
    {
        $ret = "<table width='100%'>";
        foreach($attributes as $key => $value)
        {
            $ret .= "<tr><td><b>".ucwords(str_replace("_", " ", $key))."</b></td><td>{$value}</td></tr>";
        }
        $ret .= "</table>";
        return $ret;
    }
    
    /**
     * Returns the name of the controller class. This method is very useful when
     * specifying callbacks from a Form to a static method in a controller.
     * 
     * @code
     * class SomeController extends Controller
     * {
     *     public function someFormAction()
     *     {
     *         $form = new Form();
     *         $form->add(
     *             Element::create('TextField', 'Username', 'username'),
     *             Element::create('PasswordField', 'Password', 'password')
     *         );
     *         $form->setCallback($this->getClassName() . '::doLogin', null);
     *     }
     * 
     *     public static function doLogin($data, &$form, $callback)
     *     {
     *         // Use this for the login logic
     *     }
     * }
     * @endcode
     * 
     * @return
     */
    public function getClassName()
    {
        $objectInfo = new ReflectionObject($this);
        return $objectInfo->getName();
    }
    
    public function setLabel($label)
    {
        Application::setTitle($label);
        $this->label = $label;
    }
    
    public function getLabel()
    {
        return $this->label;
    }
    
    public function setActionMethod($actionMethod)
    {
        $this->actionMethod = $actionMethod;
    }
    
    public function __get($name) 
    {
        switch($name)
        {
            case 'label': return $this->getLabel();
        }
    }
    
    public function __set($name, $value)
    {
        switch($name)
        {
            case 'label': $this->setLabel($value); return;
        }
    }
}
