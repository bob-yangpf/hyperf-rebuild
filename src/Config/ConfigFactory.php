<?php


namespace Rebuild\Config;


use Symfony\Component\Finder\Finder;

class ConfigFactory
{
    public function __invoke()
    {
        $basePath = BASE_PATH . DIRECTORY_SEPARATOR . 'config';
        $config = $this->readConfig($basePath . DIRECTORY_SEPARATOR . 'config.php');
        $autoloads = $this->readPath($basePath . DIRECTORY_SEPARATOR . 'autoload');
        $config = array_replace_recursive($config,$autoloads);
        return new Config($config);
    }

    protected function readConfig($string):array
    {
        $config = require $string;
        if (!is_array($config)) {
            return [];
        }
        return $config;
    }

    protected function readPath($dirs) {
        $config = [];
        $finder = new Finder();
        $finder->files()->in($dirs)->name('*.php');
        foreach ($finder as $fileInfo) {
            $key = $fileInfo->getBasename('.php');
            $path = $fileInfo->getRealPath();
            if (!$path) {
                continue;
            }
            $value = require $path;
            $config[$key] = $value;
        }
        return $config;
    }


}