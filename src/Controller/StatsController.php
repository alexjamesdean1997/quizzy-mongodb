<?php
// src/Controller/HomeController.php
namespace App\Controller;

use App\Document\Users;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Security;

class StatsController extends AbstractController
{

    /**
     * @var Security
     */
    private $security;
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder, Security $security)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->security = $security;
    }

    /**
     * @Route("/stats", name="stats")
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
                ->sum($builder->expr()->cond('$answer.success',1,0));

        $result = $builder->execute()->toArray();
        $totalScore = $result[0]["TotalScore"];
        $totalSuccess = $result[0]["TotalSuccess"];

        $rankBuilder = $dm->createAggregationBuilder(Users::class);
        $rankBuilder
            ->unwind('$answer')
            ->group()
                ->field('_id')
                ->expression('$_id')
                ->field('TotalScore')
                ->sum($rankBuilder->expr()->sum('$answer.score'))
            ->sort('TotalScore','desc');

        $rank = 0;

        foreach ($rankBuilder->getAggregation() as $key=>$collec){
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

        return $this->render('stats.html.twig', [
            'stats' => $stats
        ]);
    }
}