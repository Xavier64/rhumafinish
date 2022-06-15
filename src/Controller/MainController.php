<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Repository\CommandeRepository;
use App\Repository\ProduitRepository;
use App\Services\PanierService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    // charge la commande enregistrée en bdd s'il y en a une dans le panier
    #[Route('/initAfterLogin', name: 'initAfterLogin')]
    public function initAfterLogin(CommandeRepository $commandeRepository, PanierService $panierService)
    {
        // 1 - on récupère la commande enregistré en bdd
        // SELECT * FROM commande WHERE user_id = 1 and etat = 0
        $commande = $commandeRepository->findOneBy(['user' => $this->getUser(), 'etat' => 0]);
        if ($commande) {
            // 2 - on récupère les produits de la commande et on les ajoute au panier
            foreach ($commande->getCommandeProduits() as $commandeProduit) {
                $panierService->setProduitPanier($commandeProduit->getProduit()->getId(), $commandeProduit->getQuantite());
            }
        }
        return $this->redirectToRoute('app_main');
    }


    #[Route('/', name: 'app_main')]
    public function index(ProduitRepository $produitRepository): Response
    {
        return $this->render('main/index.html.twig', [
            'produits' => $produitRepository->findAll(),
        ]);
    }

    #[Route('/produit/{id}', name: 'app_produit')]
    public function produit(Produit $produit) // on récupère une instance de la class Produit (entity) dans la variable $produit => params converters
    {
        return $this->render('main/produit.html.twig', [
            'produit' => $produit
        ]);
    }
}
