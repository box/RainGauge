#!/usr/bin/env bash
PORT=$(mysql -h localhost -e "select @@port" -ss)
FILE="$HOSTNAME-$(date +%Y_%m_%d_%H_%M_%S).tar.gz"
SERVER="localhost"
URL="http://$SERVER/RainGauge/index.php?action=upload&hostname=$HOSTNAME&port=$PORT"
tar -zcf "$FILE" -C /tmp/pt-stalk/ .
if [[ -r "$FILE" ]]; then
	rm /tmp/pt-stalk/*
	curl -F "file=@$FILE" "$URL"
	rm "$FILE"
fi
