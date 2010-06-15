<?php

namespace Bundle\LichessBundle\Controller;

use Symfony\Framework\WebBundle\Controller;
use Bundle\LichessBundle\Socket;
use Bundle\LichessBundle\Chess\Generator;

class MainController extends Controller
{

    public function indexAction($color)
    {
        //$game = $this->getGameTestPromotion();
        //$game = $this->getGameTestStalemate();
        $game = $this->getNewGame();
        $player = $game->getPlayer($color);
        $game->setCreator($player);

        $this->container->getLichessPersistenceService()->save($game);
        $socket = new Socket($player, $this->container['kernel.root_dir'].'/cache/socket');
        $socket->write(array());

        return $this->render('LichessBundle:Main:index', array(
            'player' => $player
        ));
    }

    public function aboutAction()
    {
        return $this->render('LichessBundle:Main:about');
    }

    public function notFoundAction()
    {
        return $this->render('LichessBundle:Main:notFound');
    }

    protected function getNewGame()
    {
        $generator = new Generator();
        return $generator->createGame();
    }

    protected function getGameTestPromotion()
    {
        $generator = new Generator();
        $data = <<<EOF
       k
        
 PPPP   
        
        
   pppp 
        
K       
EOF;
        return $generator->createGameFromVisualBlock($data);
    }

    protected function getGameTestStalemate()
    {
        $generator = new Generator();
        $data = <<<EOF
       k
     K  
     Q  
        
        
        
        
        
EOF;
        return $generator->createGameFromVisualBlock($data);
    }
}
