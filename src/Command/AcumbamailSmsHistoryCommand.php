<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Command;

use AmorebietakoUdala\SMSServiceBundle\Providers\SmsAcumbamailApi;
use App\Entity\History;
use App\Repository\HistoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Description of AcumbamailSmsHistoryCommand.
 *
 * @author ibilbao
 */
#[AsCommand('app:sms-history-acumbamail', 'Gets the last History messages from SMS provider API and stores them in the database. If no argument provided, it will return todays SMS History.')]
class AcumbamailSmsHistoryCommand extends Command
{
    private $provider = 'Acumbamail';

    public function __construct(
        private readonly EntityManagerInterface $em, 
        private readonly SmsAcumbamailApi $smsApi, 
        private readonly HistoryRepository $repo)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Gets the last History messages from SMS provider API and stores them in the database.'
                             .'If no argument provided, it will return todays SMS History.')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Gets the last History messages from SMS provider API and stores them in the database.')
            ->addArgument('start_date', InputArgument::OPTIONAL, 'Start Date in "YYYY-MM-DD HH:MM" format use quotation marks')
            ->addArgument('end_date', InputArgument::OPTIONAL, 'End Date in "YYYY-MM-DD HH:MM" format use quotation marks')
            ->addOption('print','-p',InputOption::VALUE_NONE,'Show results and briefing')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $histories = $this->getHistory($input, $output);
        if ($input->getOption('print')) {
            dump($histories);
            dump('Total histories: '. count($histories));
        };

        return Command::SUCCESS;
    }

    private function getHistory(InputInterface $input, OutputInterface $output): array
    {
        $histories = [];
        $start_date = new \DateTime((new \DateTime())->format('Y-m-d'));
        if (null != $input->getArgument('start_date')) {
            $start_date = new \DateTime($input->getArgument('start_date'));
        }
        $end_date = new \DateTime();
        if (null != $input->getArgument('end_date')) {
            $end_date = new \DateTime($input->getArgument('end_date'));
        }
        try {
            $api_histories = $this->smsApi->getHistory($start_date, $end_date);
            if (count($api_histories) > 0) {
                $firstResult = $api_histories[0];
            } else {
//                $output->writeln('No histories found from: '.$start_date->format('Y/m/d H:i:s').' to '.$end_date->format('Y/m/d H:i:s'));

                return 0;
            }
            $lastId = $this->em->getRepository(History::class)->getLastProviderIdForProvider($this->provider);
            if ($firstResult['sms_id'] === $lastId) {
                return 0;
            }
            foreach ($api_histories as $record) {
                if ($record['sms_id'] > $lastId) {
                    $history = new History();
                } else {
                    /** @var History $history */
                    $history = $this->repo->findOneBy(['providerId' => $record['sms_id']]);
                }
                $history->loadFromArray($record, $this->provider);
                $histories[] = $history;
                $this->em->persist($history);
            }
            if (count($api_histories) > 0) {
                $this->em->flush();
            }
        } catch (Exception $e) {
            $output->writeln('<error>ERROR: '.$e->getMessage().'</error>');
        }
        return $histories;
    }
}
