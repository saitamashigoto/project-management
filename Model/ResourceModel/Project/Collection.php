<?php
namespace Piyush\ProjectManagement\Model\ResourceModel\Project;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';
    
    /**
     * Dependency Initilization
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init(
            \Piyush\ProjectManagement\Model\Project::class,
            \Piyush\ProjectManagement\Model\ResourceModel\Project::class
        );
        $this->_map['fields']['entity_id'] = 'main_table.entity_id';
    }
}