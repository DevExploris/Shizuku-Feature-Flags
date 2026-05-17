<?php

namespace Devexploris\ShizukuFeatureFlags\Command;

use Devexploris\ShizukuFeatureFlags\Repository\FeatureFlagRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

#[AsCommand(name: 'shizuku:list', description: 'List feature flags')]
class ListCommand extends Command
{
    private const VALID_FILTERS = ['All', 'Enabled', 'Disabled', 'Locked'];

    public function __construct(private readonly FeatureFlagRepository $featureFlagRepository)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'filter',
            InputArgument::OPTIONAL,
            'Filter to apply: All, Enabled, Disabled, Locked',
            null
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $filterArgument = $input->getArgument('filter');

        if ($filterArgument !== null) {
            $type = $filterArgument;
        } else {
            $helper = new QuestionHelper();

            $question = new ChoiceQuestion(
                'Which feature flag ⚐ ',
                self::VALID_FILTERS,
                0
            );

            $question->setErrorMessage('Feature flag status %s is invalid.');

            $type = $helper->ask($input, $output, $question);
        }

        $featureFlags = match ($type) {
            'All'      => $this->featureFlagRepository->findAll(),
            'Disabled' => $this->featureFlagRepository->findBy(['isEnabled' => false]),
            'Enabled'  => $this->featureFlagRepository->findBy(['isEnabled' => true]),
            'Locked'   => $this->featureFlagRepository->findBy(['isLocked' => true]),
            default    => $this->featureFlagRepository->findAll(),
        };

        $table = new Table($output);
        $table
            ->setHeaders(['Name', 'Description', 'Enabled', 'Locked'])
            ->setRows(array_map(function ($featureFlag) {
                return [
                    $featureFlag->getName(),
                    $featureFlag->getDescription(),
                    $featureFlag->isEnabled() ? 'Yes' : 'No',
                    $featureFlag->isLocked() ? 'Yes' : 'No',
                ];
            }, $featureFlags))
        ;
        $table->render();

        return Command::SUCCESS;
    }
}
