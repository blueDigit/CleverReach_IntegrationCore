<?php

namespace CleverReach\Tests\BusinessLogic\Entity;

use CleverReach\BusinessLogic\Entity\Tag;
use CleverReach\BusinessLogic\Entity\TagCollection;
use CleverReach\BusinessLogic\Entity\TagInOldFormat;
use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Tests\Common\TestComponents\Logger\TestShopConfiguration;
use PHPUnit\Framework\TestCase;

/**
 * Class TagCollectionTest
 *
 * @package CleverReach\Tests\BusinessLogic\Entity
 */
class TagCollectionTest extends TestCase
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

    public function testAddTagToTagCollection()
    {
        $tagCollection = new TagCollection();
        self::assertCount(0, $tagCollection);

        $tagCollection->addTag(new Tag('first', 'type'));
        self::assertCount(1, $tagCollection);

        $tagCollection->addTag(new Tag('second', 'type'));
        self::assertCount(2, $tagCollection);

        $tagCollection = new TagCollection(
            array(new Tag('first', 'type'), new Tag('second', 'type'), new Tag('third', 'type'))
        );
        self::assertCount(3, $tagCollection);
    }

    public function testIterationThroughTagCollection()
    {
        $tagCollection = new TagCollection();
        $i = 0;
        foreach ($tagCollection as $tag) {
            self::assertNotEmpty($tag);
            $i++;
        }

        self::assertEquals(0, $i);

        $tagCollection->addTag(new Tag('first', 'type'));
        $tagCollection->addTag(new Tag('second', 'type'));

        $i = 0;
        foreach ($tagCollection as $tag) {
            if ($i === 0) {
                self::assertEquals($this->integrationName . '-type.first', (string)$tag);
            } else {
                self::assertEquals($this->integrationName . '-type.second', (string)$tag);
            }

            $i++;
        }

        self::assertEquals(2, $i);

        // repeat to test rewind
        $i = 0;
        foreach ($tagCollection as $tag) {
            if ($i === 0) {
                self::assertEquals($this->integrationName . '-type.first', (string)$tag);
            } else {
                self::assertEquals($this->integrationName . '-type.second', (string)$tag);
            }

            $i++;
        }

        self::assertEquals(2, $i);
    }

    public function testRemoveCollectionFromTagCollection()
    {
        $tagCollection = new TagCollection();
        $tagCollection->addTag(new Tag('first', 'type'));
        $tagCollection->addTag(new Tag('second', 'type'));

        $tagCollection2 = new TagCollection();
        $tagCollection2->addTag(new Tag('third', 'type'));

        // no change
        $tagCollection->remove($tagCollection2);

        self::assertCount(2, $tagCollection);

        $tagCollection2->addTag(new Tag('first', 'type'));
        // should remove one
        $tagCollection->remove($tagCollection2);
        self::assertCount(1, $tagCollection);
    }

    public function testDiffTagCollections()
    {
        $tagCollection = new TagCollection();
        $tagCollection->addTag(new Tag('first', 'type'));
        $tagCollection->addTag(new Tag('second', 'type'));

        $tagCollection2 = new TagCollection();
        $tagCollection2->addTag(new Tag('third', 'type'));

        // should diff by 2, original collections should not change
        $result = $tagCollection->diff($tagCollection2);

        self::assertCount(2, $tagCollection);
        self::assertCount(1, $tagCollection2);
        self::assertCount(2, $result);

        $tagCollection2->addTag(new Tag('first', 'type'));

        // should diff by one, original collections should not change
        $result = $tagCollection->diff($tagCollection2);

        self::assertCount(2, $tagCollection);
        self::assertCount(2, $tagCollection2);
        self::assertCount(1, $result);
    }

    public function testAddCollectionToTagCollection()
    {
        $tagCollection = new TagCollection();
        $tagCollection->addTag(new Tag('first', 'type'));
        $tagCollection->addTag(new Tag('second', 'type'));

        $tagCollection2 = new TagCollection();
        $tagCollection2->addTag(new Tag('third', 'type'));

        // add one
        $tagCollection->add($tagCollection2);

        self::assertCount(3, $tagCollection);

        $tagCollection2->addTag(new Tag('first', 'type'));
        // should not add because the same element already exists
        $tagCollection->add($tagCollection2);
        self::assertCount(3, $tagCollection);

        $tagCollection->add($tagCollection2);
        self::assertCount(3, $tagCollection);
    }

    public function testMergeCollections()
    {
        $tagCollection = new TagCollection(array(new Tag('first', 'type'), new Tag('second', 'type')));

        $tagCollection2 = new TagCollection();
        $tagCollection2->addTag(new Tag('third', 'type'));

        // merge, original collections should not change
        $tagCollection3 = $tagCollection->merge($tagCollection2);

        self::assertCount(2, $tagCollection);
        self::assertCount(1, $tagCollection2);
        self::assertCount(3, $tagCollection3);

        $tagCollection2->addTag(new Tag('first', 'type'));

        // should not increase because the same element added, original collections should not change
        $tagCollection3 = $tagCollection->merge($tagCollection2);

        self::assertCount(2, $tagCollection);
        self::assertCount(2, $tagCollection2);
        self::assertCount(3, $tagCollection3);

        // repeat to test references, original collections should not change
        $tagCollection3 = $tagCollection->merge($tagCollection2);

        self::assertCount(2, $tagCollection);
        self::assertCount(2, $tagCollection2);
        self::assertCount(3, $tagCollection3);
    }

    public function testTagNameOriginAndPrefix()
    {
        $tag = new TagInOldFormat('Name');
        self::assertEquals('Name', (string)$tag);
        self::assertEquals('Name', $tag->getTitle());

        $tag = new Tag('Name', 'Type');
        self::assertEquals($this->integrationName . '-Type.Name', (string)$tag);
        self::assertEquals('Type: Name', $tag->getTitle());
    }

    public function testSpecialCharacters()
    {
        $tag = new Tag('Name of the tag', 'Tag group');
        self::assertEquals($this->integrationName . '-Tag_group.Name_of_the_tag', (string)$tag);
        self::assertEquals('Tag group: Name of the tag', $tag->getTitle());

        $tag = new Tag('Näme of t::he tag.&#(', '*Tagć ++gro:up-');
        $tag->markDeleted();
        self::assertEquals('-' . $this->integrationName . '-_Tagć_gro_up_.Näme_of_t_he_tag_', (string)$tag);
        self::assertEquals('*Tagć ++gro:up-: Näme of t::he tag.&#(', $tag->getTitle());
    }

    public function testMarkTagDeleted()
    {
        $tag = new Tag('Name', 'Origin');
        $tag->markDeleted();

        self::assertEquals('-' . $this->integrationName . '-Origin.Name', (string)$tag);
    }

    public function testCuttingTagIfTooLong()
    {
        $tag = new Tag('qwertyuiopassdfghkzxcvbnm123456789012345678901234567', 'Origin');
        self::assertLessThanOrEqual(49, strlen((string)$tag));

        $tag->markDeleted();
        self::assertLessThanOrEqual(50, strlen((string)$tag));

        $tag1 = new Tag('qwertyuiopassdfghkzxcvbnm123456789012345678901234567', 'Origin');
        $tag2 = new Tag('qwertyuiopassdfghkzxcvbnm123456789012345678901234567456789456', 'Origin');
        self::assertTrue($tag1->isEqual($tag2));
    }

    public function testSerialization()
    {
        $tag1 = new Tag('Name', 'Origin');
        $unserializedTag1 = unserialize(serialize($tag1));
        self::assertEquals((string)$tag1, (string)$unserializedTag1);

        $tag2 = new Tag('Name2', 'Origin2');
        $tagCollection = new TagCollection(array($tag1, $tag2));
        $unserializedCollection = unserialize(serialize($tagCollection));
        self::assertEquals(0, $tagCollection->diff($unserializedCollection)->count());
    }

    public function testValidationEmptyNameAndTag()
    {
        $this->expectException('InvalidArgumentException');
        new Tag('', '');
    }

    public function testValidationEmptyTag()
    {
        $this->expectException('InvalidArgumentException');
        new Tag('asdf', '');
        new Tag('', 'asdf');
    }

    public function testValidationEmptyName()
    {
        $this->expectException('InvalidArgumentException');
        new Tag('', 'asdf');
    }

    public function testValidationEmptyNameTagInOldFormat()
    {
        new TagInOldFormat('name');

        $this->expectException('InvalidArgumentException');
        new TagInOldFormat('');
    }
}
