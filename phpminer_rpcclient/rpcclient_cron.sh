#!/bin/sh

if [ -f /tmp/phpminer_rpcclient.pid ]; then
	RUNNING=`cat /tmp/phpminer_rpcclient.pid | awk '{print "ps -p "$1" -f"}' | sh | grep "phpminer"`
	if [ -z "$RUNNING" ]; then
		echo "PHPMiner RPC-Client not running. Restarting"
		PHPSCREEN=`screen -list | grep "phpminer" | awk '{print $1}' | awk -F . '{print $1}'`
		if [ -z "$PHPSCREEN" ]; then
			echo "No screen found, good"
		else
			echo "Screen still running, killing it"
			kill -9 $PHPSCREEN
		fi
		rm /tmp/phpminer_rpcclient.pid
		/etc/init.d/phpminer_rpcclient start
	fi
else
	echo "RPCMiner didn't started yet, start it"
	/etc/init.d/phpminer_rpcclient start
fi
