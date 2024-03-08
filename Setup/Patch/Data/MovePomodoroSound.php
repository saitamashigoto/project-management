<?php

namespace Piyush\ProjectManagement\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Module\Dir;

class MovePomodoroSound implements DataPatchInterface
{
    private $io;

    private $directoryList;

    private $moduleDir;

    /**
     * @param File $io
     * @param DirectoryList $directoryList
     */
    public function __construct(
        File $io,
        DirectoryList $directoryList,
        Dir $dir
    ) {
        $this->io = $io;
        $this->directoryList = $directoryList;
        $this->dir = $dir;
    }

    public function apply()
    {
        $this->io->mkdir($this->directoryList->getPath(
            \Magento\Framework\App\Filesystem\DirectoryList::MEDIA
        ).'/pomodoro/audio/default', 0755);

        $pomodoroAudioFile = $this->dir->getDir(
            'Piyush_ProjectManagement',
            Dir::MODULE_VIEW_DIR
        ) . '/frontend/web/media/pomodoro-audio.mp3';
        
        $targetPath = $this->directoryList->getPath(DirectoryList::MEDIA)
            . '/pomodoro/audio/default/pomodoro-audio.mp3';
        $this->io->cp($pomodoroAudioFile, $targetPath);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }
}