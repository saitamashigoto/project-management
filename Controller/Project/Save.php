<?php
declare(strict_types=1);

namespace Piyush\ProjectManagement\Controller\Project;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlFactory;
use Magento\Framework\Escaper;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\View\Result\PageFactory;
use Piyush\ProjectManagement\Api\Data\ProjectInterfaceFactory;
use Piyush\ProjectManagement\Api\Data\PomodoroInterfaceFactory;
use Piyush\ProjectManagement\Helper\Data as DataHelper;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Registry;

class Save extends AbstractAction implements HttpPostActionInterface
{
    /**
     * @var \Magento\Framework\Escaper
     */
    protected $escaper;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlModel;

    /**
     * @var Validator
     */
    private $formKeyValidator;

    private $pomodoroFactory;
    
    private $projectFactory;

    private $dateTime;

    private $resources;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param UrlFactory $urlFactory
     * @param Registration $registration
     * @param Escaper $escaper
     * @param Validator $formKeyValidator
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        PageFactory $resultPageFactory,
        RedirectFactory $resultRedirectFactory,
        UrlFactory $urlFactory,
        Escaper $escaper,
        Validator $formKeyValidator,
        ProjectInterfaceFactory $projectFactory,
        PomodoroInterfaceFactory $pomodoroFactory,
        DataHelper $dataHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\App\ResourceConnection $resources,
        Registry $registry
    ) {
        $this->escaper = $escaper;
        $this->urlModel = $urlFactory->create();
        $this->formKeyValidator = $formKeyValidator;
        $this->projectFactory = $projectFactory;
        $this->pomodoroFactory = $pomodoroFactory;
        $this->dataHelper = $dataHelper;
        $this->dateTime = $dateTime;
        $this->resources = $resources;
        parent::__construct($context, $resultPageFactory, $customerSession, $resultRedirectFactory, $dataHelper, $registry);
    }

    /**
     * Create customer account action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if (!$this->isAllowed()) {
            $resultRedirect->setPath('/');
            return $resultRedirect;
        }

        if (!$this->getRequest()->isPost()
            || !$this->formKeyValidator->validate($this->getRequest())
        ) {
            $url = $this->urlModel->getUrl('*/*/create', ['_secure' => true]);
            return $this->resultRedirectFactory->create()
                ->setUrl($this->_redirect->error($url));
        }
        try {
            $title = $this->getRequest()->getPostValue('title', null);
            $dueDate = $this->getRequest()->getPostValue('due_date', null);
            $duration = $this->getRequest()->getPostValue('pomodoro_duration', null);
            $description = $this->getRequest()->getPostValue('description', null);
            $redirectUrl = $this->session->getBeforeAuthUrl();
            $errors = [];
            if (empty($title)) {
                $errors[] = __('Title can not be empty.');
            }
            if (empty($dueDate)) {
                $errors[] = __('Due Date can not be empty.');
            } else {
                $dueDate = $this->dateTime->gmtDate('Y-m-d', $dueDate);
                if (!$dueDate) {
                    $errors[] = __('Invalid due date format %1', $this->getRequest()->getPostValue('due_date'));
                }
            }

            if (empty($duration)) {
                $errors[] = __('Pomodoro duration can not be empty.');
            } else {
                if ($duration < 10 || $duration > 120) {
                    $errors[] = __('Pomodoro duration should be between 10 and 120 mintues.');
                }
            }
            if (empty($description)) {
                $errors[] = __('Description can not be empty.');
            }

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->messageManager->addErrorMessage($error);
                }
                $this->session->setProjectFormData($this->getRequest()->getPostValue());
                $defaultUrl = $this->urlModel->getUrl('*/*/new', ['_secure' => true]);
                return $resultRedirect->setUrl($this->_redirect->error($defaultUrl));
            }

            $project = $this->projectFactory->create();
            $projectId = $this->getRequest()->getPostValue('id');
            if ($projectId) {
                $project->load($projectId);
            } else {
                $project->setCreatedAt($this->dateTime->gmtDate());
            }

            if ($project->getId() && $this->session->getCustomerId() != $project->getCustomerId()) {
                $errorUrl = $this->urlModel->getUrl('*/*/index', ['_secure' => true]);
                return $resultRedirect->setUrl($this->_redirect->error($errorUrl));
            }
            
            $this->beginTransaction();

            $project->setTitle($title)
                ->setDescription($description)
                ->setCustomerId($this->session->getCustomerId())
                ->setUpdatedAt($this->dateTime->gmtDate())
                ->setDueDate($dueDate)
                ->save();
            ;
            
            $pomodoro = $this->pomodoroFactory->create()->getCollection()
                ->addFieldToFilter('project_id', $project->getId())
                ->addFieldToFilter('is_active', true)
                ->getFirstItem();

            if (!$pomodoro->getId()) {
                $pomodoro->setProjectId($project->getId())
                    ->setDuration($duration)
                    ->setMinutes(0)
                    ->setSeconds(0)
                    ->setIsActive(true)
                    ->setCreatedAt($this->dateTime->gmtDate())
                    ->setUpdatedAt($this->dateTime->gmtDate())
                    ->save();
            } else {
                $minutes = $pomodoro->getMinutes();
                $seconds = $pomodoro->getSeconds();
                if ($minutes > $duration) {
                    $times = (int)($minutes / $duration) - 1;
                    $pomodoro->setProjectId($projectId)
                            ->setDuration($duration)
                            ->setMinutes($duration)
                            ->setIsActive(false)
                            ->setSeconds(0)
                            ->setUpdatedAt($this->dateTime->gmtDate())
                            ->save();
                    foreach (range(1, $times) as $count) {
                        $this->pomodoroFactory->create()
                            ->setProjectId($projectId)
                            ->setDuration($duration)
                            ->setMinutes($duration)
                            ->setSeconds(0)
                            ->setIsActive(false)
                            ->setCreatedAt($this->dateTime->gmtDate())
                            ->setUpdatedAt($this->dateTime->gmtDate())
                            ->save();
                    }
                    $this->pomodoroFactory->create()
                        ->setProjectId($projectId)
                            ->setDuration($duration)
                            ->setMinutes(($minutes % $duration))
                            ->setSeconds($seconds)
                            ->setIsActive(true)
                            ->setCreatedAt($this->dateTime->gmtDate())
                            ->setUpdatedAt($this->dateTime->gmtDate())
                            ->save();

                } elseif ((int)$minutes === (int)$duration) {
                    if ((int)$seconds === 0) {
                        $pomodoro->setProjectId($projectId)
                            ->setDuration($duration)
                            ->setMinutes($minutes)
                            ->setSeconds($seconds)
                            ->setIsActive(false)
                            ->setUpdatedAt($this->dateTime->gmtDate())
                            ->save();
                        $this->pomodoroFactory->create()
                            ->setProjectId($projectId)
                            ->setDuration($duration)
                            ->setMinutes(0)
                            ->setSeconds(0)
                            ->setIsActive(true)
                            ->setCreatedAt($this->dateTime->gmtDate())
                            ->setUpdatedAt($this->dateTime->gmtDate())
                            ->save();
                    } else {
                        $pomodoro->setProjectId($projectId)
                            ->setDuration($duration)
                            ->setMinutes($minutes)
                            ->setSeconds(0)
                            ->setIsActive(false)
                            ->setUpdatedAt($this->dateTime->gmtDate())
                            ->save();
                        $this->pomodoroFactory->create()
                            ->setProjectId($projectId)
                            ->setDuration($duration)
                            ->setMinutes(0)
                            ->setSeconds($seconds)
                            ->setIsActive(true)
                            ->setCreatedAt($this->dateTime->gmtDate())
                            ->setUpdatedAt($this->dateTime->gmtDate())
                            ->save();
                    }
                } elseif($minutes < $duration) {
                    $pomodoro->setDuration($duration)
                        ->setUpdatedAt($this->dateTime->gmtDate())
                        ->save();
                }
            }
            $this->commit();
            $this->messageManager->addSuccessMessage(__('Project successfully saved.'));
            $this->session->unsProjectFormData();
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('piyush_project_management/project/index');
            return $resultRedirect;
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('We can\'t save the customer.'));
            $this->rollBack();
        }
        $this->session->setProjectFormData($this->getRequest()->getPostValue());
        $defaultUrl = $this->urlModel->getUrl('*/*/new', ['_secure' => true]);
        return $resultRedirect->setUrl($this->_redirect->error($defaultUrl));
    }

    
    protected function getConnection()
    {
        return $this->resources->getConnection();
    }

    protected function beginTransaction()
    {
        $this->getConnection()->beginTransaction();
    }

    protected function commit()
    {
        if ($this->getConnection()->getTransactionLevel() > 0) {
            $this->getConnection()->commit();
        }
    }

    protected function rollBack()
    {
        if ($this->getConnection()->getTransactionLevel() > 0) {
            $this->getConnection()->rollBack();
        }
    }
}
