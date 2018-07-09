<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class TrackControllerTest extends WebTestCase
{
    protected $exampleUrl = 'https://urun.n11.com/kulce-altin/nadirgold-1000-gr-kulce-altin-P99120644';

    /** @var Client */
    protected $client;

    public function setUp()
    {
        $this->client = static::createClient();
    }

    public function testTrackApi()
    {
        $this->logIn();

        $this->client->request('POST', '/api/track', [
            'url' => $this->exampleUrl,
            'interval' => 30,
        ]);

        $response = $this->client->getResponse();

        $this->assertNotEmpty($response->getContent());

        $this->assertEquals(201, $response->getStatusCode());
    }

    private function logIn()
    {
        $session = $this->client->getContainer()->get('session');

        $provide = 'fos_userbundle';
        $firewall = 'main';

        $token = new UsernamePasswordToken('test_user', null, $provide, array('ROLE_USER'));
        $session->set('_security_'.$firewall, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }
}