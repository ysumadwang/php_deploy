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

### Deploying a CakePHP plugin
```json
{
	"repo": "davidsteinsland/cakephp-gearman",
	"profile": {
		"dev": {
			"address": "255.255.255.255",
			"login": "david",
			"key": {
				"private": "private.priv",
				"public": "public.pub"
			},
			"path": "/home/david/CakeGearman",
			"scripts": {
				"pre-deploy": [
					"git clone git@github.com:cakephp/cakephp.git cakephp"
				],
				"deploy": [
					"echo \"CakePlugin::load('Gearman', array('bootstrap' => true));\" >> ../cakephp/app/Config/bootstrap.php",
					"cd .. && cp -R CakeGearman cakephp/app/Plugin/Gearman"
				]
			},
			"branch": "master"
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

## Github Webhook
Create a web accessible file, `hook.php`:
```php
require '../vendor/autoload.php';

$hook = new \Epimetheus\Action\WebHook('davidsteinsland/epimetheus', 'githubhook');
$hook->execute();
```

This will:
- pull the repository `davidsteinsland/epimetheus` whenever a payload is sent
- Use the profile `githubhook'
- Initiate the repository to the path specified in `profile.githubhook.path` or default to the directory of the webhook
- Check out the branch specified in `profile.githubhook.branch` or default to the branch of the commit
- Run the `deploy` commands, with working directory set to the repo

In case the Github IP addresses change, set them via:
```php
$hook->setAllowedHosts(array(
    '204.232.175.64/27',
    '192.30.252.0/22'
));
```

Go the Settings page in Github, and install the web hook.