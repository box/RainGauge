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
	<table id="legend" class="table table-bordered table-striped">
		<tr>
			<th></th><th>Server</th><th>Total Samples</th><th>Last Sample</th>
		</tr>
	</table>
</div>
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
		bars: { show: true },
	},
	legend: { show: false },
	xaxis: { tickDecimals: 0, mode: "time" },
	yaxis: { min: 0 },
	selection: { mode: "x" }, // any mouse selections should be along x axis
};

// Placeholder for data to plot
var SERIES_DATA = <?php echo json_encode($servers); ?>;
var DATA = <?php echo json_encode($graph_data); ?>;
var plot_obj
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
	plot_obj = $.plot(theplot, DATA, FLOT_OPTS);
	
	write_server_list(plot_obj.getData());
}

function format_label_row(label, series, i) {
	return '<tr><td onmouseover="highlight_series('+i+')" onmouseout="plot_all()"><div style="border:1px solid ;padding:1px"><div style="height:0;border:5px solid ' + series.color + ';overflow:hidden"></div></td>'
	 + '<td><a href="<?php echo site_url() ?>?action=server&server=' + label + '">' + label + '</a></td><td>'+SERIES_DATA[label]['sample_count']+'</td><td>'+SERIES_DATA[label]['last_sample']+'</td></tr>';
}


function write_server_list(data)
{
	legend_table = $('#legend');
	for(var i=0; i<data.length; i++) {
		legend_table.append( format_label_row(data[i].label, data[i], i));
	}
}

function highlight_series(i)
{
	data = [];
	data.push(DATA[i]);
	plot_obj.setData(data);
	plot_obj.draw();
}

function plot_all() {
	plot_obj.setData(DATA);
	plot_obj.draw();
}

$(document).ready( function ()  {
	//	new_plot_data(tmp_data);
	// div to insert the flot graph in
	var theplot = $("#theplot");	
	new_plot_data(DATA);
});
</script>


