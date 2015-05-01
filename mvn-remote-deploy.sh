#!/bin/bash
#-----------------------------------------------------------------------------------------------
# Please consult the project's README for further details.
#
# This script:
#
#
# Author: Jeff Lusted (jbl4@le.ac.uk)
#-----------------------------------------------------------------------------------------------
ssh -L 2222:login128.lamp.le.ac.uk:22 jbl4@spectre.le.ac.uk

mvn clean deploy 

echo ""
echo "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!"
echo "===>Remote development deploy completed."
echo "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!"
