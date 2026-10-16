<?php

declare(strict_types=1);

namespace Essentia\RetiradaLoja\Plugin;

use Magento\Framework\DB\Select;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection;

class OrderGridDateFilter
{
    private TimezoneInterface $timeZone;

    public function __construct(TimezoneInterface $timeZone)
    {
        $this->timeZone = $timeZone;
    }

    /**
     * Preserve date filtering when other extensions also join sales_order.
     *
     * @param Collection $subject
     * @param string|array $field
     * @param mixed $condition
     * @return array
     */
    public function beforeAddFieldToFilter(Collection $subject, $field, $condition = null): array
    {
        $tables = $subject->getSelect()->getPart(Select::FROM);

        if ($field !== 'created_at' || !isset($tables['essentia_order_shipping'])) {
            return [$field, $condition];
        }

        // Match Order\Grid\Collection's conversion, exactly once. Qualifying
        // the field prevents the native plugins from converting it again.
        if (is_array($condition)) {
            foreach ($condition as $key => $value) {
                $condition[$key] = $this->timeZone->convertConfigTimeToUtc($value);
            }
        }

        return ['main_table.created_at', $condition];
    }
}
