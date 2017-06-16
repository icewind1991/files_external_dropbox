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
                (new DefinitionParameter('client_id', $l->t('Client ID'))),
                (new DefinitionParameter('client_secret', $l->t('Client Secret')))
                    ->setType(DefinitionParameter::VALUE_PASSWORD),
                (new DefinitionParameter('token', $l->t('Token')))
            ])
            ->addAuthScheme(AuthMechanism::SCHEME_BUILTIN);
    }

}