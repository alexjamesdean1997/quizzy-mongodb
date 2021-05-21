<?php
// src/Controller/HomeController.php
namespace App\Controller;

use App\Document\Users;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Security\Core\Security;

class StatsController extends AbstractController
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
            'gastronomie',
            'histoire',
            'litterature',
            'musique',
            'nature',
            'quotidien',
            'sciences',
            'sports',
            'tech',
            'television',
            'voyage'
        ];

        $scoreByCategory = $this->getScoreByCategory($dm, $user);
        $scoreByCategory = $this->order_results($scoreByCategory, 'all', 'desc');

        $totalAnswersByCategory = $this->getTotalAnswersByCategory($dm, $user);
        $totalCorrectAnswersByCategory = $this->getTotalCorrectAnswersByCategory($dm, $user);

        $successRateByCategory = [];

        foreach ($totalAnswersByCategory as $category => $totalAnswers)
        {
            if($totalAnswers > 0){
                $successRateByCategory[$category] = round((($totalCorrectAnswersByCategory[$category] /$totalAnswers) * 100), 2);
            }else{
                $successRateByCategory[$category] = 0;
            }
        }
        $successRateByCategory = $this->order_results($successRateByCategory, 'all', 'desc');

        $rankByCategory = [];
        if($scoreByCategory){
            foreach ($categories as $category)
            {
                $rankByCategory[$category] = $this->getRankByCategory($category, $dm, $user);
            }
            $rankByCategory = $this->order_results($rankByCategory, 'all', 'asc');
        }

        $categoriesPlayed = $this->getMostPlayedCategories($dm, $user);
        $categoriesPlayed = $this->order_results($categoriesPlayed, 'all', 'asc');

        //dump($categoriesPlayed);die();

        return $this->render('stats.html.twig', [
            'rankings' => $rankByCategory,
            'scores' => $scoreByCategory,
            'sucess_rate' => $successRateByCategory,
            'categories_played' => $categoriesPlayed
        ]);
    }

    public function order_results(&$array, $key, $order) {

        if($order == 'desc'){
            arsort($array);
        }else{
            asort($array);
        }

        if(isset($array[$key])){
            $temp = array($key => $array[$key]);
            unset($array[$key]);
            $array = $temp + $array;
            return $array;
        }

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
                ->field('gastronomie')
                ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                    $rankBuilder->expr()->eq('$answer.category', 'gastronomie'),
                    '$answer.score',
                    0
                )))
                ->field('voyage')
                ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                    $rankBuilder->expr()->eq('$answer.category', 'voyage'),
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

    public function getTotalAnswersByCategory($dm, $user){

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
            ->sum($rankBuilder->expr()->sum(1))
            ->field('animaux')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'animaux'),
                1,
                0
            )))
            ->field('celebrites')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'celebrites'),
                1,
                0
            )))
            ->field('cinema')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'cinema'),
                1,
                0
            )))
            ->field('culture')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'culture'),
                1,
                0
            )))
            ->field('geographie')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'geographie'),
                1,
                0
            )))
            ->field('histoire')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'histoire'),
                1,
                0
            )))
            ->field('litterature')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'litterature'),
                1,
                0
            )))
            ->field('musique')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'musique'),
                1,
                0
            )))
            ->field('gastronomie')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'gastronomie'),
                1,
                0
            )))
            ->field('voyage')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'voyage'),
                1,
                0
            )))
            ->field('nature')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'nature'),
                1,
                0
            )))
            ->field('quotidien')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'quotidien'),
                1,
                0
            )))
            ->field('sciences')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'sciences'),
                1,
                0
            )))
            ->field('sports')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'sports'),
                1,
                0
            )))
            ->field('tech')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'tech'),
                1,
                0
            )))
            ->field('television')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'television'),
                1,
                0
            )));

        $score = [];

        foreach ($rankBuilder->getAggregation() as $collec){
            $score = $collec;
            unset($score['_id']);
        }

        return $score;
    }

    public function getTotalCorrectAnswersByCategory($dm, $user){

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
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->gte('$answer.score', 1),
                1,
                0
            )))
            ->field('animaux')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'animaux'),
                $rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                    $rankBuilder->expr()->gte('$answer.score', 1),
                    1,
                    0
                )),
                0
            )))
            ->field('celebrites')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'celebrites'),
                $rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                    $rankBuilder->expr()->gte('$answer.score', 1),
                    1,
                    0
                )),
                0
            )))
            ->field('cinema')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'cinema'),
                $rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                    $rankBuilder->expr()->gte('$answer.score', 1),
                    1,
                    0
                )),
                0
            )))
            ->field('culture')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'culture'),
                $rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                    $rankBuilder->expr()->gte('$answer.score', 1),
                    1,
                    0
                )),
                0
            )))
            ->field('geographie')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'geographie'),
                $rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                    $rankBuilder->expr()->gte('$answer.score', 1),
                    1,
                    0
                )),
                0
            )))
            ->field('histoire')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'histoire'),
                $rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                    $rankBuilder->expr()->gte('$answer.score', 1),
                    1,
                    0
                )),
                0
            )))
            ->field('litterature')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'litterature'),
                $rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                    $rankBuilder->expr()->gte('$answer.score', 1),
                    1,
                    0
                )),
                0
            )))
            ->field('musique')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'musique'),
                $rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                    $rankBuilder->expr()->gte('$answer.score', 1),
                    1,
                    0
                )),
                0
            )))
            ->field('gastronomie')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'gastronomie'),
                $rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                    $rankBuilder->expr()->gte('$answer.score', 1),
                    1,
                    0
                )),
                0
            )))
            ->field('voyage')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'voyage'),
                $rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                    $rankBuilder->expr()->gte('$answer.score', 1),
                    1,
                    0
                )),
                0
            )))
            ->field('nature')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'nature'),
                $rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                    $rankBuilder->expr()->gte('$answer.score', 1),
                    1,
                    0
                )),
                0
            )))
            ->field('quotidien')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'quotidien'),
                $rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                    $rankBuilder->expr()->gte('$answer.score', 1),
                    1,
                    0
                )),
                0
            )))
            ->field('sciences')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'sciences'),
                $rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                    $rankBuilder->expr()->gte('$answer.score', 1),
                    1,
                    0
                )),
                0
            )))
            ->field('sports')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'sports'),
                $rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                    $rankBuilder->expr()->gte('$answer.score', 1),
                    1,
                    0
                )),
                0
            )))
            ->field('tech')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'tech'),
                $rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                    $rankBuilder->expr()->gte('$answer.score', 1),
                    1,
                    0
                )),
                0
            )))
            ->field('television')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'television'),
                $rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                    $rankBuilder->expr()->gte('$answer.score', 1),
                    1,
                    0
                )),
                0
            )));

        $score = [];

        foreach ($rankBuilder->getAggregation() as $collec){
            $score = $collec;
            unset($score['_id']);
        }

        return $score;
    }

    public function getMostPlayedCategories($dm, $user){

        $rankBuilder = $dm->createAggregationBuilder(Users::class);
        $rankBuilder
            ->match()
            ->field('_id')
            ->equals($user->getId())
            ->unwind('$answer')
            ->group()
            ->field('_id')
            ->expression('$_id')
            ->field('animaux')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'animaux'),
                1,
                0
            )))
            ->field('celebrites')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'celebrites'),
                1,
                0
            )))
            ->field('cinema')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'cinema'),
                1,
                0
            )))
            ->field('culture')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'culture'),
                1,
                0
            )))
            ->field('geographie')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'geographie'),
                1,
                0
            )))
            ->field('histoire')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'histoire'),
                1,
                0
            )))
            ->field('litterature')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'litterature'),
                1,
                0
            )))
            ->field('musique')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'musique'),
                1,
                0
            )))
            ->field('gastronomie')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'gastronomie'),
                1,
                0
            )))
            ->field('voyage')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'voyage'),
                1,
                0
            )))
            ->field('nature')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'nature'),
                1,
                0
            )))
            ->field('quotidien')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'quotidien'),
                1,
                0
            )))
            ->field('sciences')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'sciences'),
                1,
                0
            )))
            ->field('sports')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'sports'),
                1,
                0
            )))
            ->field('tech')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'tech'),
                1,
                0
            )))
            ->field('television')
            ->sum($rankBuilder->expr()->sum($rankBuilder->expr()->cond(
                $rankBuilder->expr()->eq('$answer.category', 'television'),
                1,
                0
            )));

        $categories = [];

        foreach ($rankBuilder->getAggregation() as $collec){
            $categories = $collec;
            unset($categories['_id']);
        }

        return $categories;
    }
}