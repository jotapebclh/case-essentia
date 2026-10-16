<?php

declare(strict_types=1);

namespace Essentia\RetiradaLoja\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

class ShippingMethod extends Column
{
    /**
     * Render the description saved on the order without loading order models.
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        $fieldName = (string) $this->getData('name');

        foreach ($dataSource['data']['items'] as &$item) {
            $method = (string) ($item[$fieldName] ?? '');
            $description = (string) ($item['essentia_shipping_description'] ?? '');

            $item[$fieldName] = $method === ''
                ? (string) __('Sem entrega')
                : ($description !== '' ? $description : $method);
        }
        unset($item);

        return $dataSource;
    }
}
