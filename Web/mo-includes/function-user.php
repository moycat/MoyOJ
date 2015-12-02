<?php
	/*
	 * mo-includes/function-user.php @ MoyOJ
	 * 
	 * This file provides the functions ralated to the user system.
	 * 
	 */
	
	function mo_add_user( $username, $password, $email, $nickname = '' )
	{
		global $db;
		$password = password_hash( $password, PASSWORD_DEFAULT, ['cost' => CRYPT_COST] );
		$ip = mo_get_user_ip();
		$sql = 'INSERT INTO `mo_user` (`username`, `password`, `email`, `nickname`, `reg_time`, `reg_ip`) VALUES ( ?, ?, ?, ?, CURRENT_TIMESTAMP, ?)';
		$db->prepare( $sql );
		$db->bind( 'ssssi', $username, $password, $email, $nickname, $ip );
		$db->execute();
		$sql = 'SELECT `id` FROM `mo_user` WHERE `username` = ? LIMIT 1';
		$db->prepare( $sql );
		$db->bind( 's', $username );
		$result = $db->execute();
		$uid = $result[0]['id'];
		$sql = 'INSERT INTO `mo_user_info` (`uid`) VALUES (\''. $uid. '\')';
		$db->prepare( $sql );
		$db->execute();
		$sql = 'INSERT INTO `mo_user_record` (`uid`) VALUES (\''. $uid. '\')';
		$db->prepare( $sql );
		$db->execute();
		return $uid;
	}
	function mo_del_user( $uid )
	{
		global $db;
		$sql = 'DELETE FROM `mo_user` WHERE `id` = ?';
		$db->prepare( $sql );
		$db->bind( 'i', $uid );
		$db->execute();
		$sql = 'DELETE FROM `mo_user_info` WHERE `uid` = ?';
		$db->prepare( $sql );
		$db->bind( 'i', $uid );
		$db->execute();
		$sql = 'DELETE FROM `mo_user_record` WHERE `uid` = ?';
		$db->prepare( $sql );
		$db->bind( 'i', $uid );
		$db->execute();
		return True;
	}
