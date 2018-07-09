<?php

namespace App\Command;

use App\Command\Traits\Lock;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class StopTrackingCommand extends ContainerAwareCommand
{
    public const NAME = 'tracking:stop';

    use Lock;

    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Stop tracking')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->isLocked())
        {
            $io->error('No tracking process running.');
            return;
        }

        $this->setLocked(false);

        $io->success('Stopped the tracking process.');
    }
}