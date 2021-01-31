<?php
// src/Controller/HomeController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Document\Questions;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Response;

class HomeController extends AbstractController
{

    /**
     * @Route("/download", name="download")
     */
    public function index()
    {
        return $this->render('home.html.twig', []);
    }

    /**
     * @Route("/dashboard", name="dashboard")
     */
    public function dashboard()
    {
        return $this->render('dashboard.html.twig', []);
    }

    /**
     * @Route("/savequestion/ajax", name="save_question")
     */
    public function saveQuestion(Request $request, DocumentManager $dm)
    {

        $questionRepository = $dm->getRepository(Questions::class);
        $lastQuestion = $questionRepository->findOneBy([], ['id' => 'desc']);
        $lastId = $lastQuestion->getQid();

        $data = json_decode($request->query->get('data'), true);
        $allQuestions = $questionRepository->findAll();

        $questionsCount = count($allQuestions);
        $categories = [];
        foreach ($allQuestions as $question){
            if (!array_key_exists($question->getCategory(), $categories)) {
                $categories[$question->getCategory()] = 0;
            }else{
                $categories[$question->getCategory()] = $categories[$question->getCategory()] + 1;
            }
        }

        if($data && $data["response_code"] === 0){

            $message = null;

            foreach ($allQuestions as $question){
                if($question->getQuestion() === $data["results"][0]["question"]){
                    $message = 'question already exists in database';
                    $response = array(
                        "code" => 200,
                        "message" => $message,
                        "duplicate" => true,
                        "count" => $questionsCount,
                        "categories" => $categories
                    );
                    return new JsonResponse($response);
                }
            }

            $newQuestion = new Questions();
            $newQuestion->setQid($lastId + 1);
            $newQuestion->setLanguage($data["results"][0]["langue"]);
            $newQuestion->setCategory($data["results"][0]["categorie"]);
            $newQuestion->setQuestion($data["results"][0]["question"]);
            $newQuestion->setCorrectAnswer($data["results"][0]["reponse_correcte"]);
            $newQuestion->setTheme($data["results"][0]["theme"]);
            $newQuestion->setDifficulty($data["results"][0]["difficulte"]);
            $newQuestion->setChoices($data["results"][0]["autres_choix"]);

            $dm->persist($newQuestion);
            $dm->flush();

            $message = 'saved question';
            $response = array(
                "code" => 200,
                "message" => $message,
                "duplicate" => false,
                "count" => $questionsCount + 1,
                "categories" => $categories
            );
            return new JsonResponse($response);

        }else{
            $response = array(
                "code" => 400,
            );
            return new JsonResponse($response);
        }
    }
}