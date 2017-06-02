<?php

namespace OCA\Files_external_dropbox\Backend;

use OCP\IL10N;
use OCP\Files\External\Backend\Backend;
use OCP\Files\External\DefinitionParameter;
use OCP\Files\External\Auth\AuthMechanism;

class Dropbox extends Backend {

    /**
     * Dropbox constructor.
     * @param IL10N $l
     */
    public function __construct(IL10N $l) {
        $this
            ->setIdentifier('files_external_dropbox')
            ->addIdentifierAlias('\OC\Files\External_Storage\Dropbox') // legacy compat
            ->setStorageClass('\OCA\Files_external_dropbox\Storage\Dropbox')
            ->setText($l->t('Dropbox (Fly)'))
            ->addParameters([
                // all parameters handled in OAuth2 mechanism
            ])
            ->addAuthScheme(AuthMechanism::SCHEME_OAUTH2);
    }

}