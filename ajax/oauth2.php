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
