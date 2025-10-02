<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Traits\Prompts;

use function Laravel\Prompts\text;

trait GitPrompts
{
    private function askGitUserName(): string
    {
        return text(
            label: 'Please enter your Git username',
            placeholder: 'techieni3',
            required: 'Git username is required.',
            validate: static fn ($value) => preg_match('/[^\pL\pN\-_.\s]/u', mb_trim($value)) !== 0
                ? 'The name may only contain letters, numbers, dashes, underscores, and periods.'
                : null,
        );
    }

    private function askGitEmail(): string
    {
        return text(
            label: 'Please enter your Git email',
            placeholder: 'techieni3@example.com',
            required: 'Git email is required.',
            validate: static fn ($value) => filter_var($value, FILTER_VALIDATE_EMAIL) === false
                ? 'The email is invalid.'
                : null,
        );
    }
}
