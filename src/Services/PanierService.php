<?php

namespace App\Services;

use App\Entity\Commande;
use App\Entity\CommandeProduit;
use App\Repository\CommandeRepository;
use App\Repository\ProduitRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;

class PanierService
{
    private $requestStack;
    private $produitRepository;
    private $doctrine;
    private $commandeRepository;

    public function __construct(RequestStack $requestStack, ProduitRepository $produitRepository, ManagerRegistry $doctrine, CommandeRepository $commandeRepository)
    {
        $this->requestStack = $requestStack;
        $this->produitRepository = $produitRepository;
        $this->doctrine = $doctrine;
        $this->commandeRepository = $commandeRepository;
    }

    public function setProduitPanier($idProduit, $quantite)
    {
        // on récupère la clé panier en session ; si elle n'existe pas, on la crée
        $panier = $this->requestStack->getSession()->get('panier', []); // $_SESSION
        // $panier =[
        //     151 => 2,
        //     152 => 1
        // ];
        $panier[$idProduit] = $quantite;
        // on remet le tableau modifié ($panier) dans la session
        $this->requestStack->getSession()->set('panier', $panier);
    }

    public function ajouterProduit(int $id)
    {
        $session = $this->requestStack->getSession(); // $_SESSION
        $panier = $session->get('panier'); // $_SESSION["panier]
        // si le produit est déjà dans le panier, on incrémente la quantité
        // id = 153
        if (isset($panier[$id])) { // $panier = [ 153 => 1] , $_SESSION["panier][153] ?
            $panier[$id]++; // $panier = [ 153 => 2], 
        } else { // si le produit n'est pas dans le panier, on l'ajoute avec la quantité 1
            $panier[$id] = 1; // $panier = [ 153 => 1], 
        }
        $session->set('panier', $panier); // $_SESSION["panier][153] = 1 ou $_SESSION["panier][153] = 2
    }

    // modification de la quantité d'un produit du panier
    public function modifierProduit(int $id, int $quantite)
    {
        if ($quantite > 0 && $quantite <= 10) {
            $session = $this->requestStack->getSession(); // $_SESSION
            $panier = $session->get('panier'); // $_SESSION["panier]
            if (isset($panier[$id])) { // $panier = [ 153 => 1] , $_SESSION["panier][153] ?
                $panier[$id] = $quantite; // $panier = [ 153 => 2], 
            }
            $session->set('panier', $panier); // $_SESSION["panier][153] = 1 ou $_SESSION["panier][153] = 2
        }
    }

    public function getProduitsPanier()
    {
        $produits = [];
        $session = $this->requestStack->getSession();
        $panier = $session->get('panier');
        // dd($panier);
        if ($panier) {
            // $panier =[
            //     151 => 2,
            //     152 => 1
            // ];
            foreach ($panier as $id => $quantite) {
                $produit = $this->produitRepository->find($id);
                $produit->qtite = $quantite;
                $produits[] = $produit;
            }
        }

        return $produits;
    }

    // supprimer un produit du panier
    public function supprimerProduit(int $id)
    {
        $session = $this->requestStack->getSession();
        $panier = $session->get('panier');
        unset($panier[$id]);
        $session->set('panier', $panier);
    }

    public function enregistrerCommande($user)
    {
        // on récupère le panier en session
        $session = $this->requestStack->getSession();
        $panier = $session->get('panier');

        if (!empty($panier)) {

            // s'il y a une comande enregistrée en bdd avec l'état 0, on la supprime
            $commande = $this->commandeRepository->findOneBy(['user' => $user, 'etat' => 0]);
            if ($commande) {
                $this->commandeRepository->remove($commande);
            }

            // on récupère l'entity manager pour pouvoir faire des persist et des flush
            $manager = $this->doctrine->getManager();
            // on crée une Commande
            $commande = new Commande();
            $commande->setDate(new \DateTime());
            $commande->setEtat(0);
            $commande->setUser($user);


            // pour chaque élément du panier, on crée une CommandeProduit et on la relie à la commande créée précédemment ($commande)
            foreach ($panier as $id => $quantite) {
                $commandeProduit = new CommandeProduit();
                $commandeProduit->setCommande($commande);
                $produit = $this->produitRepository->find($id); // on récupère le produit correspondant à $id
                $commandeProduit->setProduit($produit);
                $commandeProduit->setPrixVente($produit->getPrix());
                $commandeProduit->setQuantite($quantite);
                $manager->persist($commandeProduit);
            }
            // on enregistre en bdd
            $manager->persist($commande);
            $manager->flush();


            return true;
        }
        return false;
    }

    // valider commande (passage de l'état 0 à 1)
    public function payer($user)
    {
        // SELECT * FROM commande WHERE user_id = $user->getId() AND etat = 0
        // => renvoie la commande d'id 12 par exemple
        $commande = $this->commandeRepository->findOneBy(['user' => $user, 'etat' => 0]);
        if ($commande) {
            // update commande set etat = 1 WHERE id=12
            $commande->setEtat(1);
            $this->doctrine->getManager()->flush();
            // on récupère le panier en session
            $this->requestStack->getSession()->remove('panier');

            return true;
        }
        return false;
    }
}
