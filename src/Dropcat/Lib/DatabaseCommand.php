<?php


namespace Dropcat\Lib;

use Dropcat\Services\DatabaseService;
use Dropcat\Services\DropcatConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class DatabaseCommand
 * DropcatCommand with DatabaseService injected.
 * @package Dropcat\Lib
 */
class DatabaseCommand extends DropcatCommand
{
    /**
     * @var DatabaseService
     */
    protected $databaseService;

    public function __construct(ContainerBuilder $container, DropcatConfigurationInterface $conf, DatabaseService $databaseService)
    {
        parent::__construct($container, $conf);
        $this->databaseService = $databaseService;
    }
}