$(document).ready(function() {
	var storageId = 'files_external_dropbox';
	var backendUrl = OC.filePath(storageId, 'ajax', 'oauth2.php');

	$('.configuration').on('oauth_step1', function (event, data) {
		if (data['storage_id'] !== storageId) {
			return false;	// means the trigger is not for this storage adapter
		}

		OCA.External.Settings.OAuth2.getAuthUrl(backendUrl, data, function (authUrl) {
			// (Optional) do some extra task - then control shifts back to getAuthUrl
		})
	})

	$('.configuration').on('oauth_step2', function (event, data) {
		if (data['storage_id'] !== storageId || data['code'] === undefined) {
			return false;		// means the trigger is not for this OAuth2 grant
		}
		
		OCA.External.Settings.OAuth2.verifyCode(backendUrl, data, function (verified) {
			// do any additional task once storage is verified
		})
	})
});
