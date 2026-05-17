<?php

namespace Devexploris\ShizukuFeatureFlags\Command;

use Devexploris\ShizukuFeatureFlags\Repository\FeatureFlagRepository;
use Devexploris\ShizukuFeatureFlags\Service\FeatureFlagService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

#[AsCommand(name: 'shizuku:flag:disable', description: 'Disable a feature flag')]
class DisableCommand extends Command
{
    public function __construct(
        private FeatureFlagRepository $repository,
        private EntityManagerInterface $em,
        private ?CacheInterface $cache = null,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('name', null, InputOption::VALUE_REQUIRED, 'Name of the flag to disable');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $nameOption = $input->getOption('name');

        if ($nameOption !== null) {
            $flag = $this->repository->findOneBy(['name' => $nameOption]);

            if ($flag === null) {
                $output->writeln("<error>Feature flag '{$nameOption}' does not exist.</error>");
                return Command::FAILURE;
            }

            if ($flag->isLocked()) {
                $output->writeln("<error>Feature flag '{$nameOption}' is locked and cannot be modified.</error>");
                return Command::FAILURE;
            }

            $flag->setIsEnabled(false);
            $this->em->flush();
            $this->cache?->delete(FeatureFlagService::cacheKey($nameOption));

            $output->writeln("<info>Feature flag '{$nameOption}' is now disabled.</info>");

            return Command::SUCCESS;
        }

        $flags = $this->repository->findBy(['isEnabled' => true, 'isLocked' => false]);

        if (empty($flags)) {
            $output->writeln('<info>No enabled and unlocked feature flags to disable.</info>');
            return Command::SUCCESS;
        }

        $flagsByName = [];
        foreach ($flags as $flag) {
            $flagsByName[$flag->getName()] = $flag;
        }

        $choices = array_keys($flagsByName);

        $name = (new QuestionHelper())->ask(
            $input,
            $output,
            new ChoiceQuestion('Which flag to disable? ', $choices)
        );

        $flagsByName[$name]->setIsEnabled(false);
        $this->em->flush();
        $this->cache?->delete(FeatureFlagService::cacheKey($name));

        $output->writeln("<info>Feature flag '{$name}' is now disabled.</info>");

        return Command::SUCCESS;
    }
}
