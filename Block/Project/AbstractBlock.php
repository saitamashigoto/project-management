<?php
namespace Piyush\ProjectManagement\Block\Project;

use Piyush\ProjectManagement\Helper\Data as DataHelper;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Framework\Exception\NoSuchEntityException;

abstract class AbstractBlock extends Template
{
    /**
     * @var DataHelper
     */
    protected $dataHelper;

    /**
     * @var CurrentCustomer
     */
    protected $currentCustomer;

    /**
     * Constructor
     *
     * @param Context $context
     * @param CurrentCustomer $currentCustomer
     * @param SubscriberFactory $subscriberFactory
     * @param View $helperView
     * @param array $data
     */
    public function __construct(
        Context $context,
        CurrentCustomer $currentCustomer,
        DataHelper $dataHelper,
        array $data = []
    ) {
        $this->currentCustomer = $currentCustomer;
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $data);
    }

    /**
     * Returns the Magento Customer Model for this block
     *
     * @return CustomerInterface|null
     */
    public function getCustomer()
    {
        try {
            return $this->currentCustomer->getCustomer();
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * @return int
     */
    public function getPomodoroDefaultDuration()
    {
        return $this->dataHelper->getDuration();
    }

    public function getCustomerId()
    {
        return $this->currentCustomer->getCustomerId();
    }

    /**
     * @inheritdoc
     */
    protected function _toHtml()
    {
        return $this->currentCustomer->getCustomerId() ? parent::_toHtml() : '';
    }
}
