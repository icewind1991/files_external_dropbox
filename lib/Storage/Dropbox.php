<?php

namespace OCA\Files_external_dropbox\Storage;
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

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