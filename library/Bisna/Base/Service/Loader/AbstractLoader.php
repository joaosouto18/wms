<?php

namespace Bisna\Base\Service\Loader;

/**
 * AbstractLoader class.
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
abstract class AbstractLoader
{
    /**
     * @var Bisna\Base\Service\ServiceLocator
     */
    protected $locator;

    /**
     * Constructor.
     *
     * @param Bisna\Base\Service\ServiceLocator $locator ServiceLocator
     */
    public function __construct(\Bisna\Base\Service\ServiceLocator $locator)
    {
        $this->locator = $locator;
    }
}
