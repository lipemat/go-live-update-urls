# Go Live Update Urls - WordPress Plugin

This Readme is for development.

Full plugin information is available in [readme.txt](readme.txt).

## Deploying to WordPress.org
Currently, using the [action provided by 10Up](https://github.com/10up/action-wordpress-plugin-deploy). 

New tags are automatically deployed to wordpress.org via SVN.

At the time of implementation, version 1.5.0 is the latest stable. If we run into issues with future versions, point to that version directly.

change
```yml
10up/action-wordpress-plugin-deploy@stable
```
to
```yml
10up/action-wordpress-plugin-deploy@1.5.0
```

## Updating Readme or Assets between versions
Currently, using the [action provided by 10Up](https://github.com/10up/action-wordpress-plugin-asset-update). 

Changes to `readme.txt` or `.wordpress-org` on the `master` branch are automatically deployed to the matching tag on wordpress.org.

**If other changes have been made to the `master` branch, nothing will be deployed.**

At the time of implementation, version 1.4.1 is the latest stable. If we run into issues with future versions, point to that version directly.


## Configuration

* Ignore files from SVN via `.distignore`.
* Assets are updated within `.wordpress-org`.
* SVN credentials are stored as [GitHub secrets](https://github.com/lipemat/go-live-update-urls/settings/secrets)

