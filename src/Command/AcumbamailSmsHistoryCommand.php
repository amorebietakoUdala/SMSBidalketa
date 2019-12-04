<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Command;

use AmorebietakoUdala\SMSServiceBundle\Providers\SmsAcumbamailApi;
use App\Entity\History;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Description of AcumbamailSmsHistoryCommand.
 *
 * @author ibilbao
 */
class AcumbamailSmsHistoryCommand extends Command
{
    protected static $defaultName = 'app:sms-history-acumbamail';

    private $em;
    private $smsApi;
    private $provider = 'Acumbamail';

    public function __construct(EntityManagerInterface $em, SmsAcumbamailApi $smsApi)
    {
        $this->em = $em;
        $this->smsApi = $smsApi;

        parent::__construct();
    }

    protected function configure()
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
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getHistory($input, $output);
    }

    private function getHistory(InputInterface $input, OutputInterface $output)
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
        $found = false;
        /** @var App\Entity\History */
        $lastHistory = $this->em->getRepository(History::class)->findOneBy(
            ['provider' => $this->provider], ['providerId' => 'desc'], 1);
        if (null === $lastHistory) {
            $lastHistory = null;
        }
        try {
            $api_histories = $this->smsApi->getHistory($start_date, $end_date);
            $firstResult = $api_histories[0];
            if (null === $lastHistory) {
                $lastId = 0;
            } else {
                $lastId = $lastHistory->getProviderId();
            }
            if ($firstResult['sms_id'] === $lastId) {
                return 0;
            }
            foreach ($api_histories as $record) {
                if ($record['sms_id'] > $lastId) {
                    $history = new History($record, $this->provider);
                    $histories[] = $history;
                    $this->em->persist($history);
                    $found = true;
                }
            }
            if ($found) {
                $this->em->flush();
            }
        } catch (Exception $e) {
            $output->writeln('<error>ERROR: '.$e->getMessage().'</error>');
        }

        return $histories;
    }
}
