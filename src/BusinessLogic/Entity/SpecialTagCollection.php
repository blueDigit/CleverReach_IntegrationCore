<?php

namespace CleverReach\BusinessLogic\Entity;

/**
 * Class SpecialTagCollection
 *
 * @package CleverReach\BusinessLogic\Entity
 */
class SpecialTagCollection extends TagCollection
{
    /**
     * SpecialTagCollection constructor.
     *
     * @param \CleverReach\BusinessLogic\Entity\SpecialTag[] $tags List of special tags.
     */
    public function __construct(array $tags = array())
    {
        parent::__construct();

        if (!empty($tags)) {
            foreach ($tags as $tag) {
                $this->addTag($tag);
            }
        }
    }

    /**
     * Adds tag to collection if it does not exist.
     *
     * @param \CleverReach\BusinessLogic\Entity\SpecialTag|AbstractTag $tag Special tag.
     *
     * @return \CleverReach\BusinessLogic\Entity\TagCollection
     *   List of special tags.
     */
    public function addTag($tag)
    {
        if ($tag instanceof SpecialTag) {
            return parent::addTag($tag);
        }

        throw new \InvalidArgumentException('Special tag collection accepts only SpecialTag instances.');
    }
}
