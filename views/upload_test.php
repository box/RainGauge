<form action="index.php?action=upload" method="post" enctype="multipart/form-data">
	<label for="hostname">Hostname:</label>
	<input type="text" name="hostname" id="hostname" />
	<br />
	<label for="port">Port:</label>
	<input type="text" name="port" id="port" value="3306" /> 
	<br />
	<label for="file">Filename:</label>
	<input type="file" name="file" id="file" /> 
	<br />
	<input type="submit" name="submit" value="Submit" />
</form>
