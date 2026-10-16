<?php

declare(strict_types=1);

namespace Essentia\RetiradaLoja\Model\Carrier;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Rate\ResultFactory;
use Psr\Log\LoggerInterface;

class RetiradaLoja extends AbstractCarrier implements CarrierInterface
{
    private const METHOD_CODE = 'retirada_loja';

    /** @var string */
    protected $_code = 'essentia_retiradaloja';

    /** @var bool */
    protected $_isFixed = true;

    private ResultFactory $rateResultFactory;

    private MethodFactory $rateMethodFactory;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        array $data = []
    ) {
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);

        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
    }

    /**
     * Return a single free pickup rate when the carrier is enabled.
     */
    public function collectRates(RateRequest $request): Result|false
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $method = $this->rateMethodFactory->create();
        $method->setCarrier($this->_code);
        $method->setCarrierTitle((string) $this->getConfigData('title'));
        $method->setMethod(self::METHOD_CODE);
        $method->setMethodTitle((string) $this->getConfigData('name'));
        $method->setPrice(0.00);
        $method->setCost(0.00);

        $result = $this->rateResultFactory->create();
        $result->append($method);

        return $result;
    }

    /**
     * List the method exposed to the native shipping workflow.
     *
     * @return array<string, string>
     */
    public function getAllowedMethods(): array
    {
        return [self::METHOD_CODE => (string) $this->getConfigData('name')];
    }
}
