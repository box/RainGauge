#!/usr/bin/env bash

# Bring in config options for raingauge (to get user/password info)
source /etc/raingauge_rc

if [[ -n "$PT_MYSQL_USER" ]] && [[ -n "$PT_MYSQL_PASS" ]]
then
        userPassArgs="-u${PT_MYSQL_USER} -p${PT_MYSQL_PASS}"
else
        userPassArgs=
fi

PORT=$(mysql -h localhost -e "select @@port" -ss $userPassArgs)
DATE=$(ls -r "$PT_STALK_COLLECT_DIR" | tail -n1 | cut -d'-' -f1)
FILE="/tmp/$HOSTNAME-$DATE.tar.gz"
URL="${RG_WEB_SERVER}/index.php?action=upload&hostname=$HOSTNAME&port=$PORT"

#echo "$FILE"
if [[ ! "$DATE" =~ "^[0-9][0-9][0-9][0-9]" ]];
then
        echo "bad/no file"
fi

tar -zcf "$FILE" -C "$PT_STALK_COLLECT_DIR" .
if [[ -r "$FILE" ]]; then
        rm -f "$PT_STALK_COLLECT_DIR"/*
	curl -F "file=@$FILE" $RG_CURL_PARAMS "$URL"
        rm -f "$FILE"
fi
