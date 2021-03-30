<?php


namespace Rebuild\Server;


use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\MiddlewareInitializerInterface;
use Hyperf\HttpServer\Contract\CoreMiddlewareInterface;
use Hyperf\HttpServer\CoreMiddleware;
use Hyperf\Server\ServerConfig;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Rebuild\HttpServer\Router\DispatherFactory;
use Swoole\Http\Server as SwooleHttpServer;
use Swoole\Server as SwooleServer;

class Server implements ServerInterface, MiddlewareInitializerInterface
{
    protected $server = null;



    public function __construct()
    {
    }


    public function initCoreMiddleware(string $serverName): void
    {
        $this->serverName = $serverName;
        $this->coreMiddleware = $this->createCoreMiddleware();
        $this->routerDispatcher = $this->createDispatcher($serverName);

        $config = $this->container->get(ConfigInterface::class);
        $this->middlewares = $config->get('middlewares.' . $serverName, []);
        $this->exceptionHandlers = $config->get('exceptions.handler.' . $serverName, $this->getDefaultExceptionHandler());
    }

    public function init(array $config): ServerInterface
    {
        foreach ($config['servers'] as $server) {
            $this->server = new SwooleHttpServer($server['host'], $server['port'], $server['type'], $server['sock_type']);
            $this->registerSwooleEvents($server['callbacks']);
            break;
        }
        return $this;
    }

    public function start()
    {
        $this->getServer()->start();
    }

    /**
     * @inheritDoc
     */
    public function getServer()
    {
        return $this->server;
    }

    protected function registerSwooleEvents(array $callbacks) {
        foreach ($callbacks as $swooleEvent => $callback) {
            [$class, $method] = $callback;
            if ($class == \Rebuild\HttpServer\Server::class) {
                $callbackClass = new $class(new DispatherFactory());
            } else {
                $callbackClass = new $class;
            }
            if ($callbackClass instanceof MiddlewareInitializerInterface) {
                $callbackClass->initCoreMiddleware('http');;
            }
            $this->server->on($swooleEvent, [$callbackClass, $method]);
        }
    }

    protected function createCoreMiddleware(): CoreMiddlewareInterface
    {
        return make(CoreMiddleware::class, [$this->container, $this->serverName]);
    }

}