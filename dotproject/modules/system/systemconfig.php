<?php  // $Id$

// check permissions
if (!$canEdit) {
    $AppUI->redirect( "m=public&amp;a=access_denied" );
}

$dPcfg = new CConfig();

// retrieve the system configuration data
$rs = $dPcfg->loadAll('config_group');

$titleBlock = new CTitleBlock('System Configuration', 'control-center.png', $m);
$titleBlock->addCrumb( "?m=system", "system admin" );
$titleBlock->addCrumb( "?m=system&amp;a=addeditpref", "default user preferences" );
$titleBlock->show();

// prepare the automated form fields based on db system configuration data
$last_group = '';

$tpl->assign('AppUI', $AppUI);
$tpl->assign('baseDir', $baseDir);
$tpl->assign('last_group', $last_group);
$tpl->assign('rs', $rs);
$tpl->displayFile('systemconfig', 'system');
?>
