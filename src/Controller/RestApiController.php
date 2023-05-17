<?php

namespace App\Controller;

use App\Entity\Audit;
use App\Entity\Contact;
use App\Entity\History;
use App\Entity\Label;
use App\Repository\AuditRepository;
use App\Repository\HistoryRepository;
use App\Repository\LabelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;


/**
 * @Route("/api")
 */
class RestApiController extends AbstractController
{

    private LoggerInterface $logger;
    private AuditRepository $auditRepo;
    private HistoryRepository $historyRepo;
    private EntityManagerInterface $em;

    public function __construct(LoggerInterface $logger, AuditRepository $auditRepo, HistoryRepository $historyRepo, EntityManagerInterface $em)
    {
        $this->logger = $logger;
        $this->auditRepo = $auditRepo;
        $this->historyRepo = $historyRepo;
        $this->em = $em;
    }

    /**
     * Retrieves an Labels resource.
     *
     * @Route("/labels", name="api_get_labels", options={"expose"=true})
     */
    public function getLabelsAction(Request $request, LabelRepository $repo)
    {
        $query = $request->get('name');
        $labels = $repo->findLabelsThatContain($query);

        return $this->json(['labels' => $labels], Response::HTTP_OK, [], [
            ObjectNormalizer::GROUPS => 'show',
            ObjectNormalizer::ENABLE_MAX_DEPTH => 1,
        ]);
    }

    /**
     * Removes the specified label from a given contact.
     *
     * @Route("/contact/{contact}/label/{label}/remove", name="api_remove_contact_label")
     */
    public function deleteLabelRemoveAction(Contact $contact, Label $label, EntityManagerInterface $em)
    {
        $contact->removeLabel($label);
        $em->persist($contact);
        $em->flush();

        return $this->json([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Returns a list of telephones from the given audit.
     *
     * @Route("/audit/{id}/telephones", name="api_get_audit_telephones")
     */
    public function getAuditTelephonesAction(Audit $audit)
    {
        $telephones = $audit->getTelephones();
        sort($telephones, SORT_STRING);

        return $this->json($telephones, Response::HTTP_OK);
    }

    /**
     * Returns a list of telephones from the given audit.
     *
     * @Route("/sending/smspubli/confirmation", name="api_sending_smspubli_confirmation")
     */
    public function sendingSmsPubliConfirmation(Request $request)
    {
        # Confirmation request example
        // Single
        //$json ='[{"sms_id":"35d6b03f92d542c789c7231917208ca7","from":"AMOREBIETA","to":"34655708598","custom":"1678104508344","sms_date":"2023-03-06 13:08:32","status":"DELIVRD","dlr_date":"2023-03-06 13:08:32"}]';
        // Multiple
        // $json = '[{"sms_id":"033795fe33d140e9b342c1ff113f73af","from":"AMOREBIETA","to":"34676089124","custom":"1684151115465","sms_date":"2023-05-15 13:45:21","status":"DELIVRD","dlr_date":"2023-05-15 13:45:21"},{"sms_id":"73cacad5179245f3a397b4ad019a783f","from":"AMOREBIETA","to":"34660805705","custom":"1684151115465","sms_date":"2023-05-15 13:45:21","status":"DELIVRD","dlr_date":"2023-05-15 13:45:21"},{"sms_id":"f143c9b35c6a41fd9ca4f4d631a8cc65","from":"AMOREBIETA","to":"34644654914","custom":"1684151115465","sms_date":"2023-05-15 13:45:21","status":"DELIVRD","dlr_date":"2023-05-15 13:45:21"}]';
        $json = $request->getContent();
        $this->logger->debug('Confirmation:'. $json);
        if ($json !== null && !empty($json)) {
            $confirmations = json_decode($json, true);
            $id = null;
            foreach ($confirmations as $key =>$value) {
                if ( $id !== $value['custom'] ) {
                    $id = $value['custom'];
                    /** @var Audit|null $audit */
                    $audit = $this->auditRepo->findOneBy([
                        'deliveryId' => $id
                    ]);
                }
                if ($audit !== null) {
                    $text = $audit->getMessageContent();
                    $value['text'] = $text;
                }
                $history = $this->historyRepo->findOneBy(['providerId' => $value['sms_id']]);
                if ($history === null ) {
                    $history = new History();
                }
                $history->setProvider('Smspubli');
                $history->loadFromArray($value, 'Smspubli');
                $this->em->persist($history);
                $this->em->flush();
            }
            return $this->json('', Response::HTTP_NO_CONTENT);
        }
        $this->logger->debug('Confirmation: Empty');
        return $this->json('', Response::HTTP_NO_CONTENT);
    }

}
