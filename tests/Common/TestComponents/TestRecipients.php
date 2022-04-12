<?php

namespace CleverReach\Tests\Common\TestComponents;

use CleverReach\BusinessLogic\Entity\OrderItem;
use CleverReach\BusinessLogic\Entity\Recipient;
use CleverReach\BusinessLogic\Entity\SpecialTag;
use CleverReach\BusinessLogic\Entity\SpecialTagCollection;
use CleverReach\BusinessLogic\Entity\Tag;
use CleverReach\BusinessLogic\Entity\TagCollection;
use CleverReach\BusinessLogic\Interfaces\Recipients;

class TestRecipients implements Recipients
{
    /** @var TagCollection  */
    public $allTags;

    /** @var SpecialTagCollection  */
    public $allSpecialTags;

    /** @var bool  */
    public $getAllTagsIsCalled = false;
    /** @var bool  */
    public $getAllSpecialTagsIsCalled = false;

    /** @var bool */
    public $shouldGenerateTimeStampForDeactivated = false;

    public function __construct()
    {
        $this->allTags = new TagCollection(
            array(
                new Tag('Group1', 'Group'),
                new Tag('Group2', 'Group'),
                new Tag('Subsystem1', 'System'),
            )
        );

        $this->allSpecialTags = new SpecialTagCollection(
            array(
                SpecialTag::customer(),
                SpecialTag::subscriber(),
            )
        );
    }

    public function getAllTags()
    {
        $this->getAllTagsIsCalled = true;

        return $this->allTags;
    }

    public function getAllSpecialTags()
    {
        $this->getAllSpecialTagsIsCalled = true;

        return $this->allSpecialTags;
    }

    public function getRecipientsWithTags(array $batchRecipientIds, $includeOrders)
    {
        $recipients = array();

        foreach ($batchRecipientIds as $id) {
            $recipient = new Recipient($id);
            $recipient->setActive(!$this->shouldGenerateTimeStampForDeactivated);
            $recipient->setActivated(date_create_from_format('m/d/Y', '1/10/2014'));
            $recipient->setDeactivated(
                $this->shouldGenerateTimeStampForDeactivated ? date_create_from_format('m/d/Y', '1/10/2014') : null
            );
            $recipient->setRegistered(date_create_from_format('m/d/Y', '1/10/2014'));
            $recipient->setLastName('Koca');
            $recipient->setFirstName('Dule');
            $recipient->setSalutation('Mr.');
            $recipient->setTitle('Dr');
            $recipient->setStreet('BB');
            $recipient->setZip('11000');
            $recipient->setCity('Belgrade');
            $recipient->setCompany('LGC');
            $recipient->setState('Serbia');
            $recipient->setCountry('Serbia');
            $recipient->setBirthday(date_create_from_format('m/d/Y', '1/10/2014'));
            $recipient->setPhone('+49117666666');
            $recipient->setShop('Presta');
            $recipient->setCustomerNumber('123212');
            $recipient->setLanguage('de');
            $recipient->setNewsletterSubscription(true);
            $recipient->setAttributes(array('is_batman'=> false));
            $recipient->setTags(new TagCollection(array(new Tag('Group2', 'Group'), new Tag('Group3', 'Group'))));
            $recipient->setSpecialTags(new SpecialTagCollection(array(SpecialTag::subscriber())));
            
            if ($includeOrders) {
                $recipient->setOrders($this->createOrdersForRecipient());
            }

            $recipients[] = $recipient;
        }

        return $recipients;
    }
    
    private function createOrdersForRecipient()
    {
        $order1 = new OrderItem('xyz123412', 'SN9876543');
        $order1->setProductId('Batman - The Movie (DVD)');
        $order1->setPrice(99);
        $order1->setCurrency('EUR');
        $order1->setAmount(12);
        $order1->setProductSource('Presta');
        $order1->setBrand('nike');
        $order1->setProductCategory(array('cd'));
        $order1->setAttributes(array('attr' => 'a'));
        $order2 = new OrderItem('xyz123413', 'SN9876544');
        
        return array($order1, $order2);
    }

    public function getAllRecipientsIds()
    {
        $numberOfRecipientsForMock = 300;
        $allRecipientIds = array();
        
        for ($i = 0; $i < $numberOfRecipientsForMock; $i++) {
            $allRecipientIds[] = $i . 'test@test.com';
        }
        
        return $allRecipientIds; 
    }

    /**
     * Informs service about completed synchronization of provided recipients (IDs).
     *
     * @param array $recipientIds
     */
    public function recipientSyncCompleted(array $recipientIds)
    {

    }
}