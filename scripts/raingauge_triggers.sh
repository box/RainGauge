#!/bin/bash
#
# This script builds the custom trg_plugin function used by pt-stalk-rainguage
#

# Bring in config options for raingauge
source /etc/raingauge_rc

# Calculates rate (change in value over 1 second) for a given trigger command
get_delta() {
    local counter_name="$1"
    local new_value="$2"
    local file="/tmp/raingauge_saved_trigger_values"

    if [[ ! -e "$file" ]]; then
        touch "$file"
    fi

    local last_value="$(cat "$file" | grep "$counter_name" | awk '{print $2}')"

    if [[ -z "$last_value" ]]; then
        echo 0
    else
        echo "$(( new_value - last_value ))"
        sed -i "/${counter_name}/d" "$file"
    fi
    echo -e "${counter_name}\t${new_value}" >> "$file"
}

# Function called by pt-stalk-raingauge
trg_plugin() {

    local trigger_status=''

    # Evaluate all trigger commands, calling get_delta if necessary
    local cpu_percentage="$(top -d 0.1 -bn2 | grep "Cpu(s)" | tail -n 1 | awk -F',' '{print 100 - $4}')"
    local seconds_behind_master="$(mysql $EXT_ARGV -e "SHOW SLAVE STATUS\G" -ss | grep -i seconds_behind | awk '{print $2}')"
    local threads_created="$(get_delta "threads_created" "$(mysql $EXT_ARGV -e "SHOW GLOBAL STATUS LIKE 'Threads_created'" -ss | awk '{print $2}')")"
    local threads_running="$(mysql $EXT_ARGV -e "SHOW GLOBAL STATUS LIKE 'Threads_running'" -ss | awk '{print $2}')"

    # Check triggers against their threshold
    # Nonzero value will cause pt-stalk-raingauge to collect and is used to reference which trigger was set off
    # See which trigger set off collector by peeking in /var/log/pt-stalk.log
    # Checks are formatted this way to handle comparisons involving floating point numbers
    # To add new trigger, copy the format below and change variables, and increment echo number
    if (( "$(echo "$cpu_percentage" | awk '{print ($1 > 50)}')" )); then
        trigger_status='1'
    elif (( "$(echo "$seconds_behind_master" | awk '{print ($1 > 10)}')" )); then
        trigger_status='2'
    elif (( "$(echo "$threads_created" | awk '{print ($1 > 100)}')" )); then
        trigger_status='3'
    elif (( "$(echo "$threads_running" | awk '{print ($1 > 150)}')" )); then
        trigger_status='4'
    else
        trigger_status='0'	# Nothing triggered
    fi

    if [[ $trigger_status != "0" ]]
    then
        cat << EOF > "${PT_STALK_COLLECT_DIR}/test_triggers"
  1) cpu_percentage -- $cpu_percentage > 50
  2) seconds_behind_master -- $seconds_behind_master > 10
  3) threads_created -- $threads_created > 100
  4) threads_running -- $threads_running > 150
EOF
    fi

    echo $trigger_status
}
