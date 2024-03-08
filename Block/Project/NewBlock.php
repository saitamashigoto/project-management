<?php
namespace Piyush\ProjectManagement\Block\Project;

use Piyush\ProjectManagement\Helper\Data as DataHelper;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Piyush\ProjectManagement\Model\ResourceModel\Project\CollectionFactory as ProjectCollectionFactory;
use Magento\Framework\UrlInterface;
use Magento\Framework\Registry;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Locale\Bundle\DataBundle;
use Magento\Framework\Locale\ResolverInterface;

class NewBlock extends AbstractBlock
{
    /**
     * @var ProjectCollectionFactory
     */
    protected $projectCollectionFactory;

    protected $urlBuilder;

    protected $registry;

    protected $encoder;

    protected $localeResolver;

    protected $dateElement;

    protected $customerSession;

    protected $dateTime;

    protected $project = null;

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
        UrlInterface $urlBuilder,
        Registry $registry,
        ResolverInterface $localeResolver,
        EncoderInterface $encoder,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\View\Element\Html\Date $dateElement,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        array $data = []
    ) {
        $this->projectCollectionFactory = $projectCollectionFactory;
        $this->urlBuilder = $urlBuilder;
        $this->registry = $registry;
        $this->localeResolver = $localeResolver;
        $this->encoder = $encoder;
        $this->dateElement = $dateElement;
        $this->customerSession = $customerSession;
        $this->dateTime = $dateTime;
        parent::__construct($context, $currentCustomer, $dataHelper, $data);
    }

    /**
     * Retrieve form posting url
     *
     * @return string
     */
    public function getPostActionUrl()
    {
        return $this->urlBuilder->getUrl('piyush_project_management/project/save');
    }

    public function getBackUrl()
    {
        return $this->urlBuilder->getUrl('piyush_project_management/project/index');
    }

    public function getProjectId()
    {
        return $this->registry->registry('project_id');
    }

    public function getProject()
    {
        if (!$this->project) {
            $projectId = $this->getProjectId();
            if (!$projectId) {
                return null;
            }
            $collection = $this->projectCollectionFactory->create()
                ->addFieldToFilter('entity_id', $projectId);
            $collection->join(
                ['pomodoro' => $collection->getTable('piyush_project_management_pomodoro')],
                'pomodoro.project_id=main_table.entity_id AND pomodoro.is_active = 1',
                [
                    'pomodoro_duration' => 'pomodoro.duration',
                    'pomodoro_entity_id' => 'pomodoro.entity_id'
                ]
            );
            $this->project = $collection->getFirstItem();
        }
        
        return $this->project;
    }

    public function getTranslatedCalendarConfigJson(): string
    {
        $localeData = (new DataBundle())->get($this->localeResolver->getLocale());
        $monthsData = $localeData['calendar']['gregorian']['monthNames'];
        $daysData = $localeData['calendar']['gregorian']['dayNames'];

        return $this->encoder->encode(
            [
                'closeText' => __('Done'),
                'prevText' => __('Prev'),
                'nextText' => __('Next'),
                'currentText' => __('Today'),
                'monthNames' => array_values(iterator_to_array($monthsData['format']['wide'])),
                'monthNamesShort' => array_values(iterator_to_array($monthsData['format']['abbreviated'])),
                'dayNames' => array_values(iterator_to_array($daysData['format']['wide'])),
                'dayNamesShort' => array_values(iterator_to_array($daysData['format']['abbreviated'])),
                'dayNamesMin' =>
                 array_values(iterator_to_array(($daysData['format']['short']) ?: $daysData['format']['abbreviated'])),
            ]
        );
    }

    public function getProjectFormData()
    {
        $projectFormData = $this->customerSession->getProjectFormData();
        return $projectFormData && is_array($projectFormData) ? $projectFormData : [];
    }

    public function getTitle()
    {
        if (($projectFormData = $this->getProjectFormData())) {
            return $projectFormData['title'];
        }
        if (($project = $this->getProject()) && $project->getId()) {
            return $project->getTitle();
        }
    }
    
    public function getDueDate()
    {
        if (($projectFormData = $this->getProjectFormData())) {
            return $projectFormData['due_date'];
        }
        if (($project = $this->getProject()) && $project->getId()) {
            return $this->dateTime->gmtDate('m/d/Y', $project->getDueDate());
        }
    }
    
    public function getDuration()
    {
        if (($projectFormData = $this->getProjectFormData())) {
            return $projectFormData['pomodoro_duration'];
        }
        if (($project = $this->getProject()) && $project->getId()) {
            return $project->getPomodoroDuration() ?: $this->getPomodoroDefaultDuration();
        }
        return $this->getPomodoroDefaultDuration();
    }
    
    public function getDescription()
    {
        if (($projectFormData = $this->getProjectFormData())) {
            return $projectFormData['description'];
        }
        if (($project = $this->getProject()) && $project->getId()) {
            return $project->getDescription();
        }
    }

    public function getHtmlExtraParams()
    {
        $validators = [];
        $validators['required'] = true;
        $validators['validate-due-date-format'] = [
            'dateFormat' => $this->getDateFormat()
        ];
        $validators['validate-due-date'] = [
            'dateFormat' => $this->getDateFormat()
        ];

        return 'data-validate="' . $this->_escaper->escapeHtml(json_encode($validators)) . '"';
    }

    public function getDueDateHtml()
    {
        $this->dateElement->setData(
            [
                'extra_params' => $this->getHtmlExtraParams(),
                'name' => 'due_date',
                'id' => 'due-date',
                'class' => 'project-due-date',
                'value' => $this->getDueDate(),
                'date_format' => $this->getDateFormat(),
                'image' => $this->getViewFileUrl('Magento_Theme::calendar.png'),
                'change_month' => 'true',
                'change_year' => 'true',
                'show_on' => 'both'
            ]
        );
        return $this->dateElement->getHtml();
    }

    /**
     * Returns format which will be applied for DOB in javascript
     *
     * @return string
     */
    public function getDateFormat()
    {
        $dateFormat = $this->setTwoDayPlaces($this->_localeDate->getDateFormatWithLongYear());
        /** Escape RTL characters which are present in some locales and corrupt formatting */
        $escapedDateFormat = preg_replace('/[^MmDdYy\/\.\-]/', '', $dateFormat);
        return $escapedDateFormat;
    }

    /**
     * Set 2 places for day value in format string
     *
     * @param string $format
     * @return string
     */
    private function setTwoDayPlaces(string $format): string
    {
        return preg_replace(
            '/(?<!d)d(?!d)/',
            'dd',
            $format
        );
    }
}
