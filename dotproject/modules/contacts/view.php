<?php /* CONTACTS $Id$ */
$contact_id = intval( dPgetParam( $_GET, 'contact_id', 0 ) );
$AppUI->savePlace();

// check permissions for this record
//$canEdit = !getDenyEdit( $m, $contact_id );
//if (!$canEdit) {
//	$AppUI->redirect( "m=public&a=access_denied" );
//}

// load the record data
$msg = '';
$row = new CContact();
$canDelete = $row->canDelete( $msg, $contact_id );
// Don't allow to delete contacts, that have a user associated to them.
$q  = new DBQuery;
$q->addTable('users');
$q->addQuery('user_id');
$q->addWhere('user_contact = ' . $contact_id);
$sql = $q->prepare();
$q->clear();
$tmp_user = db_loadResult($sql);
if (!empty($tmp_user))
	$canDelete = false; 

$canEdit = $perms->checkModuleItem($m, "edit", $contact_id);

if (!$row->load( $contact_id ) && $contact_id > 0) {
	$AppUI->setMsg( 'Contact' );
	$AppUI->setMsg( "invalidID", UI_MSG_ERROR, true );
	$AppUI->redirect();
} else if ($row->contact_private && $row->contact_owner != $AppUI->user_id
	&& $row->contact_owner && $contact_id != 0) {
// check only owner can edit
	$AppUI->redirect( "m=public&a=access_denied" );
}

// Get the contact details for company and department
$company_detail = $row->getCompanyDetails();
$dept_detail = $row->getDepartmentDetails();

// setup the title block
$ttl = "View Contact";
$titleBlock = new CTitleBlock( $ttl, 'monkeychat-48.png', $m, "$m.$a" );
$titleBlock->addCrumb( "?m=contacts", "contacts list" );
if ($canEdit && $contact_id)
        $titleBlock->addCrumb( "?m=contacts&a=addedit&contact_id=$contact_id", 'edit' );
	$titleBlock->addCell(
		'<input type="submit" class="button" value="'.$AppUI->_('new project').'" />', '',
		'<form action="?m=projects&a=addedit&company_id='.$row->contact_company.'&contact_id='.$contact_id.'" method="post">', '</form>'
	);
if ($canDelete && $contact_id) {
	$titleBlock->addCrumbDelete( 'delete contact', $canDelete, $msg );
}
$titleBlock->show();
?>
<form name="changecontact" action="?m=contacts" method="post">
        <input type="hidden" name="dosql" value="do_contact_aed" />
        <input type="hidden" name="del" value="0" />
        <input type="hidden" name="contact_id" value="<?php echo $contact_id;?>" />
        <input type="hidden" name="contact_owner" value="<?php echo $row->contact_owner ? $row->contact_owner : $AppUI->user_id;?>" />
</form>
<script language="JavaScript">
function delIt(){
        var form = document.changecontact;
        if(confirm( "<?php echo $AppUI->_('contactsDelete', UI_OUTPUT_JS);?>" )) {
                form.del.value = "<?php echo $contact_id;?>";
                form.submit();
        }
}
</script>

<table border="0" cellpadding="4" cellspacing="0" width="100%" class="std">
<tr>
	<td colspan="2">
		<table border="0" cellpadding="1" cellspacing="1">
		<tr>
			<td align="right"><?php echo $AppUI->_('First Name');?>:</td>
			<td><?php echo @$row->contact_first_name;?></td>
		</tr>
		<tr>
			<td align="right">&nbsp;&nbsp;<?php echo $AppUI->_('Last Name');?>:</td>
			<td><?php echo @$row->contact_last_name;?></td>
		</tr>
		<tr>
			<td align="right" width="100"><?php echo $AppUI->_('Display Name');?>: </td>
			<td><?php echo @$row->contact_order_by;?></td>
		</tr>
		</table>
	</td>
</tr>
<tr>
	<td valign="top" width="50%">
		<table border="0" cellpadding="1" cellspacing="1" class="details" width="100%">
		<tr>
			<td align="right"><?php echo $AppUI->_('Job Title');?>:</td>
			<td><?php echo @$row->contact_job;?></td>
		</tr>
		<tr>
			<td align="right" width="100"><?php echo $AppUI->_('Company');?>:</td>
			<?php if ($perms->checkModuleItem( 'companies', 'access', $row->contact_company )) {?>
            			<td nowrap> <?php echo "<a href='?m=companies&a=view&company_id=" . @$row->contact_company ."'>" . htmlspecialchars( $company_detail['company_name'], ENT_QUOTES) . '</a>' ;?></td>
			<?php } else {?>
						<td nowrap><?php echo htmlspecialchars( $company_detail['company_name'], ENT_QUOTES);?></td>
			<?php }?>
		</tr>
<?php
        if (isset($_SESSION['all_tabs']['departments']))
        {
?>
		<tr>
			<td align="right" width="100"><?php echo $AppUI->_('Department');?>:</td>
			<td nowrap><?php echo $dept_detail['dept_name'];?></td>
		</tr>
<?php } ?>
		<tr>
			<td align="right"><?php echo $AppUI->_('Title');?>:</td>
			<td><?php echo @$row->contact_title;?></td>
		</tr>
		<tr>
			<td align="right"><?php echo $AppUI->_('Type');?>:</td>
			<td><?php echo @$row->contact_type;?></td>
		</tr>
		<tr>
			<td align="right" valign="top" width="100"><?php echo $AppUI->_('Address');?>:</td>
			<td>
                                <?php echo @$row->contact_address1;?><br />
			        <?php echo @$row->contact_address2;?><br />
			        <?php echo @$row->contact_city . ', ' . @$row->contact_state . ' ' . @$row->contact_zip;?>
                        </td>
		</tr>
		<tr>
			<td align="right" width="100"><?php echo $AppUI->_('Phone');?>:</td>
			<td><?php echo @$row->contact_phone;?></td>
		</tr>
		<tr>
			<td align="right"><?php echo $AppUI->_('Phone');?>2:</td>
			<td><?php echo @$row->contact_phone2;?></td>
		</tr>
		<tr>
			<td align="right"><?php echo $AppUI->_('Fax');?>:</td>
			<td><?php echo @$row->contact_fax;?></td>
		</tr>
		<tr>
			<td align="right"><?php echo $AppUI->_('Mobile Phone');?>:</td>
			<td><?php echo @$row->contact_mobile;?></td>
		</tr>
		<tr>
			<td align="right" width="100"><?php echo $AppUI->_('Email');?>:</td>
			<td nowrap><a href="mailto:<?php echo @$row->contact_email;?>"><?php echo @$row->contact_email;?></a></td>
		</tr>
		<tr>
			<td align="right"><?php echo $AppUI->_('Email');?>2:</td>
			<td nowrap><a href="mailto:<?php echo @$row->contact_email2;?>"><?php echo @$row->contact_email2;?></a></td>
		</tr>
		<tr>
			<td align="right"><?php echo $AppUI->_('URL');?>:</td>
			<td nowrap><a href="<?php echo @$row->contact_url;?>"><?php echo @$row->contact_url;?></a></td>
		</tr>
		<tr>
			<td align="right">Jabber:</td>
			<td><?php echo @$row->contact_jabber;?></td>
		</tr>
		<tr>
			<td align="right">ICQ:</td>
			<td><?php echo @$row->contact_icq;?></td>
		</tr>
		<tr>
			<td align="right">AOL:</td>
			<td><?php echo @$row->contact_aol;?></td>
		</tr>
		<tr>
			<td align="right">MSN:</td>
			<td><?php echo @$row->contact_msn;?></td>
		</tr>
		<tr>
			<td align="right">Yahoo:</td>
			<td><?php echo @$row->contact_yahoo?></td>
		</tr>
		<tr>
			<td align="right"><?php echo $AppUI->_('Birthday');?>:</td>
			<td nowrap><?php echo @substr($row->contact_birthday, 0, 10);?></td>
		</tr>
		</table>
	</td>
	<td valign="top" width="50%">
		<strong><?php echo $AppUI->_('Contact Notes');?></strong><br />
		<?php echo @nl2br($row->contact_notes);?>
	</td>
</tr>
<tr>
	<td>
		<input type="button" value="<?php echo $AppUI->_('back');?>" class="button" onClick="javascript:window.location='./index.php?m=contacts';" />
	</td>
</tr>
</form>
</table>
