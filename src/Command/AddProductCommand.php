<?php

namespace App\Command;

use App\Entity\Product;
use App\Exception\InvalidUrlException;
use App\Exception\UnsupportedSiteException;
use App\Tracker\TrackerFactory;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class AddProductCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('tracking:add')
            ->setDescription('Track new product')
            ->addArgument('url', InputArgument::OPTIONAL, 'The product url to be tracked')
            ->addOption('interval', 'i', InputOption::VALUE_OPTIONAL, 'The time request interval (minutes)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $io = $this->io = new SymfonyStyle($input, $output);
        $io->title('Add product');

        $url = $input->getArgument('url');

        /** @var Product $product */
        $product = null;

        while (!$product)
        {
            // ask for url if it not typed or invalid typed
            if (empty($url))
            {
                $url = $io->ask('Enter product url');
            }

            // create tracker and fetch product
            try {
                $tracker = TrackerFactory::createFromURL($url);
                $product = $tracker->fetchProduct();
            } catch (\Exception $e) {
                $io->error($e->getMessage());
                $url = null;
            }
        }

        // ask for interval if value is not typed in option
        if (empty($interval = $input->getOption('interval')))
        {
            $interval = $io->ask('Enter request interval time (minutes)');
        }

        $product->setIntervalTime($interval);

        // save
        $em->persist($product);
        $em->flush();

        $output->writeln('<info>The product is tracking now.</info>');
    }
}