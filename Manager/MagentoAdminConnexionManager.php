<?php

namespace Manager;

use Goutte\Client;

class MagentoAdminConnexionManager
{
    /** @var string */
    protected $adminUrl;

    /** @var string */
    protected $login;

    /** @var string */
    protected $password;

    /** @var Client */
    protected $client;

    /**
     * @param $url      string
     * @param $login    string
     * @param $password string
     */
    public function __construct(
        $adminUrl,
        $login,
        $password
    ) {
        $this->adminUrl = $adminUrl;
        $this->login    = $login;
        $this->password = $password;
    }

    /**
     * Allows you to connect to Magento admin page
     *
     * @return Crawler Admin page crawler
     */
    public function connectToAdminPage()
    {
        if (empty($this->client)) {
            $client = new Client();
        } else {
            $client = $this->client;
        }

        printf('Requesting %s' . PHP_EOL, $this->adminUrl);
        $crawler = $client->request('GET', $this->adminUrl);

        printf('Login user "%s"' . PHP_EOL, $this->login);
        $form    = $crawler->selectButton('Login')->form();
        $crawler = $client->submit($form, ['login[username]' => $this->login, 'login[password]' => $this->password]);

        $this->client = $client;

        return $crawler;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        if (empty($this->client)) {
            $this->connectToAdminPage();
        }

        return $this->client;
    }

    /**
     * @param string $adminUrl
     *
     * @return $this
     */
    public function setAdminUrl($adminUrl)
    {
        $this->adminUrl = $adminUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getAdminUrl()
    {
        return $this->adminUrl;
    }

    /**
     * @param string $login
     *
     * @return $this
     */
    public function setLogin($login)
    {
        $this->login = $login;
        return $this;
    }

    /**
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * @param string $password
     *
     * @return $this
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }
}