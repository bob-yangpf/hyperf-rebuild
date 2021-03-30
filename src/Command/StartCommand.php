<?php


namespace Rebuild\Command;


use Rebuild\Server\ServerFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StartCommand extends Command
{
    protected $config = [];

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param array $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    protected function configure()
    {
        $this->setName('start')->setDescription('启动服务');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
//        $http = new \Swoole\Http\Server('0.0.0.0', 9501);
//
//        $http->on('Request', function ($request, $response) {
//            $response->header('Content-Type', 'text/html; charset=utf-8');
//            $response->end('<h1>Hello rebuild</h1>');
//        });
//
//        $http->start();
        $server = new ServerFactory();
        $httpserver = $server->configure($this->config)->getServer();
        $httpserver->start();
        return 1;
    }


}