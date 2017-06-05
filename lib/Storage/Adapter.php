<?php

namespace OCA\Files_external_dropbox\Storage;
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

use HemantMann\Flysystem\Dropbox\Adapter as DropboxAdapter;

class Adapter extends DropboxAdapter {
	protected function normalizeResponse($obj) {
		$result = parent::normalizeResponse($obj);

		if ($result['type'] === 'dir') {
			$result['timestamp'] = 0;
		}
		return $result;
	}
}