<?php

namespace App\Controller;

use App\Document\Questions;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{

    /**
     * @Route("/", name="home")
     */
    public function index(DocumentManager $dm)
    {
        $questionRepository = $dm->getRepository(Questions::class);
        $questions = $questionRepository->findAll();

        $rand_keys = array_rand($questions, 3);
        $sessionQuestions = [];

        foreach($rand_keys as $item) {
            $sessionQuestions[] = $questions[$item];
        }

        return $this->render('landing.html.twig', [
            "questions" => $sessionQuestions
        ]);
    }
}