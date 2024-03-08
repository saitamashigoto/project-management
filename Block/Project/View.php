<?php
namespace Piyush\ProjectManagement\Block\Project;

use Piyush\ProjectManagement\Helper\Data as DataHelper;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Framework\Registry;
use Piyush\ProjectManagement\Api\Data\ProjectInterfaceFactory;
use Piyush\ProjectManagement\Api\Data\PomodoroInterfaceFactory;
use Magento\Framework\HTTP\Client\Curl;

class View extends AbstractBlock
{
    protected $registry;

    protected $projectFactory;

    protected $pomodoroFactory;

    protected $storeManager;

    protected $curlClient;

    protected $serializer;
    
    protected $isFileValidated = null;

    /**
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
        Registry $registry,
        ProjectInterfaceFactory $projectFactory,
        PomodoroInterfaceFactory $pomodoroFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Serialize\Serializer\Json $serializer,
        Curl $curlClient,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->projectFactory = $projectFactory;
        $this->pomodoroFactory = $pomodoroFactory;
        $this->storeManager = $storeManager;
        $this->curlClient = $curlClient;
        $this->serializer = $serializer;
        parent::__construct($context, $currentCustomer, $dataHelper, $data);
    }

    public function getProjectIdFromRegistry()
    {
        return (int)$this->registry->registry('project_id');
    }

    public function getPomodoro()
    {
        return $this->pomodoroFactory->create()->getCollection()
            ->addFieldToFilter('project_id', $this->getProjectIdFromRegistry())
            ->addFieldToFilter('is_active', true)
            ->getFirstItem();
    }

    public function getPersistUrl()
    {
        return $this->getUrl('piyush_project_management/project/updatepomodoro');
    }

    public function getSoundUrl()
    {
        if ($this->isFileValidated === null) {
            $this->isFileValidated = $this->isValidSoundFile();
        }
        if ($this->isFileValidated !== false) {
            return $this->getPomodoroSoundUrl();
        }
        return "";
    }

    private function isValidSoundFile()
    {
        try {
            $url = $this->getPomodoroSoundUrl();
            $this->curlClient->get($url);
            $headers = $this->curlClient->getHeaders();
            $contentType = $headers['Content-Type'] ?? $headers['content-type'];
            return $this->isValidAudioMimeType($contentType); 
        } catch(\Exception $e) {
            return false;
        }
        
    }

    private function getPomodoroSoundUrl()
    {
        return rtrim($this->getBaseMediaUrl(), '/') .
            '/pomodoro/audio/' .
            ltrim($this->dataHelper->getPomodoroAudioFilePath(), '/');
    }

    private function isValidAudioMimeType($mimeType)
    {
        return str_contains($mimeType, 'audio/mpeg') || str_contains($mimeType, 'audio/aac');
    }

    private function getBaseMediaUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

    public function getChartData()
    {
        $pomodoroCollection = $this->pomodoroFactory->create()
            ->getCollection()
            ->addFieldToFilter(
                'project_id',
                $this->getProjectIdFromRegistry()
            )
        ;
        $pomodoroCollection->getSelect()
            ->reset(\Zend_Db_Select::COLUMNS)
            ->columns([
                'CONCAT(YEAR(updated_at), "/", LPAD(MONTH(updated_at), 2, "0")) as label',
                '(SUM(minutes) + SUM(seconds)/60) as value'
            ])->group(new \Zend_Db_Expr('YEAR(updated_at), MONTH(updated_at)'))
            ->order(['YEAR(updated_at) asc', 'MONTH(updated_at) asc'])
        ;
        return $this->serializer->serialize($pomodoroCollection->getData());
    }
}