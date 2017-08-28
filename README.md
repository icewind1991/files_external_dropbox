# files_external_dropbox
Flysystem based dropbox backend for ownCloud

Requires ownCloud 10.0 or later

## Steps For Installation:
- Get the code
```bash
git clone
cd files_external_dropbox
composer install
```
- Move this folder ```files_external_dropbox``` to ```owncloud/apps```
- Activate the app from settings page
- Fill up the storage details
- Fire up the files page to see the ```Dropbox``` mounted as external storage

## Dependencies
This app depends on the flysystem adapter for dropbox which can be found here [https://github.com/Hemant-Mann/flysystem-dropbox](https://github.com/Hemant-Mann/flysystem-dropbox)


## Future Work
- Update the Guzzle Dependency to ^6.0