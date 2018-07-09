<?php

namespace App\Command;

use App\Command\Traits\Lock;
use App\Tracker\TrackerFactory;
use App\Entity\Product;
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

    /** @var int seconds */
    protected $checkForLockInterval = 1;

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
            $io->writeln(['CTRL + C to be quiet.', '']);
            $this->setLocked(true);
        }

        $count = 0;

        foreach ($this->trackProducts() as $tracked)
        {
            $count++;
            $this->notifyForProduct($io, $tracked);
        }

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
        for ($i = 0; $i < $minutes * 60 / $this->checkForLockInterval; $i++)
        {
            sleep($this->checkForLockInterval);

            // kill child process
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

        $products = $em->getRepository('App:Product')->findBy([], [
            'nextTrackingTime' => 'ASC'
        ]);

        foreach ($products as $product)
        {
            $now = new \DateTime();
            $trackingTime = $product->getNextTrackingTime();

            $waitTime = $trackingTime < $now ? 0 : $trackingTime->diff($now)->i;

            $io->writeln("<question>{$product}</question> <info>will be updated after {$waitTime} minutes...</info>");

            $this->spendTime($waitTime);

            try {
                $io->writeln("<question>{$product}</question> <info>updating...</info>");

                $tracker = TrackerFactory::createFromProduct($product);
                $tracker->fetchProduct();

                $em->persist($product);
                $em->flush();

                $io->writeln("<question>{$product}</question> <info>updated.</info>");
            } catch (\Exception $e) {
                $io->error('An error occurred while tracking product: ' . $e->getMessage());
            }

            yield $product;
        }
    }

    public function notifyForProduct(OutputStyle $io, Product $product)
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $notifier = $this->getContainer()->get('app.notification_manager');

        try {
            $notifier->notify($product, function($product) use ($io) {
                $io->writeln("<info>Change detected for <question>{$product}</question>, information of change was sent to user.</info>");
            });
        } catch (\Exception $e) {
            $io->writeln('<error>An error occurred while notifying for the change of product price:</error> ' . $e->getMessage());
        }

        $em->persist($product);
        $em->flush();
    }
}