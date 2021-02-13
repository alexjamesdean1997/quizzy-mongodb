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

        $categories = [
            'all',
            'animaux',
            'celebrites',
            'cinema',
            'culture',
            'geographie',
            'histoire',
            'litterature',
            'musique',
            'nature',
            'quotidien',
            'sciences',
            'sports',
            'tech',
            'television'
        ];

        $rankByCategory = [];
        foreach ($categories as $category)
        {
            $rankByCategory[$category] = $this->getRankByCategory($category, $dm, $user);
        }
        $rankByCategory = $this->order_results($rankByCategory, 'all', 'asc');

        $scoreByCategory = $this->getScoreByCategory($dm, $user);
        $scoreByCategory = $this->order_results($scoreByCategory, 'all', 'desc');

        return $this->render('stats.html.twig', [
            'rankings' => $rankByCategory,
            'scores' => $scoreByCategory
        ]);
    }

    public function order_results(&$array, $key, $order) {

        if($order == 'desc'){
            arsort($array);
        }else{
            asort($array);
        }

        $temp = array($key => $array[$key]);
        unset($array[$key]);
        $array = $temp + $array;
        return $array;
    }

    public function getRankByCategory($category, $dm, $user){

        $rankBuilder = $dm->createAggregationBuilder(Users::class);
        if($category == 'all'){
            $rankBuilder
                ->unwind('$answer')
                ->group()
                    ->field('_id')
                    ->expression('$_id')
                    ->field($category)
                    ->sum($rankBuilder->expr()->sum('$answer.score'))
                ->sort(array(
                    $category => 'desc',
                    '_id'       => 'desc',
                ));
        }else{
            $rankBuilder
                ->unwind('$answer')
                ->group()
                    ->field('_id')
                    ->expression('$_id')
                    ->field($category)
                    ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                        $rankBuilder->expr()->eq('$answer.category', $category),
                        '$answer.score',
                        0
                    )))
                ->sort(array(
                    $category => 'desc',
                    '_id'       => 'desc',
                ));

        }

        $rank = 0;

        foreach ($rankBuilder->getAggregation() as $key=>$collec){
            if($collec['_id'] == $user->getId()){
                $rank = $key + 1;
            }
        }

        return $rank;
    }

    public function getScoreByCategory($dm, $user){

        $rankBuilder = $dm->createAggregationBuilder(Users::class);
        $rankBuilder
            ->match()
                ->field('_id')
                ->equals($user->getId())
            ->unwind('$answer')
            ->group()
                ->field('_id')
                ->expression('$_id')
                ->field('all')
                ->sum($rankBuilder->expr()->sum('$answer.score'))
                ->field('animaux')
                ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                    $rankBuilder->expr()->eq('$answer.category', 'animaux'),
                    '$answer.score',
                    0
                )))
                ->field('celebrites')
                ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                    $rankBuilder->expr()->eq('$answer.category', 'celebrites'),
                    '$answer.score',
                    0
                )))
                ->field('cinema')
                ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                    $rankBuilder->expr()->eq('$answer.category', 'cinema'),
                    '$answer.score',
                    0
                )))
                ->field('culture')
                ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                    $rankBuilder->expr()->eq('$answer.category', 'culture'),
                    '$answer.score',
                    0
                )))
                ->field('geographie')
                ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                    $rankBuilder->expr()->eq('$answer.category', 'geographie'),
                    '$answer.score',
                    0
                )))
                ->field('histoire')
                ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                    $rankBuilder->expr()->eq('$answer.category', 'histoire'),
                    '$answer.score',
                    0
                )))
                ->field('litterature')
                ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                    $rankBuilder->expr()->eq('$answer.category', 'litterature'),
                    '$answer.score',
                    0
                )))
                ->field('musique')
                ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                    $rankBuilder->expr()->eq('$answer.category', 'musique'),
                    '$answer.score',
                    0
                )))
                ->field('nature')
                ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                    $rankBuilder->expr()->eq('$answer.category', 'nature'),
                    '$answer.score',
                    0
                )))
                ->field('quotidien')
                ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                    $rankBuilder->expr()->eq('$answer.category', 'quotidien'),
                    '$answer.score',
                    0
                )))
                ->field('sciences')
                ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                    $rankBuilder->expr()->eq('$answer.category', 'sciences'),
                    '$answer.score',
                    0
                )))
                ->field('sports')
                ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                    $rankBuilder->expr()->eq('$answer.category', 'sports'),
                    '$answer.score',
                    0
                )))
                ->field('tech')
                ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                    $rankBuilder->expr()->eq('$answer.category', 'tech'),
                    '$answer.score',
                    0
                )))
                ->field('television')
                ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                    $rankBuilder->expr()->eq('$answer.category', 'television'),
                    '$answer.score',
                    0
                )));

        $score = [];

        foreach ($rankBuilder->getAggregation() as $collec){
            $score = $collec;
            unset($score['_id']);
        }

        return $score;
    }
}