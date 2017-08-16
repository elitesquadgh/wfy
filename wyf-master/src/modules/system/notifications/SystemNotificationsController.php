<?php
class SystemNotificationsController extends Controller
{
    public function get($params) 
    {
        ntentan\logger\Logger::info("Reading Notifications " . print_r($_SESSION['notifications'], true));
        Application::$template = false;
        header('Content-Type: application/json');
        $response = json_encode($_SESSION['notifications']);
        $_SESSION['notifications'] = array();
        return $response;
    }
}
