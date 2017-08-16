<?php
require "app/config.php";
define("SOFTWARE_HOME", $config['home']);
require "coreutils.php";

// Setup the database driver and other boilerplate stuff 
$dbDriver = $config['db'][$selected]['driver'];
$dbDriverClass = Application::camelize($dbDriver);
add_include_path("lib/models/datastores/databases/$dbDriver");
Db::$defaultDatabase = $selected;
SQLDBDataStore::$activeDriver = $dbDriver;
SQLDBDataStore::$activeDriverClass = $dbDriverClass;

Cache::init($config['cache']['method']);
define('CACHE_MODELS', $config['cache']['models']);
define('CACHE_PREFIX', "");
define('ENABLE_AUDIT_TRAILS', $config['audit_trails']);

Application::$config = $config;
Application::$prefix = $config['prefix'];

Application::$templateEngine = new TemplateEngine();

if(Application::$config['custom_sessions'])
{
    $handler = Sessions::getHandler();
    session_set_save_handler
    (
        array($handler, 'open'), 
        array($handler, 'close'), 
        array($handler, 'read'), 
        array($handler, 'write'), 
        array($handler, 'destroy'), 
        array($handler, 'gc')
    );
    register_shutdown_function('session_write_close');
}

Application::setSiteName(Application::$config['name']);
//ntentan\logger\Logger::init('app/logs/application.log');

// Add the script which contains the third party libraries
require "app/includes.php";

require SOFTWARE_HOME . "app/bootstrap.php";

