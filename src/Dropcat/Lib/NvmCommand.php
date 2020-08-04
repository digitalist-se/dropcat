<?php


namespace Dropcat\Lib;

use Dropcat\Services\DropcatConfigurationInterface;
use Dropcat\Services\NvmWrapperService;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class NvmCommand
 * DropcatCommand with NvmWrapperService injected.
 * @package Dropcat\Lib
 */
class NvmCommand extends DropcatCommand
{
    /**
     * @var NvmWrapperService
     */
    protected $nvmService;

    public function __construct(ContainerBuilder $container, DropcatConfigurationInterface $conf, NvmWrapperService $nvmWrapperService)
    {
        parent::__construct($container, $conf);
        $this->nvmService = $nvmWrapperService;
    }
}