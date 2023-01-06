<?php

/*
 * This file is part of xippogmbh/boot24-bundle.
 *
 * (c) Aurelio Gisler (Xippo GmbH)
 *
 * @license LGPL-3.0-or-later
 */

namespace XippoGmbH\Boot24Bundle\Tests;

use XippoGmbH\Boot24Bundle\XippoGmbHBoot24Bundle;
use PHPUnit\Framework\TestCase;

class XippoGmbHBoot24BundleTest extends TestCase
{
    public function testCanBeInstantiated()
    {
        $bundle = new XippoGmbHBoot24Bundle();

        $this->assertInstanceOf('XippoGmbH\Boot24Bundle\XippoGmbHBoot24Bundle', $bundle);
    }
}
