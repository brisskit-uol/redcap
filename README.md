#=======================================================================#
# Procedures and Artifacts for installing the Redcap web application    #
#=======================================================================#

There are a number of possible approaches to using this project.

(1) A maven zipped artifact can be produced and used (see below)
(2) The git version of the project can be used
    Both of the above use the redcap_install.sh script in the bin directory.
(3) Some of the distinctive puppet artifacts may be archived in the 
    puppet-artifacts directory. But this is not be the whole story...
    (a) It is assumed that Mysql has been installed locally
    (b) There are other pre-requisites installed using appt-get
        (For details, read the redcap_install.sh script in the bin directory)
        
#======================================================================#
# Using Maven:                                                         #
# Inspect the POM and the production-bin.xml in the assembly directory #
#======================================================================#
To build a local zip artifact, the default install invocation is sufficient...
mvn clean install

To deploy to the remote BRISSKit repos, use the following script
which attempts to set up an ssh tunnel to the brisskit maven machine
before doing the remote deploy:
mvn-remote-deploy.sh