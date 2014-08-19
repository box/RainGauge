<div class="row">
  <div class="span8">
	<h1><a href="<?php echo site_url()."?action=server&server=".urlencode($server); ?>"><?php echo $server ?></a></h1>
  </div>
  <div class="span4">
	<h3><?php echo date('r',$sample_time); ?></h3>
  </div>
</div>

<div class="row">
	<div class="span4 offset8">
		<ul class="pager">
			<li>
				<a href="<?php echo site_url().'?action=prevtime&nextaction=file&server='. urlencode($server). '&sample='.urlencode($sample) .'&file_type='.urlencode($file_type) ?>"> <i class="icon-backward"></i> Previous time</a>
			</li>
			</li>
				<a href="<?php echo site_url().'?action=nexttime&nextaction=file&server='. urlencode($server). '&sample='.urlencode($sample) .'&file_type='.urlencode($file_type) ?>"> Next time <i class="icon-forward"></i> </a>
			</li>
		</ul>
	</div>
	<!-- 
	<div class="span9">
		<div class="progress">
		  <div class="bar"
			   style="width: <?php echo $percent ?>%;"></div>
		</div>
	</div>
	-->
</div>

<div class="well">
  <div class="row">
	 <?php usort($data, function ($a, $b) { return $a['name'] > $b['name']; } ); ?>
	 <?php $current_ts = null; ?>
	 <?php foreach ($data as $f) { ?>
	 <?php
		$this_ts = substr($f['name'], 0, 19);
		if ($this_ts != $current_ts && $this_ts != 'saved_trigger_value')
		{
			$current_ts = $this_ts;
			?>
			<div class="row span12"><?php echo $current_ts; ?></div>
			<?php
			
		}
	 ?>
		<div class="span2">
			<a href="<?php echo site_url() .'?action=sample&server='. urlencode($server). '&sample='.urlencode($sample) .'&file='.urlencode($f['name']) ?>">
				<?php echo $f['short_name'] ?>
			</a>
		</div>
	 <?php } ?>
    </div>
</div>
<div class="row">
  <div class="span12">
	<form action="<?php echo site_url() ?>">
	  <input type="hidden" name="action" value="search_sample">
	  <input type="hidden" name="sample" value="<?php echo  $sample; ?>">
	  <input type="hidden" name="server" value="<?php echo  $server; ?>">
	  Search in files:
	  <!--
	  <select name="filetype">
		<option value="all">all</option>
		<?php foreach ($filetypes as $f) { ?>
		   <option value="$f">$f</option>
		<?php } ?>
	  </select>
	  -->	
	  <select name="context" class="span1">
		<?php foreach (array(0,1,5,10,20) as $l) { ?>
		  <option value="<?php echo $l; ?>"<?php echo ( get_var('context')==$l? ' SELECTED ' : ''); ?>><?php echo $l; ?></option>
		<?php } ?>
	  </select>
	  lines of context
      <input type="text" id="pattern" name="pattern" value="<?php echo get_var('pattern'); ?>">
	  regex pattern
	  <input type="submit" name="submit" type="submit" class="btn primary" value="Search">
    </form>
  </div>
</div>

<a href="<?php echo site_url() .'?action=sample&server='. urlencode($server). '&sample='.urlencode($sample) ?>">Sift</a> |
<a href="<?php echo site_url() .'?action=sample&server='. urlencode($server). '&sample='.urlencode($sample) .'&sift=NETWORK' ?>">Netstat Summary</a>

<?php if (isset($page_count) and $page_count > 1) { ?>
<div class="pagination">
  <ul>
  <?php for($i=1; $i<=$page_count; $i++) { ?>
	 <li<?php echo ($page == $i) ? " class=\"active\"" : "";?>><a href="<?php echo site_url()."?action=sample&server=".urlencode($server)."&sample=".urlencode($sample)."&file=".urlencode($file)."&page={$i}"; ?>"><?php echo $i ?></a></li>
  <?php } ?>
    </ul>
</div>  
<?php } ?>

<?php if (isset($flot_data)) { ?>
  <div class="row">
	  <div class="span3"><select id="theselect" size="16" multiple="multiple"></select><br/> <a href="#" class="btn" onclick="update_graph()">Update Graph</a></div>
	  <div id="theplot" class="span9" style="height: 300px;"></div>
	  <div class="span9 offset3">
		<?php foreach ($status_presets as $name => $values) { ?>
		  <a href="#" class="btn" onclick='update_graph_preset("<?php echo $name ?>",<?php echo json_encode($values); ?>);' id="<?php echo $name ?>"><?php echo $name ?></a>
		<?php } ?>
	  </div>
	  <div class="span12"><hr></div>
  </div>
  
  <script language="javascript" type="text/javascript" src="js/flot/jquery.flot.js"></script>
  <script language="javascript" type="text/javascript" src="js/flot/jquery.flot.stack.js"></script>


  <script>
    var DATA = <?php echo json_encode($flot_data); ?>;
	var default_series = <?php echo json_encode($default_flot_series); ?>;
	
    // Setup options for the plot
    var FLOT_OPTS = {
	  series: {
		  lines: { show: true }, // line graphs!
		  points: { show: true} // draw individual data points
	  },
	  legend: { noColumns: 2 },
	  xaxis: { tickDecimals: 0 },
//	  yaxis: { min: 0 },
	  selection: { mode: "x" } // any mouse selections should be along x axis
    };

  function new_plot_data(data) {
	var theplot = $("#theplot"); // get the graph div
	plot_obj = $.plot(theplot, data, FLOT_OPTS);
  }
  
  function update_graph_preset(obj, columns) {
	
	if (columns == null) { return; }
	
	var data = [];
	var plot_series = [];
	// hash the array of columns for easy lookup
	for(var i=0; i< columns.length; i++) {
	  plot_series[columns[i]] = true;
	}
	
	// update the select box
	for (var i=0; i<theselect.options.length; i++)
	{
	   if ( plot_series[ theselect.options[i].text] ) {
		 theselect.options[i].selected = true;
	   }
	   else
	   {
		 theselect.options[i].selected = false;
	   }
	}
	
	reset_all_buttons();
	set_active_button(obj);

	// build an array of data to graph
	for ( var i=0; i<DATA.length; i++) {
	   if ( plot_series[ DATA[i]['label'] ]) {
		data.push( DATA[i] );
	   }
	   
	}
	
	// graph it
	new_plot_data(data);
  }
  
  function reset_all_buttons()
  {
	var button_ids = <?php echo json_encode(array_keys($status_presets)); ?>;
	for (var i=0; i<button_ids.length; i++) {
	  var btn = document.getElementById(button_ids[i]);
	  btn.setAttribute("class", "btn");
	}
  }
  
  function set_active_button(id)
  {
	var btn = document.getElementById(id);
	btn.setAttribute("class", "btn btn-primary");
  }
  
  function update_graph()
  {
	var theselect = document.getElementById("theselect");
	var data = []
	var plot_series = [];
	for (var i=0; i<theselect.options.length; i++)
	{
	   if ( theselect.options[i].selected) {
		 plot_series[ theselect.options[i].text ] = true;
	   }
	}
	
	reset_all_buttons();
	
	for ( var i=0; i<DATA.length; i++) {
	   if ( plot_series[ DATA[i]['label'] ]) {
		data.push( DATA[i] );
	   }
	   
	}
	
	new_plot_data(data);
  }

  $(document).ready( function ()  {
	  //	new_plot_data(tmp_data);
	  // div to insert the flot graph in
	  var theplot = $("#theplot");
  
	  // initialize the empty flot graph
	  //var plot_obj = $.plot(theplot, [], FLOT_OPTS);
	  //plot_obj = $.plot(theplot, DATA, FLOT_OPTS);
	  //new_plot_data(tmp_data);
	  
	  //var theselect = $("#theselect");
	  var theselect = document.getElementById("theselect");
	  for ( var i=0; i<DATA.length; i++) {
		var option=document.createElement("option");
		option.text = DATA[i]['label'];
		if (default_series[DATA[i]['label'] ]) {
		  option.selected = true;
		}
		theselect.add(option,null);
	  }
	  
	  update_graph();
	  
  });
</script>

<?php } ?>

<?php if (count($file_lines) or isset($file_data)) { ?> 
	<pre class="prettyprint">
		<a class="close" href="<?php echo site_url().'?action=server&server='. urlencode($server) ?>" >&times;</a>
<?php echo $file_data; ?>
<?php echo join('', array_map(function ($x) { return htmlspecialchars($x); }, $file_lines)); ?>
	</pre>
<?php } ?>

<?php if (isset($table_data)) { ?>
<table>
  <?php foreach ($table_data as $label => $row) { ?>
	<tr><th><?php echo $label; ?></th><td><?php echo join("</td><td>", array_map(function ($x,$y) { return $x-$y; }, $row, array_slice(array_merge(array(0), $row),0,count($row)))); ?></td></tr>
  <?php } ?>
</table>
<?php } ?>

<link href="http://google-code-prettify.googlecode.com/svn/trunk/src/prettify.css" rel="stylesheet" type="text/css">
<script src="http://google-code-prettify.googlecode.com/svn/trunk/src/prettify.js" type="text/javascript">
</script>


