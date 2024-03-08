<?php
namespace Piyush\ProjectManagement\Controller\Project;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Action\Action;
use Piyush\ProjectManagement\Helper\Data as DataHelper;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Piyush\ProjectManagement\Api\Data\ProjectInterfaceFactory;
use Piyush\ProjectManagement\Api\Data\PomodoroInterfaceFactory;
use Magento\Framework\Serialize\Serializer\Json as Serializer;
use Magento\Framework\Encryption\Helper\Security;
use Magento\Framework\App\RequestInterface;

class UpdatePomodoro extends Action implements CsrfAwareActionInterface
{
    protected $resultJsonFactory;

    protected $dataHelper;

    protected $customerRepository;

    protected $projectFactory;

    protected $pomodoroFactory;

    protected $formKey;

    protected $datetime;

    protected $resources;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        DataHelper $dataHelper,
        CustomerRepositoryInterface $customerRepository,
        ProjectInterfaceFactory $projectFactory,
        PomodoroInterfaceFactory $pomodoroFactory,
        Serializer $serializer,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Framework\Stdlib\DateTime\DateTime $datetime,
        \Magento\Framework\App\ResourceConnection $resources
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->dataHelper = $dataHelper;
        $this->customerRepository = $customerRepository;
        $this->projectFactory = $projectFactory;
        $this->pomodoroFactory = $pomodoroFactory;
        $this->serializer = $serializer;
        $this->formKey = $formKey;
        $this->datetime = $datetime;
        $this->resources = $resources;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $data = [
            'success' => false,
            'message' => __('Invalid Request') 
        ];
        
        if (!$this->isAllowed()) {
            return $resultJson->setData($data)
                ->setHttpResponseCode(400);
        }

        try {
            $content = $this->getRequest()->getPostValue();
            $formKey = $content['form_key'];
            $customerId = $content['customer_id'];
            $projectId = $content['project_id'];
            $mode = $content['mode'];
            if (empty($formKey)
                || empty($customerId)
                || empty($projectId)
                || empty($mode)
                || !$this->isValidFormKey($formKey)
                || !in_array($mode, ['reset', 'update'])
            ) {
                return $resultJson->setData($data)
                    ->setHttpResponseCode(400);
            }

            $customerId = $this->customerRepository->getById($customerId)->getId();
            $project = $this->projectFactory->create()->load($projectId);
            if(!$project->getId() || (int)$project->getCustomerId() !== (int)$customerId) {
                $data['message'] = __('Unauthorized');
                return $resultJson->setData($data)
                    ->setHttpResponseCode(401);
            }
            $this->beginTransaction();
            $pomodoro = $this->pomodoroFactory->create()->getCollection()
                ->addFieldToFilter('project_id', $projectId)
                ->addFieldToFilter('is_active', true)
                ->getFirstItem();
            if ($mode === 'reset') {
                $pomodoro->setMinutes(0)
                    ->setSeconds(0)
                    ->setUpdatedAt($this->datetime->gmtDate())
                    ->save();
            } else {
                $pomodoro->setMinutes($pomodoro->getDuration())
                    ->setSeconds(0)
                    ->setIsActive(false)
                    ->setUpdatedAt($this->datetime->gmtDate())
                    ->save();
                $this->pomodoroFactory->create()
                    ->setProjectId($projectId)
                    ->setDuration($pomodoro->getDuration())
                    ->setIsActive(true)
                    ->setMinutes(0)->setSeconds(0)
                    ->setUpdatedAt($this->datetime->gmtDate())
                    ->setCreatedAt($this->datetime->gmtDate())
                    ->save();
            }
            $this->commit();
            $data = [
                'success' => true,
                'message' => __('Success'),
            ];
            return $resultJson->setData($data);
        } catch(\InvalidArgumentException $e) {
            $data = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
            $resultJson->setHttpResponseCode(400);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $data = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
            $resultJson->setHttpResponseCode(401);
        } catch(\Magento\Framework\Exception\LocalizedException $e) {
            $data = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
            $resultJson->setHttpResponseCode(400);
            $this->rollBack();
        } catch(\Exception $e) {
            $data = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
            $resultJson->setHttpResponseCode(400);
            $this->rollBack();
        }
        return $resultJson->setData($data);
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

    public function isValidFormKey($formKey)
    {
        return Security::compareStrings($formKey, $this->formKey->getFormKey());
    }

    protected function isAllowed()
    {
        return $this->dataHelper->isEnabled();      
    }

    /**
     * Create exception in case CSRF validation failed.
     * Return null if default exception will suffice.
     *
     * @param RequestInterface $request
     *
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    /**
     * Perform custom request validation.
     * Return null if default validation is needed.
     *
     * @param RequestInterface $request
     *
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
