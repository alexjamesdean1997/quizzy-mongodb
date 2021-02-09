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
    public function stats(DocumentManager $dm)
    {
        $user = $this->security->getUser();
        $answers = $user->getAnswers();
        $questions_answered = count($answers);

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
            ->field('animauxScore')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'animaux'),
                '$answer.score',
                0
            )))
            ->field('celebritesScore')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'celebrites'),
                '$answer.score',
                0
            )))
            ->field('cultureScore')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'culture'),
                '$answer.score',
                0
            )))
            ->field('geographieScore')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'geographie'),
                '$answer.score',
                0
            )))
            ->field('histoireScore')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'histoire'),
                '$answer.score',
                0
            )))
            ->field('litteratureScore')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'litterature'),
                '$answer.score',
                0
            )))
            ->field('musiqueScore')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'musique'),
                '$answer.score',
                0
            )))
            ->field('natureScore')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'nature'),
                '$answer.score',
                0
            )))
            ->field('quotidienScore')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'quotidien'),
                '$answer.score',
                0
            )))
            ->field('sciencesScore')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'sciences'),
                '$answer.score',
                0
            )))
            ->field('sportsScore')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'sports'),
                '$answer.score',
                0
            )))
            ->field('internetScore')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'internet'),
                '$answer.score',
                0
            )))
            ->field('informatiqueScore')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'informatique'),
                '$answer.score',
                0
            )))
            ->field('televisionScore')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'television'),
                '$answer.score',
                0
            )));

        //unranked users
        $unranked_users = [];
        foreach ($rankBuilder->getAggregation() as $key=>$collec){
            $unranked_users[] = $collec;
        }
        dump($unranked_users);

        $categoryScores = [
            'animauxScore',
            'celebritesScore',
            'cultureScore',
            'geographieScore',
            'histoireScore',
            'litteratureScore',
            'musiqueScore',
            'natureScore',
            'quotidienScore',
            'sciencesScore',
            'sportsScore',
            'internetScore',
            'informatiqueScore',
            'televisionScore',
            'TotalScore'
        ];

        $rankContainer = [];
        //animaux category rank
        foreach ($categoryScores as $categoryScore)
        {
            $rankContainer[$categoryScore] = $this->rankByCategory($categoryScore, $unranked_users);
        }
        dump($rankContainer);die();


        // for score + success rate
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

        if($questions_answered){
            $success_rate = ($totalSuccess / $questions_answered) * 100;
        }else{
            $success_rate = 0;
        }

        $overalls = [];
        $overalls['success-rate'] = round($success_rate, 2);
        $overalls['rank'] = $rank;
        $overalls['score'] = $totalScore;

        return $this->render('stats.html.twig', [
            'overalls' => $overalls
        ]);
    }

    public function rankByCategory($category, $users){
        $sorter = [];
        foreach ($users as $key => $row)
        {
            $sorter[$key] = $row[$category];
        }
        array_multisort($sorter, SORT_DESC, $users);
        return $users;
    }
}