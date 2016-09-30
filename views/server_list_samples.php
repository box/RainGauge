
	<div id="samples">
		<table class="table table-striped bordered">
			<tr>
				<th>
				<?php if (get_var('sort') == 'timestamp') { ?>
					Date
				<?php } else { ?>
					<a href="<?php echo site_url()."?action=server&server=".urlencode($server)."&sort=timestamp" ?>">Date</a> 
				<?php } ?>
				</th>
				<th>
				<?php if (get_var('sort') == 'size') { ?>
					Size
				<?php } else { ?>
					<a href="<?php echo site_url()."?action=server&server=".urlencode($server)."&sort=size" ?>">Size</a> 
				<?php } ?>
				</th>
				<th>Trigger</th>
				<th></th>
			</tr>
			
		<?php foreach ($samples as $s) { ?>
			<tr>
				<td>
					<a href="<?php echo site_url()."?action=sample&server=".urlencode($server)."&sample=".urlencode($s['name']); ?>"><?php echo date("r", $s['timestamp']); ?></a>
				</td>
				<td><?php echo $s['size'] ?></td>
				<td><?php echo $s['trigger'] ?></td>
				<td><a href="<?php echo site_url()."?action=download&server=".urlencode($server)."&file=".urlencode($s['name']); ?>" class="btn">Download</a> </td>
			</tr>
		<?php } ?>
		</table>
	</div>
