<?php
/**
 * WPLib\Console\Application
 *
 * @author
 * @copyright 2014-2018
 */

namespace WPLib\Console;

use Exception,
    Phalcon\Di,
    Phalcon\Events\Manager as EventsManager,
    Phalcon\CLI\Console as ConsoleApplication,
    WPLib\Events\ApplicationListener;

class Application extends ConsoleApplication
{
    public function __construct(\Phalcon\DiInterface $dependencyInjector = null)
    {
        parent::__construct($dependencyInjector);

        $eventManager = new EventsManager();
        $applicationListener = new ApplicationListener();
        $eventManager->attach('console', $applicationListener);

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
            $task = $application->getDI()->getShared('dispatcher')->getLastTask();
            $eventsManager->fire("console:beforeHandleTask", $application, $task);
            $eventsManager->fire("console:afterHandleTask", $application, $task);
        }
        exit;
    }
}
