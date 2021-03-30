<?php

use Psr\Http\Message\ServerRequestInterface;

interface CoreMiddlewareInterface {
    public function dispatch(ServerRequestInterface $request): ServerRequestInterface;

}
