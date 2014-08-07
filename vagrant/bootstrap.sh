#!/usr/bin/env bash

# Install apache, mysql, and php
yum install -y httpd mysql mysql-server php php-mysql vim

# Let apache write to RainGauge's collected directory
sed -i 's/^User apache$/User vagrant/; s/^Group apache$/Group vagrant/' /etc/httpd/conf/httpd.conf

# Start apache and mysql services
chkconfig --levels 235 mysqld on
/etc/init.d/mysqld start
chkconfig --levels 235 httpd on
/etc/init.d/httpd start

# Set up symlink for apache files
ln -sf /vagrant/RainGauge  /var/www/html/RainGauge

# Installing RainGauge scripts
cp /vagrant/RainGauge/scripts/raingauge_package_and_send.sh /usr/bin/
cp /vagrant/RainGauge/scripts/pt-stalk-raingauge /usr/bin/

# Set up new database user
mysql -uroot -e "GRANT PROCESS, SUPER ON *.* TO 'raingauge'@'localhost' IDENTIFIED BY 'SuperSecurePass'"

# Add the RainGauge service
cp /vagrant/RainGauge/scripts/raingauge_rc /etc/raingauge_rc
cp /vagrant/RainGauge/scripts/raingauge_service /etc/init.d/raingauge

# Start the RainGauge service
sudo service raingauge start

# Add cron for deleting entries older than 2 days
cat << EOF > /home/vagrant/crontab
0 0 * * * [[ -d /www/RainGauge/collected/ ]] && find /www/RainGauge/collected/ -mindepth 1 -mtime +2 -exec rm -rf {} \;
EOF
crontab -u root /home/vagrant/crontab

yum update -y &
