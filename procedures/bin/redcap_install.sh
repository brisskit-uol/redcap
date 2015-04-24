#!/bin/bash

#Set up an ubuntu lts headless server to run redcap...
#Can actually run this several times as it doesnt try
#to reinstall stuff.

#The end point will be a fairly standard LAMP stack with
#REDCap installed in the brisskit root directory
#something like /var/local/brisskit/redcap

#To run this just do
#sudo sh ./redcap_setup.sh

#Todo
#Rigourous check that mysql set up ok - maybe seed it with
# data when its set up and check I can read it here? 
#Add salt variable in DB file.
#Fall over at any failure
#Log!
#Customize apache config.
#Sort out user privileges on files.

#Olly Butters 13/4/12

############################################
#A few useful variables

#The redcap files live here, I dont want them in svn...
SOURCE_REPOSITORY="http://dev.brisskit.le.ac.uk/admin/olly/redcap/"
VERSION_NUMBER="4.8.4"
MYSQL_IP="mysql"
ROOT_DIR="/var/local/brisskit/"


#Get the DB parameters from the config files.
MYSQL_DB="$(brisskit_db_param redcap name)"
MYSQL_UN="$(brisskit_db_param redcap user)"
MYSQL_PW="$(brisskit_db_param redcap pass)"


############################################
#Nothing to edit below here

echo "Started at: `date`";
echo "Going to install REDCap ${VERSION_NUMBER}"
echo "into ${ROOT_DIR}redcap"

#First of all lets bring this OS up to date
apt-get update
apt-get upgrade -y

#There's a fair chance that where we get here the
#system will need to be rebooted...

############################################
#Install all the prerequisits
############################################

#Apache2
apt-get install -y apache2

#php
apt-get install -y php5
apt-get install -y php5-mysql
apt-get install -y php5-curl
apt-get install -y php-pear
apt-get install -y php-auth
#apt-get install -y php-db
pear install DB

#MySQL
#apt-get install -y mysql-server
apt-get install -y mysql-client

#unzip - just to unzip the redcap source tree.
apt-get install -y unzip

echo "Finished installing packages!";



############################################
#Check we can connect to the mysql server
#Maybe get a file from a table to check???
#mysql -u ${MYSQL_UN} -p ${MYSQL_PW} -h ${MYSQL_IP}


############################################
#Set up apache
############################################
echo "About to configure Apache"

#Get rid of default config
rm /etc/apache2/sites-enabled/*

#Put config in the right place
mv apache_redcap.conf /etc/apache2/sites-available/

#Link to it
ln -s /etc/apache2/sites-available/apache_redcap.conf /etc/apache2/sites-enabled/apache_redcap.conf

#Restart apache to make the new config take effect
apache2ctl restart

echo "Apache configured!\n"

############################################
#Set up the DB
############################################
echo "About to set up the database\n";

#Run the SQL to build the table structure
mysql -u ${MYSQL_UN} -p${MYSQL_PW} -h ${MYSQL_IP} ${MYSQL_DB} < redcap_${VERSION_NUMBER}.sql

echo "Database set up\n";

############################################
#Grab REDCap source and install it
############################################
echo "About to install REDCap\n";
export http_proxy="192.168.0.103:80"
wget ${SOURCE_REPOSITORY}redcap${VERSION_NUMBER}.zip

unzip redcap${VERSION_NUMBER}.zip

#Main directory to put redcap stuff
mkdir ${ROOT_DIR}redcap

#Place to put all the web files
mkdir ${ROOT_DIR}redcap/www

#Where redcap install files get moved to after the install
mkdir ${ROOT_DIR}redcap/www_deleted

#Move the php files
mv redcap ${ROOT_DIR}redcap/www/

#Move our CUSTOM BUILT database.php file
mv database.php ${ROOT_DIR}redcap/www/redcap/database.php

#Make the php database settings file
echo "<?php" > mysql_settings.php
echo "//Automatically generated mysql settings" >> mysql_settings.php
echo "//Made on $(date)" >> mysql_settings.php
echo "\$hostname='mysql';" >> mysql_settings.php
echo "\$db='${MYSQL_DB}';" >> mysql_settings.php
echo "\$username='${MYSQL_UN}';" >> mysql_settings.php 
echo "\$password='${MYSQL_PW}';" >> mysql_settings.php
echo "?>" >> mysql_settings.php

#Move out settings file
mv mysql_settings.php ${ROOT_DIR}redcap/www/redcap/

#Make temp and edocs writable
chmod -R a+w ${ROOT_DIR}redcap/www/redcap/temp
chmod -R a+w ${ROOT_DIR}redcap/www/redcap/edocs

#Move the install files
mkdir ${ROOT_DIR}redcap/www_deleted/${VERSION_NUMBER}
#mv ${ROOT_DIR}redcap/www/redcap/install.php ${ROOT_DIR}redcap/www_deleted/${VERSION_NUMBER}/
#mv ${ROOT_DIR}redcap/www/redcap/redcap_v${VERSION_NUMBER}/Test/index.php ${ROOT_DIR}redcap/www_deleted/${VERSION_NUMBER}/index.php


echo "Finished installing REDCap!"
echo "Now go to URL/redcap/install.php and finish off the customisation"

