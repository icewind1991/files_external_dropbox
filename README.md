# files_external_dropbox
Flysystem based dropbox backend for Nextcloud

Requires Nextcloud 12.0 or later

## Steps For Installation:
- Enable the files_external app with `occ app:enable files_external` if not enabled yet.
- ~~Get the app from the [app store](https://apps.nextcloud.com/)~~
- Download the [source](https://github.com/icewind1991/files_external_dropbox/archive/master.zip) into the Nextcloud apps directory.
- Make sure the unzipped directory of the app is named "files_external_dropbox" and set the directory ownership according to you setup.
- Run `make` or `composer install`
- Enable the app in the web interface or by running `occ app:enable files_external_dropbox`
- Fill up the storage details (See Below _Configuring OAuth2_)
- Fire up the files page to see the ```Dropbox``` mounted as external storage
- To allow any user to configure his own dropbox storage, you need to ensure that relevant checkbox is setting under "Allow users to mount external storage"

## Configuring OAuth2
- Connecting Dropbox is a little more work because you have to create a Dropbox app. Log into the [Dropbox Developers](http://www.dropbox.com/developers) page and click Create Your App
- Then choose which folders to share, or to share everything in your Dropbox.
- Name Your App and then click Create App
- Under the section **OAuth2** Redirect URIs add a new URL ```http://path/to/nextcloud/index.php/settings/admin/externalstorages``` and ```http://path/to/nextcloud/index.php/settings/user/externalstorages``` _(Replace http://path/to/nextcloud/index.php with you valid Nextcloud installation path)_
- Then Go to nextcloud ```settings/admin/externalstorages``` and Add a new storage **Dropbox V2**
- Fill the details Client Id, Client Secrets from your Dropbox App page (search for "App key" and "App secret")
- Click Grant Access and then you will be redirected for OAuth login
- After completing the OAuth you will be redirect back to Storage Section and you should see **green** mark along your storage configuration
- That's it

## Dependencies
This app depends on the flysystem adapter for dropbox which can be found here [https://github.com/Hemant-Mann/flysystem-dropbox](https://github.com/Hemant-Mann/flysystem-dropbox)


## Future Work
- Update the Guzzle Dependency to ^6.0
