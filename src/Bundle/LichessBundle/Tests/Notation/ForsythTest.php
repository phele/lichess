<?php

namespace Bundle\LichessBundle\Tests\Notation;

use Bundle\LichessBundle\Chess\Generator;
use Bundle\LichessBundle\Tests\TestManipulator;
use Bundle\LichessBundle\Notation\Forsyth;
use Bundle\LichessBundle\Document\Game;
use Bundle\LichessBundle\Document\Player;

class ForsythTest extends \PHPUnit_Framework_TestCase
{
    public function testExport()
    {
        $generator = new Generator();
        $game = $generator->createGame();
        $manipulator = new TestManipulator($game, new \Bundle\LichessBundle\Document\Stack());
        $this->assertEquals('rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1', Forsyth::export($game));
        $manipulator->play('e2 e4');
        $this->assertEquals('rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq e3 0 1', Forsyth::export($game));
        $manipulator->play('c7 c5');
        $this->assertEquals('rnbqkbnr/pp1ppppp/8/2p5/4P3/8/PPPP1PPP/RNBQKBNR w KQkq c6 0 2', Forsyth::export($game));
        $manipulator->play('g1 f3');
        $this->assertEquals('rnbqkbnr/pp1ppppp/8/2p5/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq - 1 2', Forsyth::export($game));
        $manipulator->play('g8 h6');
        $this->assertEquals('rnbqkb1r/pp1ppppp/7n/2p5/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq - 2 3', Forsyth::export($game));
        $manipulator->play('a2 a3');
        $this->assertEquals('rnbqkb1r/pp1ppppp/7n/2p5/4P3/P4N2/1PPP1PPP/RNBQKB1R b KQkq - 0 3', Forsyth::export($game));
    }

    public function testExportCastling()
    {
        $generator = new Generator();
        $game = $generator->createGame();
        $game->getBoard()->getPieceByKey('a1')->setFirstMove(1);
        $game->getBoard()->getPieceByKey('h8')->setFirstMove(1);
        $manipulator = new TestManipulator($game, new \Bundle\LichessBundle\Document\Stack());
        $this->assertEquals('rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w Kq - 0 1', Forsyth::export($game));
        $game->getBoard()->getPieceByKey('a8')->setFirstMove(1);
        $game->getBoard()->getPieceByKey('h1')->setFirstMove(1);
        $this->assertEquals('rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w - - 0 1', Forsyth::export($game));
    }

    public function testDiffToMove()
    {
        $generator = new Generator();
        $game = $generator->createGame();
        $this->assertEquals(null, Forsyth::diffToMove($game, 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq'));
        $this->assertEquals('e2 e4', Forsyth::diffToMove($game, 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq'));
    }

    public function testDiffToMoveEnPassant()
    {
        $data = <<<EOF
rnbqkbnr
p pppppp

Pp


 PPPPPPP
RNBQKBNR
EOF;

        $game = $this->createGame($data);
        $game->setTurns(4);
        $game->getBoard()->getPieceByKey('b5')->setFirstMove(3);
        $this->assertEquals('a5 b6', Forsyth::diffToMove($game, 'rnbqkbnr/p1pppppp/1P6/8/8/8/1PPPPPPP/RNBQKBNR b KQkq'));
    }

    /**
     * @dataProvider fenProvider
     */
    public function testImportExport($fen)
    {
        $game = $this->createGame();
        Forsyth::import($game, $fen);
        $simplifyFen = function($fen) {
            return substr($fen, 0, strpos($fen, ' '));
        };
        $this->assertEquals($simplifyFen($fen), $simplifyFen(Forsyth::export($game)));
    }

    public function fenProvider()
    {
        return array(
            array('rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1'),
            array('rnbqkb1r/pp1ppppp/7n/2p5/4P3/P4N2/1PPP1PPP/RNBQKB1R b KQkq - 0 3'),
            array('rnbqkbnr/pp1ppppp/8/2p5/4P3/8/PPPP1PPP/RNBQKBNR w KQkq c6 0 2')
        );
    }

    /**
     * Get a game from visual data block
     *
     * @return Game
     **/
    protected function createGame($data = null)
    {
        $generator = new Generator();
        if($data) {
            $game =  $generator->createGameFromVisualBlock($data);
        }
        else {
            $game =  $generator->createGame();
        }
        $game->setStatus(Game::STARTED);

        return $game;
    }
}
