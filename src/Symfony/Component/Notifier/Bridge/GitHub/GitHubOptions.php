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

use Symfony\Component\Notifier\Exception\InvalidArgumentException;
use Symfony\Component\Notifier\Message\MessageOptionsInterface;

/**
 * @author https://github.com/kevinonthebit
 *
 * @see https://docs.github.com/en/rest/issues/issues?apiVersion=2026-03-10#create-an-issue
 */
final class GitHubOptions implements MessageOptionsInterface
{
    private string|int|null $title = null;
    private ?string $body = null;
    private ?string $status = null;
    private string|int|null $milestone = null;
    private array $labels = [];
    private array $assignees = [];
    private array $issueFieldValues = [];
    private ?string $type = null;
    private ?int $parentIssueId = null;

    /**
     * @param string $owner      Repository owner.
     * @param string $repository Repository name.
     */
    public function __construct(
        private string $owner,
        private string $repository,
    ) {
    }

    public function toArray(): array
    {
        $options = [
            'title' => $this->title,
            'body' => $this->body,
            'status' => $this->status,
            'milestone' => $this->milestone,
            'labels' => $this->labels,
            'assignees' => $this->assignees,
            'issue_field_values' => $this->issueFieldValues,
            'type' => $this->type,
            'parent_issue_id' => $this->parentIssueId,
        ];

        return array_filter($options, static fn ($value) => null !== $value && '' !== $value);
    }

    public function getRecipientId(): ?string
    {
        return null;
    }

    public function getOwner(): string
    {
        return $this->owner;
    }

    public function getRepository(): string
    {
        return $this->repository;
    }

    public function getTitle(): string|int|null
    {
        return $this->title;
    }

    public function setTitle(string|int $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $body): static
    {
        $this->body = $body;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->validateStatus($status);
        $this->status = $status;

        return $this;
    }

    public function getMilestone(): string|int|null
    {
        return $this->milestone;
    }

    public function setMilestone(string|int|null $milestone): static
    {
        $this->milestone = $milestone;

        return $this;
    }

    /**
     * @return array<string>
     */
    public function getLabels(): array
    {
        return $this->labels;
    }

    /**
     * @param array<string> $labels
     */
    public function setLabels(array $labels): static
    {
        $this->labels = $labels;

        return $this;
    }

    /**
     * @return array<string>
     */
    public function getAssignees(): array
    {
        return $this->assignees;
    }

    /**
     * @param array<string> $assignees
     */
    public function setAssignees(array $assignees): static
    {
        $this->assignees = $assignees;

        return $this;
    }

    /**
     * @return array<array{field_id: int, value: string|int|array}>
     */
    public function getIssueFieldValues(): array
    {
        return $this->issueFieldValues;
    }

    /**
     * @param array<array{field_id: int, value: string|int|array}> $issueFieldValues
     */
    public function setIssueFieldValues(array $issueFieldValues): static
    {
        $this->issueFieldValues = $issueFieldValues;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getParentIssueId(): ?int
    {
        return $this->parentIssueId;
    }

    public function setParentIssueId(int $parentIssueId): static
    {
        $this->parentIssueId = $parentIssueId;

        return $this;
    }

    private function validateStatus(string $status): void
    {
        if (!GitHubStatus::tryFrom($status)) {
            $supportedStatuses = array_column(GitHubStatus::cases(), 'value');

            $message = \sprintf(
                'Invalid "status" payload parameter value "%s". Supported values are: "%s".',
                $status,
                implode('", "', $supportedStatuses)
            );

            throw new InvalidArgumentException($message);
        }
    }
}
