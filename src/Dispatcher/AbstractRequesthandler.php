<?php

namespace Rebuild\Dispatcher;

use Hyperf\Utils\Exception\InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AbstractRequesthandler implements RequestHandlerInterface
{
    protected  $offset = 0;

    protected  $middlewares = [];

    protected $coreHandler;

    public function __construct(array $middlewares, MiddlewareInterface $coreHandler)
    {
        $this->middlewares = $middlewares;
        $this->coreHandler = $coreHandler;
    }


    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!isset($this->middlewares[$this->offset]) && $this->coreHandler) {
            $handler = $this->coreHandler;
        } else {
            $handler = $this->middlewares[$this->offset];
            is_string($handler) && $handler = new $handler($request, $this);
        }
        if (!method_exists($handler, 'process')) {
            throw new InvalidArgumentException('Invalid middleware, '. get_class($handler) .' has to provide a process method');
        }
        return $handler->process($request, $this->next());

    }

    public function next() : self
    {
        ++$this->offset;
        return $this;
    }
}