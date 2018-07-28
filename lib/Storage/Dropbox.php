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

namespace OCA\Files_external_dropbox\Storage;

use Kunnu\Dropbox\DropboxApp;
use Kunnu\Dropbox\Dropbox as DropboxClient;
use OCP\Files\Storage\FlysystemStorageAdapter;


class Dropbox extends CacheableFlysystemAdapter {
    const APP_NAME = 'files_external_dropbox';

    /**
     * @var string
     */
    protected $clientId;

    /**
     * @var string
     */
    protected $clientSecret;

    /**
     * @var string
     */
    protected $accessToken;

    /**
     * @var Adapter
     */
    protected $adapter;

    /**
     * This property is used to check whether the storage is case insensitive or not
     * @var boolean
     */
    protected $isCaseInsensitiveStorage = true;

    /**
     * @var Adapter
     */
    protected $flysystem;

    /**
     * Logger variable
     * @var \OCP\ILogger
     */
    protected $logger;

    protected $cacheFilemtime = [];

    /**
     * Initialize the storage backend with a flyssytem adapter
     * @override
     * @param \League\Flysystem\Filesystem $fs
     */
    public function setFlysystem($fs) {
        $this->flysystem = $fs;
        $this->flysystem->addPlugin(new \League\Flysystem\Plugin\GetWithMetadata());
    }

    public function setAdapter($adapter) {
        $this->adapter = $adapter;
    }

    /**
     * Dropbox constructor.
     * @throws \Exception
     */
    public function __construct($params) {
        if (isset($params['client_id']) && isset($params['client_secret']) && isset($params['token'])
            && isset($params['configured']) && $params['configured'] === 'true'
        ) {
            $this->clientId = $params['client_id'];
            $this->clientSecret = $params['client_secret'];
            $this->accessToken = $params['token'];
            $this->root = isset($params['root']) ? $params['root'] : '/';

            $app = new DropboxApp($this->clientId, $this->clientSecret, $this->accessToken);
            $dropboxClient = new DropboxClient($app);

            $this->adapter = new Adapter($dropboxClient);
            $this->buildFlySystem($this->adapter);
        } else {
            throw new \Exception('Creating \OCA\Files_external_dropbox\Storage\Dropbox storage failed');
        }
        $this->logger = \OC::$server->getLogger();
    }

    /**
     * @return string
     */
    public function getId() {
        return 'dropbox_external::' . $this->clientId . '/' . $this->accessToken;
    }

    public function file_exists($path) {
        if ($path === '' || $path === '/' || $path === '.') {
            return true;
        }
        return parent::file_exists($path);
    }

    protected function getLargest($arr, $default = 0) {
        if (count($arr) === 0) {
            return $default;
        }
        arsort($arr);
        return array_values($arr)[0];
    }

    public function filemtime($path) {
        if ($this->is_dir($path)) {
            if ($path === '.' || $path === '') {
                $path = "/";
            }

            if ($this->cacheFilemtime && isset($this->cacheFilemtime[$path])) {
                return $this->cacheFilemtime[$path];
            }

            $arr = [];
            $contents = $this->flysystem->listContents($path, true);
            foreach ($contents as $c) {
                $arr[] = $c['type'] === 'file' ? $c['timestamp'] : 0;
            }
            $mtime = $this->getLargest($arr);
        } else {
            if ($this->cacheFilemtime && isset($this->cacheFilemtime[$path])) {
                return $this->cacheFilemtime[$path];
            }
            $mtime = parent::filemtime($path);
        }
        $this->cacheFilemtime[$path] = $mtime;
        return $mtime;
    }

    public function stat($path) {
        if ($path === '' || $path === '/' || $path === '.') {
            return ['mtime' => 0];
        }
        return parent::stat($path);
    }

    public function getLatestCursor($path = '') {
        try {
            $dropbox = $this->adapter->getClient();
            $resp = $dropbox->postToAPI('/files/list_folder/get_latest_cursor', ['path' => $path, 'recursive' => true, 'include_deleted' => true]);
            $body = $resp->getDecodedBody();
            if ($body) {
                return $body['cursor'];
            }
        } catch (\Exception $e) {
            $this->logger->logException($e, ['app' => self::APP_NAME]);
        }
        return null;
    }

    public function isStorageUpdated($cursor) {
        try {
            $client = new \GuzzleHttp\Client();
            $params = ['cursor' => $cursor, 'timeout' => 30];
            $response = $client->post('https://notify.dropboxapi.com/2/files/list_folder/longpoll', ['json' => $params]);
            $body = json_decode($response->getBody(), true);

            if ($body && isset($body['changes'])) {
                return $body['changes'];
            }
        } catch (\Exception $e) {
            $this->logger->logException($e, ['app' => self::APP_NAME]);
        }
        return true;
    }

    public function getModifiedPaths($cursor) {
        $dropbox = $this->adapter->getClient();
        $listFolderContinue = $dropbox->listFolderContinue($cursor);
        $items = $listFolderContinue->getItems();

        return $this->adapter->getModifiedFolders($items);
    }

    /**
     * {@inheritDoc}
     */
    public function test() {
        try {
            $obj = $this->adapter->getClient()->getCurrentAccount();
            if ($obj && $obj->getAccountId()) {
                return true;
            }
        } catch (\Exception $e) {
            $this->logger->logException($e, ['app' => self::APP_NAME]);
        }
        
        return false;
    }
}
