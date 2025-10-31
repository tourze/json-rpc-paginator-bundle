<?php

declare(strict_types=1);

namespace Tourze\JsonRPCPaginatorBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\JsonRPCPaginatorBundle\JsonRPCPaginatorBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(JsonRPCPaginatorBundle::class)]
#[RunTestsInSeparateProcesses]
final class JsonRPCPaginatorBundleTest extends AbstractBundleTestCase
{
}
