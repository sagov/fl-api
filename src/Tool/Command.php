<?php

namespace FL\Api\Tool;

use FL\Api\Tool\Command\Log\Consumer;
use FL\Api\Tool\Command\CreateModule;
use FL\Api\Tool\Command\Help;



class Command
{
    /**
     * Handle the CLI arguments.
     *
     * @param array $arguments
     * @return int
     */
    public function __invoke(array $arguments)
    {
        $help = new Help();

        // Called without arguments
        if (count($arguments) < 1) {
            fwrite(STDERR, 'No arguments provided.' . PHP_EOL . PHP_EOL);
            $help(STDERR);
            return 1;
        }

        // требуется настроить
        $config = include __DIR__.'/../../../../../config/autoload/fl-api.global.php';

        if (!isset($config['fl-api']['module'])) {
            fwrite(STDERR, 'No found config fl-api.global.php.' . PHP_EOL . PHP_EOL);
            $help(STDERR);
            return 1;
        }

        $path = (isset($config['fl-api']['module']['path'])) ? $config['fl-api']['module']['path'] : null;
        $namespace = (isset($config['fl-api']['module']['namespace'])) ? $config['fl-api']['module']['namespace'] : null;

        if (!is_dir($path)) {
            fwrite(STDERR, 'Path '.$path.' is not found' . PHP_EOL . PHP_EOL);
            $help(STDERR);
            return 1;
        }


        try {
            switch ($arguments[0]) {
                case '-h':
                case '--help':
                    $help();
                    return 0;
                    break;

                case '--add':
                    if (empty($arguments[1])) {
                        throw new \Exception('Module name is empty');
                    }

                    $createmodule = new CreateModule($path, $namespace);
                    $createmodule->createModule($arguments[1]);
                    fwrite(STDERR, 'The module was created successfully'. PHP_EOL . PHP_EOL);
                    return 1;
                    break;
                case '--log':
                    if (empty($arguments[1])) {
                        throw new \Exception('Module name is empty');
                    }
                    if ($arguments[1] != 'consumer'){
                        throw new \Exception('Command is not found');
                    }
                    switch ($arguments[2])
                    {
                        case 'start':
                            if (!isset($arguments[3])){
                                throw new \Exception('Topic is not found');
                            }
                            $consumer = new Consumer();
                            $consumer->setTopic($arguments[3]);
                            $consumer->start();
                            fwrite(STDERR, 'The consumer started'. PHP_EOL . PHP_EOL);
                            return 1;
                            break;
                        default:
                            throw new \Exception('Command is not found');
                            break;
                    }

                    break;
                default:
                    throw new \Exception('Command is not found');
                    break;
            }
        }catch (\Exception $exception){
            fwrite(STDERR, $exception->getMessage(). PHP_EOL . PHP_EOL);
            $help(STDERR);
            return 1;
        }
    }

}