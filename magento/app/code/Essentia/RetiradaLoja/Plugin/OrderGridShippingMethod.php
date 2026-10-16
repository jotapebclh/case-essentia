<?php

declare(strict_types=1);

namespace Essentia\RetiradaLoja\Plugin;

use Magento\Framework\DB\Select;
use Magento\Framework\Data\Collection;
use Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

class OrderGridShippingMethod
{
    /**
     * Join shipping data before the data provider applies filters and pagination.
     */
    public function afterGetReport(
        CollectionFactory $subject,
        Collection $collection,
        string $requestName
    ): Collection {
        if ($requestName !== 'sales_order_grid_data_source' || !$collection instanceof SearchResult) {
            return $collection;
        }

        $select = $collection->getSelect();
        $tables = $select->getPart(Select::FROM);

        if (isset($tables['essentia_order_shipping'])) {
            return $collection;
        }

        // Expose only uniquely named fields. Native date filters can bypass the
        // field map, so joining sales_order directly would make them ambiguous.
        $shippingData = $collection->getConnection()->select()->from(
            $collection->getTable('sales_order'),
            [
                'essentia_shipping_order_id' => 'entity_id',
                'essentia_shipping_method' => 'shipping_method',
                'essentia_shipping_description' => 'shipping_description',
            ]
        );

        $select->joinLeft(
            ['essentia_order_shipping' => $shippingData],
            'main_table.entity_id = essentia_order_shipping.essentia_shipping_order_id',
            ['essentia_shipping_method', 'essentia_shipping_description']
        );

        $collection->addFilterToMap(
            'essentia_shipping_method',
            'essentia_order_shipping.essentia_shipping_method'
        );

        return $collection;
    }
}
