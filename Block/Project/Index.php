<?php
namespace Piyush\ProjectManagement\Block\Project;

use Piyush\ProjectManagement\Helper\Data as DataHelper;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Piyush\ProjectManagement\Model\ResourceModel\Project\CollectionFactory as ProjectCollectionFactory;
use Magento\Framework\Data\Form\FormKey;

class Index extends AbstractBlock
{
    /**
     * @var ProjectCollectionFactory
     */
    protected $projectCollectionFactory;

    protected $formKey;

    protected $projects = null;

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
        ProjectCollectionFactory $projectCollectionFactory,
        FormKey $formKey,
        array $data = []
    ) {
        $this->projectCollectionFactory = $projectCollectionFactory;
        $this->formKey = $formKey;
        parent::__construct($context, $currentCustomer, $dataHelper, $data);
    }

    public function getProjectCollection()
    {
        return $this->projectCollectionFactory->create();
    }

    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }

    /**
     * Get customer orders
     *
     * @return bool|\Magento\Sales\Model\ResourceModel\Order\Collection
     */
    public function getProjects()
    {
        if (!($customerId = $this->currentCustomer->getCustomerId())) {
            return false;
        }
        if (!$this->projects) {
            $this->projects = $this->getProjectCollection()->addFieldToSelect(
                '*'
            )->addFieldToFilter(
                'customer_id',
                ['in' => $customerId]
            )->setOrder(
                'created_at',
                'desc'
            );
        }
        return $this->projects;
    }

    /**
     * @inheritDoc
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if ($this->getProjects()) {
            $pager = $this->getLayout()->createBlock(
                \Magento\Theme\Block\Html\Pager::class,
                'piyush_project_management.project.index.pager'
            )->setCollection(
                $this->getProjects()
            );
            $this->setChild('pager', $pager);
            $this->getProjects()->load();
        }
        return $this;
    }

    /**
     * Get Pager child block output
     *
     * @return string
     */
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }

    /**
     * Get order view URL
     *
     * @param object $order
     * @return string
     */
    public function getViewUrl($project)
    {
        return $this->getUrl('piyush_project_management/project/view', ['project_id' => $project->getId()]);
    }

    /**
     * Get reorder URL
     *
     * @param object $order
     * @return string
     */
    public function getDeleteUrl($project)
    {
        return $this->getUrl('piyush_project_management/project/delete');
    }

    /**
     * Get reorder URL
     *
     * @param object $order
     * @return string
     */
    public function getEditUrl($project)
    {
        return $this->getUrl('piyush_project_management/project/edit', ['project_id' => $project->getId()]);
    }

    /**
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('piyush_project_management/project/');
    }

    /**
     * @return string
     */
    public function getNewUrl()
    {
        return $this->getUrl('piyush_project_management/project/new');
    }

    /**
     * Get message for no orders.
     *
     * @return \Magento\Framework\Phrase
     * @since 102.1.0
     */
    public function getEmptyProjectsMessage()
    {
        return __('You have no projects.');
    }
}
