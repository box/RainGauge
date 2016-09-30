<?php
/**
 * class RainGaugeModel
 *
 * handle getting values from the conf file, reading information about
 * collections and parsing their data.
 *
 *
 * @author Gavin Towey <gavin@box.com>
 * @created 2012-05-01
 * @license Apache 2.0 license.  See LICENSE document for more info
 */

class RainGaugeModel {

    private $conf;
    private $datasource;

    function __construct($conf, $datasource) {
        $this->conf = $conf;
        $this->datasource = $datasource;
    }

    /**
     * when receiving a file upload, call this method.  This is where we
     * can define any additional actions on a file upload, such as saving to
     * a database
     *
     * @param string $hostname
     * @param int $port
     * @param string $filename
     */
    function save_file($hostname, $port, $filename) {
        // it's a tar.gz ... we want to examine it and store some metadata
        // optionally store metadata in a database as well
        if (is_callable($this->conf['on_file_upload']))
        {
            $callback = $this->conf['on_file_upload'];
            $callback($hostname, $port, $filename);
        }
    }

    /**
     * given a hostname, read the local directory where collections are stored
     * and return a list of all gzipped samples found, along with the timestamp
     * and size
     *
     * @param string $hostname
     * @param int $starttime
     * @param int $endtime
     * @return array a list of arrays describing each samples.
     */
    function list_samples($hostname, $starttime = null, $endtime = null) {
        // get a list of all the .tar.gz files we receive
        $result = array();
        $triggers = array();
        $collection_dir = $this->collection_dir() . '/' . $hostname;

        $SERVERDIR = opendir($collection_dir);
        while ($file = readdir($SERVERDIR)) {
            $time_part = substr($file, 0, 19);
            $trigger = substr($file, 20, 7);
            $reason = substr($file, 28);
            $format = "%Y_%m_%d_%H_%M_%S";
            $time = strptime($time_part, $format);
            $timestamp = mktime($time['tm_hour'], $time['tm_min'], $time['tm_sec'], $time['tm_mon'] + 1, $time['tm_mday'], $time['tm_year'] + 1900);
            if ($trigger == "trigger") {
                $triggers[$timestamp] = $reason;
            }
        }
        closedir($SERVERDIR);

        $SERVERDIR = opendir($collection_dir);
        while ($file = readdir($SERVERDIR)) {
            $time_part = substr($file, -26);
            $format = "%Y_%m_%d_%H_%M_%S.tar.gz";
            $time = strptime($time_part, $format);

            if (is_array($time)) {
                $timestamp = mktime($time['tm_hour'], $time['tm_min'], $time['tm_sec'], $time['tm_mon'] + 1, $time['tm_mday'], $time['tm_year'] + 1900);
                if (isset($starttime) and $timestamp < $starttime) {
                    continue;
                }
                if (isset($endtime) and $timestamp > $endtime) {
                    continue;
                }

                $result[] = array(
                    'timestamp' => $timestamp,
                    'size' => filesize(join('/', array($collection_dir, $file))),
                    'name' => $file,
                    'trigger' => $triggers[$timestamp]
                );
            }
        }
        closedir($SERVERDIR);
        return $result;
    }

    /**
     * For a hostname, and a given filename, open the archive and return
     * information about the files in it. This will extract the gzip archive to
     * a temp directory.
     *
     * @param string $hostname
     * @param string $filename
     * @return array    list of arrays which describe each file.
     * @throws Exception    when the given $filename doesn't exist, or there is an error extracting the archive
     */
    function get_sample($hostname, $filename) {
        // get information about stats collected
        $source_file = $this->collection_dir() . '/' . $hostname . '/' . $filename;
        $tmp_dir = $this->tmp_dir() . '/' . $filename;
        // print "tmp_dir: {$tmp_dir}</br>";
        if (!file_exists($source_file)) {
            throw new Exception("File {$filename} doesn't exist for {$hostname}");
        }
        if (!is_dir($tmp_dir)) {
            mkdir($tmp_dir);
            $cmd = "tar -zxf {$source_file} -C {$tmp_dir}";
            //print "cmd: [{$cmd}]<br/>";
            $result = exec($cmd);
            if ($result) {
                throw new Exception("Cannot untar {$source_file} to {$tmp_dir}");
            }
        }

        $result = array();
        $DIR = opendir($tmp_dir);
        while ($file = readdir($DIR)) {
            if (in_array($file, array('.', '..', 'saved_trigger_values'))) {
                continue;
            }
            $result[] = array('name' => $file,
                'short_name' => substr($file, 20)
            );
        }

        return $result;
    }


    /**
     * Given a sample for a server, return it's position in the list of all
     * samples as a percentage when sorted by time.
     *
     * @param string $hostname
     * @param string $sample
     * @return int
     */
    public function get_sample_percent($hostname, $sample) {
        $samples = $this->list_samples($hostname);

        $sort_key = 'timestamp';
        usort($samples, function ($a, $b) use ($sort_key) {
                    return $a[$sort_key] > $b[$sort_key];
                });

        $n = 0;
        $i = 0;
        foreach ($samples as $s) {
            if ($s['name'] == $sample) {
                $n = $i;
                break;
            }
            $i++;
        }

        return $n / count($samples) * 100;
    }

    public function get_mutex_deltas($hostname, $sample, $ts)
    {
        //print "$hostname / $sample / $ts ";
        $mutex1 = $this->get_file($hostname, $sample, $ts.'-mutex-status1');
        $mutex2 = $this->get_file($hostname, $sample, $ts.'-mutex-status2');

        $result = array();
        for ($i=0; $i < count($mutex1); $i++)
        {

            $m1_parts = explode("\t", $mutex1[$i]);
            $m2_parts = explode("\t", $mutex2[$i]);

            $mutex_name = $m1_parts[1];

            list($none, $m1_value) = explode("=", $m1_parts[2]);
            list($none, $m2_value) = explode("=", $m2_parts[2]);

            //print "$mutex_name: $m1_value, $m2_value<br>";
            if (!array_key_exists($mutex_name, $result))
            {
                $result[$mutex_name] = $m2_value - $m1_value;
            }
            else
            {
                $result[$mutex_name] += $m2_value - $m1_value;
            }
        }
        return $result;
    }

    /**
     * given a server and filename, get a valid full path to a collection archive file.
     *
     * @param string $server
     * @param string $file
     * @return mixed the string representation of the full file path if the file exists. Otherwise, false.
     */
    public function get_collection_filename($server, $file) {
        // TODO: (ganderson) this is horribly insecure. We need to make sure the path doesn't step out of this directory.
        $file = $this->collection_dir() . '/' . $server . '/' . $file;
        if (file_exists($file)) {
            return $file;
        }
        return false;
    }


    /**
     * return the contents of a specific file in a sample.
     *
     * @param string $hostname
     * @param string $sample
     * @param string $file
     * @return array    The lines of the file
     */
    public function get_file($hostname, $sample, $file) {
        $file = $this->tmp_dir() . '/' . $sample . '/' . $file;
        if (file_exists($file)) {
            return file($file);
        }
        return array();
    }

    /**
     * Many samples are collected multiple times with a timestamp between each
     * sample.  This function will try to split these types of files into
     * an array of pages to make it easier to display part of the file at a time.
     *
     * @param string $hostname
     * @param string $sample
     * @param string $file
     * @return array an array with an element for each page, which itself is an array of lines for that page
     */
    public function get_file_pages($hostname, $sample, $file) {
        $file = $this->get_file($hostname, $sample, $file);
        $page_boundary = 0;
        $result = array();
        for ($i = 0; $i < count($file); $i++) {

            if (preg_match("/^\s*TS\s*\d+\.\d+/", $file[$i])) {
                //print "matched boundary $page_boundary at $i  length ".($i-$page_boundary )."<br>\n";
                if ($page_boundary > 0 and $i - $page_boundary > 1) {
                    $result[] = array_slice($file, $page_boundary, $i - $page_boundary);
                }
                $page_boundary = $i;
            }
        }
        if (!count($result)) {
            return array($file);
        }

        return $result;
    }

    /**
     * given the text output of the mysqladmin variables dump, parse them into
     * key, value pairs
     *
     * @param array $lines
     * @return array  array of Variable_name=>Value pairs
     */
    function parse_mysqladmin(array $lines) {
        $status = array();
        foreach ($lines as $line) {
            if (preg_match("/\|\s+(\w+)\s+\|\s+([\w\d]+)\s+\|/", $line, $match)) {
                if ($match[1] == 'Variable_name') {
                    continue;
                }

                $status[$match[1]][] = $match[2];
            }
        }
        return $status;
    }

    /**
     * Given the output from files such as processlist that use the \G style output
     * turn that column format into an array with one element per record.
     * @param array $lines
     * @return array    The array of records
     */
    function parse_rows_to_result_set(array $lines) {
        $result = array();
        foreach ($lines as $line) {
            if (substr($line, 1, 8) == '********') {
                if (isset($row) && is_array($row)) {
                    $result[] = $row;
                }
                $row = array();
            } else if (substr($line, 0, 3) != 'TS ' and $column = preg_split("/:/", $line)) {

                $column_name = trim($column[0]);
                if ($column_name == 'Rows_sent') {
                    $in_query = false;
                }

                if ($column_name == 'Info') {
                    $row[$column_name] = trim($column[1]);
                    $in_query = true;
                    continue;
                }

                if (!$in_query) {
                    $row[$column_name] = trim($column[1]);
                } else {
                    $row['Info'] .= $line;
                }
            }
        }

        if (is_array($row)) {
            $result[] = $row;
        }

        return $result;
    }

    /**
     * run pt-sift on the given sample
     *
     * @param string $hostname
     * @param string $filename
     * @param string $action    the action to pass to pt-sift
     * @return array    the output from pt-sift
     */
    function sift($hostname, $filename, $action = null) {
        $tmp_dir = $this->tmp_dir() . '/' . $filename;

        if (!isset($action)) {
            $action = 'DEFAULT';
        }
        // pt-sift required that HOME be defined, but some php
        // configurations don't set this for proc_open by default
        $env_home = getenv('HOME');
        if (!isset($env_home))
        {
            $env_home = '/root';
        }
        $cmd = "ACTION=$action HOME={$env_home} pt-sift {$tmp_dir}";
        $output = $this->exec_external_script($cmd, 'q');
        return array($output);
    }

    /**
     * return the tmp_dir setting configured by the conf file.
     *
     * @return string the tmp dir name
     */
    private function tmp_dir() {
        $dir = $this->conf['tmp_dir'];
        if (substr($dir, 0, 1) != '/') {
            $dir = $this->conf['base_dir'] . '/' . $dir;
        }
        return $dir;
    }

    /**
     * return the collection_dir setting configured by the conf file.
     * @return string   the collection dir setting
     */
    private function collection_dir() {
        $dir = $this->conf['collection_dir'];
        if (substr($dir, 0, 1) != '/') {
            $dir = $this->conf['base_dir'] . '/' . $dir;
        }

        return $dir;
    }

    /**
     * for each server, read it's data from the collection dir, and build
     * and array suitable to pass to flot for graphing
     *
     * @param string $hostname
     * @return array    array of information about all collections to pass to flot.
     *
     * @throws Exception    when a collection dir cannot be read
     */
    function get_collection_graph($hostname = null) {
        // build a series for each server in the collection dir
        // add a point for each collection
        $data = array();

        $collection_dir = $this->collection_dir();
        $DIR = opendir($collection_dir);
        if (!isset($DIR)) {
            throw new Exception("Can't read collection dir {$collection_dir}");
        }

        $last_sample = array();
        while ($entry = readdir($DIR)) {
            if (!is_dir($collection_dir . '/' . $entry) or in_array($entry, array('..', '.'))) {
                continue;
            }

            if (isset($hostname) and $hostname != $entry) {
                continue;
            }

            $SERVERDIR = opendir($collection_dir . '/' . $entry);

            while ($file = readdir($SERVERDIR)) {
                $time_part = substr($file, -26);
                $format = "%Y_%m_%d_%H_%M_%S.tar.gz";
                $time = strptime($time_part, $format);
                if (is_array($time)) {
                    $timestamp = mktime($time['tm_hour'], $time['tm_min'], $time['tm_sec'], $time['tm_mon'] + 1, $time['tm_mday'], $time['tm_year'] + 1900);
                    $data[$entry][] = array($timestamp, filesize(join('/', array($collection_dir, $entry, $file))));

                    if (!array_key_exists($entry, $last_sample) or $timestamp > $last_sample[$entry])
                    {
                        $last_sample[$entry] = $timestamp;
                    }
                }
            }
            closedir($SERVERDIR);
        }
        closedir($DIR);

        arsort($last_sample);
        $finaldata = array();
        $i = 0;
        foreach ($last_sample as $server => $time) {
            $series = $data[$server];
            if (!count($series)) {
                continue;
            }
            usort($series, function ($a, $b) {
                        return $a[0] > $b[0];
                    });
            $finaldata[] = array('label' => $server, 'data' => $series, 'color' => $i++);
        }

        return $finaldata;
    }

    /**
     * Open a two-way communication with an external script.  Used to send
     * data to the program on STDIN and collect output on STDOUT.
     *
     * @param string $script        The script to invoke
     * @param string $input         The input to send to the script on STDIN
     * @return string   The output from the script
     */
    private function exec_external_script($script, $input) {
        $descriptorspec = array(
            0 => array("pipe", "r"), // stdin is a pipe that the child will read from
            1 => array("pipe", "w"), // stdout is a pipe that the child will write to
            2 => array("pipe", "w"), // stderr pipe to check for errors
        );

        $process = proc_open($script, $descriptorspec, $pipes, "/tmp");
        if (is_resource($process)) {
            fwrite($pipes[0], $input);
            fclose($pipes[0]);

            $result = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);

            $ret_val = proc_close($process);
            return $result;
        }
        return null;
    }

    /**
     * given an array which represents something like a sql result set with an array
     * for each row, return the result formatted as tab separated text values and
     * a newline at the end of each row.
     *
     * @param type $result
     * @return string  The result set as a tab-text string
     */
    function result_as_table($result) {
        $table = '';
        foreach ($result as $row) {
            $table .= join("\t", array_values($row)) . "\n";
        }
        return $table;
    }

    /**
     * grep through a sample and return results
     *
     * @param string $hostname
     * @param string $sample
     * @param string $pattern
     * @param string $file
     * @param int $context
     * @return array    the results of the grep command
     */
    public function search_sample($hostname, $sample, $pattern, $file, $context) {
        $file = $this->tmp_dir() . '/' . $sample . '/' . $file;
        if (intval($context)) {
            $context = " -C{$context} ";
        } else {
            $context = '';
        }
        $cmd = "grep -h -ri {$context} \"" . addslashes($pattern) . "\" {$file}";
        //print $cmd . "\n";
        $output = array();
        exec($cmd, $output);
        return $output;
    }

    /**
     * return a list of servers in the collection dir
     * @return type
     */
    public function get_server_list() {
        $servers = array();
        $collection_dir = $this->collection_dir();
        $DIR = opendir($collection_dir);
        while ($server = readdir($DIR)) {
            if (in_array($server, array('.', '..'))) {
                continue;
            }

            if (is_dir($collection_dir . '/' . $server)) {
                $servers[] = $server;
            }
        }
        return $servers;
    }

    /**
     * given a file name, remove the date prefix to get the file type
     * @param type $name
     * @return type
     */
    public function get_type($name) {
        //print "$name<br>\n";
        return substr($name, 20);
    }

    public function pmp_summary($sample, $file)
    {
        $file = $this->tmp_dir() . '/' . $sample . '/' . $file;

        $cmd = "cat {$file} | awk '
  BEGIN { s = \"\"; }
  /^Thread/ { print s; s = \"\"; }
  /^\#/ { if (s != \"\" ) { s = s \",\" $4} else { s = $4 } }
  END { print s }' | sort | uniq -c | sort -r -n -k 1,1";

        $output = array();
        exec($cmd,$output);
        //print "<pre>";
        //print_r($output);
        //print "</pre>";
        return join("\n", $output);
    }

}

?>
