<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Util;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Util\PhpCompatUtil;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 */
class PhpVersionTest extends TestCase
{
    /**
     * @dataProvider phpVersionDataProvider
     */
    public function testUsesPhpPlatformFromComposerJsonFile(string $version, bool $expectedResult): void
    {
        $json = sprintf('{"platform-overrides": {"php": "%s"}}', $version);

        $mockFileManager = $this->createMock(FileManager::class);
        $mockFileManager
            ->expects(self::once())
            ->method('getRootDirectory')
            ->willReturn('/test')
        ;

        $mockFileManager
            ->expects(self::once())
            ->method('fileExists')
            ->with('/test/composer.lock')
            ->willReturn(true)
        ;

        $mockFileManager
            ->expects(self::once())
            ->method('getFileContents')
            ->with('/test/composer.lock')
            ->willReturn($json)
        ;

        $version = new PhpCompatUtil($mockFileManager);

        $result = $version->canUseAttributes();

        self::assertSame($expectedResult, $result);
    }

    public function phpVersionDataProvider(): \Generator
    {
        yield ['8', true];
        yield ['8.0', true];
        yield ['8.0.1', true];
        yield ['8RC1', true];
        yield ['8.1alpha1', true];
        yield ['8.0.0beta2', true];
        yield ['8beta', true];
        yield ['7', false];
        yield ['7.0', false];
        yield ['7.1.1', false];
        yield ['7.0.0RC3', false];
    }

    public function testFallBackToPhpVersionWithoutLockFile(): void
    {
        $mockFileManager = $this->createMock(FileManager::class);
        $mockFileManager
            ->expects(self::once())
            ->method('getRootDirectory')
            ->willReturn('/test')
        ;

        $mockFileManager
            ->expects(self::once())
            ->method('fileExists')
            ->with('/test/composer.lock')
            ->willReturn(false)
        ;

        $mockFileManager
            ->expects(self::never())
            ->method('getFileContents')
        ;

        $util = new PhpCompatUtilTestFixture($mockFileManager);

        $result = $util->getVersionForTest();

        self::assertSame(PHP_VERSION, $result);
    }

    public function testWithoutPlatformVersionSet(): void
    {
        $json = '{"platform-overrides": {}}';

        $mockFileManager = $this->createMock(FileManager::class);
        $mockFileManager
            ->expects(self::once())
            ->method('getRootDirectory')
            ->willReturn('/test')
        ;

        $mockFileManager
            ->expects(self::once())
            ->method('fileExists')
            ->with('/test/composer.lock')
            ->willReturn(true)
        ;

        $mockFileManager
            ->expects(self::once())
            ->method('getFileContents')
            ->with('/test/composer.lock')
            ->willReturn($json)
        ;

        $util = new PhpCompatUtilTestFixture($mockFileManager);

        $result = $util->getVersionForTest();

        self::assertSame(PHP_VERSION, $result);
    }
}

class PhpCompatUtilTestFixture extends PhpCompatUtil
{
    public function getVersionForTest(): string
    {
        return $this->getPhpVersion();
    }
}
