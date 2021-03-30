<?php


namespace Rebuild\HttpServer;


use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\MiddlewareInitializerInterface;
use Hyperf\HttpMessage\Server\Request as Psr7Request;
use Hyperf\HttpMessage\Server\Response as Psr7Response;
use Hyperf\HttpServer\Contract\CoreMiddlewareInterface;
use Rebuild\Dispatcher\HttpRequestHandler;
use Rebuild\HttpServer\MiddlewareManager;
use Hyperf\Utils\Context;
use Hyperf\Utils\Coordinator\Constants;
use Hyperf\Utils\Coordinator\CoordinatorManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rebuild\Config\ConfigFactory;
use Rebuild\HttpServer\Router\Dispatched;
use Rebuild\HttpServer\Router\DispatherFactory;
use Swoole\Http\Request;
use Swoole\Http\Response;

class Server implements MiddlewareInitializerInterface
{
    /**
     * @var array
     */
    protected $middlewares;

    /**
     * @var CoreMiddlewareInterface
     */
    protected $coreMiddleware;

    protected   $exceptionHandlers;

    protected $dispatcher;

    protected $globalMiddlewares;

    public function __construct(DispatherFactory $dispatchFactory)
    {
        $this->dispatcher = $dispatchFactory->getDispatcher('http');
        $this->coreMiddleware = new CoreMiddleware($dispatchFactory);
        $config = (new ConfigFactory())();
        $this->globalMiddlewares = $config->get('middlewares');
    }


    public function initCoreMiddleware(string $serverName): void
    {
        $this->serverName = $serverName;
        $config = (new ConfigFactory())();
        $this->middlewares = $config->get('middewares' , []);
    }

    public function onRequest(Request $request, Response $response) {
//
//        $response->header('Content-Type', 'text/html; charset=utf-8');
//        $response->end('hello rebuild');

        try {
            /**
             * @var Psr7Request $psr7Request
             * @var Psr7Response $psr7Response
             */
            [$psr7Request, $psr7Response] = $this->initRequestAndResponse($request, $response);

            $httpMethod = $psr7Request->getMethod();
            $path = $psr7Request->getUri();

            $psr7Request = $this->coreMiddleware->dispatch($psr7Request);
            /** @var Dispatched $dispatched */
            $dispatched = $psr7Request->getAttribute(Dispatched::class);
            $middlewares = $this->middlewares;
            if ($dispatched->isFound()) {
                $registeredMiddlewares = MiddlewareManager::get($path, $httpMethod);
                if ($registeredMiddlewares) {
                    $middlewares = array_merge($middlewares, $registeredMiddlewares);
                }
            }
            $requestHandler = new HttpRequestHandler($middlewares, $this->coreMiddleware);
            $psr7Response = $requestHandler->handle($psr7Request);
            $response->status($psr7Response->getStatusCode());
            $response->end($psr7Response->getBody()->getContents());
//            $psr7Response = $this->dispatcher->dispatch($psr7Request, $middlewares, $this->coreMiddleware);
        } catch (Throwable $throwable) {
            // Delegate the exception to exception handler.
            $psr7Response = $this->exceptionHandlerDispatcher->dispatch($throwable, $this->exceptionHandlers);
        } finally {
//            // Send the Response to client.
//            if (! isset($psr7Response)) {
//                return;
//            }
//            if (isset($psr7Request) && $psr7Request->getMethod() === 'HEAD') {
//                $this->responseEmitter->emit($psr7Response, $response, false);
//            } else {
//                $this->responseEmitter->emit($psr7Response, $response, true);
//            }
        }
    }

    /**
     * Initialize PSR-7 Request and Response objects.
     * @param mixed $request swoole request or psr server request
     * @param mixed $response swoole response or swow session
     */
    protected function initRequestAndResponse($request, $response): array
    {
        Context::set(ResponseInterface::class, $psr7Response = new Psr7Response());

        if ($request instanceof ServerRequestInterface) {
            $psr7Request = $request;
        } else {
            $psr7Request = Psr7Request::loadFromSwooleRequest($request);
        }

        Context::set(ServerRequestInterface::class, $psr7Request);
        return [$psr7Request, $psr7Response];
    }

}