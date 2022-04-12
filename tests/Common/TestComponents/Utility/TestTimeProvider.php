<?php

namespace CleverReach\Tests\Common\TestComponents\Utility;

use CleverReach\Infrastructure\Utility\TimeProvider;

class TestTimeProvider extends TimeProvider
{
    /** @var \DateTime */
    private $time;

    public function __construct()
    {
        $this->setCurrentLocalTime(new \DateTime());
    }

    /**
     * Setup time that will be returned with get method
     *
     * @param \DateTime $time
     */
    public function setCurrentLocalTime(\DateTime $time)
    {
        $this->time = $time;
    }

    /**
     * Returns time given as parameter for set method
     *
     * @return \DateTime
     */
    public function getCurrentLocalTime()
    {
        return new \DateTime('@' . $this->time->getTimestamp());
    }

    public function sleep($sleepTime)
    {
        $currentTime = $this->getCurrentLocalTime();
        $this->setCurrentLocalTime($currentTime->add(new \DateInterval("PT{$sleepTime}S")));
    }

}