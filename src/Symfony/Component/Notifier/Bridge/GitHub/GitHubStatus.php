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

/**
 * @author https://github.com/kevinonthebit
 *
 * @see https://docs.github.com/en/rest/issues/issues?apiVersion=2026-03-10#create-an-issue
 */
enum GitHubStatus: string
{
    case Backlog = 'Backlog';
    case Todo = 'Todo';
    case InProgress = 'In progress';
    case InReview = 'In review';
}
