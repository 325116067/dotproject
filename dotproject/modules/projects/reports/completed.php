<?php
// Output the PDF
// make the PDF file
$sql = "SELECT project_name FROM projects WHERE project_id=$project_id";
$pname = db_loadResult( $sql );
echo db_error();

$font_dir = $dPconfig['root_dir']."/lib/ezpdf/fonts";
$temp_dir = $dPconfig['root_dir']."/files/temp";
$base_url  = $dPconfig['base_url'];
require( $AppUI->getLibraryClass( 'ezpdf/class.ezpdf' ) );

$pdf =& new Cezpdf($paper='A4',$orientation='landscape');
$pdf->ezSetCmMargins( 1, 2, 1.5, 1.5 );
$pdf->selectFont( "$font_dir/Helvetica.afm" );

$pdf->ezText( $AppUI->getConfig( 'company_name' ), 12 );

$date = new CDate();
$pdf->ezText( "\n" . $date->format( $df ) , 8 );
$last_week = new CDate($date);
$last_week->subtractSpan(new Date_Span(array(7,0,0,0)));

$pdf->selectFont( "$font_dir/Helvetica-Bold.afm" );
$pdf->ezText( "\n" . $AppUI->_('Project Completed Task Report'), 12 );
$pdf->ezText( "$pname", 15 );
$pdf->ezText( $AppUI->_('Tasks Completed Since') . " " . $last_week->format($df) , 10);
$pdf->ezText( "\n" );
$pdf->selectFont( "$font_dir/Helvetica.afm" );
$title = null;
$options = array(
	'showLines' => 2,
	'showHeadings' => 1,
	'fontSize' => 9,
	'rowGap' => 4,
	'colGap' => 5,
	'xPos' => 50,
	'xOrientation' => 'right',
	'width'=>'750',
	'shaded'=> 0,
	'cols'=>array(
	 	0=>array('justification'=>'left','width'=>250),
		1=>array('justification'=>'left','width'=>95),
		2=>array('justification'=>'center','width'=>75),
		3=>array('justification'=>'center','width'=>75),
		4=>array('justification'=>'center','width'=>75))
);

$hasResources = $AppUI->isActiveModule('resources');
$perms =& $AppUI->acl();
if ($hasResources)
	$hasResources = $perms->checkModule('resources', 'view');
// Build the data to go into the table.
$pdfdata = array();
$columns = array();
$columns[] = "<b>" . $AppUI->_('Task Name') . "</b>";
$columns[] = "<b>" . $AppUI->_('Owner') . "</b>";
$columns[] = "<b>" . $AppUI->_('Assigned Users') . "</b>";
if ($hasResources)
	$columns[] = "<b>" . $AppUI->_('Assigned Resources') . "</b>";
$columns[] = "<b>" . $AppUI->_('Finish Date') . "</b>";

// Grab the completed items in the last week
$q =& new DBQuery;
$q->addQuery('a.*');
$q->addQuery('b.user_username');
$q->addTable('tasks', 'a');
$q->leftJoin('users', 'b', 'a.task_owner = b.user_id');
$q->addWhere('task_percent_complete = 100');
$q->addWhere('task_project = ' . $project_id);
$q->addWhere("task_end_date between '" . $last_week->format(FMT_DATETIME_MYSQL) . "' and '" . $date->format(FMT_DATETIME_MYSQL) . "'");
$tasks = db_loadHashList($q->prepare(), 'task_id');

if ($err = db_error()) {
	$AppUI->setMsg($err, UI_MSG_ERROR);
	$AppUI->redirect();
}
// Now grab the resources allocated to the tasks.
$task_list = array_keys($tasks);
$assigned_users = array();
// Build the array
foreach ($task_list as $tid)
	$asssigned_users[$tid] = array();

if (count($tasks)) {
	$q->clear();
	$q->addQuery('a.task_id, a.perc_assignment, b.*');
	$q->addTable('user_tasks', 'a');
	$q->leftJoin('users', 'b', 'a.user_id = b.user_id');
	$q->addWhere('a.task_id in (' . implode(',', $task_list) . ')');
	$res = $q->exec();
	if (! $res) {
		$AppUI->setMsg(db_error(), UI_MSG_ERROR);
		$AppUI->redirect();
	}
	while ($row = db_fetch_assoc($res)) {
		$assigned_users[$row['task_id']][$row['user_id']] 
		= "$row[user_first_name] $row[user_last_name] [$row[perc_assignment]%]";
	}
}

$resources = array();
if ($hasResources && count($tasks)) {
	foreach ($task_list as $tid) {
		$resources[$tid] = array();
	}
	$q->clear();
	$q->addQuery('a.*, b.resource_name');
	$q->addTable('resource_tasks', 'a');
	$q->leftJoin('resources', 'b', 'a.resource_id = b.resource_id');
	$q->addWhere('a.task_id in (' . implode(',', $task_list) . ')');
	$res = $q->exec();
	if (! $res) {
		$AppUI->setMsg(db_error(), UI_MSG_ERROR);
		$AppUI->redirect();
	}
	while ($row = db_fetch_assoc($res)) {
		$resources[$row['task_id']][$row['resource_id']] 
		= $row['resource_name'] . " [" . $row['percent_allocated'] . "%]";
	}
}

// Build the data columns
foreach ($tasks as $task_id => $detail) {
	$row =& $pdfdata[];
	$row[] = $detail['task_name'];
	$row[] = $detail['user_username'];
	$row[] = implode("\n",$assigned_users[$task_id]);
	if ($hasResources)
		$row[] = implode("\n", $resources[$task_id]);
	$end_date = new CDate($detail['task_end_date']);
	$row[] = $end_date->format($df);
}

$pdf->ezTable( $pdfdata, $columns, $title, $options );

$pdf->ezStream();
?>
