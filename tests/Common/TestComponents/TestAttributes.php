<?php

namespace CleverReach\Tests\Common\TestComponents;

use CleverReach\BusinessLogic\Entity\RecipientAttribute;
use CleverReach\BusinessLogic\Interfaces\Attributes;

class TestAttributes implements Attributes
{

    /**
     * Get attribute from shop with translated description in shop language
     * It should set description, preview_value and default_value based on attribute name
     *
     * @return RecipientAttribute[]
     */
    public function getAttributes()
    {
        return $this->createTestAttributes();
    }

    /**
     * @return array
     */
    private function createTestAttributes()
    {
        $allAttributes = array();
        $integrationAttributes = array(
            'email',
            'salutation',
            'title',
            'firstname',
            'lastname',
            'street',
            'zip',
            'city',
            'company',
            'state',
            'country',
            'birthday',
            'phone',
            'shop',
            'customernumber',
            'language',
            'newsletter',
        );

        foreach ($integrationAttributes as $integrationAttributeName) {
            $recipientAttribute = new RecipientAttribute($integrationAttributeName);
            $recipientAttribute->setDescription('Description');
            $recipientAttribute->setPreviewValue('Preview value');
            $recipientAttribute->setDefaultValue('Default value');

            $allAttributes[] = $recipientAttribute;
        }

        return $allAttributes;
    }
}
