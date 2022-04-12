<?php

namespace CleverReach\Tests\GenericTests\TestComponents;

use CleverReach\Infrastructure\TaskExecution\Task;

/**
 * Class FakeTask
 *
 * @package CleverReach\Tests\GenericTests\TestComponents
 */
class FakeTask extends Task
{
    /** @var string */
    private $name;

    /**
     * FakeTask constructor.
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize(array(
            'name' => $this->name,
        ));
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->name = $data['name'];
    }

    /**
     * Runs task logic
     */
    public function execute()
    {
        // This method was intentionally left blank because this task is for testing purposes
    }
}
