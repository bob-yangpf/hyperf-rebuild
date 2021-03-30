<?php


namespace Rebuild\HttpServer;


use FastRoute\Dispatcher;
use http\Exception\InvalidArgumentException;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\Utils\Codec\Json;
use Hyperf\Utils\Context;
use Hyperf\Utils\Contracts\Arrayable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Rebuild\HttpServer\Router\Dispatched;
use Rebuild\HttpServer\Router\DispatherFactory;

class CoreMiddleware implements MiddlewareInterface
{



    protected $dispatcher;

    public function __construct(DispatherFactory $dispatchFactory)
    {
        $this->dispatcher = $dispatchFactory->getDispatcher('http');;
    }


    public function dispatch(ServerRequestInterface $request): ServerRequestInterface
    {
        $httpMethod = $request->getMethod();
        $uri = $request->getUri()->getPath();

        $routeInfo = $this->dispatcher->dispatch($httpMethod, $uri);
        $dispatched = new Dispatched($routeInfo);
        return Context::set(ServerRequestInterface::class, $request->withAttribute(Dispatched::class, $dispatched));
    }


    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $dispachted = $request->getAttribute(Dispatched::class);
        if (!$dispachted instanceof Dispatched) {
            throw new InvalidArgumentException('Route not found');
        }
        switch ($dispachted->status) {
            case Dispatcher::NOT_FOUND:
                $reponse = $this->handleNotFound($request);
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                $reponse = $this->handleNotAllowed($request);
                break;
            case Dispatcher::FOUND:
                $reponse = $this->handleFound($request, $dispachted);
                break;
        }
        if (!($reponse instanceof ResponseInterface)) {
            $reponse = $this->transferResponse($reponse);
        }

        return $reponse;
    }

    protected function handleNotFound(ServerRequestInterface $request)
    {
        /**
         * @var ResponseInterface $response
         */
        return $this->response()->withStatus(404)->withBody(new SwooleStream('Not Found'));
    }

    protected function handleNotAllowed(ServerRequestInterface $request)
    {
        /**
         * @var ResponseInterface $response
         */
        return $this->response()->withStatus(403)->withBody(new SwooleStream('Not Allow'));

    }

    protected function handleFound(ServerRequestInterface $request, Dispatched $dispatched)
    {
        [$controller, $action] = $dispatched->handler;
        $classInstance = new $controller();
        $params = [];
        return $classInstance->{$action}(...$params);
    }

    protected function transferResponse($response)
    {
        if (is_string($response)) {
            return $this->response()->withAddedHeader('Content-Type','text/plain')->withBody(new SwooleStream($response));
        } else if (is_array($response) || $response instanceof Arrayable) {
            return $this->response()->withAddedHeader('Content-Type','application/json')->withBody(new SwooleStream(Json::encode($response)));
        } else {
            return $this->response()->withAddedHeader('Content-Type','text/plain')->withBody(new SwooleStream(Json::encode($response)));
        }

    }

    protected function response() : ResponseInterface
    {
        return Context::get(ResponseInterface::class);
    }
}