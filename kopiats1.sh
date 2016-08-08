#!/bin/bash
#===============================================================================================
#   System Required:  CentOS 6,7, Debian, Ubuntu
#   Version: 1.0.0
#   Version type: Public
#	  Extra's:
#   Author: Mareczin <pm@me> <gg:45602375> <TS3:OnlineSpeak.eu Nick: Mareczin>
#   Intro:  github.com/Mareczin/ts3-backup
#===============================================================================================

#====CONFIG START==========
#Date
date=$(date +"%d-%B_%H-%M")
#Your MEGA.nz logins,remotepath and checktime (edit in config.cfg)
source config.cfg
email=$mega_username
passwd=$mega_password
remotepath=$remotepath
prefix=$prefix
serverloc=$serverloc
#====CONFIG STOP===========



#DO NOT EDIT CODE AFTER THIS LINE!!!!

#Archive creating...
echo "Compressing TeamSpeak3 server files."
7z a -t7z /backup/$prefix$date.7z $serverloc -m0=lzma2 -mx0 -aoa -mmt=on

#Mega.nz archive sending
echo "Uploading files to mega.nz storage."
/usr/bin/megaput /backup/$prefix$date.7z --reload --username=$email --password=$passwd  --path=$remotepath --disable-previews

#deleting local copy of archive
echo "Deleting local copy of archive."
rm /backup/$prefix$date.7z
