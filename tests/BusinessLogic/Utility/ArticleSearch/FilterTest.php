<?php

use CleverReach\BusinessLogic\Utility\ArticleSearch\Conditions;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Filter;
use PHPUnit\Framework\TestCase;

class FilterTest extends TestCase
{
    const ITEM_CODE = 'article';

    const ITEM_ID = '1';

    public function testFilterWithInvalidCondition()
    {
        $this->expectException('\InvalidArgumentException');

        new Filter('testCode', 'testValue', 'testCondition', 'testOperator');
    }

    public function testFilterWithInvalidOperator()
    {
        $this->expectException('\InvalidArgumentException');

        new Filter('testCode', 'testValue', Conditions::CONTAINS, 'testOperator');
    }
}