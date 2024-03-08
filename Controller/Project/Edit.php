<?php
namespace Piyush\ProjectManagement\Controller\Project;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Action;
use Magento\Customer\Model\Session;
use Magento\Framework\Controller\Result\RedirectFactory;
use Piyush\ProjectManagement\Helper\Data as DataHelper;
use Magento\Framework\Registry;
use Piyush\ProjectManagement\Api\Data\ProjectInterfaceFactory;


class Edit extends AbstractAction implements HttpGetActionInterface
{
    protected $projectFactory;

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
        Registry $registry,
        ProjectInterfaceFactory $projectFactory
    ) {
        $this->projectFactory = $projectFactory;
        parent::__construct($context, $resultPageFactory, $session, $resultRedirectFactory, $dataHelper, $registry);
    }

    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $isValid = $this->isValidProjectId();
        $isAllowed = $this->isAllowed();
        if ($isAllowed && $isValid) {
            $this->registry->register('project_id', $this->getRequest()->getParam('project_id'), true);
            return $this->resultPageFactory->create();
        }
        if (!$isAllowed) {
            return $this->resultRedirectFactory->create()->setPath('/');
        }

        if (!$isValid) {
            $this->messageManager->addErrorMessage(__('Invalid Project.'));
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('piyush_project_management/project/index');
        return $resultRedirect;
    }

    protected function isValidProjectId()
    {
        $projectId = $this->getRequest()->getParam('project_id');
        if (!$projectId) return false;
        $project = $this->projectFactory->create()->load($projectId);
        if (empty($project->getId()) || (int)$project->getCustomerId() !== (int)$this->session->getCustomerId()) return false;
        return true;
    }
}
