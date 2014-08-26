<?php

require 'vendor/autoload.php';

use Extractor\ProductAttributeExtractor;
use Extractor\AttributeExtractor;
use Extractor\CategoriesExtractor;
use Manager\MagentoAdminConnexionManager;
use Manager\NavigationManager;

const MAGENTO_ADMIN_URL   = 'http://magento.local/index.php/admin';
const MAGENTO_ADMIN_LOGIN = 'root';
const MAGENTO_ADMIN_PWD   = 'akeneo2014';

$connexionManager = new MagentoAdminConnexionManager(
    MAGENTO_ADMIN_URL,
    MAGENTO_ADMIN_LOGIN,
    MAGENTO_ADMIN_PWD
);

$mainPageCrawler           = $connexionManager->connectToAdminPage();
$client                    = $connexionManager->getClient();
$navigationManager         = new NavigationManager($connexionManager->getClient());
$productAttributeExtractor = new ProductAttributeExtractor($navigationManager);
$attributeExtractor        = new AttributeExtractor($navigationManager);
$categoriesExtractor       = new CategoriesExtractor($navigationManager);

$totalTime = microtime(true);

/*
 * Products extraction
 */
$products = [];
$productCatalogCrawler = $navigationManager->goToProductCatalog($mainPageCrawler);
$productCatalogCrawler->filter('table#productGrid_table tbody tr')->each(
    function ($productNode, $i) use (&$products, $productAttributeExtractor) {
        $products[] = $productAttributeExtractor->extract(
            $productNode,
            $i+1
        );
    }
);
$processProductsTime = microtime(true) - $totalTime;
printf(PHP_EOL . '%d products extracted in %fs' . PHP_EOL, count($products), $processProductsTime);
printf('Average time per product : %fs' . PHP_EOL, $processProductsTime / count($products));

/*
 * Attributes extraction
 */
$attributes = [];
$attributeCatalogCrawler = $navigationManager->goToAttributeCatalog($mainPageCrawler);
$attributeCatalogCrawler->filter('table#attributeGrid_table tbody tr')->each(
    function ($attributeCrawler, $i) use (&$attributes, $attributeExtractor) {
        $attributes[] = $attributeExtractor->extract(
            $attributeCrawler,
            $i+1
        );
    }
);
$processAttributesTime = microtime(true) - $processProductsTime;
printf(PHP_EOL . '%d attributes extracted in %fs' . PHP_EOL, count($attributes), $processAttributesTime);
printf('Average time per attribute : %fs' . PHP_EOL, $processAttributesTime / count($attributes));

/*
 * Categories extraction
 */
$categories = $categoriesExtractor->extract($mainPageCrawler);
$processCategoriesTime = microtime(true) - $processAttributesTime;
printf('Categories tree extracted in %fs' . PHP_EOL, $processCategoriesTime);
