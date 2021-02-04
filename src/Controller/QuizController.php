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

        return $this->render('difficulties.html.twig', [
            "category" => $category
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
            if($category == 'tech'){
                $internetQuestions = $questionRepository->findBy(
                    ['category' => 'internet','difficulty' => $difficulty]
                );
                $computerQuestions = $questionRepository->findBy(
                    ['category' => 'informatique','difficulty' => $difficulty]
                );
                $questions = array_merge($internetQuestions, $computerQuestions);
            }elseif($category == 'aleatoire') {
                $questions = $questionRepository->findBy(
                    ['difficulty' => $difficulty]
                );
            }else{
                $questions = $questionRepository->findBy(
                    ['category' => $category,'difficulty' => $difficulty]
                );
            }
        }else{
            if($category == 'tech'){
                $internetQuestions = $questionRepository->findBy(
                    ['category' => 'internet']
                );
                $computerQuestions = $questionRepository->findBy(
                    ['category' => 'informatique']
                );
                $questions = array_merge($internetQuestions, $computerQuestions);
            }elseif($category == 'aleatoire') {
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

        return $this->render('category.html.twig', [
            "category" => $category,
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
        $success = false;

        if($score){
            $success = true;
        }

        $answer = new Answer();

        $answer->setQuestionId($question);
        $answer->setScore($score);
        $answer->setCategory($category);
        $answer->setSuccess($success);
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