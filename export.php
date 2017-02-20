<?php
  require_once 'include/DB.class.php';
  $db = new DB();
  $db->connect();
			   
  $db->file_force_download();
?>