<?php
// src/Controller/HomeController.php
namespace App\Controller;

use App\Document\Answer;
use App\Document\Users;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Document\Questions;
use Doctrine\ODM\MongoDB\DocumentManager;
use Faker\Factory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Security;

class DashboardController extends AbstractController
{

    /**
     * @var Security
     */
    private $security;
    private $passwordEncoder;
    private $faker;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder, Security $security)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->security = $security;
    }

    /**
     * @Route("/download", name="download")
     */
    public function index()
    {
        return $this->render('home.html.twig', []);
    }

    /**
     * @Route("/edit-data", name="data-edit")
     */
    public function editdata(DocumentManager $dm)
    {
        $questions = $dm->createQueryBuilder(Users::class)
            ->updateMany()
            ->field('category')->equals('internet')
            ->field('category')->set('tech')
            ->getQuery()
            ->execute();

        /*$count = 0;
        foreach ($questions as $question){

            if($question->getCategory() == "informatique" or $question->getCategory() == "internet"){
                dump($question->getCategory());
                $count = $count + 1;
            }
        }
        dump($count);die();*/

        return $this->render('home.html.twig', []);
    }

    /**
     * @Route("/dashboard", name="dashboard")
     */
    public function dashboard(DocumentManager $dm)
    {
        $user = $this->security->getUser();
        $answers = $user->getAnswers();
        $questions_answered = count($answers);

        $builder = $dm->createAggregationBuilder(Users::class);
        $builder
            ->match()
                ->field('_id')
                ->equals($user->getId())
            ->unwind('$answer')
            ->group()
                ->field('_id')
                ->expression(null)
                ->field('TotalScore')
                ->sum('$answer.score')
                ->field('TotalSuccess')
                ->sum($builder->expr()->sum($builder->expr()->cond(
                    $builder->expr()->gte('$answer.score', 1),
                    1,
                    0
                )));

        $result = $builder->execute()->toArray();
        $totalScore = $result[0]["TotalScore"];
        $totalSuccess = $result[0]["TotalSuccess"];

        $rankBuilder = $dm->createAggregationBuilder(Users::class);
        $rankBuilder
            ->unwind('$answer')
            ->group()
                ->field('_id')
                ->expression('$_id')
                ->field('first_name')
                ->expression(
                    $rankBuilder->expr()
                        ->field('$first')
                        ->expression('$first_name')
                )
                ->field('last_name')
                ->expression(
                    $rankBuilder->expr()
                        ->field('$first')
                        ->expression('$last_name')
                )
                ->field('avatar')
                ->expression(
                    $rankBuilder->expr()
                        ->field('$first')
                        ->expression('$avatar')
                )
                ->field('TotalScore')
                ->sum($rankBuilder->expr()->sum('$answer.score'))
            ->sort('TotalScore','desc');

        $rank = 0;
        $leaderboard = [];

        foreach ($rankBuilder->getAggregation() as $key=>$collec){
            if(count($leaderboard) < 5){
                $leaderboard[] = $collec;
            }
            if($collec['_id'] == $user->getId()){
                $rank = $key + 1;
            }
        }

        if($questions_answered){
            $success_rate = ($totalSuccess / $questions_answered) * 100;
        }else{
            $success_rate = 0;
        }

        $stats = [];
        $stats['success-rate'] = round($success_rate, 2);
        $stats['rank'] = $rank;
        $stats['score'] = $totalScore;

        return $this->render('dashboard.html.twig', [
            'stats' => $stats,
            'leaders' => $leaderboard
        ]);
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

            if($data["results"][0]["categorie"] == 'internet' or $data["results"][0]["categorie"] == 'informatique'){
                $data["results"][0]["categorie"] = 'tech';
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

    /**
     * @Route("/loadusers", name="load_users")
     */
    public function usersfixtures(DocumentManager $dm)
    {
        $this->faker = Factory::create();

        for ($i = 1; $i <= 160; $i++) {
            $user = new Users();
            $firstname = strtolower($this->faker->firstName);
            $lastname = strtolower($this->faker->lastName);
            $user->setFirstName($firstname);
            $user->setLastName($lastname);
            $user->setEmail($firstname.'.'.$lastname.'@gmail.com');
            $user->setRoles(['ROLE_USER']);
            $user->setAvatar(rand(1,6));
            $user->setPassword($this->passwordEncoder->encodePassword($user, 'admin123'));
            $dm->persist($user);
            $dm->flush();
        }

        return $this->render('loadusers.html.twig', [

        ]);
    }

    /**
     * @Route("/loadanswers", name="load_answers")
     */
    public function answerfixtures(DocumentManager $dm)
    {

        $questionRepository = $dm->getRepository(Questions::class);
        $userRepository = $dm->getRepository(Users::class);

        $users = $userRepository->findAll();
        $dates = ['25-01-2021' , '04-02-2021'];

        foreach ($users as $user){
            $answers_count = rand(5,80);

            for ($i = 1; $i <= $answers_count; $i++) {
                $answer = new Answer();
                $questionId = rand(867,8203);
                $question = $questionRepository->findOneBy(['qid' => $questionId]);
                $answer->setDate(new \DateTime($dates[array_rand($dates)]));
                $answer->setCategory($question->getCategory());
                $answer->setQuestionId($question->getQid());
                $answer->setScore(rand(0,3));
                $user->addAnswer($answer);
                $dm->persist($user);
                $dm->flush();
            }
        }
        return $this->render('loadanswers.html.twig', [

        ]);
    }

    /**
     * @Route("/updateanswers", name="update_answers")
     */
    public function updateanswers(DocumentManager $dm)
    {

        $userRepository = $dm->getRepository(Users::class);

        $users = $userRepository->findAll();

        $dates = ['25-01-2021' , '04-02-2021'];

        foreach ($users as $user){
            foreach ($user->getAnswers() as $answer){
                dump($answer);
                $answer->setDate(new \DateTime($dates[array_rand($dates)]));
                $user->addAnswer($answer);
                $dm->persist($user);
                $dm->flush();
            }
        }
        return $this->render('loadanswers.html.twig', [

        ]);
    }
}