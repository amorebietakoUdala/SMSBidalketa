<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Command;

use App\Entity\History;
use AmorebietakoUdala\SMSServiceBundle\Providers\SmsDinaHostingApi;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Description of SmsHistoryDaemonCommand.
 *
 * @author ibilbao
 */
#[AsCommand('app:sms-history-dinahosting', 'Gets the last History messages from SMS provider API and stores them in the database.')]
class DinahostingSmsHistoryCommand extends Command
{
    private $provider = 'Dinahosting';

    public function __construct(private readonly EntityManagerInterface $em, private readonly SmsDinaHostingApi $smsApi)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Gets the last History messages from SMS provider API and stores them in the database.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Gets the last History messages from SMS provider API and stores them in the database.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->getHistory($output);

        return Command::SUCCESS;
    }

    private function getHistory(OutputInterface $output): array
    {
        $histories = [];
        $start = 0;
        $end = 5000;
        $found = false;

        $lastHistory = $this->em->getRepository(History::class)->findOneBy(
            ['provider' => $this->provider], ['providerId' => 'desc']);
        if (null === $lastHistory) {
            $lastHistory = null;
        }
        try {
            $api_histories = $this->smsApi->getHistory($start, $end);
            $firstResult = null;
            if ( isset($api_histories['data'][0])) {
                $firstResult = $api_histories['data'][0];
            }
            if (null === $lastHistory) {
                $lastId = 0;
            } else {
                $lastId = $lastHistory->getProviderId();
            }
            if ($firstResult !== null && $firstResult['id'] === $lastId) {
                return 0;
            }
            foreach ($api_histories['data'] as $record) {
                if ($record['id'] > $lastId) {
                    $history = new History($record, $this->provider);
                    $histories[] = $history;
                    $this->em->persist($history);
                    $found = true;
                }
            }
            if ($found) {
                $this->em->flush();
            }
        } catch (\Exception $e) {
            $output->writeln('<error>ERROR: '.$e->getMessage().'</error>');
        }

        return $histories;
    }
}
