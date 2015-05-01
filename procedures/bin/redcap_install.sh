#!/bin/bash
#
# Brisskit install script for redcap version 6.4.4
#
# USAGE: sudo redcap-install.sh
#
# NOTES:
# (1) Sets up an ubuntu lts headless server to run redcap.
# (2) Check script for mysql user names and passwords
# (3) Assumes mysql has already been installed locally.
# (4) Uses standard install directory /var/local/brisskit/redcap
#
# TODO:
# (a) Rigorous check that mysql set up ok - maybe seed it with
#     data when its set up and check I can read it here? 
# (b) Add salt variable in DB file.
# (c) Fall over at any failure
# (d) Do logging.
# (e) Customize apache config.
# (f) Sort out user privileges on files.
#
# Olly Butters 13/4/2012
# Jeff Lusted 24/04/2015
############################################
ZIPPED_SOURCE_DIR="../zipped-source/"
VERSION_NUMBER="6.4.4"
MYSQL_IP="localhost"
ROOT_DIR="/var/local/brisskit/"

# Edit/replace these with suitable values...
MYSQL_ROOT_UN=""
MYSQL_ROOT_PW=""
MYSQL_REDCAP_DB="redcap"
MYSQL_RECAP_UN="redcap"
MYSQL_REDCAP_PW="br1ssk1t123"

############################################
# Nothing to edit below here

echo "Started at: `date`";
echo "About to install REDCap ${VERSION_NUMBER}"
echo "into ${ROOT_DIR}redcap"

############################################
#Install all the prerequisites
############################################

# Apache2
apt-get install -y apache2

# php
apt-get install -y php5
apt-get install -y php5-mysql
apt-get install -y php5-curl
apt-get install -y php-pear
apt-get install -y php-auth
pear install DB

# MySQL
#apt-get install -y mysql-server
apt-get install -y mysql-client

# unzip - required for the redcap source tree.
apt-get install -y unzip

echo "Finished installing packages!";

############################################
# Set up apache
############################################
echo "About to configure Apache"

# Get rid of default config
rm /etc/apache2/sites-enabled/*

# Put config in the right place
mv apache_redcap.conf /etc/apache2/sites-available/

# Link to it
ln -s /etc/apache2/sites-available/apache_redcap.conf /etc/apache2/sites-enabled/apache_redcap.conf

# Restart apache to make the new config take effect
apache2ctl restart

echo "Apache configured!\n"

############################################
# Set up the DB
############################################
echo "About to set up the database\n";

# Create the redcap database...
mysql --user=${MYSQL_ROOT_UN} \
      --password=${MYSQL_ROOT_PW} \
      --execute="CREATE DATABASE ${MYSQL_REDCAP_DB}"
 
# Create an overall redcap user...     
mysql --user=${MYSQL_ROOT_UN} \
      --password=${MYSQL_ROOT_PW} \
      --execute="CREATE USER ${MYSQL_REDCAP_UN}@${MYSQL_IP} identified by '${MYSQL_REDCAP_PW}'"

# Grant everything on the redcap database to the overall redcap user...
mysql --user=${MYSQL_ROOT_UN} \
      --password=${MYSQL_ROOT_PW} \
      --execute="GRANT ALL ON ${MYSQL_REDCAP_DB}.* TO ${MYSQL_REDCAP_UN}@${MYSQL_IP}"

# Run the SQL to build the table structure....
mysql --user=${MYSQL_REDCAP_UN} \
      --password=${MYSQL_RECAP_PW} \
      --host=${MYSQL_IP} \
      --database=${MYSQL_REDCAP_DB} < ../sql/install.sql
      
# Run the SQL to build the table structure....
mysql --user=${MYSQL_REDCAP_UN} \
      --password=${MYSQL_RECAP_PW} \
      --host=${MYSQL_IP} \
      --database=${MYSQL_REDCAP_DB} < ../sql/install_data.sql

echo "Database set up\n";

echo "Aout to populate demo databases...
cd ../sql
for f in ./create_demo_db*.sql
do
	if [ ! $# -eq 0 ] 
	then
		echo "Processing file: $(basename $f)"
		mysql --user=${MYSQL_REDCAP_UN} \
              --password=${MYSQL_RECAP_PW} \
              --host=${MYSQL_IP} \
              --database=${MYSQL_REDCAP_DB} < $f
        echo "File: $(basename $f) processed."
	fi 
done
echo "Demo databases set up\n";

############################################
# Grab REDCap source and install it
############################################
echo "About to install REDCap\n";
cp ${SOURCE_REPOSITORY}redcap${VERSION_NUMBER}.zip .

unzip redcap${VERSION_NUMBER}.zip

# Main directory to put redcap stuff
mkdir ${ROOT_DIR}redcap

# Place to put all the web files
mkdir ${ROOT_DIR}redcap/www

# Where redcap install files get moved to after the install
mkdir ${ROOT_DIR}redcap/www_deleted

# Move the php files
mv redcap ${ROOT_DIR}redcap/www/

# Move our CUSTOM BUILT database.php file
mv database.php ${ROOT_DIR}redcap/www/redcap/database.php

# Make the php database settings file
echo "<?php" > mysql_settings.php
echo "//Automatically generated mysql settings" >> mysql_settings.php
echo "//Made on $(date)" >> mysql_settings.php
echo "\$hostname='mysql';" >> mysql_settings.php
echo "\$db='${MYSQL_DB}';" >> mysql_settings.php
echo "\$username='${MYSQL_UN}';" >> mysql_settings.php 
echo "\$password='${MYSQL_PW}';" >> mysql_settings.php
echo "?>" >> mysql_settings.php

# Move out settings file
mv mysql_settings.php ${ROOT_DIR}redcap/www/redcap/

# Make temp and edocs writable
chmod -R a+w ${ROOT_DIR}redcap/www/redcap/temp
chmod -R a+w ${ROOT_DIR}redcap/www/redcap/edocs

# Move the install files
mkdir ${ROOT_DIR}redcap/www_deleted/${VERSION_NUMBER}
#mv ${ROOT_DIR}redcap/www/redcap/install.php ${ROOT_DIR}redcap/www_deleted/${VERSION_NUMBER}/
#mv ${ROOT_DIR}redcap/www/redcap/redcap_v${VERSION_NUMBER}/Test/index.php ${ROOT_DIR}redcap/www_deleted/${VERSION_NUMBER}/index.php

echo "Finished installing REDCap!"
echo "Now go to URL/redcap/install.php and finish off the customisation"