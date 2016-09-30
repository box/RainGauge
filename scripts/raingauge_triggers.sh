#!/bin/bash
#
# This script builds the custom trg_plugin function used by pt-stalk-rainguage
#

# Bring in config options for raingauge
source /etc/raingauge_rc

# Calculates rate (change in value over 1 second) for a given trigger command
# Gets previous value from file and calculates the change in values
get_delta() {
    local counter_name="$1"
    local new_value="$2"
    file="${PT_STALK_COLLECT_DIR}/saved_trigger_values"

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
    # Evaluate all trigger commands, calling get_delta if necessary
    # e.g trigger_name="$(bash_command_to_execute)"
    seconds_behind_master="$(mysql $EXT_ARGV -e "SHOW SLAVE STATUS\G" -ss | grep -i seconds_behind | awk '{print $2}')"
    threads_running="$(mysql $EXT_ARGV -e "SHOW GLOBAL STATUS LIKE 'Threads_running'" -ss | awk '{print $2}')"
    cpu_percentage="$(top -d 0.1 -bn2 | grep "Cpu(s)" | tail -n 1 | awk -F',' '{print 100 - $4}')"
    threads_created="$(get_delta "threads_created" "$(mysql $EXT_ARGV -e "SHOW GLOBAL STATUS LIKE 'Threads_created'" -ss | awk '{print $2}')")"

    # Check triggers against their threshold
    #
    # Nonzero value will cause pt-stalk-raingauge to collect and is used to reference which trigger was set off
    # See which trigger set off collector by peeking in /var/log/pt-stalk.log
    # Checks are formatted this way to handle comparisons involving floating point numbers
    #
    # The format is: triggered|threshold|name
    # The threshold is how many times the trigger needs to fire in a row before a collection is processed
    #
    # To add new trigger, copy the format below and change variables

    val="$(echo "$seconds_behind_master" | awk '{print ($1 > 10)}')"
    echo "$val|10|seconds_behind_master"

    val="$(echo "$threads_running" | awk '{print ($1 > 150)}')"
    echo "$val|5|threads_running"

    val="$(echo "$cpu_percentage" | awk '{print ($1 > 50)}')"
    echo "$val|5|cpu_percentage"

    val="$(echo "$threads_created" | awk '{print ($1 > 100)}')"
    echo "$val|5|threads_created"

    # val="$(echo "$variable" | awk '{print ($1 > 0)}')"
    # echo "$val|threshold|variable"

}
