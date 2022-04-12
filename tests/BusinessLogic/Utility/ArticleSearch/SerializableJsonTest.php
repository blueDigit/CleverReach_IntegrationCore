<?php

use CleverReach\BusinessLogic\Utility\ArticleSearch\Conditions;
use CleverReach\BusinessLogic\Utility\ArticleSearch\DataTypes;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\ComplexCollectionSchemaAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\Enum;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\EnumSchemaAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\ObjectSchemaAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SchemaAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SchemaAttributeTypes;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SearchableItemSchema;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SimpleCollectionSchemaAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SimpleSchemaAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchItem\SearchableItem;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchItem\SearchableItems;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\AuthorAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\ComplexCollectionAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\DateAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\HtmlAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\ImageAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\NumberAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\BoolAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\SearchResult;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\SearchResultItem;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\SimpleCollectionAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\TextAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\UrlAttribute;
use PHPUnit\Framework\TestCase;

class SerializableJsonTest extends TestCase
{
    const SEARCHABLE_ITEM_CODE = 'Article';

    const SEARCHABLE_ITEM_NAME = 'ARTICLE';

    const ATTRIBUTE_TITLE = 'testTittle';
    
    const ATTRIBUTE_ID = '1';

    /** @var  SearchableItem */
    private $searchableItem;

    /** @var  SearchableItems */
    private $searchableItems;

    /** @var  array */
    private $schemaAttributes;

    /** @var  SearchableItemSchema */
    private $searchableItemSchema;

    /** @var  SearchResultItem */
    private $searchResultItem;

    /** @var  SearchResult */
    private $searchResult;

    public function setUp()
    {
        $this->searchableItem = new SearchableItem(self::SEARCHABLE_ITEM_CODE, self::SEARCHABLE_ITEM_NAME);
        $this->searchableItems = new SearchableItems();

        $schemaAttribute1 =  new SimpleSchemaAttribute(
            self::SEARCHABLE_ITEM_CODE,
            self::SEARCHABLE_ITEM_NAME,
            true,
            array(Conditions::CONTAINS),
            SchemaAttributeTypes::TEXT
        );
        $complexSchemaAttribute = new ComplexCollectionSchemaAttribute(
            self::SEARCHABLE_ITEM_CODE,
            self::SEARCHABLE_ITEM_NAME,
            false,
            array(),
            array()
        );
        $complexSchemaAttribute->addSchemaAttribute($schemaAttribute1);

        $this->schemaAttributes = array($schemaAttribute1, $complexSchemaAttribute);

        $this->searchableItemSchema = new SearchableItemSchema(self::SEARCHABLE_ITEM_CODE, $this->schemaAttributes);
        $this->searchResultItem = new SearchResultItem(
            self::SEARCHABLE_ITEM_CODE,
            self::ATTRIBUTE_ID,
            self::ATTRIBUTE_TITLE,
            new DateTime(),
            array()
        );

        $this->searchResult = new SearchResult();
    }

    public function testInvalidCodeInSearchableItem()
    {
        $this->expectException('\InvalidArgumentException');

        new SearchableItem(null, null);
    }

    public function testInvalidNameInSearchableItem()
    {
        $this->expectException('\InvalidArgumentException');

        new SearchableItem(self::SEARCHABLE_ITEM_CODE, null);
    }


    public function testJsonSerializableForSearchableItem()
    {
        $jsonSerializableItem = $this->searchableItem->toArray();

        $this->assertEquals(self::SEARCHABLE_ITEM_CODE, $jsonSerializableItem['code']);
        $this->assertEquals(self::SEARCHABLE_ITEM_NAME, $jsonSerializableItem['name']);
        $this->assertEquals(json_encode(array('data' => $jsonSerializableItem)), $this->searchableItem->toJson());
    }

    public function testJsonSerializableForSearchableItems()
    {
        $this->searchableItems->addSearchableItem($this->searchableItem);
        $this->searchableItems->addSearchableItem($this->searchableItem);

        $jsonSerializableItems = $this->searchableItems->toArray();

        $this->assertEquals(self::SEARCHABLE_ITEM_CODE, $jsonSerializableItems[0]['code']);
        $this->assertEquals(self::SEARCHABLE_ITEM_NAME, $jsonSerializableItems[0]['name']);
        $this->assertEquals(json_encode(array('data' => $jsonSerializableItems)), $this->searchableItems->toJson());
    }

    public function testCreateSchemaAttributeWithInvalidType()
    {
        $this->expectException('\InvalidArgumentException');

        new SimpleSchemaAttribute(self::SEARCHABLE_ITEM_CODE, self::SEARCHABLE_ITEM_NAME, false, array(), 'test');
    }

    public function testConditionsInDateSchemaAttribute()
    {
        $dateSchemaAttribute = new SimpleSchemaAttribute(
            self::SEARCHABLE_ITEM_CODE,
            self::SEARCHABLE_ITEM_NAME,
            true,
            array(
                Conditions::EQUALS,
                Conditions::GREATER_THAN,
                Conditions::GREATER_EQUAL,
                Conditions::LESS_THAN,
                Conditions::LESS_EQUAL
            ),
            SchemaAttributeTypes::DATE
        );
        $expectedConditions = array(
            Conditions::EQUALS,
            Conditions::GREATER_THAN,
            Conditions::GREATER_EQUAL,
            Conditions::LESS_THAN,
            Conditions::LESS_EQUAL,
        );

        $schemaForJsonEncoding = $dateSchemaAttribute->toArray();

        $this->assertEquals($expectedConditions, $schemaForJsonEncoding['searchableExpressions']);
    }

    public function testSchemaAttributeWithInvalidSearchableExpressions()
    {
        $this->expectException('\InvalidArgumentException');

        new SimpleSchemaAttribute(
            self::SEARCHABLE_ITEM_CODE,
            self::SEARCHABLE_ITEM_NAME,
            true,
            array(
                Conditions::EQUALS,
                Conditions::GREATER_THAN,
                'testCondition',
                Conditions::LESS_THAN,
                Conditions::LESS_EQUAL
            ),
            SchemaAttributeTypes::DATE
        );

    }

    public function testSearchResultInvalidNumberAttribute()
    {
        $this->expectException('\InvalidArgumentException');

        new NumberAttribute('test', 'ppp');
    }

    public function testJsonSerializableForSchemaAttributeWithOneComplexCollection()
    {
        $jsonSerializableAttributes = array();

        /** @var SchemaAttribute $attribute */
        foreach ($this->schemaAttributes as $attribute) {
            $jsonSerializableAttributes[] = $attribute->toArray();
        }

        $this->assertEquals(self::SEARCHABLE_ITEM_CODE, $jsonSerializableAttributes[0]['code']);
        $this->assertEquals(self::SEARCHABLE_ITEM_NAME, $jsonSerializableAttributes[0]['name']);
        $this->assertEquals(SchemaAttributeTypes::TEXT, $jsonSerializableAttributes[0]['type']);
        $this->assertTrue($jsonSerializableAttributes[0]['searchable']);
        $this->assertTrue(!isset($jsonSerializableAttributes[0]['attributes']));
        $this->assertEquals(self::SEARCHABLE_ITEM_CODE, $jsonSerializableAttributes[1]['code']);
        $this->assertEquals(SchemaAttributeTypes::COLLECTION, $jsonSerializableAttributes[1]['type']);
        $this->assertTrue(!isset($jsonSerializableAttributes[1]['searchableExpressions']));
        $this->assertNotEmpty($jsonSerializableAttributes[1]['attributes']);
        $this->assertEquals($jsonSerializableAttributes[0], $jsonSerializableAttributes[1]['attributes'][0]);
    }

    public function testJsonSerializableForSearchableItemSchema()
    {
        $jsonSerializableAttributes = array();

        $jsonSerializableItemSchema = $this->searchableItemSchema->toArray();
        /** @var SchemaAttribute $attribute */
        foreach ($this->schemaAttributes as $attribute) {
            $jsonSerializableAttributes[] = $attribute->toArray();
        }

        $this->assertEquals(self::SEARCHABLE_ITEM_CODE, $jsonSerializableItemSchema['itemCode']);
        $this->assertNotEmpty($jsonSerializableItemSchema['attributes']);
        $this->assertEquals($jsonSerializableAttributes[0], $jsonSerializableItemSchema['attributes'][0]);
        $this->assertEquals(
            json_encode(array('data' => $jsonSerializableItemSchema)),
            $this->searchableItemSchema->toJson()
        );
    }

    public function testJsonSerializableForSchemaAttributeWithOneSimpleCollectionWithTextAttribute()
    {
        $simpleCollectionAttribute =
            new SimpleCollectionSchemaAttribute(
                self::SEARCHABLE_ITEM_CODE,
                self::SEARCHABLE_ITEM_NAME,
                true,
                array(
                    Conditions::EQUALS,
                    Conditions::GREATER_THAN,
                    Conditions::GREATER_EQUAL,
                    Conditions::LESS_THAN,
                    Conditions::LESS_EQUAL
                ),
                'text',
                DataTypes::STRING_TYPE
            );

        $jsonSerializableAttributes = array($simpleCollectionAttribute->toArray());

        $this->assertEquals(self::SEARCHABLE_ITEM_CODE, $jsonSerializableAttributes[0]['code']);
        $this->assertEquals(self::SEARCHABLE_ITEM_NAME, $jsonSerializableAttributes[0]['name']);
        $this->assertEquals(SchemaAttributeTypes::COLLECTION, $jsonSerializableAttributes[0]['type']);
        $this->assertTrue($jsonSerializableAttributes[0]['searchable']);
        $this->assertTrue(isset($jsonSerializableAttributes[0]['searchableExpressions']));
        $this->assertEquals('text', $jsonSerializableAttributes[0]['attributes']);
    }

    public function testJsonSerializableForSchemaAttributeWithOneSimpleCollectionWithFloatAttribute()
    {
        $simpleCollectionAttribute =
            new SimpleCollectionSchemaAttribute(
                self::SEARCHABLE_ITEM_CODE,
                self::SEARCHABLE_ITEM_NAME,
                true,
                array(
                    Conditions::EQUALS,
                    Conditions::GREATER_THAN,
                    Conditions::GREATER_EQUAL,
                    Conditions::LESS_THAN,
                    Conditions::LESS_EQUAL
                ),
                '22.33',
                DataTypes::FLOAT_TYPE
            );

        $jsonSerializableAttributes = array($simpleCollectionAttribute->toArray());

        $this->assertEquals(self::SEARCHABLE_ITEM_CODE, $jsonSerializableAttributes[0]['code']);
        $this->assertEquals(self::SEARCHABLE_ITEM_NAME, $jsonSerializableAttributes[0]['name']);
        $this->assertEquals(SchemaAttributeTypes::COLLECTION, $jsonSerializableAttributes[0]['type']);
        $this->assertTrue($jsonSerializableAttributes[0]['searchable']);
        $this->assertTrue(isset($jsonSerializableAttributes[0]['searchableExpressions']));
        $this->assertEquals(22.33, $jsonSerializableAttributes[0]['attributes']);
    }

    public function testJsonSerializableForSchemaAttributeWithOneSimpleCollectionWithBoolAttribute()
    {
        $simpleCollectionAttribute =
            new SimpleCollectionSchemaAttribute(
                self::SEARCHABLE_ITEM_CODE,
                self::SEARCHABLE_ITEM_NAME,
                true,
                array(
                    Conditions::EQUALS,
                    Conditions::NOT_EQUAL
                ),
                true,
                DataTypes::BOOL_TYPE
            );

        $jsonSerializableAttributes = array($simpleCollectionAttribute->toArray());

        $this->assertEquals(self::SEARCHABLE_ITEM_CODE, $jsonSerializableAttributes[0]['code']);
        $this->assertEquals(self::SEARCHABLE_ITEM_NAME, $jsonSerializableAttributes[0]['name']);
        $this->assertEquals(SchemaAttributeTypes::COLLECTION, $jsonSerializableAttributes[0]['type']);
        $this->assertTrue($jsonSerializableAttributes[0]['searchable']);
        $this->assertTrue(isset($jsonSerializableAttributes[0]['searchableExpressions']));
        $this->assertEquals(22.33, $jsonSerializableAttributes[0]['attributes']);
    }

    public function testToArrayForObjectSchemaAttribute()
    {
        $objectAttribute =
            new ObjectSchemaAttribute(
                self::SEARCHABLE_ITEM_CODE,
                self::SEARCHABLE_ITEM_NAME,
                true,
                array(
                    Conditions::EQUALS,
                    Conditions::GREATER_THAN,
                    Conditions::GREATER_EQUAL,
                    Conditions::LESS_THAN,
                    Conditions::LESS_EQUAL
                ),
                array()
            );

        $objectAttribute->addSchemaAttribute($this->schemaAttributes[1]);

        $jsonSerializableObjectAttribute = $objectAttribute->toArray();

        $this->assertEquals(self::SEARCHABLE_ITEM_CODE, $jsonSerializableObjectAttribute['code']);
        $this->assertEquals(self::SEARCHABLE_ITEM_NAME, $jsonSerializableObjectAttribute['name']);
        $this->assertEquals(SchemaAttributeTypes::OBJECT, $jsonSerializableObjectAttribute['type']);
        $this->assertTrue($jsonSerializableObjectAttribute['searchable']);
        $this->assertTrue(isset($jsonSerializableObjectAttribute['searchableExpressions']));
        $this->assertEquals($this->schemaAttributes[1]->toArray(), $jsonSerializableObjectAttribute['attributes'][0]);
    }

    public function testToArrayEnumSchemaAttribute()
    {
        $yesOption = new Enum('yes', 1);
        $noOption = new Enum('no', 0);
        $possibleValuesForEnum = array($yesOption, $noOption);
        $enumAttribute =
            new EnumSchemaAttribute(
                self::SEARCHABLE_ITEM_CODE,
                self::SEARCHABLE_ITEM_NAME,
                true,
                array(
                    Conditions::EQUALS,
                    Conditions::GREATER_THAN,
                    Conditions::GREATER_EQUAL,
                    Conditions::LESS_THAN,
                    Conditions::LESS_EQUAL
                ),
                $possibleValuesForEnum
            );

        $jsonSerializableObjectAttribute = $enumAttribute->toArray();

        $this->assertEquals(self::SEARCHABLE_ITEM_CODE, $jsonSerializableObjectAttribute['code']);
        $this->assertEquals(self::SEARCHABLE_ITEM_NAME, $jsonSerializableObjectAttribute['name']);
        $this->assertEquals(SchemaAttributeTypes::ENUM, $jsonSerializableObjectAttribute['type']);
        $this->assertTrue($jsonSerializableObjectAttribute['searchable']);
        $this->assertTrue(isset($jsonSerializableObjectAttribute['searchableExpressions']));
        $this->assertEquals($yesOption->getLabel(), $jsonSerializableObjectAttribute['possibleValues'][0]['label']);
        $this->assertEquals($yesOption->getValue(), $jsonSerializableObjectAttribute['possibleValues'][0]['value']);
        $this->assertEquals($noOption->getLabel(), $jsonSerializableObjectAttribute['possibleValues'][1]['label']);
        $this->assertEquals($noOption->getValue(), $jsonSerializableObjectAttribute['possibleValues'][1]['value']);
    }

    public function testJsonSerializableForSearchResultWithAllKindOfAttributes()
    {
        $simpleTextAttr = new TextAttribute('testTitle', 'testText');
        $simpleDateAttr = new DateAttribute('testDate', new DateTime('2012-04-23T18:25:43.511'));
        $this->searchResultItem->addAttribute(new TextAttribute('testTitle', 'testText'));
        $this->searchResultItem->addAttribute(new AuthorAttribute('testAuthor', 'testUser'));
        $this->searchResultItem->addAttribute(new UrlAttribute('testUrl', 'www.test.test'));
        $this->searchResultItem->addAttribute(new ImageAttribute('testImage', 'testImage'));
        $this->searchResultItem->addAttribute(new DateAttribute('testDate', new DateTime('2012-04-23T18:25:43.511')));
        $this->searchResultItem->addAttribute(new SimpleCollectionAttribute(
            'testTags',
            array($simpleTextAttr, $simpleDateAttr))
        );
        $this->searchResultItem->addAttribute(new ComplexCollectionAttribute(
            'testComplex',
            array($simpleTextAttr, $simpleDateAttr)
        ));
        $this->searchResultItem->addAttribute(new HtmlAttribute('testHtml', '<html></html>'));
        $this->searchResultItem->addAttribute(new BoolAttribute('testBool', true));
        $this->searchResultItem->addAttribute(new NumberAttribute('testNumber', '32.32'));
        $numberOfAddedAttributes = 10;
        $numberOfImplicitMandatoryAttributes = 2;

        $jsonSerializableSearchResult = $this->searchResultItem->toArray();

        $this->assertEquals(self::SEARCHABLE_ITEM_CODE, $jsonSerializableSearchResult['itemCode']);
        $this->assertEquals(self::ATTRIBUTE_ID, $jsonSerializableSearchResult['id']);
        $this->assertNotEmpty($jsonSerializableSearchResult['attributes']['title']);
        $this->assertEquals(self::ATTRIBUTE_TITLE, $jsonSerializableSearchResult['attributes']['title']);
        $this->assertNotEmpty($jsonSerializableSearchResult['attributes']['date']);
        $this->assertEquals(
            $numberOfAddedAttributes + $numberOfImplicitMandatoryAttributes,
            count($jsonSerializableSearchResult['attributes'])
        );
        $this->assertNotEmpty($jsonSerializableSearchResult['attributes']['testTitle']);
        $this->assertEquals('testText', $jsonSerializableSearchResult['attributes']['testTitle']);
        $this->assertNotEmpty($jsonSerializableSearchResult['attributes']['testAuthor']);
        $this->assertEquals('testUser', $jsonSerializableSearchResult['attributes']['testAuthor']);
        $this->assertNotEmpty($jsonSerializableSearchResult['attributes']['testUrl']);
        $this->assertEquals('www.test.test', $jsonSerializableSearchResult['attributes']['testUrl']);
        $this->assertNotEmpty($jsonSerializableSearchResult['attributes']['testImage']);
        $this->assertEquals('testImage', $jsonSerializableSearchResult['attributes']['testImage']);
        $this->assertNotEmpty($jsonSerializableSearchResult['attributes']['testDate']);
        $this->assertEquals('2012-04-23T18:25:43.511000Z', $jsonSerializableSearchResult['attributes']['testDate']);
        $this->assertNotEmpty($jsonSerializableSearchResult['attributes']['testTags']);
        $this->assertEquals(
            array('testText', '2012-04-23T18:25:43.511000Z'), $jsonSerializableSearchResult['attributes']['testTags']
        );
        $this->assertNotEmpty($jsonSerializableSearchResult['attributes']['testHtml']);
        $this->assertEquals('<html></html>', $jsonSerializableSearchResult['attributes']['testHtml']);
        $this->assertNotEmpty($jsonSerializableSearchResult['attributes']['testNumber']);
        $this->assertEquals(32.32, $jsonSerializableSearchResult['attributes']['testNumber']);

        $this->assertEquals(
            json_encode(array('data' => $jsonSerializableSearchResult)),
            $this->searchResultItem->toJson()
        );
    }

    public function testJsonSerializableForSearchResults()
    {
        $this->searchResult->addSearchResultItem($this->searchResultItem);
        $this->searchResult->addSearchResultItem($this->searchResultItem);

        $jsonSerializableSearchResult = $this->searchResult->toArray();

        $this->assertEquals(self::SEARCHABLE_ITEM_CODE, $jsonSerializableSearchResult[0]['itemCode']);
        $this->assertEquals(self::ATTRIBUTE_ID, $jsonSerializableSearchResult[0]['id']);
        $this->assertNotEmpty($jsonSerializableSearchResult[0]['attributes']['title']);
        $this->assertEquals(self::ATTRIBUTE_TITLE, $jsonSerializableSearchResult[0]['attributes']['title']);
        $this->assertNotEmpty($jsonSerializableSearchResult[0]['attributes']['date']);
        $this->assertEquals(
            json_encode(array('data' => $jsonSerializableSearchResult)),
            $this->searchResult->toJson()
        );
    }
}