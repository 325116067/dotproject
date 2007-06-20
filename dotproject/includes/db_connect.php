<?php /* INCLUDES $Id$ */
if (!defined('DP_BASE_DIR')){
	die('You should not access this file directly');
}

/**
* Generic functions based on library function (that is, non-db specific)
*
* @todo Encapsulate into a database object
*/

// load the db specific handlers
//require_once( DP_BASE_DIR . "/includes/db_{$dPconfig['dbtype']}.php" );
//require_once( "./includes/db_adodb.php" );
require_once DP_BASE_DIR . '/includes/db_adodb.php';

// make the connection to the db
db_connect( $dPconfig['dbhost'], $dPconfig['dbname'],
	$dPconfig['dbuser'], $dPconfig['dbpass'], $dPconfig['dbpersist'] );

	
/*
* Having successfully established the database connection now,
* we will hurry up to load the system configuration details from the database.
*/

$sql = "SELECT config_name, config_value, config_type FROM config";
$rs = $db->Execute($sql);

if ($rs) { // Won't work in install mode.
	$rsArr = $rs->GetArray();

	foreach ($rsArr as $c) {
		if ($c['config_type'] == 'checkbox') {
			$c['config_value'] = ($c['config_value'] == 'true') ? true : false;
		}
		$dPconfig["{$c['config_name']}"] = $c['config_value'];
	}
}

// Moved here because some versions of PHP don't seem to honour it
// if set later on.
if (isset($dPconfig['reset_memory_limit']))
	ini_set('memory_limit', $dPconfig['reset_memory_limit']);

/**
* This global function loads the first field of the first row returned by the query.
*
* @param string The SQL query
* @return The value returned in the query or null if the query failed.
*/
function db_loadResult( $sql )
{
	$cur = db_exec( $sql );
	$cur or exit( db_error() );
	$ret = null;
	if ($row = db_fetch_row( $cur )) {
		$ret = $row[0];
	}
	db_free_result( $cur );
	return $ret;
}

/**
* This global function loads the first row of a query into an object
*
* If an object is passed to this function, the returned row is bound to the existing elements of <var>object</var>.
* If <var>object</var> has a value of null, then all of the returned query fields returned in the object. 
* @param string The SQL query
* @param object The address of variable
*/
function db_loadObject( $sql, &$object, $bindAll=false , $strip = true)
{
	if ($object != null) {
		$hash = array();
		if( !db_loadHash( $sql, $hash ) ) {
			return false;
		}
		bindHashToObject( $hash, $object, null, $strip, $bindAll );
		return true;
	} else {
		$cur = db_exec( $sql );
		$cur or exit( db_error() );
		if ($object = db_fetch_object( $cur )) {
			db_free_result( $cur );
			return true;
		} else {
			$object = null;
			return false;
		}
	}
}

/**
* This global function return a result row as an associative array 
*
* @param string The SQL query
* @param array An array for the result to be return in
* @return <b>True</b> is the query was successful, <b>False</b> otherwise
*/
function db_loadHash( $sql, &$hash )
{
	$cur = db_exec( $sql );
	$cur or exit( db_error() );
	$hash = db_fetch_assoc( $cur );
	db_free_result( $cur );
	if ($hash == false) {
		return false;
	} else {
		return true;
	}
}

/**
* Document::db_loadHashList()
*
* { Description }
*
* @param string $index
*/
function db_loadHashList( $sql, $index='' )
{
	$cur = db_exec( $sql );
	$cur or exit( db_error() );
	$hashlist = array();
	while ($hash = db_fetch_array( $cur )) {
		$hashlist[$hash[$index ? $index : 0]] = $index ? $hash : $hash[1];
	}
	db_free_result( $cur );
	return $hashlist;
}

/**
* Document::db_loadList()
*
* { Description }
*
* @param [type] $maxrows
*/
function db_loadList( $sql, $maxrows=null )
{
	global $AppUI;
	if (!($cur = db_exec( $sql ))) {;
		$AppUI->setMsg( db_error(), UI_MSG_ERROR );
		return false;
	}
	$list = array();
	$cnt = 0;
	while ($hash = db_fetch_assoc( $cur )) {
		$list[] = $hash;
		if( $maxrows && $maxrows == $cnt++ ) {
			break;
		}
	}
	db_free_result( $cur );
	return $list;
}

/**
* Document::db_loadColumn()
*
* { Description }
*
* @param [type] $maxrows
*/
function db_loadColumn( $sql, $maxrows=null )
{
	global $AppUI;
	if (!($cur = db_exec( $sql ))) {;
		$AppUI->setMsg( db_error(), UI_MSG_ERROR );
		return false;
	}
	$list = array();
	$cnt = 0;
	$row_index = null;
	while ($row = db_fetch_row( $cur )) {
		if (! isset($row_index)) {
			if (isset($row[0])) {
				$row_index = 0;
			} else {
				$row_indices = array_keys($row);
				$row_index = $row_indices[0];
			}
		}
		$list[] = $row[$row_index];
		if ( $maxrows && $maxrows == $cnt++ ) {
			break;
		}
	}
	db_free_result( $cur );
	return $list;
}

/* return an array of objects from a SQL SELECT query
 * class must implement the Load() factory, see examples in Webo classes
 * @note to optimize request, only select object oids in $sql
 */
function db_loadObjectList( $sql, $object, $maxrows = null )
{
	$cur = db_exec( $sql );
	if (!$cur) {
		die( "db_loadObjectList : " . db_error() );
	}
	$list = array();
	$cnt = 0;
	$row_index = null;
	while ($row = db_fetch_array( $cur )) {
		if (! isset($row_index)) {
			if (isset($row[0]))
				$row_index = 0;
			else {
				$row_indices = array_keys($row);
				$row_index = $row_indices[0];
			}
		}
		$object->load( $row[$row_index] );
		$list[] = $object;
		if ( $maxrows && $maxrows == $cnt++ ) {
			break;
		}
	}
	db_free_result( $cur );
	return $list;
}

/**
* Document::db_delete()
*
* { Description }
*
*/
function db_delete( $table, $keyName, $keyValue )
{
	$q = new DBQuery();
	$q->setDelete($table);
	$q->addWhere("$keyName = '$keyValue'");
	return $q->exec();
}


/**
* Document::db_insertObject()
*
* { Description }
*
* @param [type] $keyName
* @param [type] $verbose
*/
function db_insertObject( $table, &$object, $keyName = null, $verbose=false )
{
//	$fmtsql = "INSERT INTO `$table` ( %s ) VALUES ( %s ) ";
	$q = new DBQuery();
	$q->addTable($table);
	foreach (get_object_vars( $object ) as $k => $v) {
		if (is_array($v) or is_object($v) or $v == null) {
			continue;
		}
		if ($k[0] == '_') { // internal field
			continue;
		}
		$q->addInsert($k, $v);
		//$fields[] = $k;
		//$values[] = "'" . db_escape(htmlspecialchars( $v )) . "'";
		//$values[] = "'" . db_escape($v) . "'";
		$insert_list[] = $k;
		$values_list[] = $v;
	}
	$change = '"' . implode('","', $insert_list) . '"="' . implode('","', $values_list) . '"';
	//$sql = sprintf( $fmtsql, implode( ",", $fields ) ,  implode( ",", $values ) );
	//($verbose) && print "$sql<br />\n";
	if (!$q->exec()) {
		return false;
	}
	$id = db_insert_id();
	($verbose) && print "id=[$id]<br />\n";
	if ($keyName && $id) {
		$object->$keyName = $id;
	}
	return $change;
}

/**
* Document::db_updateObject()
*
* { Description }
*
* @param [type] $updateNulls
*/
function db_updateObject( $table, &$object, $keyName, $updateNulls=true )
{
	$q = new DBQuery;
	$q->addQuery('*');
	$q->addTable($table);
	$q->addWhere($keyName . '=' . $object->$keyName);
	list($old_obj) = $q->loadList();

	$update_list = array();
	$values_list = array();
	foreach($old_obj as $field => $value)
		if ($object->$field != $value && ($object->$field !== null || $updateNulls)) {
			$update_list[] = $field;
			//$values_list[] = htmlspecialchars($object->$field);
			$values_list[] = $object->$field;
		}

	if (count($update_list)) {
		$change = '"' . implode('","', $update_list) . '"="' . implode('","', $values_list) . '"';
		// addHistory($table, $object->$keyName, 'modify', $change, 0);
		
		$q->addUpdate($update_list, $values_list, true);
		$q->addWhere($keyName . '=' . db_escape($object->$keyName));
		$q->addTable($table);
		$ret = $q->exec();
		if ($ret) {
			return $change;
		} else {
			return $ret;
		}
	} else {
		return true;
	}
}

/**
* Document::db_dateConvert()
*
* { Description }
*
*/
function db_dateConvert( $src, &$dest, $srcFmt )
{
	$result = strtotime( $src );
	$dest = $result;
	return ( $result != 0 );
}

/**
* Document::db_datetime()
*
* { Description }
*
* @param [type] $timestamp
*/
function db_datetime( $timestamp = null )
{
	if (!$timestamp) {
		return null;
	}
	if (is_object($timestamp)) {
		return $timestamp->toString( '%Y-%m-%d %H:%M:%S');
	} else {
		return strftime( '%Y-%m-%d %H:%M:%S', $timestamp );
	}
}

/**
* Document::db_dateTime2locale()
*
* { Description }
*
*/
function db_dateTime2locale( $dateTime, $format )
{
	if (intval( $dateTime)) {
		$date = new CDate( $dateTime );
		return $date->format( $format );
	} else {
		return null;
	}
}

/**
* copy the hash array content into the object as properties
* only existing properties of object are filled. when undefined in hash, properties wont be deleted
* @param array the input array
* @param obj byref the object to fill of any class
* @param string
* @param boolean
* @param boolean
*/
function bindHashToObject( $hash, &$obj, $prefix=null, $checkSlashes=true, $bindAll=false )
{
	is_array( $hash ) or die( "bindHashToObject : hash expected" );
	is_object( $obj ) or die( "bindHashToObject : object expected" );
	
	/* 
	 * checking that all hash values are non-objects so that stripslashes() and other such 
	 * functions are correctly used as well as making sure that we actually create new values and 
	 * not just copy a reference to an object. bind() already filters non-objects but we still need 
	 * to check on this should the funtion be called independently of bind()
	 */
	$go_on = true;
    foreach ($hash as $k => $v) {
		if (is_object( $hash[$k] )) {
			$error_str .= 'bindHashToObject : non-object expected for hash value with key '.$k . "\n";
			$go_on = false;
		}
	}
	$go_on or die ( $error_str );

	if ($bindAll) {
		foreach ($hash as $k => $v) {
			$obj->$k = ($checkSlashes && get_magic_quotes_gpc()) ? stripslashes( $hash[$k] ) : $hash[$k];
		}
	} else if ($prefix) {
		foreach (get_object_vars($obj) as $k => $v) {
			if (isset($hash[$prefix . $k ])) {
				$obj->$k = ($checkSlashes && get_magic_quotes_gpc()) ? stripslashes( $hash[$k] ) : $hash[$k];
			}
		}
	} else {
		foreach (get_object_vars($obj) as $k => $v) {
			if (isset($hash[$k])) {
				$obj->$k = ($checkSlashes && get_magic_quotes_gpc()) ? stripslashes( $hash[$k] ) : $hash[$k];
			}
		}
	}
	//echo "obj="; print_r($obj); exit;
}
?>
