<?php

/*
 * This file is part of xippogmbh/boot24-bundle.
 *
 * (c) Aurelio Gisler (Xippo GmbH)
 *
 * @license LGPL-3.0-or-later
 */

namespace XippoGmbH\Boot24Bundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use XippoGmbH\Boot24Bundle\XippoGmbHBoot24Bundle;

class Plugin implements BundlePluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create(XippoGmbHBoot24Bundle::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }
}
