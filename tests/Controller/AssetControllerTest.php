<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AssetControllerTest extends WebTestCase
{
    public function testAddAssetButton()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/assets/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Assets');

        $this->assertSelectorTextContains('div.dropdown .dropdown-toggle', 'Add Asset');
        $this->assertSelectorTextContains('div.dropdown div.dropdown-menu .dropdown-header', 'Select Asset Class');

        $items = $crawler->filter('div.dropdown div.dropdown-menu .dropdown-item');
        $this->assertEquals(0, $items->filter(':contains("Cash")')->count());
        $this->assertEquals(1, $items->filter(':contains("Equity")')->count());
        $this->assertEquals(1, $items->filter(':contains("Fixed Income")')->count());
        $this->assertEquals(1, $items->filter(':contains("Crypto")')->count());
        $this->assertEquals(1, $items->filter(':contains("Real Estate")')->count());
        $this->assertEquals(1, $items->filter(':contains("Commodity")')->count());
        $this->assertEquals(1, $items->filter(':contains("Other")')->count());
    }

    public function testViewAsset()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/assets/AAPL');
        $this->assertResponseIsSuccessful();

        $this->assertPageTitleContains('Apple, Inc.');
        $this->assertSelectorTextContains('h1', 'Apple, Inc.');
        $this->assertEquals(1, $crawler->filter('p:contains("NASDAQ")')->count());
        $this->assertEquals(1, $crawler->filter('p small:contains("Data provided by")')->count());
        $this->assertEquals(2, $crawler->filter('a:contains("IEX Cloud")')->count());
    }
}
