<div class="row">
	<h1>Samples Collected for <?php echo $server ?></h1>
	<!-- <h3><span id="selection"></span></h3> -->
	<div class="row">
		<div id="theplot" class="span12" style="height: 100px;"></div>
		
	</div>
	



<script language="javascript" type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/flot/0.7.0/jquery.flot.min.js"></script>
<script language="javascript" type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/flot/0.7.0/jquery.flot.selection.min.js"></script>
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
		bars: { show: true, fill: true } // line graphs!
//		points: { show: true}, // draw individual data points
	},
	legend: { noColumns: 2 },
	xaxis: { tickDecimals: 0, mode: "time" },
	yaxis: { min: 0 },
	selection: { mode: "x" } // any mouse selections should be along x axis
};

// Placeholder for data to plot
var DATA = [];
var tmp_data = <?php echo json_encode($graph_data); ?>;
var NEW_SAMPLES_URL = "<?php echo $new_samples_url; ?>";
var TABLE_URL_TIME_START_PARAM = "<?php echo $time_start_param; ?>";
var TABLE_URL_TIME_END_PARAM = "<?php echo $time_end_param; ?>";
/**
 * Callback function for drawing the graph after data is retrieved from an AJAX call
 * @param data 	The array of objects containing time series data to plot.
 */
function new_samples(data) {
	$("#samples").html(data);
}

function new_plot_data(data) {
	// flot requires milliseconds, so convert the timestamp from seconds to milliseconds
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

/**
 * Function to left pad a value (needed for date padding)
 * @param pad_this 	the data to pad (this will be converted to a string)
 * @param padding 	a string of what to left pad the data with
 * @param amount 	how much padding to apply.
 */
function left_pad(pad_this, padding, amount)
{
	var s = String(pad_this);
	var padded_str = '';
	if(s.length < amount)
	{
		for ( var i = 1; i < amount; i++)
		{
			padded_str += padding;
		}
		padded_str += pad_this;
	}
	else
	{
		padded_str += pad_this;
	}
	return padded_str;
}

/**
 * convert a date object to an ANSI-compliant date string (e.g. YYYY-mm-dd HH:MM:ss)
 * @param d 	the javascript Date object
 */
function to_sql_date(d)
{
	// put the year together in the form of YYYY-MM-DD
	ansi_date = d.getFullYear() + '-' + left_pad(d.getMonth()+1, '0', 2) + '-' + left_pad(d.getDate(), '0', 2);
	ansi_date += ' ';
	// put the time together as HH:MM:ss and append to the year
	ansi_date += left_pad(d.getHours(), '0', 2) + ':' + left_pad(d.getMinutes(), '0', 2) + ':' + left_pad(d.getSeconds(), '0', 2);
	return ansi_date;
}

$(document).ready( function ()  {
	//	new_plot_data(tmp_data);
	// div to insert the flot graph in
	var theplot = $("#theplot");

	// initialize the empty flot graph
	var plot_obj = $.plot(theplot, DATA, FLOT_OPTS);
	
	new_plot_data(tmp_data);
	
	theplot.bind("plotselected", function (event, ranges) {
		//	var plot = $.plot(theplot, DATA, $.extend ( true, {}, FLOT_OPTS, {
		//		xaxis: { min: ranges.xaxis.from, max: ranges.xaxis.to }
		//	}));
		
		// Get the data for the table and re-populate it!
		/*
		$.ajax({
			url: new_samples_url,
			method: 'GET',
			dataType: 'html',
			success: show_samples
		});
		*/
		// need a date object to shove timestamp into for conversion to ANSI-type date string
		d = new Date();
		
		// get start datetime for selected fields
		d.setTime(Math.floor(ranges.xaxis.from + (60*60*7*1000)));
		start_time = to_sql_date(d);

		// get end datetime for selected fields
		d.setTime(Math.floor(ranges.xaxis.to + (60*60*7*1000)));
		end_time = to_sql_date(d);
		
		// Throw the selected time values just under the graph for clarity
		//$('#selection').text(start_time + " to " + end_time);
		
		var samples = $("#samples");
		samples.html = '<span id="report_table"><center><img src="img/ajax-loader.gif"></center></div>';
		
		var url_start_end_params = '&' + escape(TABLE_URL_TIME_START_PARAM) + '=' + escape(start_time) + '&' + escape(TABLE_URL_TIME_END_PARAM)  + '=' + escape(end_time);
		
		$.ajax({
			url: NEW_SAMPLES_URL + url_start_end_params,
			method: 'GET',
			dataType: 'html',
			success: new_samples
		});
	
	});
	
	theplot.bind("plotunselected", function (event) {
        $("#selection").text("");
    });
		
});
</script>
