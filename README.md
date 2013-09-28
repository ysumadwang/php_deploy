Epimetheus
==========

A simple deployment tool for projects written in PHP. *Epimetheus* from Greek means *hindsight*, and the purpose of this application is to help you uncover errors *before* you deploy to a production environment.

In its current state, *Epimetheus* can be used to deploy your project to a test server. Integrated builds and tests will be added later.

## Usage
Add `.epimetheus.json` to your Github-repo, and put `davidsteinsland/epimetheus` to your `composer.json` file.

On your workstation you can then run:
```sh
./vendor/bin/epimetheus --deploy <profile>
```
to deploy the repo to a server. The `profile` is configured in the JSON file.

## Example configuration
```json
{
	"repo": "myuser/app",
	"profile": {
		"dev": {
			"address": "127.0.0.1",
			"login": "testuser",
			"key": {
				"private": "key.priv",
				"public": "key.pub"
			},
			"path": "/home/testuser/app",
			"branch": "master",
			"scripts": {
				"pre-deploy": [],
				"deploy": []
			}
		},
		"production": {
			"address": "111.111.111.111",
			"login": "root",
			"path": "/var/www/html",
			"branch": "1.1.0"
		}
	},
	"scripts": {
		"pre-deploy": [],
		"deploy": [
			"composer install"
		]
	}
}
```