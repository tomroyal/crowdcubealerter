# CrowdCube Investment Alerter
#### Monitors a crowdcube.com investment page, logging investment levels and emailing you updates

This little app is designed to run on Heroku using hobby dynos and addons. When installed using the button below, it adds:

* A Heroku app
* A hobby-tier Heroku Postgres database
* A hobby-tier Advanced Scheduler addon

You will be asked to enter four configuration details:

* The full https:// URL of the CrowdCube page to monitor
* A valid API key for https://postmarkapp.com to send email
* The sender email address to use
* The recipient email address to use

Once installed, open the Advanced Scheduler addon and instruct it to run this command:

php bin/ccscrape.php

.. every N hours. Every time this executes, the script will check to see if the investment value has increased and, if it has, both log it and send you a simple email alert.

I am not affiliated with CrowdCube in any way, and this is not an official CrowdCube service. You use it entirely at your own risk.

@tomroyal
tomroyal.com

[![Deploy](https://www.herokucdn.com/deploy/button.svg)](https://heroku.com/deploy)
