<?php
namespace Dropcat\Lib;

use Exception;

/**
 * Class Remote
 *
 * Check a remote path
 *
 * @package Dropcat\Lib
 */
class Cleanup
{
    public $verbose;

    public function __construct($verbose = false)
    {
        $this->verbose = $verbose;
    }


    /**
     * @param $backup_dir
     */
    public function deleteAutomaticDbBackups($backup_dir)
    {

        // Cycle through all files.
        foreach (glob($backup_dir."*.sql") as $file) {
            // If the file is 48 hours (172800 seconds) or older delete.
            if (time() - filectime($file) > 172800) {
                try {
                    if (!is_writable($file)) {
                        throw new Exception("$file is not writable");
                    }
                    unlink($file);
                } catch (Exception $e) {
                    echo $e->getMessage();
                }
            }
        }
    }

    /**
     * @param string $yaml_dir
     * @return void
     */
    public function deleteOldRollbackTrackers(string $yaml_dir)
    {
        // Cycle through all files.
        foreach (glob($yaml_dir."*.yml") as $file) {
            // If the file is 48 hours (172800 seconds) or older delete.
            if (time() - filectime($file) > 172800) {
                try {
                    if (!is_writable($file)) {
                        throw new Exception("$file is not writable");
                    }
                    unlink($file);
                } catch (Exception $e) {
                    echo $e->getMessage();
                }
            }
        }
    }
}
