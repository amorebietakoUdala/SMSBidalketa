<?php

namespace App\Controller;

use App\DTO\HistorySearchDTO;
use App\Entity\History;
use App\Form\HistorySearchType;
use App\Repository\HistoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/{_locale}')]
class HistoryController extends AbstractController
{
    #[Route(path: '/history', name: 'history_list')]
    public function list(Request $request, HistoryRepository $repo)
    {
        $maxLimit = $this->getParameter('historyMaxLimit');

        $form = $this->createForm(HistorySearchType::class, new HistorySearchDTO());

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var HistorySearchDTO $data */
            $data = $form->getData();
            $criteria = $data->toArray();
            $histories = $repo->findByDates($criteria, ['date' => 'DESC'], $maxLimit);

            if (count($histories) === $maxLimit) {
                $this->addFlash('warning', 'Max results reached: %maxLimit%');
            }

            return $this->render('history/list.html.twig', [
                'histories' => $histories,
                'form' => $form->createView(),
                'maxLimit' => $maxLimit,
            ]);
        }

        return $this->render('history/list.html.twig', [
            'histories' => [],
            'form' => $form->createView(),
        ]);
    }
}
