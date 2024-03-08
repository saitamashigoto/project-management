<?php
namespace Piyush\ProjectManagement\Model\ResourceModel\Pomodoro;

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
            \Piyush\ProjectManagement\Model\Pomodoro::class,
            \Piyush\ProjectManagement\Model\ResourceModel\Pomodoro::class
        );
        $this->_map['fields']['entity_id'] = 'main_table.entity_id';
    }
}