<?php
namespace Piyush\ProjectManagement\Controller\Project;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Action;
use Magento\Customer\Model\Session;
use Magento\Framework\Controller\Result\RedirectFactory;
use Piyush\ProjectManagement\Helper\Data as DataHelper;
use Magento\Framework\Registry;

class AbstractAction extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    protected $session;

    protected $resultRedirectFactory;

    protected $dataHelper;

    protected $registry;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Session $session,
        RedirectFactory $resultRedirectFactory,
        DataHelper $dataHelper,
        Registry $registry
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->session = $session;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->dataHelper = $dataHelper;
        $this->registry = $registry;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        if ($this->isAllowed()) {
            return $this->resultPageFactory->create();
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('/');
        return $resultRedirect;
    }

    protected function isAllowed()
    {
        return $this->session->isLoggedIn() && $this->dataHelper->isEnabled();      
    }
}
