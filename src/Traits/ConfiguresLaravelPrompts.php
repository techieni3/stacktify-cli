<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Traits;

use Laravel\Prompts\ConfirmPrompt;
use Laravel\Prompts\MultiSelectPrompt;
use Laravel\Prompts\Prompt;
use Laravel\Prompts\SelectPrompt;
use Laravel\Prompts\TextPrompt;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

trait ConfiguresLaravelPrompts
{
    /**
     * Configure the prompt fallbacks.
     */
    private function configurePrompts(InputInterface $input, OutputInterface $output): void
    {
        Prompt::fallbackWhen( ! $input->isInteractive() || PHP_OS_FAMILY === 'Windows');

        TextPrompt::fallbackUsing(
            static fn (TextPrompt $prompt) => new SymfonyStyle($input, $output)->ask($prompt->label, $prompt->default ?: null, $prompt->validate)
        );

        ConfirmPrompt::fallbackUsing(
            static fn (ConfirmPrompt $prompt) => new SymfonyStyle($input, $output)->confirm($prompt->label, $prompt->default)
        );

        SelectPrompt::fallbackUsing(
            static fn (SelectPrompt $prompt) => new SymfonyStyle($input, $output)->choice($prompt->label, $prompt->options, $prompt->default),
        );

        MultiSelectPrompt::fallbackUsing(function (MultiSelectPrompt $prompt) use ($input, $output) {
            $io = new SymfonyStyle($input, $output);

            if ($prompt->default !== []) {
                $selected = $io->choice($prompt->label, $prompt->options, implode(',', $prompt->default), true);
                $selected = is_array($selected) ? $selected : [$selected];
            } else {
                // Build choices with a "None" sentinel, so empty selection is possible
                $isList = array_is_list($prompt->options);
                $choices = $isList
                    ? array_merge(['None'], $prompt->options)
                    : ['none' => 'None'] + $prompt->options;

                $selected = $io->choice($prompt->label, $choices, 'None', true);
                $selected = is_array($selected) ? $selected : [$selected];

                // Remove sentinel
                if ($isList) {
                    $selected = array_values(array_filter($selected, static fn ($v) => $v !== 'None'));
                } else {
                    $selected = array_values(array_filter($selected, static fn ($v) => $v !== 'none'));
                }
            }

            return $selected;
        });
    }
}
