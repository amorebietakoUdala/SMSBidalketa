<?php

namespace App\Command;

use AmorebietakoUdala\SMSServiceBundle\Providers\SmsSarenetApi;
use App\Entity\Audit;
use App\Entity\History;
use App\Repository\AuditRepository;
use App\Repository\HistoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sms-history-sarenet',
    description: 'Gets the last History messages from SMS provider API and stores them in the database.',
)]
class SarenetSmsHistoryCommand extends Command
{
    private $provider = 'Sarenet';

    public function __construct(
        private readonly EntityManagerInterface $em, 
        private readonly SmsSarenetApi $smsApi, 
        private readonly AuditRepository $auditRepo,
        private readonly HistoryRepository $historyRepo,
        )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('from', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('print','-p',InputOption::VALUE_NONE,'Show results and briefing')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $histories = $this->getHistory($input, $output);
        if ($input->getOption('print')) {
            dump($histories);
            $io->success('Total histories: '. count($histories));
        };

        return Command::SUCCESS;
    }

    private function getHistory(InputInterface $input, OutputInterface $output)
    {
        $histories = [];
        $io = new SymfonyStyle($input, $output);
        // Para poder comprobar si se han enviado correctamente los SMS el Api de Sarenet no nos proporciona un historial de envíos
        // Por lo tanto, tenemos que consultar la tabla de auditoría de la base de datos para comprobar qué mensajes se han enviado a dicho proveedor
        $lastProviderID = null;
        $apiHistories = $this->historyRepo->findOneBy(['provider' => $this->provider], ['providerId' => 'desc']);
        if ( null !== $apiHistories) {
            $lastProviderID = $apiHistories->getProviderId();
        }
        // If there are no histories, we get all the audits for the provider else only from the last providerId
        if ( null === $lastProviderID ) {
            $audits = $this->auditRepo->findBy(['provider' => $this->provider]);
        } else {
            $audits = $this->auditRepo->findDeliveryIdGTForProvider($this->provider, $lastProviderID);
        } 
        /** @var Audit $audit */
        foreach ($audits as $audit) {
            $deliveryResponse = json_decode($audit->getResponse(), true);
            // Remove responseCode and message from the response. It's repeated in every element
            unset($deliveryResponse['responseCode']);
            unset($deliveryResponse['message']);
            foreach ($deliveryResponse as $delivery) {
                try {
                    $status = $this->smsApi->getStatus($delivery['id']);
                    $delivery['status'] = $status;
                    $delivery['sender'] = $this->smsApi->getSender();
                    $delivery['text'] = $audit->getMessageContent();
                    $delivery['deliveryId'] = $audit->getDeliveryId();
                    $delivery['provider'] = $audit->getProvider();
                    $delivery['date'] = $audit->getTimestamp();
                    $history = new History();
                    $history->loadFromArray($delivery, $this->provider);
                    $this->em->persist($history);
                    $this->em->flush();
                } catch (\Exception $e) { 
                    $io->error('Error: '.$e->getMessage());
                }
            }
        }
        return $histories;
    }
}
