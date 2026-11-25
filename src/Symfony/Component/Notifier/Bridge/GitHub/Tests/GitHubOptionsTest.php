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

use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Notifier\Bridge\GitHub\GitHubOptions;
use Symfony\Component\Notifier\Exception\InvalidArgumentException;

/**
 * @author https://github.com/kevinonthebit
 */
final class GitHubOptionsTest extends TestCase
{
    public function testToArrayWithAllOptions()
    {
        $options = (new GitHubOptions('acme', 'e-commerce'))
            ->setTitle('Payment service unavailable')
            ->setBody('Logs')
            ->setStatus('Todo')
            ->setMilestone('2.0')
            ->setLabels(['Payment'])
            ->setAssignees(['foo'])
            ->setIssueFieldValues([['field_id' => 1, 'value' => 'Bar']])
            ->setType('Incident')
            ->setParentIssueId(2);

        $expectedOptions = [
            'title' => 'Payment service unavailable',
            'body' => 'Logs',
            'status' => 'Todo',
            'milestone' => '2.0',
            'labels' => ['Payment'],
            'assignees' => ['foo'],
            'issue_field_values' => [['field_id' => 1, 'value' => 'Bar']],
            'type' => 'Incident',
            'parent_issue_id' => 2,
        ];

        $this->assertSame($expectedOptions, $options->toArray());
    }

    public function testToArrayWithoutOptions()
    {
        $options = new GitHubOptions('acme', 'e-commerce');

        $expectedOptions = [
            'labels' => [],
            'assignees' => [],
            'issue_field_values' => [],
        ];

        $this->assertSame($expectedOptions, $options->toArray());
    }

    #[TestWith(['Payment service unavailable', '2.0'])]
    #[TestWith([1, 2])]
    public function testToArrayWithUnionTypeOptions(string|int $title, string|int $milestone)
    {
        $options = (new GitHubOptions('acme', 'e-commerce'))
            ->setTitle($title)
            ->setMilestone($milestone);

        $expectedOptions = [
            'title' => $title,
            'milestone' => $milestone,
            'labels' => [],
            'assignees' => [],
            'issue_field_values' => [],
        ];

        $this->assertSame($expectedOptions, $options->toArray());
    }

    #[TestWith([['Payment'], ['foo'], [['field_id' => 1, 'value' => 'Bar']]])]
    #[TestWith([[], [], [['field_id' => 1, 'value' => 2]]])]
    #[TestWith([[], [], [['field_id' => 1, 'value' => []]]])]
    public function testToArrayWithArrayOptions(array $labels, array $assignees, array $issueFieldValues)
    {
        $options = (new GitHubOptions('acme', 'e-commerce'))
            ->setLabels($labels)
            ->setAssignees($assignees)
            ->setIssueFieldValues($issueFieldValues);

        $expectedOptions = [
            'labels' => $labels,
            'assignees' => $assignees,
            'issue_field_values' => $issueFieldValues,
        ];

        $this->assertSame($expectedOptions, $options->toArray());
    }

    public function testSetInvalidStatusThrowsInvalidArgumentException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('Invalid "status" payload parameter value "Invalid". Supported values are: "Backlog", "Todo", "In progress", "In review".');

        (new GitHubOptions('foo', 'bar'))->setStatus('Invalid');
    }
}
