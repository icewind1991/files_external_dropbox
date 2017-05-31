<?php
namespace OCA\Files_external_dropbox\AppInfo;

use OCP\AppFramework\App;
use OCP\Files\External\Config\IBackendProvider;

class Application extends App implements IBackendProvider {

    public function __construct(array $urlParams = []) {
        parent::__construct('files_external_dropbox', $urlParams);

        $container = $this->getContainer();

        $backendService = $container->getServer()->getStoragesBackendService();
        $backendService->registerBackendProvider($this);
    }

    /**
     * @{inheritdoc}
     */
    public function getBackends() {
        $container = $this->getContainer();

        $backends = [
            $container->query('OCA\Files_external_dropbox\Backend\Dropbox')
        ];
        return $backends;
    }

}