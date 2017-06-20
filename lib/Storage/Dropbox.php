<?php
/**
 * @author Hemant Mann <hemant.mann121@gmail.com>
 *
 * @copyright Copyright (c) 2017, ownCloud GmbH.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
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
     * Dropbox constructor.
     * @throws \Exception
     */
    public function __construct($params) {
        if (isset($params['client_id']) && isset($params['client_secret']) && isset($params['token'])) {
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

    public function filemtime($path) {
        if ($path === '' || $path === '/') {
            return 0;
        }
        return parent::filemtime($path);
    }

    public function stat($path) {
        if ($path === '' || $path === '/' || $path === '.') {
            return ['mtime' => 0];
        }
        return parent::stat($path);
    }

    /**
     * {@inheritdoc}
     */
    public function test() {
        $obj = $this->adapter->getClient()->getCurrentAccount();
        if ($obj && $obj->getAccountId()) {
            return true;
        }
        return false;
    }
}
