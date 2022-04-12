<?php

use CleverReach\BusinessLogic\Utility\ArticleSearch\Conditions;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Filter;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Operators;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\ComplexCollectionSchemaAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SchemaAttributeTypes;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SearchableItemSchema;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SimpleSchemaAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\SearchResult;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\SearchResultItem;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    const ITEM_CODE = 'article';

    const ATTRIBUTE_CODE = 'testCode';
    
    const ATTRIBUTE_NAME = 'testName';

    /** @var  Validator */
    private $validator;

    /** @var  SearchableItemSchema */
    private $itemSchema;

    public function setUp()
    {
        $this->validator = new Validator();
        $this->itemSchema = new SearchableItemSchema(
            self::ITEM_CODE,
            array(
                new SimpleSchemaAttribute(
                    self::ATTRIBUTE_CODE,
                    self::ATTRIBUTE_NAME,
                    false,
                    array(),
                    SchemaAttributeTypes::TEXT
                ),
                new ComplexCollectionSchemaAttribute(
                    self::ATTRIBUTE_CODE . 1,
                    self::ATTRIBUTE_NAME . 1,
                    false,
                    array(Conditions::EQUALS),
                    array(new SimpleSchemaAttribute(
                        self::ATTRIBUTE_CODE,
                        self::ATTRIBUTE_NAME,
                        false,
                        array(),
                        SchemaAttributeTypes::TEXT)
                    )
                ),
            )
        );
    }

    public function testFilterMatchingSchema()
    {
        $filter1 = new Filter(self::ATTRIBUTE_CODE, 'testValue', Conditions::EQUALS, Operators::AND_OPERATOR);
        $filter2 = new Filter(self::ATTRIBUTE_CODE . 1, 'testValue1', Conditions::EQUALS, Operators::AND_OPERATOR);
        $itemSchema = new SearchableItemSchema(
            self::ITEM_CODE,
            array(
                new SimpleSchemaAttribute(
                    self::ATTRIBUTE_CODE,
                    self::ATTRIBUTE_NAME,
                    true,
                    array(Conditions::EQUALS),
                    SchemaAttributeTypes::TEXT
                ),
                new ComplexCollectionSchemaAttribute(
                    self::ATTRIBUTE_CODE . 1,
                    self::ATTRIBUTE_NAME . 1,
                    true,
                    array(Conditions::EQUALS),
                    array(new SimpleSchemaAttribute(
                        self::ATTRIBUTE_CODE,
                        self::ATTRIBUTE_NAME,
                        true,
                        array(Conditions::EQUALS),
                        SchemaAttributeTypes::TEXT)
                    )
                ),
            )
        );

        $errorMessage = '';

        try {
            $this->validator->validateFilters(array($filter1, $filter2), $itemSchema);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
        }

        $this->assertEmpty($errorMessage);
    }

    public function testNotAllowedFilterForSearchFilterMatchingSchema()
    {
        $filter1 = new Filter(self::ATTRIBUTE_CODE, 'testValue', Conditions::EQUALS, Operators::AND_OPERATOR);
        $filter2 = new Filter(self::ATTRIBUTE_CODE . 1, 'testValue1', Conditions::EQUALS, Operators::AND_OPERATOR);

        $this->expectException('\CleverReach\BusinessLogic\Utility\ArticleSearch\Exceptions\InvalidSchemaMatching');

        $this->validator->validateFilters(array($filter1, $filter2), $this->itemSchema);
    }

    public function testInvalidSearchableExpressionsFilterMatchingSchema()
    {
        $filter1 = new Filter(self::ATTRIBUTE_CODE, 'testValue', Conditions::EQUALS, Operators::AND_OPERATOR);
        $filter2 = new Filter(self::ATTRIBUTE_CODE . 1, 'testValue1', Conditions::NOT_EQUAL, Operators::AND_OPERATOR);

        $itemSchema = new SearchableItemSchema(
            self::ITEM_CODE,
            array(
                new SimpleSchemaAttribute(
                    self::ATTRIBUTE_CODE,
                    self::ATTRIBUTE_NAME,
                    true,
                    array(Conditions::EQUALS),
                    SchemaAttributeTypes::TEXT
                ),
                new ComplexCollectionSchemaAttribute(
                    self::ATTRIBUTE_CODE . 1,
                    self::ATTRIBUTE_NAME . 1,
                    true,
                    array(Conditions::EQUALS),
                    array(new SimpleSchemaAttribute(
                        self::ATTRIBUTE_CODE,
                        self::ATTRIBUTE_NAME,
                        true,
                        array(Conditions::EQUALS),
                        SchemaAttributeTypes::TEXT)
                    )
                ),
            )
        );

        $this->expectException('\CleverReach\BusinessLogic\Utility\ArticleSearch\Exceptions\InvalidSchemaMatching');

        $this->validator->validateFilters(array($filter1, $filter2), $itemSchema);
    }

    public function testFilterNotMatchingSchema()
    {
        $filter1 = new Filter(self::ATTRIBUTE_CODE, 'testValue', Conditions::EQUALS, Operators::AND_OPERATOR);
        $filter2 = new Filter(self::ATTRIBUTE_CODE . 1, 'testValue1', Conditions::EQUALS, Operators::AND_OPERATOR);
        $filter3 = new Filter(self::ATTRIBUTE_CODE . 2, 'testValue2', Conditions::EQUALS, Operators::AND_OPERATOR);

        $this->expectException('\CleverReach\BusinessLogic\Utility\ArticleSearch\Exceptions\InvalidSchemaMatching');

        $this->validator->validateFilters(array($filter1, $filter2, $filter3), $this->itemSchema);
    }

    public function testSearchResultMatchingSchema()
    {
        $searchResultItem = new SearchResultItem(
            self::ITEM_CODE,
            'testId',
            'testTitle',
            new DateTime(),
            array(self::ATTRIBUTE_CODE => 'testValue')
        );
        $searchResult = new SearchResult();

        $searchResult->addSearchResultItem($searchResultItem);

        $errorMessage = '';

        try {
            $this->validator->validateSearchResults($searchResult, $this->itemSchema);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
        }

        $this->assertEmpty($errorMessage);
    }

    public function testSearchResultNotMatchingSchema()
    {
        $searchResultItem = new SearchResultItem(
            self::ITEM_CODE,
            'testId',
            'testTitle',
            new DateTime(),
            array('invalidTestKey' => 'testValue')
        );
        $searchResult = new SearchResult();
        $searchResult->addSearchResultItem($searchResultItem);

        $this->expectException('\CleverReach\BusinessLogic\Utility\ArticleSearch\Exceptions\InvalidSchemaMatching');

        $this->validator->validateSearchResults($searchResult, $this->itemSchema);
    }
}