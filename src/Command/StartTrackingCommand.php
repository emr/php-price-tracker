<?php

namespace App\Command;

use App\Command\Traits\Lock;
use App\Tracker\TrackerFactory;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * This command triggers trackers and notifier
 */
class StartTrackingCommand extends ContainerAwareCommand
{
    public const NAME = 'tracking:start';

    /** @var OutputStyle */
    protected $io;

    use Lock;

    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Start tracking for a product')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = $this->io = new SymfonyStyle($input, $output);

        if ($this->isLocked()) {
            $io->error('Tracking process is already running.');
            return;
        }

        if (!$io->isVerbose())
        {
            $this->createChildProcess();

            $io->setVerbosity(OutputInterface::VERBOSITY_QUIET);
        }
        else
        {
            $io->title('Product tracking');
        }

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $count = 0;

        foreach ($this->trackProducts() as $tracked)
        {
            $count++;
            $em->persist($tracked);
            $em->flush();
        }

        if (!$io->isVerbose())
            $this->setLocked(false);

        $io->success([
            "The queue is empty. The process is over.",
            "Total {$count} products were updated."
        ]);
    }

    protected function createChildProcess()
    {
        $io = $this->io;

        if (!extension_loaded('pcntl'))
        {
            $io->error('This process needs the pcntl extension to run. Tracking process can not start.');
            exit;
        }

        switch (pcntl_fork())
        {
            case -1:
                $io->error('Unable to start the tracking process.');
                exit;

            case 0:
                if ($sid = posix_setsid() < 0)
                {
                    $io->error('Unable to set the child process as session leader.');
                    exit;
                }
                break;

            default:
                $this->setLocked(true);
                $io->success('Tracking process started.');
                exit;

        }
    }

    protected function spendTime(int $minutes)
    {
        $checkingInterval = 1;
        for ($i = 0; $i < $minutes * 60 / $checkingInterval; $i++)
        {
            sleep($checkingInterval);

            if (!$this->isLocked())
                exit;
        }
    }

    /**
     * it tracks the product which has higher priority
     */
    protected function trackProducts(): iterable
    {
        $io = $this->io;
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $notifier = $this->getContainer()->get('app.notification_manager');

        $products = $em->getRepository('App:Product')->findBy([], [
            'nextTrackingTime' => 'ASC'
        ]);

        foreach ($products as $product)
        {
            $timeDiff = $product->getNextTrackingTime()->diff(new \DateTime())->i;

            $waitTime = $timeDiff < 0 ? 0 : $timeDiff;

            $io->writeln("<question>{$product}</question> <info>will be updated after {$waitTime} minutes...</info>");

            $this->spendTime($waitTime);

            try {
                $tracker = TrackerFactory::createFromProduct($product);
                $tracker->fetchProduct();

                $io->writeln("<question>{$product}</question> <info>updated.</info>");
            } catch (\Exception $e) {
                $io->writeln('<error>An error occurred while tracking product:</error> ' . $e->getMessage());
            }

            try {
                $notifier->notify($product, function($product) use ($io) {
                    $io->writeln("<info>Change detected for <question>{$product}</question>, information of change was sent to user.</info>");
                });
            } catch (\Exception $e) {
                $io->writeln('<error>An error occurred while notifying for the change of product price:</error> ' . $e->getMessage());
            }

            yield $product;
        }
    }
}