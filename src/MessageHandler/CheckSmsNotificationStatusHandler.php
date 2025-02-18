<?php

namespace App\MessageHandler;

use AmorebietakoUdala\SMSServiceBundle\Providers\SmsSarenetApi;
use App\Entity\History;
use App\Message\CheckSmsNotificationStatus;
use App\Repository\AuditRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class CheckSmsNotificationStatusHandler implements LoggerAwareInterface
{

    use LoggerAwareTrait;

    public function __construct(
        private readonly AuditRepository $auditRepo,
        private readonly EntityManagerInterface $em,
        private readonly SmsSarenetApi $smsApi,
        private readonly string $provider,
    )
    {}

    public function __invoke(CheckSmsNotificationStatus $message): void
    {
        $auditId = $message->getAuditId();
        $audit = $this->auditRepo->find($auditId);
        if ( $this->provider !== 'Sarenet') {
            $this->logger->alert('Delivery number: ' . $audit->getDeliveryId() . ' is not from Sarenet provider, so don\'t have to test status');
            return; 
        }
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
                $this->logger->error('Error: '.$e->getMessage());
            }
        }
    }
}
