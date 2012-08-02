
	<div id="samples">
		<table class="table table-striped bordered">
			<tr>
				<th>
				<?php if (get_var('sort') == 'timestamp') { ?>
					Date
				<?php } else { ?>
					<a href="<?php echo site_url()."?action=sample&server=".urlencode($server)."&sort=timestamp" ?>">Date</a> 
				<?php } ?>
				</th>
				<th>
				<?php if (get_var('sort') == 'size') { ?>
					Size
				<?php } else { ?>
					<a href="<?php echo site_url()."?action=sample&server=".urlencode($server)."&sort=size" ?>">Size</a> 
				<?php } ?>
				</th>
			</tr>
			
		<?php foreach ($samples as $s) { ?>
			<tr>
				<td>
					<a href="<?php echo site_url()."?action=sample&server=".urlencode($server)."&sample=".urlencode($s['name']); ?>"><?php echo date("r", $s['timestamp']); ?></a>
				</td>
				<td><?php echo $s['size'] ?></td>
			</tr>
		<?php } ?>
		</table>
	</div>
