<?php


namespace Dropcat\Lib;

use Dropcat\Services\DropcatConfigurationInterface;
use Dropcat\Services\NWrapperService;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class nCommand
 * DropcatCommand with NvmWrapperService injected.
 * @package Dropcat\Lib
 */
class NCommand extends DropcatCommand
{
    /**
     * @var NWrapperService
     */
    protected NWrapperService $nService;

    public function __construct(ContainerBuilder $container, DropcatConfigurationInterface $conf, NWrapperService $nWrapperService)
    {
        parent::__construct($container, $conf);
        $this->nService = $nWrapperService;
    }
}