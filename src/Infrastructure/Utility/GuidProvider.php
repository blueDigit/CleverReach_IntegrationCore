<?php

namespace CleverReach\Infrastructure\Utility;

/**
 * Class GuidProvider
 *
 * @package CleverReach\Infrastructure\Utility
 */
class GuidProvider
{
    const CLASS_NAME = __CLASS__;

    /**
     * Unique identifier generator.
     *
     * @return string
     *   Generated guid.
     */
    public function generateGuid()
    {
        return uniqid(getmypid() . '_', true);
    }
}
