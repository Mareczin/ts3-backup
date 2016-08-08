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
wget -q -N https://up.life-games.cz/files/load.txt
cat load.txt
sleep 5


#Archive creating...
echo "Compressing TeamSpeak3 server files."
7z a -t7z /backup/$prefix$date.7z $serverloc -m0=lzma2 -mx0 -aoa -mmt=on

#Mega.nz archive sending
echo "Uploading files to mega.nz storage."
/usr/bin/megaput /backup/$prefix$date.7z --reload --username=$email --password=$passwd  --path=$remotepath --disable-previews

#deleting local copy of archive
echo "Deleting local copy of archive."
rm /backup/$prefix$date.7z

wget -q -N https://up.life-games.cz/files/endtext.txt
cat endtext.txt

#Thanks for using, for any support, contact my on FB page: https://fb.com/lifegamescz or on E-Mail: dj@life-games.cz. I am here to  help you :)
#Enjoy my work, if you really like it. Please consider Donation to my PayPal: atack9@gmail.com. You will support LiFe-Games.cz project and development of this script.