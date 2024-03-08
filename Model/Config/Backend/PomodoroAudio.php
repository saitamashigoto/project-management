<?php
 
namespace Piyush\ProjectManagement\Model\Config\Backend;
 
class PomodoroAudio extends \Magento\Config\Model\Config\Backend\File
{
    /**
     * Returns the list of allowed file extensions.
     *
     * @return string[]
     */
    protected function _getAllowedExtensions()
    {
        return ['mp3', 'aac'];
    }
}