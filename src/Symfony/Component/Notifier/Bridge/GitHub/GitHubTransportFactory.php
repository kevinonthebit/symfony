<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Notifier\Bridge\GitHub;

use Symfony\Component\Notifier\Exception\IncompleteDsnException;
use Symfony\Component\Notifier\Exception\UnsupportedSchemeException;
use Symfony\Component\Notifier\Transport\AbstractTransportFactory;
use Symfony\Component\Notifier\Transport\Dsn;

/**
 * @author https://github.com/kevinonthebit
 *
 * @see https://symfony.com/doc/current/mailer.html#custom-transport-factories
 */
final class GitHubTransportFactory extends AbstractTransportFactory
{
    private const string SUPPORTED_SCHEME = 'github';

    public function create(Dsn $dsn): GitHubTransport
    {
        $scheme = $dsn->getScheme();

        if (self::SUPPORTED_SCHEME !== $scheme) {
            throw new UnsupportedSchemeException($dsn, self::SUPPORTED_SCHEME, $this->getSupportedSchemes());
        }

        $accessToken = $this->getUser($dsn);
        $apiVersion = $dsn->getOption('api_version');
        $host = 'default' === $dsn->getHost() ? null : $dsn->getHost();

        return (new GitHubTransport($accessToken, $apiVersion, $this->client, $this->dispatcher))
            ->setHost($host)
            ->setPort($dsn->getPort())
            ->setSsl($this->getSsl($dsn));
    }

    protected function getSupportedSchemes(): array
    {
        return [self::SUPPORTED_SCHEME];
    }

    protected function getUser(Dsn $dsn): string
    {
        return $dsn->getUser() ?? throw new IncompleteDsnException('Access token is missing.', self::SUPPORTED_SCHEME.'://'.$dsn->getHost());
    }
}
