<?php

namespace Extractor;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Abstract grid extractor for magento
 *
 * @author    Willy Mesnage <willy.mesnage@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
abstract class AbstractGridExtractor extends AbstractExtractor
{
    /**
     * Returns the attribute as array
     * Returns ['nameOfAttribute' => ['value', 'value2', ...]]
     *
     * @param Crawler $attributeNode Node of the Magento attribute line in product edit mode
     *                               ($crawler->filter('table.form-list tr'))
     *
     * @return array
     */
    public function getAttributeAsArray(Crawler $attributeNode)
    {
        $name   = $this->getAttributeName($attributeNode);
        $values = $this->getAttributeValues($attributeNode);

        return [$name => $values];
    }

    /**
     * Returns the name of the given attribute
     *
     * @param Crawler $attributeNode Node of the Magento attribute line in product edit mode
     *                               ($crawler->filter('table.form-list tr'))
     *
     * @return string                Name of the attribute
     */
    protected function getAttributeName(Crawler $attributeNode)
    {
        if ($attributeNode->filter('td.label')->getNode(0)) {
            if ($attributeNode->filter('td.label label')->getNode(0)) {
                $name = $attributeNode->filter('td.label label')->attr('for');
            } else {
                $name = $attributeNode->filter('td.label')->text();
            }
        } else {
            $name = 'Unknown name';
        }

        return $name;
    }

    /**
     * Returns values of given attribute
     * Returns ['value1', 'value2', ...]
     *
     * @param Crawler $attributeNode Node of the attribute line in product edit mode
     *                               ($crawler->filter('table.form-list tr'))
     *
     * @return array                 Magento attribute values
     */
    protected function getAttributeValues(Crawler $attributeNode)
    {
        if ($attributeNode->filter('td.value input')->getNode(0)) {
            $type = $attributeNode->filter('td.value input')->attr('type');

            switch ($type) {
                case 'text':
                    $values = $attributeNode->filter('td.value input')->attr('value');
                    break;

                case 'checkbox':
                case 'radio':
                    // To be tested
                    $values = [];
                    $attributeNode->filter('td.value input')->each(
                        function($input) use (&$values) {
                            if ($input->attr('checked')) {
                                $values[] = $input->attr('value');
                            }
                        }
                    );
                    break;

                default:
                    $values = 'Unknown type of input';
                    break;
            }

        } elseif ($attributeNode->filter('td.value textarea')->getNode(0)) {
            $values = $attributeNode->filter('td.value textarea')->text();

        } elseif ($attributeNode->filter('td.value select')->getNode(0)) {

            if ($attributeNode->filter('td.value select option:selected')->getNode(0)) {
                $values = $attributeNode->filter('td.value select option:selected')->text();
            } else {
                $values = 'No option selected';
            }

        } else {
            $values = 'Unknown attribute type';
        }

        return is_array($values) ? $values : [$values];
    }
}