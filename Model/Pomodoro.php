<?php

namespace Piyush\ProjectManagement\Model;

use Piyush\ProjectManagement\Api\Data\PomodoroInterface;

class Pomodoro extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface, PomodoroInterface
{
    public const NOROUTE_ENTITY_ID = 'no-route';

    public const CACHE_TAG = 'piyush_project_management_pomodoro';
    
    /**
     * @var string
     */
    protected $_cacheTag = 'piyush_project_management_pomodoro';

    /**
     * @var string
     */
    protected $_eventPrefix = 'piyush_project_management_pomodoro';
    
    public function _construct()
    {
        $this->_init(\Piyush\ProjectManagement\Model\ResourceModel\Pomodoro::class);
    }
    
    public function load($id, $field = null)
    {
        if ($id === null) {
            return $this->noRoute();
        }
        return parent::load($id, $field);
    }
    
    public function noRoute()
    {
        return $this->load(self::NOROUTE_ENTITY_ID, $this->getIdFieldName());
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG.'_'.$this->getId()];
    }

    public function getId()
    {
        return parent::getData(self::ENTITY_ID);
    }

    public function setId($id)
    {
        return $this->setData(self::ENTITY_ID, $id);
    }
}