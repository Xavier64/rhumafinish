<?php

namespace App\Controller;

use App\Services\PanierService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PanierController extends AbstractController
{
    private $panierService;

    public function __construct(PanierService $panierService)
    {
        $this->panierService = $panierService;
    }

    // affichage du panier
    #[Route('/panier', name: 'app_panier')]
    public function index(): Response
    {
        $produits = $this->panierService->getProduitsPanier();

        return $this->render('panier/index.html.twig', [
            'produits' => $produits
        ]);
    }

    // ajout d'un produit au panier
    #[Route('/ajoutPanier/{id}', name: 'app_ajout_panier')]
    public function ajoutPanier($id): Response
    {
        $this->panierService->ajouterProduit($id);

        return $this->redirectToRoute('app_panier');
    }

    // suppression d'un produit du panier
    #[Route('/supprimerPanier/{id}', name: 'app_supprimer_panier')]
    public function supprimerPanier($id): Response
    {
        $this->panierService->supprimerProduit($id);

        return $this->redirectToRoute('app_panier');
    }

    // modifier la quantité d'un produit du panier
    #[Route('/modifierPanier/{id}/{quantite}', name: 'app_modifier_panier')]
    public function modifierPanier($id, $quantite): Response
    {
        $this->panierService->modifierProduit($id, $quantite);

        return $this->redirectToRoute('app_panier');
    }

    // validation du panier => enregistrement de la commande en bdd
    #[Route('/validerPanier', name: 'app_valider_panier')]
    public function validerPanier()
    {
        // s'il n'y a d'utilisateur connecté, on redirige vers la page de connexion
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }
        $user = $this->getUser();
        // on enregistre la commande si le panier n'est pas vide
        if ($this->panierService->enregistrerCommande($user)) {
            return $this->render('panier/paiement.html.twig');
        }
        // si le panier est vide, on redirige vers la page du panier
        return $this->redirectToRoute('app_panier');
    }

    // enregistrement du paiement => passage de la commande en état 1
    #[Route('/payer', name: 'app_payer')]
    public function payer()
    {
        // TODO
        // enregistre le panier en bdd avec l'état 1
        // on vérifie qu"il y a bien un utilisateur connecté
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }
        // si le panier est vide, on redirige vers la page du panier, sinon on modifie l'état de la commande en bdd
        if ($this->panierService->payer($this->getUser())) {
            return $this->redirectToRoute('app_main');
        }
        return $this->redirectToRoute('app_panier');
    }

    // reset session panier
    #[Route('/resetPanier', name: 'app_reset_panier')]
    public function resetPanier(RequestStack $requestStack)
    {
        $session = $requestStack->getSession();
        $session->set('panier', []);
        return $this->redirectToRoute('app_panier');
    }
}
