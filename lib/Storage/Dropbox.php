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


class Dropbox extends FlysystemStorageAdapter {
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
            && isset($params['configured']) && $params['configured'] == 'true'
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
    }

    /**
     * @return string
     */
    public function getId() {
        return 'dropbox_external::' . $this->clientId . ':' . $this->clientSecret . '/' . $this->root;
    }

    public function file_exists($path) {
        if ($path == '' || $path == '/') {
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
            $arr = [];
            $contents = $this->flysystem->listContents($path, true);
            foreach ($contents as $c) {
                $arr[] = $c['type'] === 'file' ? $c['timestamp'] : 0;
            }
            $mtime = $this->getLargest($arr);
            return $mtime;
        } else {
            return parent::filemtime($path);
        }
    }

    public function stat($path) {
        if ($path === '' || $path === '/' || $path === '.') {
            return ['mtime' => 0];
        }
        return parent::stat($path);
    }

    /**
     * {@inheritDoc}
     */
    public function test() {
        $obj = $this->adapter->getClient()->getCurrentAccount();
        if ($obj && $obj->getAccountId()) {
            return true;
        }
        return false;
    }
}
