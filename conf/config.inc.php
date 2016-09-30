<?php
$conf['collection_dir'] = 'collected';
$conf['base_dir'] = dirname($_SERVER['SCRIPT_FILENAME']);
$conf['tmp_dir'] = "/tmp";

$conf['non_delta_status_vars'] = array('Threads_running', 'Innodb_data_pending_fsyncs', 'Innodb_data_pending_writes','Innodb_data_pending_reads', 'Innodb_os_log_pending_fsyncs', 'Innodb_os_log_pending_writes');

$conf['status_presets'] = array(
	'Bandwidth'		=> array( 'Bytes_sent', 'Bytes_received' ),
	'Threads'		=> array( 'Threads_connected', 'Threads_running'),
	'DML'			=> array( 'Questions', 'Com_select', 'Com_insert','Com_update','Com_delete', 'Com_replace'),
	'Select_Types'	=> array( 'Select_full_join', 'Select_full_range_join','Select_range','Select_range_check','Select_scan'),
	'Os_Waits'		=> array( 'Innodb_mutex_os_waits', 'Innodb_x_lock_os_waits', 'Innodb_s_lock_os_waits'),
	'LSN'			=> array( 'Innodb_lsn_current', 'Innodb_lsn_flushed', 'Innodb_lsn_last_checkpoint'),
	'Log'			=> array( 'Innodb_log_writes','Innodb_log_write_requests', 'Innodb_log_waits'),
	'Pages'			=> array( 'Innodb_pages_created', 'Innodb_pages_read','Innodb_pages_written'),
	'Rows'			=> array( 'Innodb_rows_read','Innodb_rows_updated','Innodb_rows_inserted','Innodb_rows_deleted'),
	'Pending'		=> array( 'Innodb_data_pending_fsyncs', 'Innodb_data_pending_writes','Innodb_data_pending_reads'),
	'Binlog'		=> array( 'binlog_commits', 'binlog_group_commits'),
);

function extract_trigger($hostname, $port, $filename) {
	$dir = dirname($filename);
	$cmd = "tar -xf $filename -C $dir --wildcards --no-anchored '*-trigger-*'";
	exec($cmd);
}

$conf['on_file_upload'] = 'extract_trigger';
?>
