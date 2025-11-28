<?php

declare(strict_types=1);

namespace Phenix\Auth\Console;

use Phenix\Auth\PersonalAccessToken;
use Phenix\Util\Date;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function sprintf;

class PurgeExpiredTokens extends Command
{
    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultName = 'tokens:purge';

    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultDescription = 'Deletes all expired personal access tokens.';

    protected function configure(): void
    {
        $this->setHelp('This command removes personal access tokens whose expiration datetime is in the past.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $now = Date::now()->toDateTimeString();

        $count = PersonalAccessToken::query()
            ->whereLessThan('expires_at', $now)
            ->count();

        PersonalAccessToken::query()
            ->whereLessThan('expires_at', $now)
            ->delete();

        $output->writeln(sprintf('<info>%d expired token(s) purged successfully.</info>', $count));

        return Command::SUCCESS;
    }
}
