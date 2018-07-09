<?php

namespace App\Command;

use App\Command\Traits\Lock;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RestartTrackingCommand extends StartTrackingCommand
{
    public const NAME = 'tracking:restart';

    use Lock;

    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Restart tracking')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->writeln(['', 'Restarting process...']);

        sleep($this->checkForLockInterval);

        $this->setLocked(false);

        parent::execute($input, $output);
    }
}