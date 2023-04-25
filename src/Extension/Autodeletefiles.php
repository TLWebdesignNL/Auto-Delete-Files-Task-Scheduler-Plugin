<?php

/**
 * @package         Joomla.Plugins
 * @subpackage      Task.CheckFiles
 *
 * @copyright   (C) 2021 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Task\Autodeletefiles\Extension;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status as TaskStatus;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Path;
use LogicException;

\defined('_JEXEC') or die;

/**
 * Task plugin with routines that offer checks on files.
 * At the moment, offers a single routine to check and resize image files in a directory.
 *
 * @since  4.1.0
 */
final class Autodeletefiles extends CMSPlugin implements SubscriberInterface
{
    use TaskPluginTrait;

    /**
     * @var string[]
     *
     * @since 4.1.0
     */
    protected const TASKS_MAP = [
        'Autodeletefiles.autodeletefiles' => [
            'langConstPrefix' => 'PLG_TASK_AUTODELETEFILES_AUTODELETEFILES',
            'form'            => 'autodeletefiles',
            'method'          => 'deleteOldFiles',
        ],
    ];

    /**
     * @inheritDoc
     *
     * @return string[]
     *
     * @since 4.1.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onTaskOptionsList' => 'advertiseRoutines',
            'onExecuteTask' => 'standardRoutineHandler',
            'onContentPrepareForm' => 'enhanceTaskItemForm',
        ];
    }

    /**
     * @var boolean
     * @since 4.1.0
     */
    protected $autoloadLanguage = true;

    /**
     * Constructor.
     *
     * @param   DispatcherInterface  $dispatcher  The dispatcher
     * @param   array                $config      An optional associative array of configuration settings
     *
     * @since   4.2.0
     */
    public function __construct(DispatcherInterface $dispatcher, array $config)
    {
        parent::__construct($dispatcher, $config);
    }

    /**
     * @param   ExecuteTaskEvent  $event  The onExecuteTask event
     *
     * Deletes files older than the specified number of days from the specified folder.
     *
     * @return integer  The exit code
     *
     * @throws \RuntimeException
     * @throws LogicException
     *
     * @since 4.1.0
     */
    protected function deleteOldFiles(ExecuteTaskEvent $event): int
    {
        $params = $event->getArgument('params');
        $folderPath = $params->folder_path;
        $olderThan = (int)$params->older_than;
        $timeUnit = $params->time_unit;

        if (empty($folderPath)) {
            $this->logTask('Folder path is empty', 'warning');
            return TaskStatus::NO_RUN;
        }

        // Sanitize the folder path by removing any leading/trailing whitespace and double slashes.
        $folderPath = rtrim(JPATH_ROOT . '/' . trim(str_replace('//', '/', $folderPath)), '/');

        // Make sure the specified folder is within the Joomla root directory.
        if (strpos(realpath($folderPath), JPATH_ROOT) !== 0) {
            $this->logTask('Folder path is not within the Joomla root directory', 'warning');
            return TaskStatus::NO_RUN;
        }

        $files = Folder::files($folderPath, '.', false, true);

        $now = time();

        switch ($timeUnit) {
            case 'minutes':
                $threshold = $olderThan * 60;
                break;
            case 'hours':
                $threshold = $olderThan * 60 * 60;
                break;
            default:
                $threshold = $olderThan * 24 * 60 * 60;
                break;
        }

        $this->logTask('Processing files in ' . $folderPath, 'info');

        if (is_array($files)) {
            foreach ($files as $file) {
                $fileAge = $now - filemtime($file);

                if ($fileAge > $threshold) {
                    try {
                        File::delete($file);
                        $this->logTask('Deleted file ' . $file, 'info');
                    } catch (LogicException $e) {
                        $this->logTask('Failed to delete file ' . $file, 'error');
                        return TaskStatus::KNOCKOUT;
                    }
                }
            }
        } else {
            $this->logTask('No files found in ' . $folderPath, 'warning');
            return TaskStatus::NO_RUN;
        }

        $this->logTask('Completed processing files in ' . $folderPath, 'info');

        return TaskStatus::OK;
    }
}
