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

        //dump($rankByCategory);die();

        return $this->render('stats.html.twig', [
            'rankings' => $rankByCategory
        ]);
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
}