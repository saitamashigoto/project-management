<?php

namespace Piyush\ProjectManagement\Model;

use Piyush\ProjectManagement\Api\Data\ProjectInterface;

class Project extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface, ProjectInterface
{
    public const NOROUTE_ENTITY_ID = 'no-route';

    public const CACHE_TAG = 'piyush_project_management_project';
    
    /**
     * @var string
     */
    protected $_cacheTag = 'piyush_project_management_project';

    /**
     * @var string
     */
    protected $_eventPrefix = 'piyush_project_management_project';
    
    public function _construct()
    {
        $this->_init(\Piyush\ProjectManagement\Model\ResourceModel\Project::class);
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