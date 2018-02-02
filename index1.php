<?php
require_once('PHPMailer_5.2.0/class.phpmailer.php');
$con=mysql_connect('localhost','root','<qr~6M?@m+mN') or ('error');
mysql_select_db('agilehealth',$con);
require 'Slim/Slim.php';
$app = new Slim();
$app->post('/sendmail','sendmail');
$app->POST('/UserRegister','UserRegister');
$app->POST('/login','login');
$app->POST('/checkEmail','checkEmail');
$app->POST('/forgotpassword','forgotpassword');
$app->GET('/verify/:id/:token','verify');
$app->GET('/logout/:id','logout');
$app->GET('/checkEmailStatus/:id','checkEmailStatus');
$app->POST('/imageUpload','imageUpload');

$app->POST('/postMessage','postMessage');
$app->POST('/getMessage','getMessage');
$app->POST('/insertComment','insertComment');

$app->run();

function logout($id){
	$date = date("Y-m-d");
	$time = date("H:i:s");
	$sql="select id from ah_customer where id=:id";
	try{
		$db=getConnection();
		$stmt = $db->prepare($sql);
		$stmt->bindParam("id", $id);
		$stmt->execute();
		$rate = $stmt->fetchAll(PDO::FETCH_OBJ);
		if($rate){
			$res = array('Result'=>'Success',
				     'message'=>'Loggout successfully');
			echo json_encode($res); exit;
		}else{
			

			echo '{"Result":"Failed","message":"Customer not found"}';
		}
	}catch(PDOException $e) {
		$res = array('Result'=>'Failed');
					echo json_encode($res); exit;

		echo '{ "Result": "Failed"}';
	}
}


function getConnection() {
	$dbhost="localhost";
	$dbuser="root";
	$dbpass="<qr~6M?@m+mN";
	$dbname="agilehealth";

	$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass,array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
	$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	return $dbh;
}
