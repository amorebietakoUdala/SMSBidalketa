<?php

namespace App\MessageHandler;

use AmorebietakoUdala\SMSServiceBundle\Services\SmsServiceApi;
use App\Entity\Audit;
use App\Message\CheckSmsNotificationStatus;
use App\Message\SmsNotification;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

#[AsMessageHandler]
class SmsNotificationHandler implements LoggerAwareInterface {

   use LoggerAwareTrait;

   public function __construct(
      private readonly SmsServiceApi $smsapi,
      private readonly EntityManagerInterface $em,
      private readonly MessageBusInterface $messageBus,
      private readonly UserRepository $userRepo,
      private readonly int $checkDelayTimeinMiliseconds
   ) 
   {}

   public function __invoke(SmsNotification $message)
   {
      $user = $this->userRepo->find($message->getUserId());
      $audit = Audit::createAudit($message->getTelephones(), '', '', '', $user, $this->smsapi->getProvider(), $message->getMessage());
      $this->logger->debug('Delivery ID: '. $audit->getDeliveryId());
      $response = $this->smsapi->sendMessage($message->getTelephones(), $message->getMessage(), $message->getDate(), $audit->getDeliveryId());
      if (null !== $response) {
         $audit->setMessage($response['message']);
         $audit->setResponseCode($response['responseCode']);
         $audit->setResponse(json_encode($response));
         $this->logger->info('API Response: ' . json_encode($response));
         $this->em->persist($audit);
         $this->em->flush();
         $message = new CheckSmsNotificationStatus($audit->getId());
         $this->messageBus->dispatch(new Envelope($message, [new DelayStamp($this->checkDelayTimeinMiliseconds)]));
      } else {
         $this->logger->alert('API Response: The API has not responded for deliveryId: ' . $audit->getDeliveryId());
         $this->em->persist($audit);
         $this->em->flush();
         throw new \Exception('API Response: The API has not responded for deliveryId: ' . $audit->getDeliveryId());
         return;
     }
   }
}