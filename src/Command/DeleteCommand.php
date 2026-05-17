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
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[AsCommand(name: 'shizuku:flag:delete', description: 'Permanently delete a feature flag')]
class DeleteCommand extends Command
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
        $this
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Name of the flag to delete')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Delete even if the flag is not locked');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $nameOption  = $input->getOption('name');
        $forceOption = $input->getOption('force');

        if ($nameOption !== null) {
            $flag = $this->repository->findOneBy(['name' => $nameOption]);

            if ($flag === null) {
                $output->writeln("<error>Feature flag '{$nameOption}' does not exist.</error>");
                return Command::FAILURE;
            }

            if (!$flag->isLocked() && !$forceOption) {
                $output->writeln("<error>Feature flag '{$nameOption}' is not locked. Lock it first with shizuku:flag:lock, or use --force.</error>");
                return Command::FAILURE;
            }

            $this->em->remove($flag);
            $this->em->flush();
            $this->cache?->delete(FeatureFlagService::cacheKey($nameOption));

            $output->writeln("<info>Feature flag '{$nameOption}' has been permanently deleted.</info>");

            return Command::SUCCESS;
        }

        $flags = $forceOption
            ? $this->repository->findAll()
            : $this->repository->findBy(['isLocked' => true]);

        if (empty($flags)) {
            $output->writeln('<info>No locked feature flags to delete. Use --force to delete non-locked flags.</info>');
            return Command::SUCCESS;
        }

        $flagsByName = [];
        foreach ($flags as $flag) {
            $flagsByName[$flag->getName()] = $flag;
        }

        $helper = new QuestionHelper();

        $name = $helper->ask(
            $input,
            $output,
            new ChoiceQuestion('Which flag to delete? ', array_keys($flagsByName))
        );

        if (!$flagsByName[$name]->isLocked()) {
            $confirmed = $helper->ask($input, $output, new ConfirmationQuestion(
                "<comment>Warning: '{$name}' is not locked. Any remaining isEnabled('{$name}') call will return false. Confirm? [y/N] </comment>",
                false
            ));

            if (!$confirmed) {
                $output->writeln('<info>Deletion cancelled.</info>');
                return Command::SUCCESS;
            }
        }

        $this->em->remove($flagsByName[$name]);
        $this->em->flush();
        $this->cache?->delete(FeatureFlagService::cacheKey($name));

        $output->writeln("<info>Feature flag '{$name}' has been permanently deleted.</info>");

        return Command::SUCCESS;
    }
}
