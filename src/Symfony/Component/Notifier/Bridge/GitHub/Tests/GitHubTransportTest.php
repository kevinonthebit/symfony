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

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Mime\RawMessage;
use Symfony\Component\Notifier\Bridge\GitHub\GitHubOptions;
use Symfony\Component\Notifier\Bridge\GitHub\GitHubTransport;
use Symfony\Component\Notifier\Exception\TransportException;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\DesktopMessage;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Message\NullMessage;
use Symfony\Component\Notifier\Message\PushMessage;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\Test\TransportTestCase;
use Symfony\Component\Notifier\Tests\Transport\DummyMessage;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author https://github.com/kevinonthebit
 */
final class GitHubTransportTest extends TransportTestCase
{
    public static function createTransport(?HttpClientInterface $client = null, ?string $apiVersion = null, ?string $host = null): GitHubTransport
    {
        return new GitHubTransport('access_token', $apiVersion, $client ?? new MockHttpClient())->setHost($host);
    }

    public static function toStringProvider(): iterable
    {
        yield ['github://api.github.com?api_version=', self::createTransport()];
        yield ['github://new.github.com?api_version=2026-03-10', self::createTransport(null, '2026-03-10', 'new.github.com')];
    }

    public static function supportedMessagesProvider(): iterable
    {
        yield [new ChatMessage('Foo', new GitHubOptions('bar', 'baz'))];
    }

    public static function unsupportedMessagesProvider(): iterable
    {
        yield [new ChatMessage('Foo')];
        yield [new DesktopMessage('Foo', 'Bar')];
        yield [new DummyMessage()];
        yield [new EmailMessage(new RawMessage('Foo'))];
        yield [new NullMessage(new DummyMessage())];
        yield [new PushMessage('Foo', 'Bar')];
        yield [new SmsMessage('Foo', 'Bar')];
    }

    #[DataProvider('optionsProvider')]
    public function testSend(GitHubOptions $gitHubOptions)
    {
        $client = new MockHttpClient(function (string $method, string $url, array $options) use ($gitHubOptions) {
            $this->assertSame('POST', $method);
            $this->assertSame('https://api.github.com/repos/acme/e-commerce/issues', $url);

            $headers = $options['headers'];
            $this->assertContains('Authorization: Bearer access_token', $headers);
            $this->assertContains('Accept: application/vnd.github.raw+json', $headers);
            $this->assertContains('Content-Type: application/json', $headers);
            $this->assertContains('X-GitHub-Api-Version: 2026-03-10', $headers);

            $expectedBody = $gitHubOptions->toArray();
            $expectedBody['title'] ??= 'Incident';

            $actualBody = json_decode($options['body'], true);
            $this->assertArraysAreEqualIgnoringOrder($expectedBody, $actualBody);

            return new MockResponse('{"id":"1"}');
        });

        $transport = $this->createTransport($client, '2026-03-10');
        $sentMessage = $transport->send(new ChatMessage('Incident', $gitHubOptions));

        $this->assertSame('1', $sentMessage->getMessageId());
    }

    public static function optionsProvider(): iterable
    {
        yield 'With options.' => [
            (new GitHubOptions('acme', 'e-commerce'))
                ->setTitle('Payment service unavailable')
                ->setBody('Logs')
                ->setStatus('Todo')
                ->setMilestone('2.0')
                ->setLabels(['Payment'])
                ->setAssignees(['foo'])
                ->setIssueFieldValues([['field_id' => 1, 'value' => 'Bar']])
                ->setType('Incident')
                ->setParentIssueId(2)
        ];

        yield 'Without options.' => [new GitHubOptions('acme', 'e-commerce')];
    }

    #[DataProvider('errorProvider')]
    public function testSendWithErrorResponseThrowsTransportException(int $statusCode, ?array $errors = [])
    {
        $body = ['message' => 'Foo', 'status' => $statusCode];
        if ($errors) {
            $body['errors'] = $errors;
        }

        $client = new MockHttpClient(new MockResponse(json_encode($body), ['http_code' => $statusCode]));
        $transport = $this->createTransport($client);

        $relatedFields = array_map(static fn ($error) => "\"{$error['field']}\" (error code: {$error['code']})", $errors);
        $expectedMessage = $errors
            ? "Unable to create the GitHub issue (status code: {$statusCode}): Foo. Related fields: ".implode(', ', $relatedFields).'.'
            : "Unable to create the GitHub issue (status code: {$statusCode}): Foo.";

        $this->expectException(TransportException::class);
        $this->expectExceptionMessageIs($expectedMessage);

        $options = new GitHubOptions('foo', 'bar');
        $transport->send(new ChatMessage('Baz', $options));
    }

    public static function errorProvider(): iterable
    {
        yield 'Bad Request.' => [400];
        yield 'Unauthorized.' => [401];
        yield 'Forbidden.' => [403];
        yield 'Resource not found.' => [404];
        yield 'Issues are disabled in the repository.' => [410];
        yield 'Validation failed, or the endpoint has been spammed.' => [422];
        yield 'Validation failed with multiple errors.' => [422, [['value' => 1, 'resource' => 'Issue', 'field' => 'type', 'code' => 'invalid']]];
        yield 'Service unavailable.' => [503];
    }
}
