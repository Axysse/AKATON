<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\AjoutProduitType;
use Doctrine\Migrations\Events;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_main')]
    public function index(EntityManagerInterface $entity): Response
    {

        $products = $entity->getRepository(Product::class)->findAll();
        return $this->render('main/index.html.twig', [
            'controller_name' => 'MainController',
            'products' => $products
        ]);
    }

    #[Route('/main/produit{id}', name: 'app_produit')]
    public function produit($id, EntityManagerInterface $entity): Response
    {
        $products = $entity->getRepository(Product::class)->findAll();

        $product = $entity->getRepository(Product::class)->findOneBy(['id' => $id]);

        if (isset($_POST['supprimer'])) {
            $del = $entity->getRepository(Product::class)->find($_POST['supprimer']);
            $entity->remove($del);
            $entity->flush();
            return $this->redirectToRoute('app_main');
        }

        if (isset($_POST['modifier'])) {
            $update = $entity->getRepository(Product::class)->find($_POST['modifier']);
            
                $update->setName($_POST['name']);
                $update->setDescription($_POST['description']);
                $update->setPrice($_POST['price']);
                $update->setStock($_POST['stock']);
                $update->setAuthor($_POST['author']);
                $update->setEnable($_POST['enable']);
                $entity->flush();
                return $this->redirectToRoute('app_main');
            
        }

        return $this->render('main/produit.html.twig', [
            'controller_name' => 'MainController',
            'products' => $products,
            'product' => $product
        ]);
    }

    #[Route('/main/soumission', name: 'app_soumission')]
    public function soumission(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {

        $product = new Product();

        $form = $this->createForm(AjoutProduitType::class, $product);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('image')->getData();

            if ($file) {

                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = 'images/' . $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();


                $file->move(
                    $this->getParameter('kernel.project_dir') . '/assets/images',
                    $newFilename
                );
                $product->setImage($newFilename);
            }
            $em->persist($product);
            $em->flush();
            $this->addFlash("success", "Ajout du produit réussit");
            return $this->redirectToRoute('app_main');
        }

        // $products = $em->getRepository(Product::class)->findAll();
        return $this->render('main/soumission.html.twig', [
            'controller_name' => 'MainController',
            'form' => $form->createView()
        ]);
    }
}
