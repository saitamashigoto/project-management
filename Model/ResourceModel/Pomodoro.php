<?php
namespace Piyush\ProjectManagement\Model\ResourceModel;

class Pomodoro extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Dependency Initilization
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init("piyush_project_management_pomodoro", "entity_id");
    }
}