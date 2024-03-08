<?php

namespace Piyush\ProjectManagement\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    /*
     * @return bool
     */
    public function isEnabled($scope = ScopeInterface::SCOPE_WEBSITES)
    {
        return (bool)$this->scopeConfig->getValue(
            'project_management/general/is_enabled',
            $scope
        );
    }

    /*
     * @return string
     */
    public function getDuration($scope = ScopeInterface::SCOPE_WEBSITES)
    {
        return $this->scopeConfig->getValue(
            'project_management/general/duration',
            $scope
        );
    }

    /*
     * @return string
     */
    public function getPomodoroAudioFilePath($scope = ScopeInterface::SCOPE_WEBSITES)
    {
        return $this->scopeConfig->getValue(
            'project_management/general/pomodoro_audio',
            $scope
        );
    }
}