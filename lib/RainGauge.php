<?php
/**
 * class RainGauge
 *
 * Controller class for Rain Gauge.
 *
 * @author Gavin Towey <gavin@box.com>
 * @created 2012-05-01
 * @license Apache 2.0 license.  See LICENSE document for more info
 */

require_once("lib/Helpers.php");
require_once("lib/Loader.php");
require_once("lib/RainGaugeModel.php");

class RainGauge {

    private $conf;
    private $output_type;
    private $datasource;
    private $header_printed = false;

    /**
     * Constructor.  Pass in the global configuration object
     *
     * @param type $conf
     */
    function __construct($conf) {
        $this->load = new Loader();
        if (empty($conf)) {
            throw new Exception("No conf defined");
        }
        $this->conf = $conf;
        if (get_var('output')) {
            $this->output = get_var('output');
        } else {
            $this->output = 'html';
        }
        $this->datasource = get_var('datasource');
        $this->model = new RainGaugeModel($conf, $this->datasource);
        session_start();
    }

    /**
     * default action 
     */
    public function index() {
        $this->header();

        $data = array();
        $data['graph_data'] = $this->model->get_collection_graph();
        
        $data['servers'] = array();
        foreach( $data['graph_data'] as $series)
        {
            $data['servers'][$series['label']] = array ( 'server' => $series['label'], 'sample_count' => count($series['data']), 'last_sample' => date('r',$series['data'][count($series['data'])-1][0]));
        }
        
        $sort_key = 'last_sample';
        /*
        usort($data['servers'], function ($a, $b) use ($sort_key) {
            return $a[$sort_key] < $b[$sort_key];
        });*/
        $this->load->view("index", $data);
        $this->footer();
    }

    /**
     * list samples for a single server
     * @return void
     */
    public function server() {
        $this->header();

        $sort_key = get_var('sort');
        if (!isset($sort_key)) {
            $sort_key = 'timestamp';
        }

        $data['server'] = get_var('server');
        $data['graph_data'] = $this->model->get_collection_graph($data['server']);
        $data['time_start_param'] = 'start_time';
        $data['time_end_param'] = 'end_time';
        $data['new_samples_url'] = site_url() . "?action=api&type=list_samples&server=" . $data['server'];

        $data['samples'] = $this->model->list_samples($data['server']);
        usort($data['samples'], function ($a, $b) use ($sort_key) {
                    return $a[$sort_key] < $b[$sort_key];
                });

        $_GET['sort'] = $sort_key;
        $this->load->view('server', $data);
        $this->load->view('server_list_samples', $data);

        $this->footer();
    }

    /**
     * grep through a sample for matching lines
     */
    public function search_sample() {
        $this->header();

        $data['server'] = get_var('server');
        $data['sample'] = get_var('sample');
        $data['file'] = get_var('file');
        $data['sample_time'] = $this->get_timestamp_from_file($data['sample']);
        $data['data'] = $this->model->get_sample($data['server'], $data['sample']);
        $data['percent'] = $this->model->get_sample_percent($data['server'], $data['sample']);

        $data['file_data'] = join('<br>', $this->model->search_sample($data['server'], $data['sample'], get_var('pattern'), get_var('file_type'), get_var('context')));
        $this->load->view('sample', $data);

        $this->footer();
    }

    /**
     * display a collected sample for a single server
     * @return void 
     */
    public function sample() {
        $this->header();

        $data['server'] = get_var('server');
        $data['sample'] = get_var('sample');
        $data['file'] = get_var('file');
        $data['sample_time'] = $this->get_timestamp_from_file($data['sample']);

        $data['data'] = $this->model->get_sample($data['server'], $data['sample']);
        $data['page'] = get_var('page');

        $file_type = $this->model->get_type($data['file']);
        if (get_var('file_type') != null) {
            $data['file'] = $this_ts = substr($data['data'][0]['name'], 0, 19) . '-' . get_var('file_type');
            $file_type = get_var('file_type');
        }
        $data['file_type'] = $file_type;

        if (isset($data['file']) and $data['file'] != '') {
            $paged_file = $this->model->get_file_pages($data['server'], $data['sample'], $data['file']);
            if (!isset($data['page']) or $data['page'] > count($paged_file) or $data['page'] < 1) {
                $_GET['page'] = 1;
                $data['page'] = 1;
            }
            $data['file_lines'] = $paged_file[$data['page'] - 1];
            $data['page_count'] = count($paged_file);

            if (in_array($file_type, array('processlist'))) {
                // strip sleeping threads
                $result = $this->model->parse_rows_to_result_set($data['file_lines']);

                $result = array_filter($result, function ($x) {
                            if ($x['Command'] == 'Sleep') {
                                return false;
                            } return true;
                        });
                $data['file_data'] = $this->model->result_as_table($result);
                $data['file_lines'] = array();
            }

            if ($file_type == 'mysqladmin') {
                $status = $this->model->parse_mysqladmin($data['file_lines']);

                $temp_data = array();
                $flot_data = array();
                foreach ($status as $var_name => $values) {
                    if (in_array($var_name, $this->conf['non_delta_status_vars'])) {
                        $data['flot_data'][] = array('label' => $var_name, 'data' => array_map(function ($i, $x) {
                                        return array($i++, $x);
                                    }, array_keys($values), $values));
                    } else {

                        $data['flot_data'][] = array(
                            'label' => $var_name,
                            'data' =>
                            array_slice(
                                    array_map(
                                            function ($i, $x, $y) {
                                                return array($i++, $x - $y);
                                            }, array_keys($values), $values, array_merge(array(0), $values)
                                    ), 1, count($values) - 1
                            )
                        );
                    }
                }

                $data['file_lines'] = array();
                $data['table_data'] = $status;

                $data['status_presets'] = $this->conf['status_presets'];
                $data['default_flot_series'] = array('Threads_running' => true);
            }
            else if ($file_type == 'lsof')
            {
                $groups = array();
                for ($i=1; $i< count($data['file_lines']); $i++)
                {
                    $parts = preg_split("/\s+/", $data['file_lines'][$i]);
                    $groups[$parts[0]] ++;
                    $groups[$parts[4]] ++;
                }
                
                
                $data['file_data'] = "SUMMARY\n";
                foreach ($groups as $g => $value)
                {
                    $data['file_data'] .= "{$g} = {$value}\n";
                }
                $data['file_data'] .= "\n";
                
            }
            else if ($file_type == 'stacktrace')
            {
                $data['file_data'] = htmlspecialchars($this->model->pmp_summary($data['sample'],$data['file']));
                $data['file_data'] .= "<br/><hr>";
            }
            else if ($file_type == 'mutex-status2')
            {
                $this_ts = substr($data['data'][0]['name'], 0, 19);
                $mutexes = $this->model->get_mutex_deltas($data['server'],$data['sample'], $this_ts);
                /*
                print "<pre>";
                print_r($mutexes);
                print "</pre>";
                */
                
                arsort($mutexes);
                
                $data['file_data'] = "MUTEX DELTAS\n";
                $data['file_data'] .= join("\n", array_map(function ($x,$y) { return "$x = $y"; } , array_keys($mutexes), array_values($mutexes)));
                $data['file_data'] .= "<hr>";
            }
        }
        else if ($file_type == 'custom-plaintext') {
                $data['file_data'] = $data['file'];
	    }
        else {
            $data['file_lines'] = $this->model->sift($data['server'], $data['sample'], get_var('sift'));
        }
        $data['percent'] = $this->model->get_sample_percent($data['server'], $data['sample']);
        $this->load->view('sample', $data);

        $this->footer();
    }

    /**
     * given a sample, find the previous collected sample
     * then call the sample() method to display it
     */
    public function prevtime() {
        $data['server'] = get_var('server');
        $data['sample'] = get_var('sample');

        $samples = $this->model->list_samples($data['server']);
        $sort_key = 'timestamp';
        //sort ascending
        usort($samples, function ($a, $b) use ($sort_key) {
                    return $a[$sort_key] > $b[$sort_key];
                });

        for ($i = 0; $i < count($samples); $i++) {
            $s = $samples[$i];
            if ($s['name'] == $data['sample']) {
                if ($i > 0) {
                    $_GET['sample'] = $samples[$i - 1]['name'];
                }
                break;
            }
        }
        $this->sample();
    }

    /**
     * given a sample, find the next collected sample 
     * then call the sample() method to display it 
     */
    public function nexttime() {
        $data['server'] = get_var('server');
        $data['sample'] = get_var('sample');
        $data['file'] = get_var('file');

        $samples = $this->model->list_samples($data['server']);
        $sort_key = 'timestamp';
        // sort descending
        usort($samples, function ($a, $b) use ($sort_key) {
                    return $a[$sort_key] < $b[$sort_key];
                });

        for ($i = 0; $i < count($samples); $i++) {
            $s = $samples[$i];
            if ($s['name'] == $data['sample']) {
                if ($i > 0) {
                    $_GET['sample'] = $samples[$i - 1]['name'];
                    //print "found ".$samples[$i-1]['name'] ."<br>\n";
                }
                break;
            }
        }
        $this->sample();
    }

    /**
     * used to allow remote collection clients to send results for central storage.
     * accepts a multi-part form upload
     * */
    public function upload() {
        if ($_FILES["file"]["error"] > 0) {
            $this->alert($_FILES["file"]["error"]);
            return;
        }

        echo "Upload: " . $_FILES["file"]["name"] . "<br />";
        echo "Type: " . $_FILES["file"]["type"] . "<br />";
        echo "Size: " . ($_FILES["file"]["size"] / 1024) . " Kb<br />";
        echo "Stored in: " . $_FILES["file"]["tmp_name"];

        $hostname = get_var('hostname');
        if (!isset($hostname) or $hostname == '') {
            $this->alert("hostname is required");
            return;
        }

        $port = get_var('port');
        if ($port == null or $port == '') {
            $port = 3306;
        }

        $target_path = join("/", array($this->conf['collection_dir'], "{$hostname}:{$port}"));
        if (substr($target_path, 0, 1) != '/') {
            $target_path = $this->conf['base_dir'] . '/' . $target_path;
        }

        $filename = $target_path . '/' . $_FILES["file"]["name"];

        if (!is_dir($target_path)) {
            if (!mkdir($target_path)) {
                $this->alert("unable to create directory {$target_path}");
                return;
            }
            //chmod($target_path, 0775 );
        }

        if (file_exists($filename)) {
            $this->alert("File " . $_FILES["file"]["name"] . " already exists");
            return;
        }
        rename($_FILES["file"]["tmp_name"], $filename);
        $this->model->save_file($hostname, $port, $target_path);
    }

    /**
     * display the web application header
     * @return boolean  true if the header was actually printed
     */
    private function header() {

        if ($this->header_printed) {
            return false;
        }
        // todo limit the number of servers shown
        $data['servers'] = $this->model->get_server_list();
        $this->load->view('header');
        $this->load->view('navbar', $data);
        $this->header_printed = true;
    }

    /**
     * main method for getting report results.  This method can be called as an
     * ajax callback and return the raw data in json format, or it can display
     * a table or graph directly.  All other methods that get report results use this
     * either directly or as an ajax call.
     * 
     * to access this method, use action=api&type=<api type method>
     */
    public function api() {
        $type = get_var('type');
        switch ($type) {
            case 'list_samples':
                $this->api_samples();
                break;
            default:
                $this->alert("API Error: Unknown type $type", 'alert-error');
        }
    }

    /**
     * List samples for an API request
     */
    private function api_samples() {
        $start_time = get_var('start_time');
        $end_time = get_var('end_time');

        $start_time = $this->unix_timestamp(get_var('start_time'));
        $end_time = $this->unix_timestamp(get_var('end_time'));
        $data['server'] = get_var('server');
        $data['samples'] = $this->model->list_samples($data['server'], $start_time, $end_time);
        $sort_key = get_var('sort');
        if (!isset($sort_key)) {
            $sort_key = 'timestamp';
        }

        usort($data['samples'], function ($a, $b) use ($sort_key) {
                    return $a[$sort_key] < $b[$sort_key];
                });
        switch (get_var('output')) {
            case 'json':
                $this->load->view('server_list_samples_json', $data);
                break;
            default:
                $this->load->view('server_list_samples', $data);
        }
    }

    /**
     * convert a date string to a timestamp
     * @param string $date
     * @return int 
     */
    private function unix_timestamp($date) {
        if (!isset($date) or $date == '')
        {
            return null;
        }
        return strtotime($date);
    }

    /**
     * display a message in a formatted div element
     *
     * @param string $string    The message to display
     * @param string $level     The div class to use (default alert-warning)
     */
    private function alert($string, $level = 'alert-warning') {
        $this->header();
        print "<div class=\"alert {$level}\">{$string}</div>";
    }

    /**
     * display the global web application footer
     */
    private function footer() {
        $this->load->view("footer");
    }

    /**
     * given a filename from pt-stalk, find the time part and return the timestamp
     * 
     * @param string $file
     * @return int 
     */
    private function get_timestamp_from_file($file) {
        $time_part = substr($file, -26);
        //print "$time_part\n<br>";
        $format = "%Y_%m_%d_%H_%M_%S.tar.gz";
        $time = strptime($time_part, $format);
        if (is_array($time)) {
            return mktime($time['tm_hour'], $time['tm_min'], $time['tm_sec'], $time['tm_mon'] + 1, $time['tm_mday'], $time['tm_year'] + 1900);
        }
        return 0;
    }

    /**
     * given a server and name of a sample, allow the user to download the file.
     * 
     * @return int 
     */
    public function download() {
        // ask model for path to file
        $server = get_var('server');
        $file = get_var('file');
        $filename = $this->model->get_collection_filename($server, $file);

        // stream file
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: private',false);
        header('Content-type: "application/octet-stream"');
        header('Content-Disposition: attachment; filename="' . basename($filename) . '";' );
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: '. filesize($filename) );
        ob_clean();
        flush();
        readfile($filename);
        return 0;

    }
}

?>
