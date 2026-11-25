<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Notifier\Bridge\GitHub\Tests;

use Symfony\Component\Notifier\Bridge\GitHub\GitHubTransportFactory;
use Symfony\Component\Notifier\Test\AbstractTransportFactoryTestCase;
use Symfony\Component\Notifier\Test\IncompleteDsnTestTrait;

/**
 * @author https://github.com/kevinonthebit
 */
final class GitHubTransportFactoryTest extends AbstractTransportFactoryTestCase
{
    use IncompleteDsnTestTrait;

    public function createFactory(): GitHubTransportFactory
    {
        return new GitHubTransportFactory();
    }

    public static function createProvider(): iterable
    {
        yield 'Default host.' => [
            'github://api.github.com?api_version=',
            'github://access_token@default',
        ];

        yield 'Provided host and API version.' => [
            'github://new.github.com?api_version=2026-03-10',
            'github://access_token@new.github.com?api_version=2026-03-10',
        ];
    }

    public static function supportsProvider(): iterable
    {
        yield [true, 'github://access_token@default'];
        yield [false, 'invalid://access_token@default'];
    }

    public static function incompleteDsnProvider(): iterable
    {
        yield ['github://@default', 'Invalid "github://default" notifier DSN: Access token is missing.'];
    }

    public static function unsupportedSchemeProvider(): iterable
    {
        yield ['invalid://access_token', 'The "invalid" scheme is not supported; supported schemes for notifier "github" are: "github"'];
    }
}
