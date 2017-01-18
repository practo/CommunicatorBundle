# CommunicatorBundle

Installation
============

Step 1: Download the Bundle
---------------------------

Add the dependency in your project's `composer.json`:
```JSON

"require": {
    "practo/communicator-bundle": "dev-master"
},
"repositories": [
    {
        "type": "vcs",
        "url": "git@github.com:practo/CommunicatorBundle.git"
    }
]

```

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```bash
$ composer update practo/communicator-bundle
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Step 2: Enable the Bundle
-------------------------

Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...

            new Practo\CommunicatorBundle\PractoCommunicatorBundle(),
        );

        // ...
    }

    // ...
}
```

Step 3: Add required parameters
-------------------------------

Communicator has 4 required parameters, add them to your `parameters.yml`

```
communicator.queue.uri: beanstalk://localhost/
communicator.base_uri: replace-with-communicator-url
communicator.api.secret: replace-with-communicator-api-secret
communicator.api.app_name: replace-with-application-name
communicator.s3.bucket: attachment-bucket-name
```

Version Control
---------------

There are no release tags for this bundle. If you want to update, please use commit references.

* Update commit reference in your `composer.lock`:
```

{
    "name": "practo/communicator-bundle",
    "version": "dev-master",
    "target-dir": "Practo/CommunicatorBundle",
    "source": {
        "type": "git",
        "url": "git@github.com:practo/CommunicatorBundle.git",
        "reference": "" /* Update this reference to your target commit hash */
    },
},

```

* Run the following command to update composer hash:
```bash
$ composer update --lock
```

* Below is snippet to send doctor sms:
```php
$sms = $this->container->get('practo_communicator.sms_doctor');
$sms>sendSms(
             $reference_id, $reference_type,
             $name, $to, $messageType, $body,
             $notes = null, $expiryTime = null
            );

```

* Below is snippet to send staff mail:
```bash
$mailStaff = $this->container->get('practo_communicator.mail_staff');
$mailStaff->sendMail(
                     $fromName, $fromMail, $toName = "", $to,
                     $cc = "", $bcc = "", $subject, $body, $type,
                     $files, $hash, $plaintext = "", $replyTo = NULL,
                     $reference_id = NULL, $reference_type = NULL,
                     $expiryTime = NULL, $appName = null)s = null, $expiryTime = null
            );
```
```
