$(document).ready(function() {
	var backendId = 'files_external_dropbox';
	var backendUrl = OC.generateUrl('apps/' + backendId + '/ajax/oauth2.php');

	$('.configuration').on('oauth_step1', function (event, data) {
		if (data['backend_id'] !== backendId) {
			return false;	// means the trigger is not for this storage adapter
		}

		OCA.External.Settings.OAuth2.getAuthUrl(backendUrl, data);
	})

	$('.configuration').on('oauth_step2', function (event, data) {
		if (data['backend_id'] !== backendId || data['code'] === undefined) {
			return false;		// means the trigger is not for this OAuth2 grant
		}
		
		OCA.External.Settings.OAuth2.verifyCode(backendUrl, data)
		.fail(function (message) {
			OC.dialogs.alert(message,
				t(backendId, 'Error verifying OAuth2 Code for ' + backendId)
			);
		})
	})
});
