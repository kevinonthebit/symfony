# GitHub Notifier

Provides [GitHub REST API for issues](https://docs.github.com/en/rest/issues/issues?apiVersion=2026-03-10#create-an-issue) integration for Symfony Notifier, as a `Chatter` transport.

## Configuration

```bash
# .env
GITHUB_DSN=github://ACCESS_TOKEN@default?api_version=2026-03-10
```

| Parameter      | Required | Description                                                                                                                                                                                                                                                                                              |
| -------------- | -------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `ACCESS_TOKEN` | ✅        | [Authentication token](https://docs.github.com/en/rest/authentication/authenticating-to-the-rest-api?apiVersion=2026-03) required by the GitHub REST API.<br>Supported types are:<br>- GitHub App user access token,<br>- GitHub App installation access token,<br>- fine-grained personal access token. |
| `api_version`  | ❌        | GitHub REST [API version](https://docs.github.com/en/rest/about-the-rest-api/api-versions?apiVersion=2026-03-10#specifying-an-api-version).                                                                                                                                                              |

```yaml
# config/packages/notifier.yaml
framework:
    notifier:
        chatter_transport:
            github: '%env(GITHUB_DSN)%'
```

## Usage

```php
use Symfony\Component\Notifier\Bridge\GitHub\GitHubOptions;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Message\ChatMessage;

$message = new ChatMessage(subject: 'Incident');

$options = (new GitHubOptions(owner: 'acme', repository: 'e-commerce'))
    // You can overwrite the issue title set by the `ChatMessage` subject.
    // ->setTitle('Payment service unavailable')
    ->setBody('Logs')
    ->setStatus('Todo')
    ->setMilestone('2.0')
    ->setLabels(['Payment'])
    ->setAssignees(['foo'])
    ->setIssueFieldValues([['field_id' => 1, 'value' => 'Bar']])
    ->setType('Incident')
    ->setParentIssueId(2);

$message->options($options);

/** @var ChatterInterface $chatter */
$chatter->send($message);
```

Sponsor
-------

This package is looking for a [backer][1].

Help Symfony by [sponsoring][3] its development!

Resources
---------

 * [Contributing](https://symfony.com/doc/current/contributing/index.html)
 * [Report issues](https://github.com/symfony/symfony/issues) and
   [send Pull Requests](https://github.com/symfony/symfony/pulls)
   in the [main Symfony repository](https://github.com/symfony/symfony)

[1]: https://symfony.com/backers
[3]: https://symfony.com/sponsor
