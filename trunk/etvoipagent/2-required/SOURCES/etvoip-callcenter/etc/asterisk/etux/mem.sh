#!/bin/sh

# Set a variable called stdin to help us
# get the variables from Asterisk
stdin="0"

# Read in the variables from Asterisk,
# and write them to a log file
while [ "$stdin" != "" ]
do
read stdin
if [ "$stdin" != EOF ]
then
echo $stdin >> /tmp/logfile.txt
fi
done

# check the amount of memory in use in megabytes
# and assign the value to a variable named memused
memused=`free -mto | grep Mem: | awk '{print $3}'`

# check the amount of average ping time
# and assign the value to a variable named avgping
avgping=`ping -q -c5 10.10.10.1 | grep = | awk '{print $4}' | cut -d / -f 2`

# check the amount of packet loss
# and assign the value to a variable named packetloss
packetloss=`ping -q -c5 10.10.10.1 | grep received | awk '{print $6}'`


# Execute the SayNumber command to verbalize
# the $memused variable
echo "EXEC SayNumber $memused"
# Execute the PlayBack command add the word "megabytes"
echo "EXEC PlayBack \"megabytes\" "
echo "EXEC PlayBack \"with\" "

echo "EXEC SayNumber $avgping"
echo "EXEC PlayBack \"ms\" "
echo "EXEC PlayBack \"ping\" "
echo "EXEC PlayBack \"time\" "
echo "EXEC PlayBack \"and\" "

echo "EXEC SayNumber $packetloss"

# Execute the PlayBack command add the word "loss"
echo "EXEC PlayBack \"percent\" "
echo "EXEC PlayBack \"loss\" "

# Execute the SayUnixTime command to verbalize a timestamp
echo "EXEC SayUnixTime \",,IMp\""


# Now read the response back from Asterisk,
# and write it to the log file
read response
echo $response >> /tmp/logfile.txt

exit 0
