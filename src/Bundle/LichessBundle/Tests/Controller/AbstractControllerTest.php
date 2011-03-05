<?php

namespace Bundle\LichessBundle\Tests\Controller;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractControllerTest extends WebTestCase
{
    protected function inviteFriend($color = 'white')
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/');
        $crawler = $client->click($crawler->selectLink('Play with a friend')->link());
        $this->assertTrue($client->getResponse()->isSuccessful());
        $form = $crawler->filter('.submit.'.$color)->form();
        $client->submit($form, array('config[color]' => $color));
        $this->assertTrue($client->getResponse()->isRedirect());
        $crawler = $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());

        return array($client, $crawler);
    }

    protected function inviteAnybody($color = 'white', $join = false)
    {
        $client = $this->createClient();
        !$join && $this->clearSeekQueue($client);
        $crawler = $client->request('GET', '/');
        $crawler = $client->click($crawler->selectLink('Play with anybody')->link());
        $this->assertTrue($client->getResponse()->isSuccessful());
        $form = $crawler->filter('.submit.'.$color)->form();
        $client->submit($form, array('config[color]' => $color));
        $this->assertTrue($client->getResponse()->isRedirect());
        $crawler = $client->followRedirect();
        if($join) {
            $this->assertTrue($client->getResponse()->isSuccessful());
            $redirectUrl = $crawler->filter('a.join_redirect_url')->attr('href');
            $client->request('GET', $redirectUrl);
            $this->assertTrue($client->getResponse()->isRedirect());
            $crawler = $client->followRedirect();
            $this->assertTrue($client->getResponse()->isSuccessful());
        } else {
            $this->assertTrue($client->getResponse()->isSuccessful());
            $this->assertRegexp('/Waiting for opponent/', $client->getResponse()->getContent());
        }

        return array($client, $crawler);
    }

    protected function clearSeekQueue($client)
    {
        $client->getContainer()->get('lichess.repository.seek')->createQueryBuilder()->remove()->getQuery()->execute();
    }
}
