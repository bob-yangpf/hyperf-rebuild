<?php

namespace Rebuild\HttpServer\Router;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

class DispatherFactory
{
    /**
     * @var array
     */
    protected $routeFiles = [BASE_PATH . '/config/routes.php'];

    protected $routes = [];

    protected $dispatchers = [];

    public function __construct()
    {
        $this->initConfigRoute();
    }


    public function getDispatcher(string $serverName) : Dispatcher
    {
        if (!isset($this->dispatchers[$serverName])) {
            $this->dispatchers[$serverName] = simpleDispatcher(function (RouteCollector $r) {
                foreach ($this->routes as $route) {
                    [$httpMethod, $path, $handler] = $route;
                    $r->addRoute($httpMethod, $path, $handler);
                }
            });
        }
        return $this->dispatchers[$serverName];
    }

    public function initConfigRoute() {
        foreach ($this->routeFiles as $file) {
            if (file_exists($file)) {
                $routes = require_once $file;
                if ($routes && is_array($routes)) {
                    $this->routes = array_merge_recursive($this->routes, $routes);
                }
            }
        }
    }

}