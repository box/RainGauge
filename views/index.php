<script language="javascript" type="text/javascript" src="js/flot/jquery.flot.js"></script>
<script language="javascript" type="text/javascript" src="js/flot/jquery.flot.stack.js"></script>
<div class="row">
	<div id="theplot" class="span12" style="height: 300px;"></div>
</div>
<!--
<pre>
		<?php // echo print_r($graph_data) ?>
</pre>
-->
<div class="row">
		<ul>
		<?php foreach( $graph_data as $series) { ?>
				<?php $server = $series['label']; ?>
				<li><a href="<?php echo site_url() ."?action=server&server=".urlencode($server) ?>"><?php echo $server ?></a></li> 
		<?php } ?>
		</ul>
<script>
// urls to retrieve data from
/*
var GRAPH_DATA_URL = "<?php echo $ajax_request_url ?>";
var GRAPH_PERMALINK_URL = "<?php echo $graph_permalink; ?>";
var TABLE_BASE_URL = "<?php echo $ajax_table_request_url_base ?>"
var TABLE_URL_TIME_START_PARAM = "<?php echo $table_url_time_start_param ?>"
var TABLE_URL_TIME_END_PARAM = "<?php echo $table_url_time_end_param ?>"
*/
// Setup options for the plot
var FLOT_OPTS = {
	series: {
		stack: true,
//		lines: { show: true }, // line graphs!
//		points: { show: true}, // draw individual data points
		bars: { show: true }, 
	},
	legend: { noColumns: 2 },
	xaxis: { tickDecimals: 0, mode: "time" },
	yaxis: { min: 0 },
	selection: { mode: "x" }, // any mouse selections should be along x axis
};

// Placeholder for data to plot
var DATA = [];
var tmp_data = <?php echo json_encode($graph_data); ?>;

/**
 * Callback function for drawing the graph after data is retrieved from an AJAX call
 * @param data 	The array of objects containing time series data to plot.
 */
function new_plot_data(data) {
	// flot requires millseconds, so convert the timestamp from seconds to milliseconds
	for ( var i = 0; i < data.length; i++ )
	{
		for ( var j = 0; j < data[i].data.length; j++ )
		{
			data[i].data[j][0] = data[i].data[j][0] * 1000;
			data[i].data[j][0] = data[i].data[j][0] - (60*60*7*1000);
		}
	}
	var theplot = $("#theplot"); // get the graph div
	DATA = data;
	plot_obj = $.plot(theplot, DATA, FLOT_OPTS);
}

$(document).ready( function ()  {
	//	new_plot_data(tmp_data);
	// div to insert the flot graph in
	var theplot = $("#theplot");

	// initialize the empty flot graph
	var plot_obj = $.plot(theplot, DATA, FLOT_OPTS);
	
	new_plot_data(tmp_data);
});
</script>


