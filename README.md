

Box Rain Gauge
==============
[![Project Status](http://opensource.box.com/badges/maintenance.svg)](http://opensource.box.com/badges)
[![Travis](https://img.shields.io/travis/box/RainGauge.svg?maxAge=2592000)](https://travis-ci.org/box/RainGauge)
[![Join the chat at https://gitter.im/box/Anemometer](https://badges.gitter.im/box/RainGauge.svg)](https://gitter.im/box/RainGauge?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)


Rain Gauge is a tool to simplify the process of collecting detailed information from mysql database servers when specific conditions are triggered.  Collections are packaged and centralised in one place, with a convenient web interface to let you explore the data easily.  This tool uses a modified version of the percona toolkit script pt-stalk to handle collecting data from remote servers.

## Quickstart

Installation consists of two parts: setting up the web interface, and setting up the collector.

### Setting up the web interface

Installation of the web interface is super simple!  Just clone the RainGauge project in the document root of your webserver:

	git clone git://github.com/box/RainGauge.git

There are a few things to configure in the conf/config.inc.php file if you want, but those aren't necessary

### Installing the collection scripts

The collection scripts are a bit more involved. You'll want to create a separate user account in mysql.  You'll also need to customise a couple of the scripts to put in the address of the web server where you installed the web interface, and change and options you may want to send to pt-stalk.  Then you'll install a service and start it.

#### Install the scripts

    cp RainGauge/scripts/raingauge_package_and_send.sh /usr/bin/
    cp RainGauge/scripts/pt-stalk-raingauge /usr/bin/

#### Set up the package and send script

    vi /usr/bin/raingauge_package_and_send.sh

  Now change SERVER='' to be the web server where you installed the interface.  The collection script will do a http post to copy collected data to a central location

#### Set up a new database user in mysql

    mysql -uroot -e "GRANT PROCESS, SUPER ON *.* TO 'raingauge'@'localhost' IDENTIFIED BY 'SuperSecurePass'"

#### Add the raingauge service

    cp RainGauge/scripts/raingauge_rc /etc/raingauge_rc
    cp RainGauge/scripts/raingauge_service /etc/init.d/raingauge

#### Edit the rc file to set up options

    vi /etc/raingauge_rc

  Edit the following line to use the same password you created for the mysql user:

    PT_MYSQL_PASS=''

#### Start the service

    sudo service raingauge start

#### Install a cleanup cron

  You will probably want to clean up old collections after a while, try 2 days to start:

    [[ -d /www/RainGauge/collected/ ]] && find /www/RainGauge/collected/ -mindepth 1 -mtime +2 -exec rm -rf {} \;


## Copyright and License

Copyright 2014 Box, Inc. All rights reserved.

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

   http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
