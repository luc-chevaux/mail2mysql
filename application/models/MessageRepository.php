<?php
class MessageRepository {
	public function insertMail($tPasswort, $tPrio, $tStatus, $tProbTitel, $tProbBesch, $tProbKat, $tKNick, $tHNick, $tLastEdit, $tEinDat, $tSollEndDat, $tEndDat, $tKommentar, $tReplyTo, $tAuth) {
		$db = Zend_Registry::get ( 'db' );
		
		$tPasswort = trim ( $tPasswort );
		$tPrio = trim ( $tPrio );
		$tStatus = trim ( $tStatus );
		$tProbTitel = trim ( $tProbTitel );
		$tProbBesch = trim ( $tProbBesch );
		$tProbKat = trim ( $tProbKat );
		$tKNick = trim ( $tKNick );
		$tHNick = trim ( $tHNick );
		$tLastEdit = trim ( $tLastEdit );
		$tEinDat = trim ( $tEinDat );
		$tSollEndDat = trim ( $tSollEndDat );
		$tEndDat = trim ( $tEndDat );
		$tKommentar = trim ( $tKommentar );
		$tReplyTo = trim ( $tReplyTo );
		
		$dati = array (
				'tPasswort' => $tPasswort,
				'tPrio' => $tPrio,
				'tStatus' => $tStatus,
				'tProbTitel' => $tProbTitel,
				'tProbBesch' => $tProbBesch,
				'tProbKat' => $tProbKat,
				'tKNick' => $tKNick,
				'tHNick' => $tHNick,
				'tLastEdit' => $tLastEdit,
				'tEinDat' => $tEinDat,
				'tSollEndDat' => $tSollEndDat,
				'tEndDat' => $tEndDat,
				'tKommentar' => $tKommentar,
				'tReplyTo' => $tReplyTo,
				'tAuth' => $tAuth 
		);
		
		return $db->insert ( 'Ticket', $dati );
	}
	
	public function getTicketNumberByTitleAndPassword($password, $title) {
		$db = Zend_Registry::get ( 'db' );
		$sql = "SELECT tNr FROM ticket WHERE tPasswort='" . $password . "' AND tProbTitel = '" . $title . "'";
		$row = $db->fetchRow ( $sql );
		
		return $row;
	}
	
	public function getSpamEmail($emailAddress) {
		$db = Zend_Registry::get ( 'db' );
		$sql = "SELECT * FROM spamfilter where email LIKE '%" . $emailAddress . "%'";
		$rows = $db->fetchAll ( $sql );
		
		return $rows;
	}
	
	public function getAuthEmail($emailAddress) {
		$db = Zend_Registry::get ( 'db' );
		$sql = "SELECT * FROM mitarbeiter where mEmail = '" . $emailAddress . "' AND mSupp=0";
		$rows = $db->fetchAll ( $sql );
		
		return $rows;
	}
}
?>