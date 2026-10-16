<?php

declare(strict_types=1);

namespace Essentia\RetiradaLoja\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Shipping\Model\Config\Source\Allmethods;

class ShippingMethods implements OptionSourceInterface
{
    private Allmethods $shippingMethods;

    public function __construct(Allmethods $shippingMethods)
    {
        $this->shippingMethods = $shippingMethods;
    }

    /**
     * Keep pickup selectable even when disabled or renamed in store settings.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function toOptionArray(): array
    {
        $pickupCode = 'essentia_retiradaloja_retirada_loja';
        $options = [
            $pickupCode => [
                'value' => $pickupCode,
                'label' => (string) __('Retirado na Loja'),
            ],
        ];

        // The native source includes inactive carriers and does not collect rates.
        foreach ($this->shippingMethods->toOptionArray(false) as $carrier) {
            if (!is_array($carrier['value'])) {
                continue;
            }

            foreach ($carrier['value'] as $method) {
                $code = (string) $method['value'];

                if ($code === '' || isset($options[$code])) {
                    continue;
                }

                $options[$code] = [
                    'value' => $code,
                    'label' => (string) $carrier['label'] . ' - ' . (string) $method['label'],
                ];
            }
        }

        return array_values($options);
    }
}
