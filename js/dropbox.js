$(document).ready(function() {
	$('.configuration').on('oauth_step2', function (event, data) {
		if (data['storage_id'] !== 'files_external_dropbox' || data['code'] === undefined) {
			return false;		// means the trigger is not for this OAuth2 grant
		}

		var $tr = data['tr'];
		var configured = $tr.find('[data-parameter="configured"]');
		var token = $tr.find('.configuration [data-parameter="token"]');
		var statusSpan = $tr.find('.status span');
		statusSpan.removeClass();
		statusSpan.addClass('waiting');
		
		var backendUrl = OC.filePath('files_external_dropbox', 'ajax', 'oauth2.php');
		$.post(backendUrl, {
				step: 2,
				client_id: data['client_id'],
				client_secret: data['client_secret'],
				code: data['code'],
				redirect: data['redirect']
			}, function(result) {
				if (result && result.status == 'success') {
					$(token).val(result.data.token);
					$(configured).val('true');
					OCA.External.Settings.mountConfig.saveStorageConfig($tr, function(status) {
						if (status) {
							$tr.find('.configuration input.auth-param')
								.attr('disabled', 'disabled')
								.addClass('disabled-success')
						}
					});
				} else {
					OC.dialogs.alert(result.data.message,
						t('files_external', 'Error configuring OAuth2')
					);
				}
			}
		);
	})

	$('.configuration').on('oauth_step1', function (event, data) {
		if (data['storage_id'] !== 'files_external_dropbox') {
			return false;	// means the trigger is not for this storage adapter
		}

		var self = data['context'];
		var tr = self.parent().parent();
		var configured = self.parent().find('[data-parameter="configured"]');
		var token = self.parent().find('[data-parameter="token"]');

		var backendUrl = OC.filePath('files_external_dropbox', 'ajax', 'oauth2.php');
		$.post(backendUrl, {
				step: 1,
				client_id: data['client_id'],
				client_secret: data['client_secret'],
				redirect: data['redirect'],
			}, function(result) {
				if (result && result.status == 'success') {
					$(configured).val('false');
					$(token).val('false');
					OCA.External.Settings.mountConfig.saveStorageConfig(tr, function(status) {
						window.location = result.data.url;
					});
				} else {
					OC.dialogs.alert(result.data.message,
						t('files_external_dropbox', 'Error configuring OAuth2')
					);
				}
			}
		);
	})
});
