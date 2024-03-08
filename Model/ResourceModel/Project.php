<?php
namespace Piyush\ProjectManagement\Model\ResourceModel;

class Project extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Dependency Initilization
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init("piyush_project_management_project", "entity_id");
    }
}