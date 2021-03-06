<?php

namespace Akeneo\Component\MagentoAdminExtractor\Extractor;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Extractor for magento attributes
 *
 * @author    Willy Mesnage <willy.mesnage@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class AttributeExtractor extends AbstractGridExtractor
{
    /**
     * Allows to extract product attributes
     * Returns ['param_1' => ['value1', ...], ...]
     *
     * @param Crawler $attributeCrawler Crawler positioned on the attribute in catalog page
     *                                  ex : $attributeCatalogCrawler->filter('table#attributeGrid_table tbody tr')
     * @param mixed   $attributeName    Name of the attribute which will be display in terminal
     *
     * @throws \RuntimeException if the CssSelector Component is not available
     *
     * @return array $attributes Array with parameters of attribute
     */
    public function extract(Crawler $attributeCrawler, $attributeName = '')
    {
        printf(PHP_EOL . 'Accessing to attribute %s edit page' . PHP_EOL, $attributeName);
        $crawler = $this->navigationManager->goToUri('GET', $attributeCrawler->first()->attr('title'));
        $parameters = [];

        printf('Processing parameters' . PHP_EOL);
        $crawler->filter('table.form-list tr')->each(
            function ($attributeNode) use (&$parameters) {
                $parameters = array_merge(
                    $parameters,
                    $this->getAttributeAsArray($attributeNode)
                );
            }
        );
        $parameters['title'][] = $this->getTitles($crawler);
        printf('%d parameters processed' . PHP_EOL, count($parameters));

        $mappingJsLabel = $this->getOptionsParametersMapping($crawler);
        $script         = $crawler
            ->filter('div#product_attribute_tabs_labels_content script[type="text/javascript"]')
            ->first()
            ->text();

        $parameters['options'] = $this->extractOptionsFromJS($script, $mappingJsLabel);
        printf('%d options processed' . PHP_EOL, count($parameters['options']));

        return $parameters;
    }

    /**
     * Give each title translations of the attribute
     * Returns ['storeview label 1' => 'translatedTitle', ...]
     *
     * @param Crawler $editAttributeCrawler Crawler positioned in the edit attribute page
     *
     * @return array
     */
    protected function getTitles(Crawler $editAttributeCrawler)
    {
        $headers = [];
        $titles  = [];

        $editAttributeCrawler->filter('table#attribute-labels-table th')->each(
            function ($heading) use (&$headers) {
                $headers[] = trim($heading->text());
            }
        );

        $editAttributeCrawler->filter('table#attribute-labels-table td input')->each(
            function ($input) use (&$titles) {
                $titles[] = trim($input->first()->attr('value'));
            }
        );

        return array_combine($headers, $titles);
    }

    /**
     * Get the mapping between options parameters (which are store views, position, and is default or not) and
     * javascript binding
     * Returns (reversed) ['javascript_1' => 'option_parameter_1', 'javascript_2' => 'option_parameter_2', ...]
     * Returns (not reversed) ['option_parameter_1' => 'javascript_1', 'option_parameter_2' => 'javascript_2', ...]
     *
     * @param Crawler $editAttributeCrawler Crawler positioned in the edit attribute page
     * @param boolean $reverse              Get the reversed mapping or not (true by default)
     *
     * @return array Mapping between options and javascript binding
     */
    protected function getOptionsParametersMapping(Crawler $editAttributeCrawler, $reverse = true)
    {
        $headers = [];
        $mapping = [];

        $editAttributeCrawler->filter('div#matage-options-panel table tr#attribute-options-table th')->each(
            function ($heading) use (&$headers) {
                $headers[] = trim($heading->text());
            }
        );

        $editAttributeCrawler->filter('div#matage-options-panel table tr td input')->each(
            function ($input, $i) use (&$mapping, $headers) {
                if (preg_match('#\{{2}(.*)\}{2}#', $input->first()->attr('value'), $value)) {
                    $mapping[$headers[$i]] = $value[1];
                } else {
                    $mapping[$headers[$i]] = '';
                }
            }
        );

        unset($mapping['Add Option']);
        $mapping['Is Default'] = 'checked';
        if (true === $reverse) {
            $mapping = array_flip($mapping);
        }

        return $mapping;
    }

    /**
     * Extracts options from the script which bind parameters in html table
     * Returns [ ['optionParam_1' => 'value1', ...], [], ... ]
     *
     * @param string $script         Javascript from attribute edit page which bind values
     * @param array  $mappingJsLabel Mapping between options and javascript binding
     *
     * @return array $computedOptions
     */
    protected function extractOptionsFromJS($script, array $mappingJsLabel)
    {
        $computedOptions = [];

        if (preg_match_all('# attributeOption\.add\(\{([^\}]+)\}\);#', $script, $optionsParams)) {
            $computedOptions = [];

            foreach ($optionsParams[1] as $stuckParams) {
                $params         = explode(',', $stuckParams);
                $mappingJsValue = [];

                foreach ($params as $param) {
                    if (preg_match_all('#"(.*)":"(.*)"#', $param, $values)) {
                        $mappingJsValue[$values[1][0]] = $values[2][0];
                    }
                }
                unset($mappingJsValue['intype'], $mappingJsValue['id']);

                $computedOption = [];
                foreach ($mappingJsValue as $key => $value) {
                    $computedOption[$mappingJsLabel[$key]] = $value;
                }
                $computedOptions[] = $computedOption;
            }
        }

        return $computedOptions;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtractedEntity()
    {
        return 'attribute';
    }
}
