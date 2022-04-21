<?php

namespace App\Controller;

use App\DTO\AuditSearchDTO;
use App\Entity\Audit;
use App\Form\AuditSearchType;
use App\Repository\AuditRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * @Route("/{_locale}")
 */
class AuditController extends AbstractController
{
    /**
     * @Route("/audit", name="audit_list")
     */
    public function listAction(Request $request, AuthorizationCheckerInterface $authChecker, AuditRepository $repo)
    {
        $form = $this->createForm(AuditSearchType::class, new AuditSearchDTO());
        $criteria = [];
        $limit = $this->getParameter('resultLimit');
        if (!$authChecker->isGranted('ROLE_ADMIN')) {
            $criteria['user'] = $this->get('security.token_storage')->getToken()->getUser();
        }
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /* @var $data AuditSearchDTO */
            $data = $form->getData();
            $criteria = array_replace($data->toArray(), $criteria);
            $audits = $repo->findByTimestamp($criteria);
            if (count($audits) >= $limit) {
                $this->addFlash('warning', new TranslatableMessage('Max results reached: %maxLimit%', [
                    '%maxLimit%' => $limit
                ]));
            }

            return $this->render('audit/list.html.twig', [
                'audits' => $audits,
                'form' => $form->createView(),
            ]);
        }
        $audits = $repo->findBy($criteria, ['timestamp' => 'DESC'], $limit);
        if (count($audits) >= $limit) {
            $this->addFlash('warning', new TranslatableMessage('Max results reached: %maxLimit%', [
                '%maxLimit%' => $limit
            ]));
        }


        return $this->render('audit/list.html.twig', [
            'audits' => $audits,
            'form' => $form->createView(),
        ]);
    }
}
