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

require_once dirname(__DIR__) . '/vendor/autoload.php';

OCP\JSON::checkAppEnabled('files_external_dropbox');
OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();
$l = \OC::$server->getL10N('files_external_dropbox');

if (!isset($_POST['client_id']) || !isset($_POST['client_secret']) || !isset($_POST['redirect'])) {
	return OCP\JSON::error(['data' => [
		'message' => 'Invalid request params'
	]]);
}

if (!isset($_POST['step'])) {
	return OCP\JSON::error(['data' => [
		'message' => 'Step not defined'
	]]);
}

$app = new Kunnu\Dropbox\DropboxApp($_POST['client_id'], $_POST["client_secret"]);
$dropbox = new Kunnu\Dropbox\Dropbox($app);
$authHelper = $dropbox->getAuthHelper();
$step = $_POST['step'];
if ($step == 1) {
	$authUrl = $authHelper->getAuthUrl($_POST['redirect']);
	OCP\JSON::success(['data' => [
		'url' => $authUrl
	]]);
} else if ($step == 2 && isset($_POST['code'])) {
	try {
		$accessToken = $authHelper->getAccessToken($_POST['code'], null, $_POST['redirect']);
		OCP\JSON::success(['data' => [
			'token' => $accessToken->getToken()
		]]);
	} catch (Exception $exception) {
		OCP\JSON::error(['data' => [
			'message' => $l->t('Step 2 failed. Exception: %s', [$exception->getMessage()])
		]]);
	}
} else {
	OCP\JSON::error(['data' => [
			'message' => $l->t('Step 2 failed because CODE was not provided!!')
		]]);
}
