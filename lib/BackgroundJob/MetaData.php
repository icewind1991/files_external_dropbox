<?php
/**
 * @author Hemant Mann <hemant.mann121@gmail.com>
 *
 * @copyright Copyright (c) 2017, ownCloud GmbH.
 * @license GPL-2.0
 * 
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details.
 * 
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

namespace OCA\Files_external_dropbox\BackgroundJob;


use OCP\IConfig;
use OCP\IDBConnection;
use OCP\ILogger;
use OCP\IUser;
use OCP\IUserManager;
use OC\Files\Utils\Scanner;
use OC\BackgroundJob\TimedJob;

class MetaData extends TimedJob {
    /** @var IConfig */
    private $config;
    /** @var IUserManager */
    private $userManager;
    /** @var IDBConnection */
    private $dbConnection;
    /** @var ILogger */
    private $logger;
    /** Amount of users that should get scanned per execution */
    const USERS_PER_SESSION = 500;

    private $appName = 'files_external_dropbox';

    /**
     * @param IConfig|null $config
     * @param IUserManager|null $userManager
     * @param IDBConnection|null $dbConnection
     * @param ILogger|null $logger
     */
    public function __construct(IConfig $config = null,
                                IUserManager $userManager = null,
                                IDBConnection $dbConnection = null,
                                ILogger $logger = null) {
        // Run once per 10 minutes
        $this->setInterval(1);
        if (is_null($userManager) || is_null($config)) {
            $this->fixDIForJobs();
        } else {
            $this->config = $config;
            $this->userManager = $userManager;
            $this->logger = $logger;
        }
    }

    protected function fixDIForJobs() {
        $this->config = \OC::$server->getConfig();
        $this->userManager = \OC::$server->getUserManager();
        $this->logger = \OC::$server->getLogger();
    }

    protected function syncStorage(\OC\Files\External\StorageConfig $storageConfig) {
        $opts = $storageConfig->getBackendOptions();
        if ($opts['configured'] == 'false') {
            return false;
        }
        try {
            $storage = new \OCA\Files_external_dropbox\Storage\Dropbox($opts);
            $key = 'dropbox_cursor_storage_' . $storageConfig->getId();

            $cursor = $this->config->getAppValue($this->appName, $key, null);
            if ($cursor && $isUpdated = $storage->isStorageUpdated($cursor)) {
                $directories = $storage->getModifiedPaths($cursor);
                foreach ($directories as $directory) {
                   $result = $storage->getScanner($directory)->scan($directory, true);
                }
                $cursor = $storage->getLatestCursor();
            } else {
                $cursor = $storage->getLatestCursor();
                $storage->getScanner('/')->scan('/', true);
            }
            $this->config->setAppValue($this->appName, $key, $cursor);
        } catch (\Exception $e) {
            $this->logger->logException($e, ['message' => 'Storage Syncing failed for ' . $storageConfig->getId()]);
        }
    }

    public function run($argument) {
        $service = \OC::$server->getGlobalStoragesService();
        $resp = $service->getAllStorages();
        $result = [];
        foreach ($resp as $r) {
            $data = $r->getBackend()->jsonSerialize();
            if ($data['identifier'] == $this->appName) {
                $result[] = $r;
            }
        }
        foreach ($result as $r) {
            $this->syncStorage($r);
        }
        return true;
    }
}