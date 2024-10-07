<?php

namespace App\Controller;

use App\DTO\AuditSearchDTO;
use App\Entity\Audit;
use App\Form\AuditSearchType;
use App\Repository\AuditRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatableMessage;

#[Route(path: '/{_locale}')]
class AuditController extends BaseController
{
    #[Route(path: '/audit', name: 'audit_list')]
    public function list(Request $request, AuthorizationCheckerInterface $authChecker, AuditRepository $repo)
    {
        $this->loadQueryParameters($request);
        $form = $this->createForm(AuditSearchType::class, new AuditSearchDTO());
        $criteria = [];
        $limit = $this->getParameter('resultLimit');
        if (!$authChecker->isGranted('ROLE_ADMIN')) {
            $criteria['user'] = $this->getUser();
        }
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AuditSearchDTO $data */
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
