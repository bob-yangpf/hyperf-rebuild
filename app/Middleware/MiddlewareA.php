<?php


namespace App\Middleware;


use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\Utils\Context;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiddlewareA implements MiddlewareInterface
{

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        var_dump(get_class($this));
        $path = $request->getUri()->getPath();
        if (strpos($path, 'ypf') !== false) {
            return Context::get(ResponseInterface::class)->withBody(new SwooleStream('牛逼'));
        }
        $response = $handler->handle($request);
        $response = $response->withBody(new SwooleStream($response->getBody()->getContents() . '++A'));
        return $response;
    }
}