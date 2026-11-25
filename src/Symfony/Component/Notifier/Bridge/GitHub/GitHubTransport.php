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

use Symfony\Component\Notifier\Exception\RuntimeException;
use Symfony\Component\Notifier\Exception\TransportException;
use Symfony\Component\Notifier\Exception\UnsupportedMessageTypeException;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Message\SentMessage;
use Symfony\Component\Notifier\Transport\AbstractTransport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @author https://github.com/kevinonthebit
 *
 * @see https://docs.github.com/en/rest/issues/issues?apiVersion=2026-03-10#create-an-issue
 */
final class GitHubTransport extends AbstractTransport
{
    protected const string HOST = 'api.github.com';

    public function __construct(
        #[\SensitiveParameter] private string $accessToken,
        private ?string $apiVersion = null,
        ?HttpClientInterface $client = null,
        ?EventDispatcherInterface $dispatcher = null,
    ) {
        parent::__construct($client, $dispatcher);
    }

    public function __toString()
    {
        return \sprintf('github://%s?api_version=%s', $this->getEndpoint(), $this->apiVersion);
    }

    public function supports(MessageInterface $message): bool
    {
        return $message instanceof ChatMessage && $message->getOptions() instanceof GitHubOptions;
    }

    protected function doSend(MessageInterface $message): SentMessage
    {
        if (!($message instanceof ChatMessage && $message->getOptions() instanceof GitHubOptions)) {
            throw new UnsupportedMessageTypeException(__CLASS__, ChatMessage::class, $message);
        }

        /** @var GitHubOptions $options */
        $options = $message->getOptions();
        $path = "/repos/{$options->getOwner()}/{$options->getRepository()}/issues";

        $payload = $options->toArray();
        $payload['title'] ??= $message->getSubject();

        $response = null;

        try {
            $response = $this->request('POST', $path, $payload);
            $statusCode = $response->getStatusCode();
            $data = $response->toArray(false);
        } catch (ExceptionInterface $e) {
            if (null !== $response) {
                throw new TransportException($e->getMessage(), $response, 0, $e);
            }

            throw new RuntimeException($e->getMessage(), 0, $e);
        }

        if (400 <= $statusCode) {
            $errorMessage = \sprintf('Unable to create the GitHub issue (status code: %s): %s.', $data['status'], $data['message']);

            if (422 === $statusCode && \array_key_exists('errors', $data)) {
                $relatedFields = array_map(static fn ($error) => "\"{$error['field']}\" (error code: {$error['code']})", $data['errors']);

                $errorMessage = \sprintf(
                    'Unable to create the GitHub issue (status code: %s): %s. Related fields: %s.',
                    $data['status'],
                    $data['message'],
                    implode(', ', $relatedFields)
                );
            }

            throw new TransportException($errorMessage, $response);
        }

        $sentMessage = new SentMessage($message, (string) $this);
        $sentMessage->setMessageId($data['id']);

        return $sentMessage;
    }

    private function request(string $method, string $path, array $payload = []): ResponseInterface
    {
        $url = \sprintf('%s://%s%s', $this->getHttpScheme(), $this->getEndpoint(), $path);

        $options = [
            /**
             * @see https://docs.github.com/en/rest/authentication/authenticating-to-the-rest-api?apiVersion=2026-03-10#about-authentication
             */
            'auth_bearer' => $this->accessToken,
            'headers' => ['Accept' => 'application/vnd.github.raw+json'],
            'json' => $payload,
        ];

        /**
         * @see https://docs.github.com/en/rest/about-the-rest-api/api-versions?apiVersion=2026-03-10#specifying-an-api-version
         */
        if ($this->apiVersion) {
            $options['headers']['X-GitHub-Api-Version'] = $this->apiVersion;
        }

        return $this->client->request($method, $url, $options);
    }
}
