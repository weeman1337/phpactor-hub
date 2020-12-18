<?php

namespace PhpactorHub\Pipeline;

use Maestro2\Core\Config\RepositoryNode;
use Maestro2\Core\Task\CatTask;
use Maestro2\Core\Task\ClosureTask;
use Maestro2\Core\Task\FileTask;
use Maestro2\Core\Task\GitCommitTask;
use Maestro2\Core\Task\GitDiffTask;
use Maestro2\Core\Task\ProcessTask;
use Maestro2\Core\Task\ReplaceLineTask;
use Maestro2\Core\Task\SequentialTask;
use Maestro2\Core\Task\Task;
use Maestro2\Core\Task\TemplateTask;
use Maestro2\Core\Task\YamlTask;

class GithubActionsPipeline extends BasePipeline
{
    protected function buildRepository(RepositoryNode $repository): Task
    {
        return new SequentialTask([
            new CatTask(
                path: '.travis.yml'
            ),
            new FileTask(
                path: '.travis.yml',
                exists: false
            ),
            new ReplaceLineTask(
                group: $repository->name(),
                path: 'README.md',
                regexp: '{Build Status.*travis}',
                line: sprintf('![CI](https://github.com/phpactor/%s/workflows/CI/badge.svg)', $repository->name()),
            ),
            new TemplateTask(
                template: 'github/workflow.yml.twig',
                target: '.github/workflows/ci.yml',
                vars: [
                    'name' => 'CI',
                    'repo' => $repository,
                    'jobs' => $repository->vars()->get('jobs')
                ]
            ),
            new GitDiffTask(),
            new GitCommitTask(
                message: 'Maestro is adding Github Actions',
                paths: [
                    'README.md',
                    '.github/workflows/ci.yml',
                    '.travis.yml',
                ],
            ),
            new ProcessTask(
                args: ['git', 'checkout', '-b', 'github-actions'],
            ),
            new ProcessTask(
                args: ['git', 'push', 'origin', 'HEAD', '--force'],
            ),
            new ProcessTask(
                args: ['gh', 'pr', 'create', '--fill', '--head', 'github-actions'],
                allowFailure: true
            )
        ]);

    }
}
