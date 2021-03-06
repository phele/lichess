<?php

namespace Bundle\LichessBundle\Document;

use Doctrine\ODM\MongoDB\DocumentRepository;
use FOS\UserBundle\Model\User;
use DateTime;
use MongoDate;

class GameRepository extends DocumentRepository
{
    /**
     * Find all games played by a user
     *
     * @return array
     **/
    public function findRecentByUser(User $user)
    {
        return $this->createRecentByUserQuery($user)
            ->getQuery()->execute();
    }

    /**
     * Find games played by a potential cheater, where we can cancel the elo points exchanged
     *
     * @return array
     **/
    public function findCancelableByUser(User $user)
    {
        return $this->createQueryBuilder()
            ->field('winnerUserId')->equals($user->getId())
            ->field('isRated')->equals(true)
            ->field('isEloCanceled')->notEqual(true)
            ->getQuery()->execute();
    }

    /**
     * Finds one game by its Id
     *
     * @param string $id
     * @return Game or null
     **/
    public function findOneById($id)
    {
        return $this->find($id);
    }

    /**
     * Tells if a game with this id exists
     *
     * @param string $id
     * @return bool
     */
    public function existsById($id)
    {
        return 1 === $this->createQueryBuilder()
            ->field('id')->equals($id)
            ->getQuery()->count();
    }

    /**
     * Gets the number of games with this status
     *
     * @return int
     */
    public function countByStatus($status)
    {
        return $this->createQueryBuilder()
            ->field('status')->equals($status)
            ->getQuery()->count();
    }

    /**
     * Find ids of more recent games
     *
     * @return array
     **/
    public function findRecentStartedGameIds($nb)
    {
        $data = $this->createRecentQuery()
            ->hydrate(false)
            ->field('status')->equals(Game::STARTED)
            ->select('id')
            ->limit($nb)
            ->getQuery()->execute();
        $ids = array_keys(iterator_to_array($data));

        return $ids;
    }

    /**
     * Find games for the given ids, in the ids order
     *
     * @return array
     **/
    public function findGamesByIds($ids)
    {
        if(is_string($ids)) {
            $ids = explode(',', $ids);
        }

        $games = $this->createQueryBuilder()
            ->field('_id')->in($ids)
            ->getQuery()->execute();

        $games = iterator_to_array($games);

        // sort games in the order of ids
        $idPos = array_flip($ids);
        usort($games, function($a, $b) use ($idPos)
        {
            return $idPos[$a->getId()] > $idPos[$b->getId()];
        });

        return $games;
    }

    /**
     * Return the number of games
     *
     * @return int
     **/
    public function getNbGames()
    {
        return $this->createQueryBuilder()->getQuery()->count();
    }

    /**
     * Returns the number of games being played now
     *
     * @return int
     */
    public function countPlaying()
    {
        return $this->createQueryBuilder()
            ->field('updatedAt')->gt(new \DateTime('-15 seconds'))
            ->getQuery()
            ->count();
    }

    /**
     * Returns the number of games created recently
     *
     * @return int
     */
    public function countRecentlyCreated()
    {
        return $this->createQueryBuilder()
            ->field('createdAt')->gt(new \DateTime('-1 minute'))
            ->getQuery()
            ->count();
    }

    /**
     * Return the number of mates
     *
     * @return int
     **/
    public function getNbMates()
    {
        return $this->createQueryBuilder()
            ->field('status')->equals(Game::MATE)
            ->getQuery()->count();
    }

    /**
     * Query of all games ordered by updatedAt
     *
     * @return Doctrine\ODM\Mongodb\Query
     **/
    public function createRecentQuery()
    {
        return $this->createQueryBuilder()
            ->sort('updatedAt', 'DESC');
    }

    /**
     * Query of games played by a user ordered by updatedAt
     *
     * @param  User $user
     * @return Doctrine\ODM\Mongodb\Query
     **/
    public function createRecentByUserQuery(User $user)
    {
        return $this->createByUserQuery($user)
            ->sort('updatedAt', 'DESC');
    }

    /**
     * Query of games played by a user
     *
     * @param  User $user
     * @return Doctrine\ODM\Mongodb\Query
     **/
    public function createByUserQuery(User $user)
    {
        return $this->createQueryBuilder()
            ->field('userIds')->equals((string) $user->getId());
    }

    /**
     * Query of at least started games of a user
     *
     * @return Doctrine\ODM\Mongodb\Query
     **/
    public function createRecentStartedOrFinishedByUserQuery(User $user)
    {
        return $this->createRecentByUserQuery($user)
            ->field('status')->gte(Game::STARTED);
    }

    /**
     * Query of at least started games
     *
     * @return Doctrine\ODM\Mongodb\Query
     **/
    public function createRecentStartedOrFinishedQuery()
    {
        return $this->createRecentQuery()
            ->field('status')->gte(Game::STARTED);
    }

    /**
     * Gets the games won by the user
     *
     * @return Builder
     */
    public function createRecentByWinnerQuery(User $user)
    {
        return $this->createRecentQuery()
            ->field('winnerUserId')->equals($user->getId());
    }

    /**
     * Gets the games lost by the user
     *
     * @return Builder
     */
    public function createRecentByLoserQuery(User $user)
    {
        return $this->createRecentByUserQuery($user)
            ->field('winnerUserId')->exists(true)
            ->field('winnerUserId')->notEqual($user->getId());
    }

    /**
     * Gets the games drawn by the user
     *
     * @return Builder
     */
    public function createRecentByDrawerQuery(User $user)
    {
        return $this->createRecentByUserQuery($user)
            ->field('status')->equals(Game::DRAW);
    }

    /**
     * Query of at least mate games
     *
     * @return Doctrine\ODM\Mongodb\Query
     **/
    public function createRecentMateQuery()
    {
        return $this->createRecentQuery()
            ->field('status')->equals(Game::MATE);
    }

    public function findSimilar(Game $game, \DateTime $since)
    {
        return $this->createQueryBuilder()
            ->field('id')->notEqual($game->getId())
            ->field('updatedAt')->gt(new \MongoDate($since->getTimestamp()))
            ->field('status')->equals(Game::STARTED)
            ->field('turns')->equals($game->getTurns())
            ->field('pgnMoves')->equals($game->getPgnMoves())
            ->hint(array('updatedAt' => -1))
            ->getQuery()->execute();
    }

    /**
     * Find old, unplayed games
     *
     * @return array
     */
    public function findCandidatesToCleanup($max)
    {
        $date = date_create('-2 day')->getTimestamp();

        $games = $this->createQueryBuilder()
            ->field('turns')->lt(2)
            ->sort('createdAt', 'asc')
            ->limit($max)
            ->hydrate(false)
            ->select('createdAt')
            ->getQuery()->execute();

        $ids = array();
        foreach ($games as $game) {
            $createdAt = isset($game['createdAt']) ? $game['createdAt']->sec : null;
            if (!$createdAt || $createdAt < $date) {
                $ids[] = $game['_id'];
            } else {
                break;
            }
        }

        return $ids;
    }

    /**
     * Find unfinished games where an opponent flagged
     *
     * @return array
     */
    public function findCandidatesToFinish()
    {
        $date = new DateTime('-2 hours');
        return $this->createQueryBuilder()
            ->field('status')->equals(Game::STARTED)
            ->field('clock')->exists(true)
            ->field('clock')->notEqual(null)
            ->field('updatedAt')->lt(new MongoDate($date->getTimestamp()))
            ->limit(500)
            ->getQuery()->execute();
    }

    /**
     * Find games played between these two players
     *
     * @return array of Game
     */
    public function createRecentByUsersQuery(User $playerA, User $playerB)
    {
        $qb = $this->createRecentQuery();

        return $qb
            ->addOr($qb->expr()->field('userIds')->equals(array($playerA->getId(), $playerB->getId())))
            ->addOr($qb->expr()->field('userIds')->equals(array($playerB->getId(), $playerA->getId())));
    }

    /**
     * Removes games for these ids
     *
     * @param array $ids
     */
    public function removeByIds(array $ids)
    {
        $this->createQueryBuilder()
            ->field('id')->in($ids)
            ->remove()
            ->getQuery()
            ->execute(array('safe' => true));
    }
}
