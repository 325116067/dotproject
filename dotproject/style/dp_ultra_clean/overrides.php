<?php /* $id$ */
##
##  This overrides the show function of the CTabBox_core function
##
class CTabBox extends CTabBox_core {
	function show( $extra='' ) {
		GLOBAL $AppUI;
		reset( $this->tabs );
		$s = '';
	// tabbed / flat view options
		if (@$AppUI->getPref( 'TABVIEW' ) == 0) {
			$s .= "<table border=\"0\" cellpadding=\"2\" cellspacing=\"0\" width=\"98%\">\n";
			$s .= "<tr>\n";
			$s .= "<td nowrap=\"nowrap\">";
			$s .= "<a href=\"".$this->baseHRef."tab=0\">".$AppUI->_('tabbed')."</a> : ";
			$s .= "<a href=\"".$this->baseHRef."tab=-1\">".$AppUI->_('flat')."</a>";
			$s .= "</td>\n".$extra."\n</tr>\n</table>\n";
			echo $s;
		} else {
			if ($extra) {
				echo "<table border=\"0\" cellpadding=\"2\" cellspacing=\"0\" width=\"98%\">\n<tr>\n".$extra."</tr>\n</table>\n";
			} else {
				echo "<img src=\"./images/shim.gif\" height=\"10\" width=\"1\" alt=\"\" />";
			}
		}

		if ($this->active < 0 && @$AppUI->getPref( 'TABVIEW' ) != 2 ) {
		// flat view, active = -1
			echo "<table border=\"0\" cellpadding=\"2\" cellspacing=\"0\" width=\"98%\">\n";
			foreach ($this->tabs as $v) {
				echo "<tr><td><strong>".$AppUI->_($v[1])."</strong></td></tr>\n";
				echo "<tr><td>";
				include $this->baseInc.$v[0].".php";
				echo "</td></tr>\n";
			}
			echo "</table>\n";
		} else {
		// tabbed view
			$s = '<table width="98%" border="0" cellpadding="0" cellspacing="0"><tr>';
			foreach( $this->tabs as $k => $v ) {
				$class = ($k == $this->active) ? 'tabon' : 'taboff';
				$sel = ($k == $this->active) ? 'Selected' : '';
				$s .= '<td height="28" valign="middle" width="1%"><img src="./style/dp_ultra_clean/images/tab'.$sel.'Left.png" width="3" height="28" border="0" alt="" /></td>';
				$s .= '<td valign="middle" width="1%" nowrap="nowrap"  background="./style/dp_ultra_clean/images/tab'.$sel.'Bg.png">&nbsp;<a href="'.$this->baseHRef.'tab='.$k.'">'.$AppUI->_($v[1]).'</a>&nbsp;</td>';
				$s .= '<td valign="middle" width="1%"><img src="./style/dp_ultra_clean/images/tab'.$sel.'Right.png" width="3" height="28" border="0" alt="" /></td>';
				$s .= '<td width="1%" class="tabsp"><img src="./images/shim.gif" height="1" width="3" /></td>';
			}
			$s .= '<td nowrap="nowrap" class="tabsp">&nbsp;</td>';
			$s .= '</tr><tr><td width="100%" colspan="'.(count($this->tabs)*4 + 1).'" class="tabox">';
			echo $s;
			require $this->baseInc.$this->tabs[$this->active][0].'.php';
			echo '</td></tr></table>';
		}
	}
}
?>