<?php


namespace Rebuild\Server;


class ServerFactory
{
    protected $serveConfig = [];
    protected $server = null;

    public function configure(array $configs) {
        $this->serveConfig = $configs;
        return $this;
    }

    public function getServer() {
        if (!isset($this->server)) {
            $this->server = new Server();
            $this->server->init($this->serveConfig);
        }
        return $this->server;
    }


}