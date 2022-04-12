<?php

namespace CleverReach\Tests\BusinessLogic\Entity;

use CleverReach\BusinessLogic\Entity\SpecialTag;
use CleverReach\BusinessLogic\Entity\SpecialTagCollection;
use CleverReach\BusinessLogic\Entity\Tag;
use CleverReach\BusinessLogic\Entity\TagCollection;
use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Tests\Common\TestComponents\Logger\TestShopConfiguration;
use PHPUnit\Framework\TestCase;

class SpecialTagCollectionTest extends TestCase
{
    private $integrationName = 'DUMMY';

    protected function setUp()
    {
        $config = new TestShopConfiguration();
        $config->setIntegrationName($this->integrationName);
        new ServiceRegister(
            array(
                Configuration::CLASS_NAME => function () use ($config) {
                    return $config;
                },
            )
        );
    }

    public function testAddTagsToTagCollection()
    {
        $tagCollection = new SpecialTagCollection();
        self::assertCount(0, $tagCollection);

        $tagCollection->addTag(SpecialTag::customer());
        self::assertCount(1, $tagCollection);

        $tagCollection->addTag(SpecialTag::buyer())
            ->addTag(SpecialTag::subscriber())
            ->addTag(SpecialTag::contact());

        self::assertCount(4, $tagCollection);

        $tagCollection->add(SpecialTag::all());
        self::assertCount(4, $tagCollection);

        $newTagCollection = new SpecialTagCollection();
        $newTagCollection->add(SpecialTag::all());
        self::assertCount(4, $newTagCollection);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testValidateCreateSpecialTagCollectionWithRegularTag()
    {
        new SpecialTagCollection(array(new Tag('CustomTag', 'type')));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testValidateRegularTagInSpecialTagCollection()
    {
        $tagCollection = new SpecialTagCollection();
        $tagCollection->addTag(SpecialTag::customer());

        /** @noinspection PhpParamsInspection */
        $tagCollection->addTag(new Tag('Customer', 'Special'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testValidateMergeTagCollectionToSpecialTagCollection()
    {
        $tagCollection = new SpecialTagCollection();
        $tagCollection->addTag(SpecialTag::customer());

        $tagCollection2 = new TagCollection();
        $tagCollection2->addTag(new Tag('CustomTag', 'type'));

        // allow merging special tag collection to tag collection
        $tagCollection2->add($tagCollection);

        // this should throw exception
        $tagCollection->add($tagCollection2);
    }
}
