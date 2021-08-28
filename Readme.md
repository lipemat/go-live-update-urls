# Go Live Update Urls - WordPress Plugin

This Readme is for development.

Full plugin information is available in [readme.txt](readme.txt).

## Deploying to WordPress.org
Currently, using the [action provided by 10Up](https://github.com/10up/action-wordpress-plugin-deploy). 

New tags are automatically deployed to wordpress.org via SVN.

## Updating Readme or Assets between versions
Currently, using the [action provided by 10Up](https://github.com/10up/action-wordpress-plugin-asset-update). 

Changes to `readme.txt` or `.wordpress-org` on the `master` branch are automatically deployed to the matching tag on wordpress.org.

**If other changes have been made to the `master` branch, nothing will be deployed.**

## Configuration

* Ignore files from SVN via `.distignore`.
* Assets are updated within `.wordpress-org`.
* SVN credentials are stored as [GitHub secrets](https://github.com/lipemat/go-live-update-urls/settings/secrets)

