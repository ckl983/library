<?php

/**
 * Application扩展类
 *
 * @author
 * @copyright 2014-2018
 */

namespace WPLib\Mvc;

use Exception,
    Phalcon\Di,
    Phalcon\Events\Manager as EventsManager,
    Phalcon\Mvc\Application as PhalconApplication,
    WPLib\Events\ApplicationListener;

class Application extends PhalconApplication
{
    public function __construct(\Phalcon\DiInterface $dependencyInjector = null)
    {
        parent::__construct($dependencyInjector);

        $eventManager = $this->getDI()->getShared('eventsManager');
        $applicationListener = new ApplicationListener();
        $eventManager->attach('application', $applicationListener);

        $this->setEventsManager($eventManager);

        $dependencyInjector->set('application', $this, true);
    }

    public static function end($message = '')
    {
        if ($message) {
            echo $message;
        }

        $application = Di::getDefault()->getShared('application');
        if ($eventsManager = $application->getEventsManager()) {
            $controller = $application->getDI()->getShared('dispatcher')->getLastController();
            $eventsManager->fire("application:afterHandleRequest", $application, $controller);
            $eventsManager->fire("application:beforeSendResponse", $application, $controller);
        }
        exit;
    }
}
