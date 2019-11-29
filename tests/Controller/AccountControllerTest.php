<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\DomCrawler\Link;
use Symfony\Component\DomCrawler\Crawler;

class AccountControllerTest extends WebTestCase
{
    private function getAddAccountForm(AbstractBrowser $client): Form
    {
        $crawler = $client->request('GET', '/accounts/new');
        $this->assertResponseIsSuccessful();
        $this->assertPageTitleContains('Add Account');

        return $crawler->selectButton('Add')->form();
    }

    private function getEditAccountForm(AbstractBrowser $client, Link $link, array $data): Form
    {
        $crawler = $client->click($link);
        $links = $crawler->filter('a:contains("Edit")');
        $this->assertEquals(1, $links->count());

        $crawler = $client->click($links->link());
        $this->assertResponseIsSuccessful();
        $this->assertPageTitleContains('Edit ' . $data['name']);

        return $crawler->selectButton('Save')->form();
    }

    private function fillForm(AbstractBrowser $client, Form $form, array $data): Crawler
    {
        if (isset($data['name'])) {
            $form['account[name]'] = $data['name'];
        }
        if (isset($data['type'])) {
            $form['account[type]']->select($data['type']);
        }
        if (isset($data['allocationType'])) {
            $form['account[allocationType]']->select($data['allocationType']);
        }
        if (isset($data['allocationPercent'])) {
            $form['account[allocationPercent]'] = $data['allocationPercent'];
        }

        return $client->submit($form);
    }

    public function testAddAccount()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/accounts');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextNotContains('ul#accounts li', 'Active Investing');

        $form = $this->getAddAccountForm($client);
        $data = [
            'name' => 'Active Investing',
            'type' => 2, // Taxable
            'allocationType' => 'value',
            'allocationPercent' => 50,
        ];
        $this->fillForm($client, $form, $data);
        $crawler = $client->followRedirect();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('div.alert-success', 'Account successfully created.');
        $this->assertEquals(1, $crawler->filter('ul#accounts li:contains("Active Investing")')->count());
    }

    public function testPercentNegative()
    {
        $client = static::createClient();
        $form = $this->getAddAccountForm($client);
        $data = [
            'name' => 'Active Investing',
            'type' => 2, // Taxable
            'allocationType' => 'value',
            'allocationPercent' => -10.0,
        ];
        $crawler = $this->fillForm($client, $form, $data);

        $this->assertResponseIsSuccessful();
        $this->assertEquals(1, $crawler->filter('span.form-error-message:contains("Allocation must be between 0% and 100%.")')->count());
    }

    public function testPercentMoreThan100()
    {
        $client = static::createClient();
        $form = $this->getAddAccountForm($client);
        $data = [
            'name' => 'Active Investing',
            'type' => 2, // Taxable
            'allocationType' => 'value',
            'allocationPercent' => 150.00,
        ];
        $crawler = $this->fillForm($client, $form, $data);

        $this->assertResponseIsSuccessful();
        $this->assertEquals(1, $crawler->filter('span.form-error-message:contains("Allocation must be between 0% and 100%.")')->count());
    }

    public function testEditAccount()
    {
        $client = static::createClient();
        // Add account first.
        $form = $this->getAddAccountForm($client);
        $data = [
            'name' => 'Active Investing',
            'type' => 2,
            'allocationType' => 'value',
            'allocationPercent' => 50.00,
        ];
        $this->fillForm($client, $form, $data);
        $crawler = $client->followRedirect();

        $links = $crawler->filter('a:contains("' . $data['name'] . '")');
        $this->assertEquals(1, $links->count());
        $link = $links->link();

        // Edit form
        $form = $this->getEditAccountForm($client, $link, $data);

        $this->assertEquals($data['name'], $form['account[name]']->getValue());
        $this->assertEquals($data['type'], $form['account[type]']->getValue());
        $this->assertEquals($data['allocationType'], $form['account[allocationType]']->getValue());
        $this->assertEquals($data['allocationPercent'], $form['account[allocationPercent]']->getValue());

        // Change percent and save
        $data['allocationPercent'] = 60;
        $this->fillForm($client, $form, $data);
        $client->followRedirect();
        $this->assertSelectorTextContains('div.alert-success', $data['name'] . ' has been modified.');

        $links = $crawler->filter('a:contains("' . $data['name'] . '")');
        $this->assertEquals(1, $links->count());
        $link = $links->link();

        $form = $this->getEditAccountForm($client, $link, $data);
        $this->assertEquals($data['allocationPercent'], $form['account[allocationPercent]']->getValue());
    }

    public function testDeleteAccount()
    {
        $client = static::createClient();
        // Add account first.
        $form = $this->getAddAccountForm($client);
        $data = [
            'name' => 'Active Investing',
            'type' => 2,
            'allocationType' => 'value',
            'allocationPercent' => 50.00,
        ];
        $this->fillForm($client, $form, $data);
        $crawler = $client->followRedirect();

        $links = $crawler->filter('a:contains("' . $data['name'] . '")');
        $this->assertEquals(1, $links->count());
        $link = $links->link();

        $crawler = $client->click($link);
        $links = $crawler->filter('a:contains("Edit")');
        $this->assertEquals(1, $links->count());

        $crawler = $client->click($links->link());

        $deleteForm = $crawler->selectButton('Delete')->form();
        $client->submit($deleteForm);

        $crawler = $client->followRedirect();
        $links = $crawler->filter('a:contains("' . $data['name'] . '")');
        $this->assertEquals(0, $links->count());
    }
}
