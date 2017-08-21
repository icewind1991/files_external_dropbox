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

use HemantMann\Flysystem\Dropbox\Adapter as DropboxAdapter;

class Adapter extends DropboxAdapter {
	protected function normalizeResponse($obj) {
		$result = parent::normalizeResponse($obj);

		if ($result['type'] === 'dir') {
			$result['timestamp'] = 0;
		}
		return $result;
	}

    public function getModifiedFolders($items) {
	    $result = [];
        foreach ($items as $item) {
            $klass = get_class($item);
            if ($klass == 'Kunnu\Dropbox\Models\FolderMetadata') {
                $dirname = $item->getPathDisplay();
            } else {
                $dirname = dirname($item->getPathDisplay());
            }
            $result[] = $dirname;
	    }
	    return array_unique($result);
    }
}