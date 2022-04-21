<?php

namespace App\Controller;

use App\Entity\Audit;
use App\Entity\Contact;
use App\Entity\Label;
use App\Repository\LabelRepository;
use Doctrine\ORM\EntityManagerInterface;
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
}
