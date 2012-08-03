Box Rain Gauge
==============

Rain Gauge is a tool to simplify the process of collecting detailed information from mysql database servers when specific conditions are triggered.  Collections are packaged and centralised in one place, with a convenient web interface to let you explore the data easily.  This tool uses a modified version of the percona toolkit script pt-stalk to handle collecting data from remote servers.

## Quickstart

Installation consists of two parts: setting up the web interface, and setting up the collector.

### Setting up the web interface

Installation of the web interface is super simple!  Just clone the RainGauge project in the document root of your webserver:

	git clone git://github.com/box/RainGauge.git

There are a few things to configure in the conf/config.inc.php file if you want, but those arent't necessary

### Installing the collection scripts

The collection scripts are a bit more involved. You'll want to create a separate user account in mysql.  You'll also need to customise a couple of the scripts to put in the address of the web server where you installed the web interface, and change and options you may want to send to pt-stalk.  Then you'll install a service and start it.

#### Install the scripts

    cp RainGauge/scripts/raingauge_package_and_send.sh /usr/bin/
    cp RainGauge/scripts/pt-stalk /usr/bin/

#### Set up the package and send script

    vi /usr/bin/raingauge_package_and_send.sh

  Now change SERVER='' to be the web server where you installed the interface.  The collection script will do a http post to copy collected data to a central location

#### Set up a new database user in mysql

    mysql -uroot -e "GRANT ... ON *.* TO 'raingauge'@'localhost' IDENTIFIED BY 'SuperSecurePass'"

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
