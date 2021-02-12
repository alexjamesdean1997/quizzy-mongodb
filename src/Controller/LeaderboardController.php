<?php
// src/Controller/HomeController.php
namespace App\Controller;

use App\Document\Users;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Security;

class LeaderboardController extends AbstractController
{

    /**
     * @Route("/leaderboard", name="leaderboard")
     */
    public function leaderboard(DocumentManager $dm)
    {
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
                ->field('overall')
                ->sum($rankBuilder->expr()->sum('$answer.score'))
            ->sort(array(
                'overall' => 'desc',
                '_id'       => 'desc',
            ));

        $overall_rank = [];

        foreach ($rankBuilder->getAggregation() as $key=>$collec){
            if(count($overall_rank) < 10){
                $overall_rank[] = $collec;
            }
        }

        $categories = [
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

        $rankContainer = [];
        $rankContainer['overall'] = $overall_rank;
        foreach ($categories as $category)
        {
            $rankContainer[$category] = $this->getCategoryRank($category, $dm);
        }

        //dump($rankContainer);die();

        return $this->render('leaderboard.html.twig', [
            'ranks' => $rankContainer
        ]);
    }

    public function getCategoryRank($category, $dm){
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

        $ranked_cat = [];
        foreach ($rankBuilder->getAggregation() as $collec){
            if(count($ranked_cat) < 10){
                $ranked_cat[] = $collec;
            }else{
                return $ranked_cat;
            }
        }

        return $ranked_cat;
    }
}