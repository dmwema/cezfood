<?php

namespace App\Controller;

use App\Entity\Users;
use App\Repository\CategoriesRepository;
use App\Repository\OrdersDetailsRepository;
use App\Repository\ProductsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    #[Route('/', name: 'main')]
    public function index(CategoriesRepository $categoriesRepository, ProductsRepository $productsRepository): Response
    {
        /**
         * @var Users $user
         */
        $user = $this->getUser();
        $suggestions = [];

        if ($user !== null) {
            $suggestions = $productsRepository->findSuggestions($user);
        }

        return $this->render('main/index.html.twig', [
            'categories' => $categoriesRepository->findBy([], ['categoryOrder' => 'asc']),
            'suggestions' => $suggestions
        ]);
    }
}
