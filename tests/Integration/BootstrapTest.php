<?php

declare(strict_types=1);

namespace GatewaysSdk\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Baseline integration suite test.
 */
class BootstrapTest extends TestCase
{
	public function testIntegrationEnvironmentIsReady(): void
	{
		$this->assertTrue(extension_loaded('curl'));
		$this->assertTrue(extension_loaded('json'));
		$this->assertTrue(extension_loaded('mbstring'));
	}
}
