<?php
/*
* Hit Counter - Counter image
*
* Copyright (C) 2016 Daniel Winzen <d@winzen4.de>
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

//prepare
include_once('counter_config.php');
$time=time();
$update_time=$time-($time%3600);
try{
	$db=new PDO('mysql:host=' . DBHOST . ';dbname=' . DBNAME, DBUSER, DBPASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_WARNING, PDO::ATTR_PERSISTENT=>PERSISTENT]);
}catch(PDOException $e){
	exit($I['nodb']);
}
if(!isset($_REQUEST['id'])){
	exit;
}
$stmt=$db->prepare('SELECT * FROM ' . PREFIX . 'registered WHERE api_key=?;');
$stmt->execute([$_REQUEST['id']]);
if(!$id=$stmt->fetch(PDO::FETCH_NUM)){
	exit;
}

//headers
header('Pragma: no-cache');
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Expires: 0');
header('Content-Type: image/gif');

//add visitor to db
if(isSet($_COOKIE["counted$_REQUEST[id]"])){
	$stmt=$db->prepare('INSERT INTO ' . PREFIX . 'visitors (id, time, count, unique_count) VALUES (?, ?, 1, 1) ON DUPLICATE KEY UPDATE count=count+1;');
}else{
	setcookie("counted$_REQUEST[id]", 1, $time+3600);
	$stmt=$db->prepare('INSERT INTO ' . PREFIX . 'visitors (id, time, count, unique_count) VALUES (?, ?, 1, 1) ON DUPLICATE KEY UPDATE count=count+1, unique_count=unique_count+1;');
}
$stmt->execute([$id[0], $update_time]);

//get number of visitors
if(!isSet($_REQUEST['unique']) || $_REQUEST['unique']==0){
	$stmt=$db->prepare('SELECT SUM(count) FROM ' . PREFIX . 'visitors WHERE id=? AND time>=? AND time<?;');
}else{
	$stmt=$db->prepare('SELECT SUM(unique_count) FROM ' . PREFIX . 'visitors WHERE id=? AND time>=? AND time<?;');
}
if(!isSet($_REQUEST['mode']) || $_REQUEST['mode']==0){
	//overalll
	$stmt->execute([$id[0], 0, $time]);
}elseif($_REQUEST['mode']==1){
	//last hour
	$stmt->execute([$id[0], $update_time-3600, $update_time]);
}elseif($_REQUEST['mode']==2){
	//last 24 hours
	$stmt->execute([$id[0], $update_time-86400, $update_time]);
}elseif($_REQUEST['mode']==3){
	// last week
	$stmt->execute([$id[0], $update_time-604800, $update_time]);
}else{
	//last month
	$stmt->execute([$id[0], $update_time-2592000, $update_time]);
}
$num=$stmt->fetch(PDO::FETCH_NUM);
//prepare and output image
$im=imagecreatetruecolor(strlen($num[0])*9+10, 24);
if(isset($_REQUEST['bg']) && preg_match('/^[0-9A-F]{6}$/i', $_REQUEST['bg'])){
	$bg=imagecolorallocate($im, hexdec(substr($_REQUEST['bg'], 0, 2)), hexdec(substr($_REQUEST['bg'], 2, 2)), hexdec(substr($_REQUEST['bg'], 4, 2)));
}else{
	$bg=imagecolorallocate($im, 0, 0, 0);
}
if(isset($_REQUEST['fg']) && preg_match('/^[0-9A-F]{6}$/i', $_REQUEST['fg'])){
	$fg=imagecolorallocate($im, hexdec(substr($_REQUEST['fg'], 0, 2)), hexdec(substr($_REQUEST['fg'], 2, 2)), hexdec(substr($_REQUEST['fg'], 4, 2)));
}else{
	$fg=imagecolorallocate($im, 255, 255, 255);
}
if(isset($_REQUEST['tr']) && $_REQUEST['tr']==1){
	$bg=imagecolorallocate($im, 0, 0, 0);
	imagecolortransparent($im, $bg);
}else{
	imagefill($im, 0, 0, $bg);
}
imagestring($im, 5, 5, 5, $num[0], $fg);
imagegif($im);
imagedestroy($im);
?>
