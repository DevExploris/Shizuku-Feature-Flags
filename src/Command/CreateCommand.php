<?php

namespace Devexploris\ShizukuFeatureFlags\Command;

use Devexploris\ShizukuFeatureFlags\Entity\FeatureFlag;
use Devexploris\ShizukuFeatureFlags\Service\FeatureFlagService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

#[AsCommand(name: 'shizuku:flag:create', description: 'Create a new feature flag')]
class CreateCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private ?CacheInterface $cache = null,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Flag name (snake_case)')
            ->addOption('description', null, InputOption::VALUE_REQUIRED, 'Flag description')
            ->addOption('enable', null, InputOption::VALUE_NONE, 'Enable the flag immediately');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $nameOption        = $input->getOption('name');
        $descriptionOption = $input->getOption('description');
        $enableOption      = $input->getOption('enable');

        $nameValidator = function (?string $value): string {
            if (empty($value)) {
                throw new \RuntimeException('Flag name cannot be empty.');
            }
            if (!preg_match('/^[a-z][a-z0-9_]*$/', $value)) {
                throw new \RuntimeException('Flag name must be snake_case (lowercase letters, digits, underscores).');
            }
            return $value;
        };

        if ($nameOption !== null || $descriptionOption !== null || $enableOption) {
            $name        = $nameValidator($nameOption);
            $description = (string) ($descriptionOption ?? '');
            $enabled     = (bool) $enableOption;
        } else {
            $helper = new QuestionHelper();

            $nameQuestion = new Question('Flag name (snake_case): ');
            $nameQuestion->setValidator($nameValidator);

            $name        = $helper->ask($input, $output, $nameQuestion);
            $description = $helper->ask($input, $output, new Question('Description: '));
            $enabled     = $helper->ask($input, $output, new ConfirmationQuestion('Enable now? [y/N] ', false));
        }

        $flag = new FeatureFlag($name, $description, $enabled);
        $this->em->persist($flag);
        $this->em->flush();
        $this->cache?->delete(FeatureFlagService::cacheKey($name));

        $output->writeln("<info>Feature flag '{$name}' created successfully.</info>");

        return Command::SUCCESS;
    }
}
