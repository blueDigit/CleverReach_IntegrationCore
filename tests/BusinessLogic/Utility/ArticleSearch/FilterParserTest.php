<?php

use CleverReach\BusinessLogic\Utility\ArticleSearch\Filter;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Conditions;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Operators;
use CleverReach\BusinessLogic\Utility\ArticleSearch\FilterParser;
use PHPUnit\Framework\TestCase;

class FilterParserTest extends TestCase
{
    const ITEM_CODE = 'article';

    const ITEM_ID = '1';

    /** @var  FilterParser */
    private $filterParser;

    private $operators;

    public function setUp()
    {
        $this->filterParser = new FilterParser();
        $this->operators = array(Operators::AND_OPERATOR);
    }

    public function testGenerateFiltersWithOneExpressionQuery()
    {
        $query = urlencode('title ct \'great\'');

        $filters = $this->filterParser->generateFilters(self::ITEM_CODE, null, $query);
        /** @var Filter $generatedFilter1 */
        $generatedFilter1 = !empty($filters[0]) ? $filters[0] : null;
        $generatedFilter2 = !empty($filters[1]) ? $filters[1] : null;
        $generatedFilter3 = !empty($filters[2]) ? $filters[2] : null;

        $this->assertEmpty($generatedFilter3);
        $this->checkGeneratedFilter('itemCode', self::ITEM_CODE, Conditions::EQUALS, $generatedFilter1);
        $this->checkGeneratedFilter('title', 'great', Conditions::CONTAINS, $generatedFilter2);
    }

    public function testGenerateFiltersWithTwoExpressionsQuery()
    {
        $query = urlencode('title ct \'great\' AND date gt \'2012-04-23T18:25:43.511Z\'');

        $filters = $this->filterParser->generateFilters(self::ITEM_CODE, null, $query);
        /** @var Filter $generatedFilter1 */
        $generatedFilter1 = !empty($filters[0]) ? $filters[0] : null;
        $generatedFilter2 = !empty($filters[1]) ? $filters[1] : null;
        $generatedFilter3 = !empty($filters[2]) ? $filters[2] : null;

        $this->checkGeneratedFilter('itemCode', self::ITEM_CODE, Conditions::EQUALS, $generatedFilter1);
        $this->checkGeneratedFilter('title', 'great', Conditions::CONTAINS, $generatedFilter2);
        $this->checkGeneratedFilter(
            'date',
            '2012-04-23T18:25:43.511Z',
            Conditions::GREATER_THAN,
            $generatedFilter3
        );
    }

    public function testGenerateFiltersWithUnordinaryQuery()
    {
        $query = urlencode('title isa \'great\' ANDAND date eq \'2012-04-23T18:25:43.511Z\'');

        $filters = $this->filterParser->generateFilters(self::ITEM_CODE, null, $query);
        /** @var Filter $generatedFilter1 */
        $generatedFilter = !empty($filters[1]) ? $filters[1] : null;

        $this->checkGeneratedFilter(
            'title isa \'great\' ANDAND date',
            '2012-04-23T18:25:43.511Z',
            Conditions::EQUALS,
            $generatedFilter
        );
    }

    public function testGenerateFiltersWithInvalidConditionInQuery()
    {
        $query = urlencode('title isa \'great\' ANDAND date cc \'2012-04-23T18:25:43.511Z\'');

        $this->expectException('\InvalidArgumentException');

        $this->filterParser->generateFilters(self::ITEM_CODE, null, $query);
    }

    public function testGenerateFiltersWithItemIdAndWithQuery()
    {
        $query = 'title ct \'great\'';

        $filters = $this->filterParser->generateFilters(self::ITEM_CODE, 1, $query);
        /** @var Filter $generatedFilter1 */
        $generatedFilter1 = !empty($filters[0]) ? $filters[0] : null;
        $generatedFilter2 = !empty($filters[1]) ? $filters[1] : null;
        $generatedFilter3 = !empty($filters[2]) ? $filters[2] : null;

        $this->checkGeneratedFilter('itemCode', self::ITEM_CODE, Conditions::EQUALS, $generatedFilter1);
        $this->checkGeneratedFilter('itemId', self::ITEM_ID, Conditions::EQUALS, $generatedFilter2);
        $this->checkGeneratedFilter('title', 'great', Conditions::CONTAINS, $generatedFilter3);
    }

    /**
     * @param $expectedCode
     * @param $expectedValue
     * @param $expectedCondition
     * @param Filter $actualFilter
     */
    private function checkGeneratedFilter($expectedCode, $expectedValue, $expectedCondition, $actualFilter)
    {
        $this->assertNotEmpty($actualFilter);
        $this->assertInstanceOf('CleverReach\BusinessLogic\Utility\ArticleSearch\Filter', $actualFilter);
        $this->assertEquals($expectedCode, $actualFilter->getAttributeCode());
        $this->assertEquals($expectedValue, $actualFilter->getAttributeValue());
        $this->assertEquals($expectedCondition, $actualFilter->getCondition());
        $this->assertContains($actualFilter->getOperator(), $this->operators);
    }

}