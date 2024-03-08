<?php
 
namespace Piyush\ProjectManagement\Plugin;

use Piyush\ProjectManagement\Model\Config\Backend\PomodoroAudio;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\App\Filesystem\DirectoryList;
 
class DeleteExistingPomodoroAudio
{
    private $io;

    private $directoryList;

    public function __construct(
        File $io,
        DirectoryList $directoryList
    ) {
        $this->io = $io;
        $this->directoryList = $directoryList;
    }

    public function beforeBeforeSave(PomodoroAudio $subject)
    {
        $value = $subject->getValue();
        if (!isset($value['delete']) || !$value['delete']) {
            return;
        }
        $filename = ltrim($value['value'], '/');

        $targetPath = $this->directoryList->getPath(DirectoryList::MEDIA)
            . '/pomodoro/audio/' . $filename;
        $this->io->rm($targetPath);
        return;
    }
}