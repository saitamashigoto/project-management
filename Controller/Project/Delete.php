<?php
namespace Piyush\ProjectManagement\Controller\Project;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Action;
use Magento\Customer\Model\Session;
use Magento\Framework\Controller\Result\RedirectFactory;
use Piyush\ProjectManagement\Helper\Data as DataHelper;
use Magento\Framework\Registry;
use Piyush\ProjectManagement\Api\Data\ProjectInterfaceFactory;
use Magento\Framework\UrlFactory;
use Magento\Framework\Data\Form\FormKey\Validator;


class Delete extends AbstractAction implements HttpPostActionInterface
{
    private $projectFactory;

    private $formKeyValidator;

    private $urlModel;

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
        ProjectInterfaceFactory $projectFactory,
        Validator $formKeyValidator,
        UrlFactory $urlFactory
    ) {
        $this->projectFactory = $projectFactory;
        $this->urlModel = $urlFactory->create();
        $this->formKeyValidator = $formKeyValidator;
        parent::__construct($context, $resultPageFactory, $session, $resultRedirectFactory, $dataHelper, $registry);
    }

    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        if (!$this->isAllowed()) {
            return $this->resultRedirectFactory->create()->setPath('/');
        }
        $returnUrl = $this->urlModel->getUrl('*/*/index', ['_secure' => true]);
        if (!$this->getRequest()->isPost()
            || !$this->formKeyValidator->validate($this->getRequest())
        ) {
            $this->messageManager->addErrorMessage(__('Invalid Request.'));
            return $this->resultRedirectFactory->create()->setUrl($this->_redirect->error($returnUrl));
        }
        $projectId = $this->getRequest()->getPostValue('project_id');
        if (!$projectId) {
            $this->messageManager->addErrorMessage(__('Invalid Request.'));
            return $this->resultRedirectFactory->create()->setUrl($this->_redirect->error($returnUrl));
        } 
        $project = $this->projectFactory->create()->load($projectId);
        if (!$project->getId() || (int)$project->getCustomerId() !== (int)$this->session->getCustomerId()) {
            $this->messageManager->addErrorMessage(__('Invalid Request.'));
            return $this->resultRedirectFactory->create()->setUrl($this->_redirect->error($returnUrl));
        } 
        $project->delete();
        $this->messageManager->addSuccessMessage(__('Project successfully deleted.'));
        return $this->resultRedirectFactory->create()->setUrl($this->_redirect->success($returnUrl));
    }
}
