<?php
// src/Controller/HomeController.php
namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Document\Questions;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Response;

class HomeController extends AbstractController
{

    /**
     * @Route("/dash", name="home")
     */
    public function index(DocumentManager $dm)
    {
        $repository = $dm->getRepository(Questions::class);
        $questions = $repository->findAll();

        return $this->render('dashboard.html.twig', [
        ]);
    }

    /**
     * @Route("/mongotest", methods={"GET"})
     */
    /*public function createAction(DocumentManager $dm)
    {
        $product = new Question();
        $product->setName('A Foo Bar');
        $product->setPrice('19.99');

        $dm->persist($product);
        $dm->flush();

        return new Response('Created product id ' . $product->getId());
    }*/
}