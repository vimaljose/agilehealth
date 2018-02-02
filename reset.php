<?php 
require_once('PHPMailer_5.2.0/class.phpmailer.php');
$con=mysql_connect('localhost','root','<qr~6M?@m+mN') or ('error');
mysql_select_db('agilehealth',$con);
require 'Slim/Slim.php';



$mail = $_REQUEST['id'];
$de = base64_decode($mail);

function getConnection() {
    $dbhost="localhost";
    $dbuser="root";
    $dbpass="<qr~6M?@m+mN";
    $dbname="agilehealth";
    
    $dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass,array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));  
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $dbh;
}

if($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $pwd = $_POST['password'];
    
    $email = base64_decode($_REQUEST['email']);
    $qry="update ah_customer set password=:password where email=:email";
    $key = hash('sha256', '!@#123');
    $iv = substr(hash('sha256', 'as12345'), 0, 16);
    $output = openssl_encrypt($pwd, "AES-256-CBC", $key, 0, $iv);
    $password = base64_encode($output);
    
    try{
    
        $db = getConnection();
        $stmt = $db->prepare($qry); 
        $stmt->bindParam("email", $email);
        $stmt->bindParam("password", $password);
        $stmt->execute();
        //$user = $stmt->fetchAll(PDO::FETCH_OBJ);
        echo("password hasbeen changed...");
        /*require('/error');//for all content Hiden*/
    }
    catch(PDOException $e)
    {
        echo $e;
        echo '{"Result":"Failed"}';
    }
}
?>


<html>
<head>
<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
<style type="text/css">
form 
{ 
    margin-top:100px;
    padding: 20px;
    width: 500px; 
    overflow: hidden;
    margin-left: 30%;
     }
 li{
        list-style: none;
 }
fieldset {
 padding: 10px; 
 
 margin-bottom: 5px; }
li { padding: 10px; }

input[type=submit] { 
    float: right;
     margin-top: 10px;
     margin-right: 5px; }
</style>
</head>
<body>
<form action="" method="POST" class="form">
<div class="form-group">
    <fieldset>
        <legend>Change Your Password</legend>
        <ul>
            <li>
                <label for="password1">Enter New Password:</label>
                <input type="password" class="form-control"  name="password" required id="password1" />
            </li>
            <li>     
                <label for="password2" name="password">Re-Enter Password:</label>
                <input type="password" class="form-control" required id="password2" />
            </li>
            <input type="hidden" name="email" value="<?php echo $mail ?>">
        </ul>

        <input type="submit" class="btn btn-primary"/>
    </fieldset>
</div>
</form>

</body>
<script type="text/javascript">
var password1 = document.getElementById('password1');
var password2 = document.getElementById('password2');

var checkPasswordValidity = function() {
    if (password1.value != password2.value) {
        password1.setCustomValidity('Passwords must match.');
    } else {
        password1.setCustomValidity('');
    }        
};

password1.addEventListener('change', checkPasswordValidity, false);
password2.addEventListener('change', checkPasswordValidity, false);

</script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
</html>