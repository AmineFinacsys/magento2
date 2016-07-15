<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Catalog\Model\ResourceModel\Product;

use Magento\Catalog\Model\Product;
use Magento\Framework\DB\Select;
use Magento\Store\Model\Store;

/**
 * Class ProductProviderByBasePrice
 */
class ProductProviderByBasePrice implements ProductProviderByPriceInterface
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resource;

    /**
     * @var \Magento\Eav\Model\Config
     */
    private $eavConfig;

    /**
     * @var \Magento\Catalog\Helper\Data
     */
    private $catalogHelper;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Catalog\Helper\Data $catalogHelper
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Catalog\Helper\Data $catalogHelper
    ) {
        $this->storeManager = $storeManager;
        $this->resource = $resourceConnection;
        $this->eavConfig = $eavConfig;
        $this->catalogHelper = $catalogHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getSelect($productId)
    {
        $priceAttribute = $this->eavConfig->getAttribute(Product::ENTITY, 'price');
        $priceSelect = $this->resource->getConnection()->select()
            ->from(['t' => $priceAttribute->getBackendTable()], 'entity_id')
            ->joinInner(
                ['link' => $this->resource->getTableName('catalog_product_relation')],
                'link.child_id = t.entity_id',
                []
            )->where('link.parent_id = ? ', $productId)
            ->where('t.attribute_id = ?', $priceAttribute->getAttributeId())
            ->where('t.value IS NOT NULL')
            ->order('t.value ' . Select::SQL_ASC)
            ->limit(1);

        $priceSelectDefault = clone $priceSelect;
        $priceSelectDefault->where('t.store_id = ?', Store::DEFAULT_STORE_ID);
        $select[] = $priceSelectDefault;

        if (!$this->catalogHelper->isPriceGlobal()) {
            $priceSelect->where('t.store_id = ?', $this->storeManager->getStore()->getId());
            $select[] = $priceSelect;;
        }

        return $select;
    }
}
