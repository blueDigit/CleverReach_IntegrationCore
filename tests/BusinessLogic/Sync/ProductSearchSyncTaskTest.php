<?php

namespace CleverReach\Tests\BusinessLogic\Sync;

use CleverReach\BusinessLogic\Sync\ProductSearchSyncTask;

class ProductSearchSyncTaskTest extends BaseSyncTest
{
    /**
     * Test execute when product search parameters are not set (name, url, password)
     */
    public function testExecuteMethodWhenProductSearchParamsNotSet()
    {
        $this->shopConfig->setProductSearchParameters(array());
        $this->expectException('\InvalidArgumentException');
        $this->syncTask->execute();
    }

    /**
     * Test execute method when proxy API throws exception
     */
    public function testExecuteMethodWhenExceptionOccurred()
    {
        $this->proxy->throwExceptionCode = 400;
        $this->expectException('CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException');
        $this->syncTask->execute();
    }

    /**
     * @return ProductSearchSyncTask
     */
    protected function createSyncTaskInstance()
    {
        return new ProductSearchSyncTask();
    }

    /**
     * @inheritdoc
     */
    protected function initShopConfiguration()
    {
        parent::initShopConfiguration();
        $this->shopConfig->setProductSearchParameters(
            array(
                'name' => 'My shop - Product search',
                'url' => 'http://myshop.com/endpoint',
                'password' => 's3Sdsdf34dfsWSW',
            )
        );
    }
}
