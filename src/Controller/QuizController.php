<?php
// src/Controller/HomeController.php
namespace App\Controller;

use App\Document\Answer;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Document\Questions;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;

class QuizController extends AbstractController
{

    /**
     * @var Security
     */
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @Route("/quiz", name="quiz")
     */
    public function quiz()
    {
        return $this->render('quiz.html.twig', []);
    }

    /**
     * @Route("/quiz/{category}", name="quiz_difficulty")
     */
    public function difficulty($category)
    {
        $categoryName = $category;

        if($category == "aleatoire"){
            $categoryName = "aléatoire";
        }elseif($category == "cinema"){
            $categoryName = "cinéma";
        }elseif($category == "celebrites"){
            $categoryName = "célébrités";
        }elseif($category == "geographie"){
            $categoryName = "géographie";
        }elseif($category == "litterature"){
            $categoryName = "littérature";
        }elseif($category == "television"){
            $categoryName = "télévision";
        }

        return $this->render('difficulties.html.twig', [
            "category" => $category,
            "category_name" => $categoryName
        ]);
    }

    /**
     * @Route("/getcorrectanswers/ajax", name="get_correct_answers")
     */
    public function getCorrectAnswers(Request $request, DocumentManager $dm)
    {
        $questionRepository = $dm->getRepository(Questions::class);

        $questionId = json_decode($request->query->get('data'), true);

        $answer = $questionRepository->find($questionId)->getCorrectAnswer();

        $response = array(
            "code" => 200,
            "answer" => $answer
        );
        return new JsonResponse($response);
    }

    /**
     * @Route("/quiz/{category}/{difficulty}", name="quiz_category")
     */
    public function category(DocumentManager $dm, $category, $difficulty)
    {
        $questionRepository = $dm->getRepository(Questions::class);


        if($difficulty === 'easy'){
            $difficulty = 'débutant';
        }elseif($difficulty === 'confirmed'){
            $difficulty = 'confirmé';
        }

        if($difficulty === 'débutant' || $difficulty === 'confirmé' || $difficulty === 'expert'){
            if($category == 'aleatoire') {
                $questions = $questionRepository->findBy(
                    ['difficulty' => $difficulty]
                );
            }else{
                $questions = $questionRepository->findBy(
                    ['category' => $category,'difficulty' => $difficulty]
                );
            }
        }else{
            if($category == 'aleatoire') {
                $questions = $questionRepository->findAll();
            }else{
                $questions = $questionRepository->findBy(
                    ['category' => $category]
                );
            }
        }

        $sessionQuestions = $this->randomize($questions);

        foreach($sessionQuestions as $sessionQuestion) {
            $choices = $sessionQuestion->getChoices();
            shuffle($choices);
            $sessionQuestion->setChoices($choices);
        }

        $categoryName = $category;

        if($category == "aleatoire"){
            $categoryName = "aléatoire";
        }elseif($category == "celebrites"){
            $categoryName = "célébrités";
        }elseif($category == "cinema"){
            $categoryName = "cinéma";
        }elseif($category == "geographie"){
            $categoryName = "géographie";
        }elseif($category == "litterature"){
            $categoryName = "littérature";
        }elseif($category == "television"){
            $categoryName = "télévision";
        }

        return $this->render('category.html.twig', [
            "category" => $category,
            "category_name" => $categoryName,
            "difficulty" => $difficulty,
            "questions" => $sessionQuestions
        ]);
    }

    public function randomize($questions){
        $rand_keys = array_rand($questions, 10);
        $sessionQuestions = [];

        foreach($rand_keys as $item) {
            $sessionQuestions[] = $questions[$item];
        }

        return $sessionQuestions;
    }


    /**
     * @Route("/saveanswer/ajax", name="save_answer")
     */
    public function saveAnswer(Request $request, DocumentManager $dm)
    {
        $data = json_decode($request->query->get('data'), true);
        $user = $this->security->getUser();
        $question = $data['questionId'];
        $category = $data['category'];
        $score = $data['score'];

        $answer = new Answer();

        $answer->setQuestionId($question);
        $answer->setScore($score);
        $answer->setCategory($category);
        $answer->setDate(new \DateTime());
        $user->addAnswer($answer);

        $dm->persist($user);
        $dm->flush();

        $message = 'saved answer';

        $response = array(
            "code" => 200,
            "message" => $message
        );
        return new JsonResponse($response);
    }
}