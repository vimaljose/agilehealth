<?php
date_default_timezone_set('America/New_York');
require_once('PHPMailer_5.2.0/class.phpmailer.php');
$con=mysql_connect('localhost','root','<qr~6M?@m+mN') or ('error');
mysql_select_db('agilehealth',$con);
require 'Slim/Slim.php';

$app = new Slim();
$app->config('debug', true);
//$app->error('custom_error_handler');

//PHP 5 >= 5.3
$app->error(function ( Exception $e ) use ($app) {
    $app->render('error.php');
});

/*function custom_error_handler( Exception $e ){
$app = Slim::getInstance();
$app->render('error.php');
}*/

$app->post('/sendmail','sendmail');
$app->POST('/UserRegister','UserRegister');
$app->POST('/login','login');
$app->POST('/checkEmail','checkEmail');
$app->POST('/forgotpassword','forgotpassword');
$app->GET('/verify/:id/:token','verify');
$app->GET('/logout/:id','logout');
$app->GET('/checkEmailStatus/:id','checkEmailStatus');
$app->POST('/imageUpload','imageUpload');

$app->POST('/postMessage','postMessage');#

$app->POST('/getMessage','getMessage');
$app->POST('/insertComment','insertComment');#
$app->POST('/updatePassword','updatePassword');
$app->POST('/commentLikeupdate','commentLikeupdate');
$app->POST('/replyLikeupdate','replyLikeupdate');
$app->POST('/commandsCount','commandsCount');
$app->POST('/parkingLot','parkingLot');
$app->get('/totalCount/:id','totalCount');
$app->POST('/updateDevice','updateDevice');
$app->get('/myNotification/:id','myNotification');
$app->POST('/insertAnswer','insertAnswer');
$app->get('/getAnswer/:userId/:categoryId','getAnswer');
$app->get('/clearCache/:id','clearCache');
$app->get('/getAnswerAvg/:id','getAnswerAvg');
$app->get('/clearNotify/:id','clearNotify');
$app->get('/clearCount/:id','clearCount');
$app->get('/notifyCount/:id','notifyCount');
$app->get('/fromNotication/:noftifyId','fromNotication');
$app->post('/setFavourite','setFavourite');
$app->get('/getFavourite/:id','getFavourite');
$app->get('/removeFavourite/:id','removeFavourite');
$app->get('/getSavedList/:userId','getSavedList');
$app->get('/parking_lot_search/:userId/:searchValue','parking_lot_search');
$app->post('/tag','tag');
$app->get('/fbaseUsers','fbaseUsers');
$app->get('/fbaseUsersRemove','fbaseUsersRemove');

//parking_lot_search($userId,$searchValue)



$app->run();







function fbaseUsers()
{

    $data = '{"id": "6"}';

    $url = "https://agilehealth-560eb.firebaseio.com/notification/status/true.json";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);                               
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/plain'));
    $jsonResponse = curl_exec($ch);
    if(curl_errno($ch))
    {
        echo 'Curl error: ' . curl_error($ch);
    }
    curl_close($ch);
    echo $jsonResponse;



}
function fbaseUsersRemove()
{
    $url = "https://agilehealth-560eb.firebaseio.com/notification/status/true.json";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $result;exit;
}



function tag()
{
$request = Slim::getInstance()->request();#request Instance
$data = json_decode($request->getBody());#get body json content
$db = getConnection();#establish Db connection
$date=date("Y-m-d H:i:s");

$getQueryid = "
SELECT ah_message.categoryId,ah_message.userid,ah_message.questionId,ah_message.message,
ah_customer.deviceToken,ah_customer.firstName,ah_customer.lastName,ah_customer.profilePicture
from ah_message
inner join ah_customer on ah_customer.id = ah_message.userid
where ah_message.id = $data->messageId";

$qry = $db->prepare($getQueryid);
$qry->execute();
$qryData = $qry->fetch(PDO::FETCH_OBJ);
foreach ($data->tag as $key) 
{


    $tagUser = "SELECT * from ah_customer
    where id = $key ";
    $tagUser = $db->prepare($tagUser);
    $tagUser->execute();
    $tagUserData = $tagUser->fetch(PDO::FETCH_OBJ);

    $questionId = $qryData->questionId;
    $categoryId = $qryData->categoryId;
    $userId = $key;
    $deviceToken = $tagUserData->deviceToken;
    $post = $qryData->message;

    $notificationTime = $data->estTime;
    $commanderId = $data->userId;
    $replyTo = "";

    $getUser = "SELECT * from ah_customer
    where id = $commanderId ";
    $qryUser = $db->prepare($getUser);
    $qryUser->execute();
    $qryUserData = $qryUser->fetch(PDO::FETCH_OBJ);

    $name = $qryUserData->firstName." ".$qryUserData->lastName;
    $image = $qryUserData->profilePicture;

    $notificationTitle ="Taged You";
    $notificationDesc = $data->comment;
    $notificationTime = $data->estTime;
    $messageId = $data->messageId;



    $notify = "INSERT into ah_notification
    (userId,message,notificationTitle,notificationDesc,notificationTime,commanderId,replyTo,messageId,isRead,
    questionId,categoryId,created_at,updated_at)
    values(:userId,:message,:notificationTitle,:notificationDesc,:notificationTime,:commanderId,:replyTo,:messageId,'0',
    :questionId,:categoryId,'$date','$date')";


    $st = $db->prepare($notify);
    $st->bindParam("userId",$userId);
    $st->bindParam("message",$post);
    $st->bindParam("notificationTitle",$notificationTitle);
    $st->bindParam("notificationDesc", $notificationDesc);
    $st->bindParam("notificationTime",$notificationTime);
    $st->bindParam("commanderId", $commanderId);
    $st->bindParam("replyTo", $replyTo);
    $st->bindParam("messageId", $messageId);
    $st->bindParam("questionId", $questionId);
    $st->bindParam("categoryId", $categoryId);
    $st->execute();


    $getCount = "SELECT count(*) as batch from ah_notification where isRead = 0 and userId = $userId";
    $qryCount = $db->prepare($getCount);
    $qryCount->execute();
    $qryCountData = $qryCount->fetch(PDO::FETCH_OBJ);
    $badge = $qryCountData->batch;
    $sound ="NotificationTune.mp3";

    $getUserMe = "SELECT * from ah_customer
    where id = $commanderId";

    $qryUserMe = $db->prepare($getUserMe);
    $qryUserMe->execute();
    $qryUserDataMe = $qryUserMe->fetch(PDO::FETCH_OBJ);
    $nameMe = $qryUserDataMe->firstName." ".$qryUserDataMe->lastName;




    send_gcm_notify($deviceToken,$notificationDesc,$questionId,$categoryId,$notificationTitle,$badge,$image,$name,$sound,"sec",$nameMe);
}

$res = array('Result'=>'Success',
             'Status'=>'message posted successfully',
             'userDetails'=>$qryCountData);
echo json_encode($res);exit;



}

function removeFavourite($id)
{
$db = getConnection();#establish Db connection

$selectQryA = "SELECT * from ah_favourite
where favouriteId=$id";
$selectQryPrepareA = $db->prepare($selectQryA);
$selectQryPrepareA->execute();
$results = $selectQryPrepareA->fetch(PDO::FETCH_OBJ);

$selectQry = "DELETE  from ah_favourite
where favouriteId=$id";
$selectQryPrepare = $db->prepare($selectQry);
$selectQryPrepare->execute();


// if($results){
//   $res = array('Result'=>'Success unfavourite',"type"=>$results->type);
// }else{
//   $res = array('Result'=>'Success unfavourite',"type"=>1);
// }

$res = array('Result'=>'Success unfavourite',"type"=>0,"favouriteId"=>0);

echo json_encode($res);exit;

}
function setFavourite()
{
$request = Slim::getInstance()->request();#request Instance
$data = json_decode($request->getBody());#get body json content
$db = getConnection();#establish Db connection
$date = date("Y-m-d H:i:s");

if(count($data->favouriteid) > 0)
{
    foreach($data->favouriteid as $favouriteid)
    {

        $selectQry = "DELETE  from ah_favourite
        where favouriteId = $favouriteid";
        $selectQryPrepare = $db->prepare($selectQry);
        $selectQryPrepare->execute();
    }
}



if($data->reply != 0)
{

//  reply
    $selectQry = "SELECT * from ah_favourite
    where userId=$data->userId and messageId=$data->reply";
//$selectQry = "SELECT * from ah_favourite
// where userId=$data->userId and messageId=$data->messageId and type=$data->type";
    $selectQryPrepare = $db->prepare($selectQry);
    $selectQryPrepare->execute();

    $already = $selectQryPrepare->fetch(PDO::FETCH_OBJ);
    if($already){
        $Fid = $already->favouriteId;

    }else{
        $Fid = 0;
    }
    if($already == "")
    {
        $insertQuery = "INSERT into ah_favourite (userId,messageId,replyId,type,tempMessageId,created_at)
        values(:userId,:messageId,:replyId,:type,:tempMessageId,:created_at)";
        $qryPrepare = $db->prepare($insertQuery);
        $qryPrepare->bindParam("userId",$data->userId);
        $qryPrepare->bindParam("messageId",$data->reply);
        $qryPrepare->bindParam("replyId",$data->reply);
        $qryPrepare->bindParam("type",$data->type);
        $qryPrepare->bindParam("tempMessageId",$data->messageId);
        $qryPrepare->bindParam("created_at",$date);
        $qryPrepare->execute();

        $cd = "select * from ah_favourite order by id desc limit 1";
        $selectQryPrepare = $db->prepare($selectQry);
        $selectQryPrepare->execute();

        $gData = $selectQryPrepare->fetch(PDO::FETCH_OBJ);

        $res = array('Result'=>'Success favourite',"type"=>$data->type,"favouriteId"=>$Fid,"details"=>$gData);
        echo json_encode($res);exit;
    }else
    {
//   $selectQry = "DELETE  from ah_favourite
// where userId=$data->userId and messageId=$data->reply";
//   $selectQryPrepare = $db->prepare($selectQry);
//   $selectQryPrepare->execute();

//   $res = array('Result'=>'Success unfavourite');
//   echo json_encode($res);exit;
        $insertQuery = "INSERT into ah_favourite (userId,messageId,replyId,type,tempMessageId,created_at)
        values(:userId,:messageId,:replyId,:type,:tempMessageId,:created_at)";
        $qryPrepare = $db->prepare($insertQuery);
        $qryPrepare->bindParam("userId",$data->userId);
        $qryPrepare->bindParam("messageId",$data->reply);
        $qryPrepare->bindParam("replyId",$data->reply);
        $qryPrepare->bindParam("type",$data->type);
        $qryPrepare->bindParam("tempMessageId",$data->messageId);
        $qryPrepare->bindParam("created_at",$date);
        $qryPrepare->execute();

        $cd = "select * from ah_favourite order by id desc limit 1";
        $selectQryPrepare = $db->prepare($selectQry);
        $selectQryPrepare->execute();

        $gData = $selectQryPrepare->fetch(PDO::FETCH_OBJ);

        $res = array('Result'=>'Success favourite',"type"=>$data->type,"favouriteId"=>$Fid,"details"=>$gData);
        echo json_encode($res);exit;
    }

}

$selectQry = "SELECT * from ah_favourite
where userId=$data->userId and messageId=$data->messageId";
//$selectQry = "SELECT * from ah_favourite
// where userId=$data->userId and messageId=$data->messageId and type=$data->type";
$selectQryPrepare = $db->prepare($selectQry); 
$selectQryPrepare->execute();

$already = $selectQryPrepare->fetch(PDO::FETCH_OBJ);

if($already == "")
{
    $insertQuery = "INSERT into ah_favourite (userId,messageId,replyId,type,tempMessageId,created_at)
    values(:userId,:messageId,:replyId,:type,:tempMessageId,:created_at)";
    $qryPrepare = $db->prepare($insertQuery);
    $qryPrepare->bindParam("userId",$data->userId);
    $qryPrepare->bindParam("messageId",$data->messageId);
    $qryPrepare->bindParam("replyId",$data->reply);
    $qryPrepare->bindParam("type",$data->type);
    $qryPrepare->bindParam("tempMessageId",$data->messageId);
    $qryPrepare->bindParam("created_at",$date);
    $qryPrepare->execute();

    $cd = "select * from ah_favourite order by id desc limit 1";
    $selectQryPrepare = $db->prepare($selectQry);
    $selectQryPrepare->execute();

    $gData = $selectQryPrepare->fetch(PDO::FETCH_OBJ);

    $res = array('Result'=>'Success favourite',"type"=>$data->type,"favouriteId"=>0,"details"=>$gData);
    echo json_encode($res);exit;
}
else
{
    $selectQry = "DELETE  from ah_favourite
    where userId=$data->userId and messageId=$data->messageId and type=$data->type";
    $selectQryPrepare = $db->prepare($selectQry);
    $selectQryPrepare->execute();

    $res = array('Result'=>'Success unfavourite',"type"=>0);
    echo json_encode($res);exit;
}

}
/*function getFavourite($id)
{

$db = getConnection();#establish Db connection

$getQuery = "SELECT * from ah_favourite WHERE userId = $id";
$qryPrepare = $db->prepare($getQuery);
$qryPrepare->execute();
$results = $qryPrepare->fetchAll(PDO::FETCH_OBJ);

//print_r($results);

$res = array();
foreach($results as $data){

if($data->type == 1){
// type = message
$sql = "SELECT * FROM ah_message WHERE id = $data->messageId";
$qryPrepare = $db->prepare($sql);
$qryPrepare->execute();
$messages = $qryPrepare->fetchAll(PDO::FETCH_OBJ);
if($messages){

$messageList = array();
foreach($messages as $m){
$sql = "SELECT * FROM ah_comment WHERE messageId = $m->id";
$qryPrepare = $db->prepare($sql);
$qryPrepare->execute();
$comments = $qryPrepare->fetchAll(PDO::FETCH_OBJ);
$messageList[] = array('comment'=>$m,'reply'=>$comments);
}
$res[] = array('message'=>$messageList);
}

}else{
// comment
//echo 'no data';
}

}
//print_r($res); exit;
$res = array('Result'=>'Success','data'=>$res);
echo json_encode($res);exit;
}*/

function getSavedList($userId)
{

$db = getConnection();  #establish Db connection SELECT DISTINCT Country FROM Customers

$sql = "SELECT DISTINCT messageId from ah_favourite WHERE userId = $userId ";
$qryPrepare = $db->prepare($sql);
$qryPrepare->execute();
$message_ids = $qryPrepare->fetchAll(PDO::FETCH_OBJ);

$final_result = array();

foreach($message_ids as $msg){

//echo $msg->messageId;

    $sql = "SELECT Count(*) AS count from ah_favourite WHERE messageId = $msg->messageId AND type = 1";
    $qryPrepare = $db->prepare($sql);
    $qryPrepare->execute();
    $is_comment = $qryPrepare->fetch(PDO::FETCH_OBJ);
//print_r($is_comment);exit;
    if($is_comment->count == 1){

        $getFav = "SELECT favouriteId from ah_favourite WHERE messageId = $msg->messageId AND type = 1";
        $qryPrepare = $db->prepare($getFav);
        $qryPrepare->execute();
        $fav = $qryPrepare->fetch(PDO::FETCH_OBJ);

        $sql = "SELECT a.id as messageid,a.userId,a.created_at,
        a.estTime,a.message,
        a.likeCount,a.unlikeCount,a.status,ah_customer.firstName,ah_customer.lastName,
        ah_customer.agileCertification,ifnull(cat.favouriteId,0) as favouriteId,ifnull(cat.type,0) as type ,
        ah_customer.email,
        ah_customer.profilePicture,ah_customer.mobile,
        ah_customer.title,ah_customer.company,

        (select count(message) from ah_message where userId=ah_customer.id) as msgcount,
        (select count(comment) from ah_comment where userId=ah_customer.id) as cmdcount,
        ifnull((select type from ah_messagelikes where userId=$userId and messageId= a.id),0) as useroption ,
        ah_comment.isColor
        FROM ah_message a
        INNER JOIN ah_comment ON a.userId=ah_comment.userId
        INNER JOIN ah_customer ON a.userId=ah_customer.id
        LEFT JOIN (SELECT * from ah_favourite where favouriteId = $fav->favouriteId) as cat
        on a.id = cat.messageId
        where  a.id = $msg->messageId
        ORDER BY a.created_at DESC";
        $qryPrepare = $db->prepare($sql);
        $qryPrepare->execute();
        $message_list = $qryPrepare->fetch(PDO::FETCH_OBJ);
//echo json_encode($res);

        $reply_sql = "SELECT * from ah_comment WHERE messageId = $msg->messageId";
        $replyPrepare = $db->prepare($reply_sql);
        $replyPrepare->execute();
        $reply_list = $replyPrepare->fetchAll(PDO::FETCH_OBJ);

        $comments_array = array();

        foreach($reply_list as $reply){
//print_r($reply); exit;
            $comments_sql = "SELECT ah_customer.firstName,
            ah_customer.lastName,ah_customer.agileCertification,ah_customer.email,
            ah_customer.profilePicture,
            ah_customer.mobile,ah_customer.title,ah_customer.company,
            a.id as commentId,ifnull(cat.favouriteId,0) as favouriteId,ifnull(cat.type,0) as type,
            a.reply,a.created_at,
            a.comment,a.likeCount,a.unlikeCount,
            a.created_at,a.estTime,
            (select count(message) from ah_message where userId=ah_customer.id) as msgcounts,
            (select count(comment) from ah_comment where userId=ah_customer.id) as cmdcounts,

            ifnull((select type from ah_commentlikes where userId=$userId and messageId=a.id),0) as useroption
            FROM ah_comment a
            INNER JOIN ah_customer ON a.userId = ah_customer.id
            LEFT JOIN (SELECT * from ah_favourite where favouriteId = $fav->favouriteId) as cat
            on a.id = cat.messageId
            where a.messageId =$msg->messageId 
            ORDER BY a.estTime DESC";

            $qryPrepare = $db->prepare($comments_sql);
            $qryPrepare->execute();
            $comments_list = $qryPrepare->fetchAll(PDO::FETCH_OBJ);
//echo json_encode($res);

            /*Newly addded*/
            if(count($comments_list)>0)
            {
                for($i=0;$i<count($comments_list);$i++)
                {
                    $id = $comments_list[$i]->reply;

                    $qryReply = "SELECT firstName ,lastName from ah_customer
                    where id = (SELECT userId from ah_comment where id= $id)";

                    $qry = $db->prepare($qryReply);
                    $qry->execute();
                    $qryResult = $qry->fetchAll(PDO::FETCH_OBJ);

                    if(count($qryResult)>0)
                    {
                        $name = $qryResult[0]->firstName.' '.$qryResult[0]->lastName;

                    }
                    else
                    {
                        $name = "";
                    }
/*echo $comments_list[$i]->firstName;
print_r($comments_list);exit;*/
$comments_list[$i]->replyName = $name;

}
}
/*For commends of commend*/
$comments_array[] = $comments_list;
}

/// get Comments


$final_result[] = array(
    'message' => $message_list,
    'commands' => $comments_array
);

}else{

    $getFav = "SELECT favouriteId from ah_favourite WHERE messageId = $msg->messageId";
    $qryPrepare = $db->prepare($getFav);
    $qryPrepare->execute();
    $fav = $qryPrepare->fetch(PDO::FETCH_OBJ);

    $get_msg_sql = "SELECT messageId FROM ah_comment WHERE id = $msg->messageId";
    $qryPrepare = $db->prepare($get_msg_sql);
    $qryPrepare->execute();
    $get_msg_single = $qryPrepare->fetch(PDO::FETCH_OBJ);

    $sql = "SELECT a.id as messageid,a.userId,a.created_at,
    a.estTime,a.message,
    a.likeCount,a.unlikeCount,a.status,ah_customer.firstName,ah_customer.lastName,
    ah_customer.agileCertification,ifnull(cat.favouriteId,0) as favouriteId,ifnull(cat.type,0) as type ,
    ah_customer.email,
    ah_customer.profilePicture,ah_customer.mobile,
    ah_customer.title,ah_customer.company,

    (select count(message) from ah_message where userId=ah_customer.id) as msgcount,
    (select count(comment) from ah_comment where userId=ah_customer.id) as cmdcount,
    ifnull((select type from ah_messagelikes where userId=$userId and messageId= a.id),0) as useroption ,
    ah_comment.isColor
    FROM ah_message a
    INNER JOIN ah_comment ON a.userId=ah_comment.userId
    INNER JOIN ah_customer ON a.userId=ah_customer.id
    LEFT JOIN (SELECT * from ah_favourite where favouriteId = $fav->favouriteId) as cat
    on a.id = cat.messageId
    where  a.id = $get_msg_single->messageId
    ORDER BY a.created_at DESC";
    $qryPrepare = $db->prepare($sql);
    $qryPrepare->execute();
    $message_list = $qryPrepare->fetch(PDO::FETCH_OBJ);

//print_r($message_list); exit;

    $reply_sql = "SELECT * from ah_favourite WHERE messageId = $msg->messageId";
    $replyPrepare = $db->prepare($reply_sql);
    $replyPrepare->execute();
    $reply_list = $replyPrepare->fetchAll(PDO::FETCH_OBJ);

    $comments_array = array();

    foreach($reply_list as $reply){
//print_r($reply); exit;
        $comments_sql = "SELECT ah_customer.firstName,
        ah_customer.lastName,ah_customer.agileCertification,ah_customer.email,
        ah_customer.profilePicture,
        ah_customer.mobile,ah_customer.title,ah_customer.company,
        a.id as commentId,ifnull(cat.favouriteId,0) as favouriteId,ifnull(cat.type,0) as type,
        a.reply,a.created_at,
        a.comment,a.likeCount,a.unlikeCount,
        a.created_at,a.estTime,
        (select count(message) from ah_message where userId=ah_customer.id) as msgcounts,
        (select count(comment) from ah_comment where userId=ah_customer.id) as cmdcounts,

        ifnull((select type from ah_commentlikes where userId=$userId and messageId=a.id),0) as useroption
        FROM ah_comment a
        INNER JOIN ah_customer ON a.userId = ah_customer.id
        LEFT JOIN (SELECT * from ah_favourite where favouriteId = $reply->favouriteId) as cat
        on a.id = cat.messageId
        where a.messageId =$msg->messageId 
        ORDER BY a.estTime DESC";

        $qryPrepare = $db->prepare($comments_sql);
        $qryPrepare->execute();
        $comments_list = $qryPrepare->fetchAll(PDO::FETCH_OBJ);
//echo json_encode($res);

        /*Newly addded*/
        if(count($comments_list)>0)
        {
            for($i=0;$i<count($comments_list);$i++)
            {
                $id = $comments_list[$i]->reply;

                $qryReply = "SELECT firstName ,lastName from ah_customer
                where id = (SELECT userId from ah_comment where id= $id)";

                $qry = $db->prepare($qryReply);
                $qry->execute();
                $qryResult = $qry->fetchAll(PDO::FETCH_OBJ);

                if(count($qryResult)>0)
                {
                    $name = $qryResult[0]->firstName.' '.$qryResult[0]->lastName;

                }
                else
                {
                    $name = "";
                }
/*echo $comments_list[$i]->firstName;
print_r($comments_list);exit;*/
$comments_list[$i]->replyName = $name;

}
}
/*For commends of commend*/
$comments_array[] = $comments_list;
}

/// get Comments

$final_result[] = array(
    'message' => $message_list,
    'commands' => $comments_array
);

} // End of Else condition

}

$res = array('Result'=>'Success',
             'Status'=>'1',
             'data'=>$final_result);
echo json_encode($res);exit;
echo json_encode($final_result);exit;
exit;



print_r($results); 
$res = array('Result'=>'Success',
             'Status'=>'1',
             'data'=>$finalResult);
echo json_encode($res);exit;


}


function getFavourite($id)
{

$db = getConnection();  #establish Db connection
$complete = array();
$res = array();
$userReport = array();
$userId = $id;


$updateFavourite = "UPDATE ah_favourite SET status = 1 WHERE userId = $id";  
$qryPrepar = $db->prepare($updateFavourite);
$qryPrepar->execute();


$getQuery = "SELECT distinct tmp.tempMessageId from (SELECT * from ah_favourite order by favouriteId desc) as tmp where tmp.userId = $id order by tmp.favouriteId desc";

$qryPrepare = $db->prepare($getQuery);
$qryPrepare->execute();
$uniqMessage = $qryPrepare->fetchAll(PDO::FETCH_OBJ);



foreach($uniqMessage as $uniq)#all Msg
{  

    $getMsg = "SELECT * from ah_favourite 
    where tempMessageId = $uniq->tempMessageId and userId = $userId";

    $qryMsgPrepare = $db->prepare($getMsg);
    $qryMsgPrepare->execute();
$uniqMsgMessage = $qryMsgPrepare->fetch(PDO::FETCH_OBJ);#return Particlar Msg


if($uniqMsgMessage)
{

    if($uniqMsgMessage->type == 1)
    {


        $favArray = array();

#post command and all reply
        $queryMostRecent = "SELECT a.id as messageid,a.userId,a.created_at,
        a.estTime,a.message,
        a.likeCount,a.unlikeCount,a.status,ah_customer.firstName,ah_customer.lastName,
        ah_customer.agileCertification,ifnull(cat.favouriteId,0) as favouriteId,ifnull(cat.type,0) as type ,ifnull(cat.isColor,0) as isColor,
        ah_customer.email,
        ah_customer.profilePicture,ah_customer.mobile,
        ah_customer.title,ah_customer.company,
        (select count(message) from ah_message where userId=ah_customer.id) as msgcount,
        (select count(comment) from ah_comment where userId=ah_customer.id) as cmdcount,
        ifnull((select type from ah_messagelikes where userId=$uniqMsgMessage->userId and messageId= a.id),0) as useroption 
        FROM ah_message a
        left JOIN ah_comment ON a.userId=ah_comment.userId
        INNER JOIN ah_customer ON a.userId=ah_customer.id
        LEFT JOIN (SELECT * from ah_favourite where favouriteId = $uniqMsgMessage->favouriteId) as cat
        on a.id = cat.messageId
        where  a.id = $uniqMsgMessage->messageId
        ORDER BY a.created_at DESC";

        $stmt = $db->prepare($queryMostRecent);
        $stmt->execute();
        $value = $stmt->fetch(PDO::FETCH_OBJ); 
#$isColor = $uniqMsgMessage->isColor;
        $isColor = $value->isColor;


        if(count($value) > 0)
        {

            $queryMostRecent = "SELECT ah_customer.firstName,
            ah_customer.lastName,ah_customer.agileCertification,ah_customer.email,
            ah_customer.profilePicture,
            ah_customer.mobile,ah_customer.title,ah_customer.company,
            a.id as commentId,ifnull(cat.favouriteId,0) as favouriteId,ifnull(cat.type,0) as type,
            a.reply,a.created_at,
            a.comment,a.likeCount,a.unlikeCount,
            a.created_at,a.estTime,
            (select count(message) from ah_message where userId=ah_customer.id) as msgcounts,
            (select count(comment) from ah_comment where userId=ah_customer.id) as cmdcounts,

            ifnull((select type from ah_commentlikes where userId=$userId and messageId=a.id),0) as useroption
            FROM ah_comment a
            INNER JOIN ah_customer ON a.userId = ah_customer.id
            LEFT JOIN (SELECT * from ah_favourite ) as cat
            on a.id = cat.messageId
            where a.messageId =$value->messageid 
            ORDER BY a.estTime DESC";


            $stmt = $db->prepare($queryMostRecent);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_OBJ);

            /*Newly addded*/
            if(count($results)>0)
            {
                for($i=0;$i<count($results);$i++)
                {
                    $id = $results[$i]->reply;

                    $qryReply = "SELECT *,
                    (select count(message) from ah_message where userId=$id ) as msgcounts,
                    (select count(comment) from ah_comment where userId=$id ) as cmdcounts from ah_customer
                    where id = (SELECT userId from ah_comment where id= $id)";

                    $qry = $db->prepare($qryReply);
                    $qry->execute();
                    $qryResult = $qry->fetchAll(PDO::FETCH_OBJ);

                    if(count($qryResult)>0)
                    {
                        $name = $qryResult[0]->firstName.' '.$qryResult[0]->lastName;
                        $replyCertifications = $qryResult[0]->agileCertification;
                        $replyJobTitle = $qryResult[0]->title;
                        $replyCompany = $qryResult[0]->company;
                        $replyPicture = $qryResult[0]->profilePicture;
                        $replyCmdCount = $qryResult[0]->cmdcounts;
                        $replyMsgCount = $qryResult[0]->msgcounts;


                    }
                    else
                    {
                        $name = "";
                        $replyCertifications = "";
                        $replyJobTitle = "";
                        $replyCompany = "";
                        $replyPicture = "";
                        $replyCmdCount = "";
                        $replyMsgCount = "";
                    }
/*echo $results[$i]->firstName;
print_r($results);exit;*/
$results[$i]->replyName = $name;
$results[$i]->replyCertifications = $replyCertifications;
$results[$i]->replyJobTitle = $replyJobTitle;
$results[$i]->replyCompany = $replyCompany;
$results[$i]->replyPicture = $replyPicture;
$results[$i]->replyCmdCount = $replyCmdCount;
$results[$i]->replyMsgCount = $replyMsgCount;

}


}
$final = $results; 
/*For commends of commend*/


}

}
#if Pending else
else
{



    $queryMostRecent = "SELECT a.id as messageid,a.userId,a.created_at,
    a.estTime,a.message,
    a.likeCount,a.unlikeCount,a.status,ah_customer.firstName,ah_customer.lastName,
    ah_customer.agileCertification,ifnull(cat.favouriteId,0) as favouriteId,ifnull(cat.type,0) as type ,ifnull(cat.isColor,0) as isColor,
    ah_customer.email,
    ah_customer.profilePicture,ah_customer.mobile,
    ah_customer.title,ah_customer.company,

    (select count(message) from ah_message where userId=ah_customer.id) as msgcount,
    (select count(comment) from ah_comment where userId=ah_customer.id) as cmdcount,
    ifnull((select type from ah_messagelikes where userId=$uniqMsgMessage->userId and messageId= a.id),0) as useroption FROM ah_message a
    INNER JOIN ah_customer  ON a.userId=ah_customer.id
    LEFT JOIN (SELECT * from ah_favourite where favouriteId = $uniqMsgMessage->favouriteId) as cat
    on a.id = cat.messageId
    where  a.id = $uniqMsgMessage->tempMessageId
    ORDER BY a.estTime DESC";
    $stmt = $db->prepare($queryMostRecent);
    $stmt->execute();
    $value = $stmt->fetch(PDO::FETCH_OBJ); 
    $isColor = $uniqMsgMessage->isColor;

    $fav = "SELECT * from ah_favourite where 
    userId = $userId and tempMessageId = $uniqMsgMessage->tempMessageId and type=2";

    $favPrepare = $db->prepare($fav);
    $favPrepare->execute();
    $favResult = $favPrepare->fetchAll(PDO::FETCH_OBJ);


    if(count($favResult) > 0)
    {

        $favArray = array();
        foreach ($favResult as $favour) 
        {
# code...


            $queryMostRecent = "SELECT ah_customer.firstName,
            ah_customer.lastName,ah_customer.agileCertification,ah_customer.email,
            ah_customer.profilePicture,
            ah_customer.mobile,ah_customer.title,ah_customer.company,
            a.id as commentId,ifnull(cat.favouriteId,0) as favouriteId,ifnull(cat.type,0) as type,
            a.reply,
            a.comment,a.likeCount,a.unlikeCount,
            a.created_at,a.estTime,

            (select count(message) from ah_message where userId=ah_customer.id) as msgcounts,
            (select count(comment) from ah_comment where userId=ah_customer.id) as cmdcounts,

            ifnull((select type from ah_commentlikes 
            where userId=$favour->userId and messageId=a.id),0) as useroption
            FROM ah_comment a
            INNER JOIN ah_customer ON a.userId = ah_customer.id
            LEFT JOIN (SELECT * from ah_favourite where favouriteId = $favour->favouriteId) as cat
            on a.id = cat.messageId
            where a.id = $favour->messageId 
            ORDER BY a.estTime DESC";

            $stmt = $db->prepare($queryMostRecent);
            $stmt->execute();
            $results = $stmt ->fetch(PDO::FETCH_OBJ);


#Newly addded
            if(count($results)>0)
            {

                $id = $results->reply;
                $qryReply = "SELECT firstName ,lastName from ah_customer
                where id = (SELECT userId from ah_comment where id= $userId)";
                $qry = $db->prepare($qryReply);
                $qry->execute();
                $qryResult = $qry->fetch(PDO::FETCH_OBJ);



                if(count($qryResult)>0)
                {
                    $name = $qryResult->firstName.' '.$qryResult->lastName;

                }
                else
                {
                    $name = "";
                }

                $results->replyName = $name;

            }
            $favArray [] = $results;


        }
        $final = $favArray;
    }
    else
    {
        $final = [];
    }

}#else End




$userReport[]=array(
    'isColor'=> $isColor,
    'messageId'=> $value->messageid,
    'agileCertification'=>$value->agileCertification,
    'favouriteId'=>$value->favouriteId,
    'type'=>$value->type,
    'msgcount'=>$value->msgcount,
    'cmdcount'=>$value->cmdcount,
    'userId'=>$value->userId,'useroption'=>$value->useroption,
    'created_at'=> $value->created_at,
    'estTime'=>$value->estTime,
    'message'=>$value->message,
    'likeCount'=>$value->likeCount,
    'unlikeCount'=>$value->unlikeCount,'status'=>$value->status,
    'firstName'=>$value->firstName,'lastName'=>$value->lastName,
    'email'=>$value->email,'profilePicture'=>$value->profilePicture,
    'mobile'=>$value->mobile,'title'=>$value->title,
    'company'=>$value->company,
    "commands"=>$final
);


}


}




#array_multisort($userReport);
$res = array('Result'=>'Success',
             'Status'=>'',          
             'messageDetails'=>$userReport);

echo json_encode($res);exit;





}

function getFavouriteBackup($id)
{

$db = getConnection();  #establish Db connection

$getQuery = "SELECT * from ah_favourite WHERE userId = $id ORDER BY created_at DESC";
$qryPrepare = $db->prepare($getQuery);
$qryPrepare->execute();
$results = $qryPrepare->fetchAll(PDO::FETCH_OBJ);
$complete = array();
$res = array();

$updateFavourite = "UPDATE ah_favourite SET status = 1 WHERE userId = $id";  
//"UPDATE ah_favourite SET lastname='Doe' WHERE id=2";
$qryPrepare = $db->prepare($updateFavourite);
$qryPrepare->execute();
foreach($results as $data)
{
    $userReport = array();

    if($data->type == 1)
    {
#post command and all reply


// most recent
        $queryMostRecent = "SELECT a.id as messageid,a.userId,a.created_at,
        a.estTime,a.message,
        a.likeCount,a.unlikeCount,a.status,ah_customer.firstName,ah_customer.lastName,
        ah_customer.agileCertification,ifnull(cat.favouriteId,0) as favouriteId,ifnull(cat.type,0) as type ,
        ah_customer.email,
        ah_customer.profilePicture,ah_customer.mobile,
        ah_customer.title,ah_customer.company,

        (select count(message) from ah_message where userId=ah_customer.id) as msgcount,
        (select count(comment) from ah_comment where userId=ah_customer.id) as cmdcount,
        ifnull((select type from ah_messagelikes where userId=$data->userId and messageId= a.id),0) as useroption ,
        ah_comment.isColor
        FROM ah_message a
        INNER JOIN ah_comment ON a.userId=ah_comment.userId
        INNER JOIN ah_customer ON a.userId=ah_customer.id
        LEFT JOIN (SELECT * from ah_favourite where favouriteId = $data->favouriteId) as cat
        on a.id = cat.messageId
        where  a.id = $data->messageId
        ORDER BY a.created_at DESC";
        $stmt = $db->prepare($queryMostRecent);
        $stmt->execute();
        $value = $stmt->fetch(PDO::FETCH_OBJ);

        if(count($value) > 0)
        {

            $queryMostRecent = "SELECT ah_customer.firstName,
            ah_customer.lastName,ah_customer.agileCertification,ah_customer.email,
            ah_customer.profilePicture,
            ah_customer.mobile,ah_customer.title,ah_customer.company,
            a.id as commentId,ifnull(cat.favouriteId,0) as favouriteId,ifnull(cat.type,0) as type,
            a.reply,a.created_at,
            a.comment,a.likeCount,a.unlikeCount,
            a.created_at,a.estTime,
            (select count(message) from ah_message where userId=ah_customer.id) as msgcounts,
            (select count(comment) from ah_comment where userId=ah_customer.id) as cmdcounts,

            ifnull((select type from ah_commentlikes where userId=$data->userId and messageId=a.id),0) as useroption
            FROM ah_comment a
            INNER JOIN ah_customer ON a.userId = ah_customer.id
            LEFT JOIN (SELECT * from ah_favourite where favouriteId = $data->favouriteId) as cat
            on a.id = cat.messageId
            where a.messageId =$value->messageid 
            ORDER BY a.estTime DESC";

            $stmt = $db->prepare($queryMostRecent);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_OBJ);

            /*Newly addded*/
            if(count($results)>0)
            {
                for($i=0;$i<count($results);$i++)
                {
                    $id = $results[$i]->reply;

                    $qryReply = "SELECT firstName ,lastName from ah_customer
                    where id = (SELECT userId from ah_comment where id= $id)";

                    $qry = $db->prepare($qryReply);
                    $qry->execute();
                    $qryResult = $qry->fetchAll(PDO::FETCH_OBJ);

                    if(count($qryResult)>0)
                    {
                        $name = $qryResult[0]->firstName.' '.$qryResult[0]->lastName;

                    }
                    else
                    {
                        $name = "";
                    }
/*echo $results[$i]->firstName;
print_r($results);exit;*/
$results[$i]->replyName = $name;

}
}
/*For commends of commend*/


}

}


else
{ 
#if User Saved 
    $userReport = array();
    /*get Message if using replyid*/

    $qryReply = "SELECT * from ah_comment where id = $data->messageId";
    $qryReplyPrepare = $db->prepare($qryReply);
    $qryReplyPrepare->execute();
    $replyDatas = $qryReplyPrepare->fetchAll(PDO::FETCH_OBJ);

    foreach($replyDatas as $replyData)
    {
        $queryMostRecent = "SELECT a.id as messageid,a.userId,a.created_at,
        a.estTime,a.message,
        a.likeCount,a.unlikeCount,a.status,ah_customer.firstName,ah_customer.lastName,
        ah_customer.agileCertification,ifnull(cat.favouriteId,0) as favouriteId,ifnull(cat.type,0) as type ,
        ah_customer.email,
        ah_customer.profilePicture,ah_customer.mobile,
        ah_customer.title,ah_customer.company,

        (select count(message) from ah_message where userId=ah_customer.id) as msgcount,
        (select count(comment) from ah_comment where userId=ah_customer.id) as cmdcount,
        ifnull((select type from ah_messagelikes where userId=$data->userId and messageId= a.id),0) as useroption FROM ah_message a
        INNER JOIN ah_customer  ON a.userId=ah_customer.id
        LEFT JOIN (SELECT * from ah_favourite where favouriteId = $data->favouriteId) as cat
        on a.id = cat.messageId
        where  a.id = $replyData->messageId
        ORDER BY a.estTime DESC";
        $stmt = $db->prepare($queryMostRecent);
        $stmt->execute();
        $value = $stmt->fetch(PDO::FETCH_OBJ); 
        $userReport = array();        
        if(count($value) > 0)
        {
            $queryMostRecent = "SELECT ah_customer.firstName,
            ah_customer.lastName,ah_customer.agileCertification,ah_customer.email,
            ah_customer.profilePicture,
            ah_customer.mobile,ah_customer.title,ah_customer.company,
            a.id as commentId,ifnull(cat.favouriteId,0) as favouriteId,ifnull(cat.type,0) as type,
            a.reply,
            a.comment,a.likeCount,a.unlikeCount,
            a.created_at,a.estTime,

            (select count(message) from ah_message where userId=ah_customer.id) as msgcounts,
            (select count(comment) from ah_comment where userId=ah_customer.id) as cmdcounts,

            ifnull((select type from ah_commentlikes where userId=$data->userId and messageId=a.id),0) as useroption
            FROM ah_comment a
            INNER JOIN ah_customer ON a.userId = ah_customer.id
            LEFT JOIN (SELECT * from ah_favourite where favouriteId = $data->favouriteId) as cat
            on a.id = cat.messageId
            where a.id = $data->messageId 
            ORDER BY a.estTime DESC";

            $stmt = $db->prepare($queryMostRecent);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_OBJ);

#Newly addded
            if(count($results)>0)
            {
                for($i=0;$i<count($results);$i++)
                {
                    $id = $results[$i]->reply;

                    $qryReply = "SELECT firstName ,lastName from ah_customer
                    where id = (SELECT userId from ah_comment where id= $id)";

                    $qry = $db->prepare($qryReply);
                    $qry->execute();
                    $qryResult = $qry->fetchAll(PDO::FETCH_OBJ);

                    if(count($qryResult)>0)
                    {
                        $name = $qryResult[0]->firstName.' '.$qryResult[0]->lastName;

                    }
                    else
                    {
                        $name = "";
                    }

                    $results[$i]->replyName = $name;

                }
            }
#For commends of commend


        }
    }
/* $userReport[]=array(
'messageId'=> $value->messageid,
'agileCertification'=>$value->agileCertification,
'favouriteId'=>$value->favouriteId,
'type'=>$value->type,
'msgcount'=>$value->msgcount,
'cmdcount'=>$value->cmdcount,
'userId'=>$value->userId,'useroption'=>$value->useroption,
'created_at'=> $value->created_at,
'estTime'=>$value->estTime,'message'=>$value->message,

'likeCount'=>$value->likeCount,'unlikeCount'=>$value->unlikeCount,'status'=>$value->status,
'firstName'=>$value->firstName,'lastName'=>$value->lastName,
'email'=>$value->email,'profilePicture'=>$value->profilePicture,
'mobile'=>$value->mobile,'title'=>$value->title,'company'=>$value->company,
'commands'=>$results
);*/

}

$qryReplyaa = "SELECT * from ah_comment where messageId = $data->messageId AND userId = $data->userId AND isColor = 1";
$qryReplyPreparea = $db->prepare($qryReplyaa);
$qryReplyPreparea->execute();
$replyDataa = $qryReplyPreparea->fetchAll(PDO::FETCH_OBJ);
//print_r($replyDataa); //exit;
//echo 'yes -'.count($replyData);
// if($replyData){
//   if($replyData->id){
//     $isColor = 1;
//   }else{
//     $isColor = 0;
//   }
// }else{
//     $isColor = 0;
//   }
$isColor = 0;
foreach($replyDataa as $rd){
    $isColor = $rd->isColor; 
}

$userReport[]=array(
    'isColor'=> $isColor,
    'messageId'=> $value->messageid,
    'agileCertification'=>$value->agileCertification,
    'favouriteId'=>$value->favouriteId,
    'type'=>$value->type,
    'msgcount'=>$value->msgcount,
    'cmdcount'=>$value->cmdcount,
    'userId'=>$value->userId,'useroption'=>$value->useroption,
    'created_at'=> $value->created_at,
    'estTime'=>$value->estTime,'message'=>$value->message,

    'likeCount'=>$value->likeCount,'unlikeCount'=>$value->unlikeCount,'status'=>$value->status,
    'firstName'=>$value->firstName,'lastName'=>$value->lastName,
    'email'=>$value->email,'profilePicture'=>$value->profilePicture,
    'mobile'=>$value->mobile,'title'=>$value->title,'company'=>$value->company,
    'commands'=>$results
);

$complete[] = $userReport;
}
$ct  = count($complete);

//print_r($complete); echo '<br>';
array_multisort($complete);
//print_r($complete); echo '<br>';

$res = array('Result'=>'Success',
             'Status'=>'',
             'ct'=>$ct,
             'messageDetails'=>$complete);
//echo '{ "Result" : "Success","ct" :'.json_encode($ct).',"messageDetails":'.json_encode($complete).'}';exit;
echo json_encode($res);exit;
}

function clearCount($id)
{
    /*Not Used*/
    $db = getConnection();
    $clear ="update ah_notification set isRead = 1 where userId =$id";

    $stm = $db->prepare($clear);
    $stm->execute();

    $res = array('Result'=>'Success');
    echo json_encode($res);exit;

}
function notifyCount($id)
{
    /*Not Used*/
    $db = getConnection();
    $clear ="SELECT count(*) as inbox from ah_notification 
    where isRead = 0 and commanderId != 0 and userId =$id 
    ORDER BY notificationId DESC";

    $sql ="SELECT ah_notification.*,ah_customer.firstName,lastName,profilePicture 
    from ah_notification
    inner join ah_customer on ah_customer.id=ah_notification.commanderId
    where ah_notification.userId=:id 
    and ah_notification.isRead = 0
    order by ah_notification.notificationTime desc";
//echo $sql; exit;
    /*$sql="select * from ah_notification where userId=:id";*/
    $stmt = $db->prepare($clear);
    $stmt->bindParam("id", $id);
    $stmt->execute();
    $notifyCount = $stmt->fetchAll(PDO::FETCH_OBJ);

    $favouriteCount ="SELECT count(*) as savedMessages from ah_favourite where userId =$id AND status = 0";

    $stm = $db->prepare($clear);
    $stm->execute();
    $results = $stm->fetch(PDO::FETCH_OBJ);

    $stm = $db->prepare($favouriteCount);
    $stm->execute();
    $result = $stm->fetch(PDO::FETCH_OBJ);

    $res = array('Result'=>'Success',"inbox"=>$results->inbox,"savedCount"=>$result->savedMessages);
    echo json_encode($res);exit;

}

function fromNotication($noftifyId)
{
    $db = getConnection();
    $queryNotify = "SELECT * from ah_notification where notificationId=$noftifyId";
    $ntf = $db->prepare($queryNotify);
    $ntf->execute();
    $data = $ntf->fetch(PDO::FETCH_OBJ);





    $queryMostRecent = "SELECT a.id as messageid,a.userId,a.created_at,a.estTime,a.message,
    a.likeCount,a.unlikeCount,a.status,ah_customer.firstName,ah_customer.lastName,
    ah_customer.agileCertification,
    ah_customer.email,
    ah_customer.profilePicture,ah_customer.mobile,
    ah_customer.title,ah_customer.company,
    a.id as commentId,
    ifnull(cat.favouriteId,0) as favouriteId,ifnull(cat.type,0) as type,

    (select count(message) from ah_message where userId=ah_customer.id) as msgcount,
    (select count(comment) from ah_comment where userId=ah_customer.id) as cmdcount,

    ifnull((select type from ah_messagelikes where userId=$data->userId and messageId= a.id),0)
    as useroption
    FROM ah_message a
    INNER JOIN ah_customer  ON a.userId=ah_customer.id 
    LEFT JOIN (SELECT * from ah_favourite where userId = $data->userId) as cat
    on a.id = cat.messageId
    where  a.id = $data->messageId 
    ORDER BY a.created_at DESC";

    $stmt = $db->prepare($queryMostRecent);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_OBJ);


    $userReport = array();
    foreach ($result as $value)
    {
        $queryMostRecent = "SELECT ah_customer.firstName,
        ah_customer.lastName,ah_customer.agileCertification,ah_customer.email,
        ah_customer.profilePicture,
        ah_customer.mobile,ah_customer.title,ah_customer.company,

        a.id as commentId,
        ifnull(cat.favouriteId,0) as favouriteId,ifnull(cat.type,0) as type,
        a.reply,
        a.comment,a.likeCount,a.unlikeCount,
        a.created_at,a.estTime,
        (select count(message) from ah_message where userId=ah_customer.id) as msgcounts,
        (select count(comment) from ah_comment where userId=ah_customer.id) as cmdcounts,

        ifnull((select type from ah_commentlikes where
        userId=$data->userId and messageId=a.id),0) as useroption
        FROM ah_comment a INNER JOIN ah_customer
        ON a.userId = ah_customer.id
        LEFT JOIN (SELECT * from ah_favourite where userId = $data->userId) as cat
        on a.id = cat.messageId
        where a.messageId =$value->messageid ORDER BY a.created_at DESC";

        $stmt = $db->prepare($queryMostRecent);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_OBJ);




        if(count($results)>0)
        {
            for($i=0;$i<count($results);$i++)
            {
                $id = $results[$i]->reply;

                $qryReply = "SELECT *,
                (select count(message) from ah_message where userId=$id ) as msgcounts,
                (select count(comment) from ah_comment where userId=$id ) as cmdcounts from ah_customer
                where id = (SELECT userId from ah_comment where id= $id)";

                $qry = $db->prepare($qryReply);
                $qry->execute();
                $qryResult = $qry->fetchAll(PDO::FETCH_OBJ);

                if(count($qryResult)>0)
                {
                    $name = $qryResult[0]->firstName.' '.$qryResult[0]->lastName;
                    $replyCertifications = $qryResult[0]->agileCertification;
                    $replyJobTitle = $qryResult[0]->title;
                    $replyCompany = $qryResult[0]->company;
                    $replyPicture = $qryResult[0]->profilePicture;
                    $replyCmdCount = $qryResult[0]->cmdcounts;
                    $replyMsgCount = $qryResult[0]->msgcounts;


                }
                else
                {
                    $name = "";
                    $replyCertifications = "";
                    $replyJobTitle = "";
                    $replyCompany = "";
                    $replyPicture = "";
                    $replyCmdCount = "";
                    $replyMsgCount = "";
                }
/*echo $results[$i]->firstName;
print_r($results);exit;*/
$results[$i]->replyName = $name;
$results[$i]->replyCertifications = $replyCertifications;
$results[$i]->replyJobTitle = $replyJobTitle;
$results[$i]->replyCompany = $replyCompany;
$results[$i]->replyPicture = $replyPicture;
$results[$i]->replyCmdCount = $replyCmdCount;
$results[$i]->replyMsgCount = $replyMsgCount;

}
}
}
/*For commends of commend*/

$userReport[]=array('messageId'=> $value->messageid,
                    'favouriteId'=>$value->favouriteId,
                    'type'=>$value->type,
                    'agileCertification'=>$value->agileCertification,
                    'msgcount'=>$value->msgcount,
                    'cmdcount'=>$value->cmdcount,
                    'userId'=>$value->userId,'useroption'=>$value->useroption,
                    'created_at'=> $value->created_at,
                    'estTime'=>$value->estTime,
                    'message'=>$value->message,
//'totalcount'=>$value->totalcount,
                    'likeCount'=>$value->likeCount,
                    'unlikeCount'=>$value->unlikeCount,'status'=>$value->status,
                    'firstName'=>$value->firstName,'lastName'=>$value->lastName,
                    'email'=>$value->email,'profilePicture'=>$value->profilePicture,
                    'mobile'=>$value->mobile,'title'=>$value->title,'company'=>$value->company,
                    'commands'=>$results);


$clearNotify = "UPDATE ah_notification set isRead =1 where notificationId=$noftifyId";
$ntfs = $db->prepare($clearNotify);
$ntfs->execute();


$res = array('Result'=>'Success',
             'Status'=>'',
             'messageDetails'=>$userReport);
echo json_encode($res);exit;

}

function clearNotify($id)
{
    $db = getConnection();
    $clear ="delete from ah_notification where notificationId =$id";

    $stm = $db->prepare($clear);
    $stm->execute();

    $res = array('Result'=>'Success');
    echo json_encode($res);exit;

}
function sendmail()
{
    $request = Slim::getInstance()->request();
    $mailData = json_decode($request->getBody());


    $mail = new PHPMailer();
$mail->IsSMTP(); // enable SMTP
$mail->SMTPAuth = true; // authentication enabled
$mail->SMTPSecure = 'ssl'; // secure transfer enabled REQUIRED for Gmail
$mail->Host = "smtp.gmail.com";
$mail->Port = 465; // or 587
$mail->IsHTML(true);
$mail->Username = 'codekhadimail@gmail.com';//'codekhadimail@gmail.com';
$mail->Password = '!@#qweasd';//'!@#qweasd';
$mail->From = $mailData->name; //Default From email same as smtp user
$mail->FromName = "agilehealth";
$mail->AddAddress($mailData->email, '');
$mail->CharSet = 'UTF-8';
$mail->Subject = $mailData->message;
$mail->MsgHTML($mailData->message);


if($mail->Send())
{
    $res = array('Result'=>'Success',
                 'Status'=>'Email has been sent Successfully');
    echo json_encode($res); exit;
//echo '{ "Result": "Success","Status":"Password reset link has been sent to your mail"}';
}
else
{
    $res = array('Result'=>'Failed',
                 'Status'=>'Email sent failed');
    echo json_encode($res);
    exit;


}
}
function getAnswerAvg($id)
{

    $db = getConnection();

    $sql ="SELECT categoryId,answerId,avg,created_at from ah_answer where userId =$id
    and  answerId = (select max(answerId) from ah_answer where categoryId =1 and userId =$id)
    or   answerId = (select max(answerId) from ah_answer where categoryId =2 and userId =$id)
    or   answerId = (select max(answerId) from ah_answer where categoryId =3 and userId =$id)
    or   answerId = (select max(answerId) from ah_answer where categoryId =4 and userId =$id)
    or   answerId = (select max(answerId) from ah_answer where categoryId =5 and userId =$id)
    order by categoryId asc";
    /*$sql="select * from ah_notification where userId=:id";*/
    $stmt = $db->prepare($sql);

    $stmt->execute();
    $avg = $stmt->fetchAll(PDO::FETCH_OBJ);

    $res = array('Result'=>'Success',
                 'answer'=>$avg);

    echo json_encode($res);exit;


}


function getAnswer($userId,$categoryId)
{
    try
    {
// update Device Token

        $db = getConnection();
        $sql ="select *  from ah_answer
        where userId=:userId and categoryId=:categoryId order by created_at  desc";
        /*$sql="select * from ah_notification where userId=:id";*/
        $stmt = $db->prepare($sql);
        $stmt->bindParam("userId", $userId);
        $stmt->bindParam("categoryId", $categoryId);
        $stmt->execute();
        $notify = $stmt->fetchAll(PDO::FETCH_OBJ);

        $res = array('Result'=>'Success',
                     'answer'=>$notify);

        echo json_encode($res);exit;

    }
    catch(PDOException $e)
    {
        echo $e;
        $res = array('Result'=>'Failed');
        echo json_encode($res); exit;
    }


}

function insertAnswer()
{
/*
0-N/A   1-Yes   2-No
*/
$request = Slim::getInstance()->request();
$data = json_decode($request->getBody());

$db = getConnection();
$date =date("Y-m-d H:i:s");
$sql1="insert into  ah_answer(userId,categoryId,q1,q2,q3,q4,q5,q6,q7,q8,q9,q10,q11,q12,q13,q14,q15,avg,created_at,updated_at)
values(:userId,:categoryId,:q1,:q2,:q3,:q4,:q5,:q6,:q7,:q8,:q9,:q10,:q11,:q12,:q13,:q14,:q15,:avg,'$date','$date')";
$stmt = $db->prepare($sql1);
$stmt->bindParam("userId", $data->userId);
$stmt->bindParam("categoryId", $data->categoryId);
$stmt->bindParam("q1", $data->q1);
$stmt->bindParam("q2", $data->q2);
$stmt->bindParam("q3", $data->q3);
$stmt->bindParam("q4", $data->q4);
$stmt->bindParam("q5", $data->q5);
$stmt->bindParam("q6", $data->q6);
$stmt->bindParam("q7", $data->q7);
$stmt->bindParam("q8", $data->q8);
$stmt->bindParam("q9", $data->q9);
$stmt->bindParam("q10", $data->q10);
$stmt->bindParam("q11", $data->q11);
$stmt->bindParam("q12", $data->q12);
$stmt->bindParam("q13", $data->q13);
$stmt->bindParam("q14", $data->q14);
$stmt->bindParam("q15", $data->q15);
$stmt->bindParam("avg", $data->avg);
$stmt->execute();

$res = array('Result'=>'Success');

echo json_encode($res);exit;

}



function clearCache($id)
{
    $db = getConnection();
#delte answer
    $sql ="delete from ah_answer
    where userId=:userId";
    $stmt = $db->prepare($sql);
    $stmt->bindParam("userId", $id);
    $stmt->execute();
#del Notifiaction
// $sqlNoti ="delete from ah_notification
//     where userId=:userId";
// $stmtNoti = $db->prepare($sqlNoti);
// $stmtNoti->bindParam("userId", $id);
// $stmtNoti->execute();
#del fav
// $sqlNoti ="delete from ah_favourite
//     where userId=:userId";
// $stmtNoti = $db->prepare($sqlNoti);
// $stmtNoti->bindParam("userId", $id);
// $stmtNoti->execute();


    $res = array('Result'=>'Success');
    echo json_encode($res);exit;

}



function myNotification($id)
{
    $request = Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    try
    {
// update Device Token

        $db = getConnection();
        $sql ="select ah_notification.*,ah_customer.firstName,lastName,profilePicture from ah_notification
        inner join ah_customer on ah_customer.id=ah_notification.commanderId
        where ah_notification.userId=:id and commanderId != 0 order by ah_notification.notificationTime desc";
//echo $sql; exit;
        /*$sql="select * from ah_notification where userId=:id";*/
        $stmt = $db->prepare($sql);
        $stmt->bindParam("id", $id);
        $stmt->execute();
        $notify = $stmt->fetchAll(PDO::FETCH_OBJ);




        $res = array('Result'=>'Success',
                     'notification'=>$notify);

        echo json_encode($res);exit;

    }
    catch(PDOException $e)
    {
        echo $e;
        $res = array('Result'=>'Failed');
        echo json_encode($res); exit;
    }
}
function updateDevice()
{
    $request = Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    try
    {
// update Device Token

        $db = getConnection();
        $sql="update ah_customer set deviceToken=:token where id=:id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam("id", $data->id);
        $stmt->bindParam("token", $data->deviceToken);
        $stmt->execute();


        $res = array('Result'=>'Success',
                     'Status'=>'Device Token  successfully Updated');

        echo json_encode($res);exit;

    }
    catch(PDOException $e)
    {
        echo $e;
        $res = array('Result'=>'Failed');
        echo json_encode($res); exit;
    }

}


function totalCount($id)
{
    $db = getConnection();
    $queryMostRecent = "SELECT (select count(message) from ah_message where userId=$id) as msgcount,
    (select count(comment) from ah_comment where userId=$id) as cmdcount";
    $stmt = $db->prepare($queryMostRecent);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_OBJ);

    $res = array('Result'=>'Success',
                 'Status'=>'message posted successfully',
                 'messageDetails'=>$result);
    echo json_encode($res);exit;

}


function send_gcm_notify($devicetoken,$message,$id,$environment,$key,$badge,$image,$name,$sound,$type="sec",$nameMe="",$replyId=0)
{

    if (!defined('FIREBASE_API_KEY')) define("FIREBASE_API_KEY", "AAAABkz3JCU:APA91bG9LYGCpvwOa3ZpP4DuwzXXf1HHGolWHBQd_tLFPl-VY1FtiS9JujYOOeX0Z4rMig5Nf8VNXZVSWp0aVLtHQM1DqyMB_MwMFQE2xDf4PIn_ErnRemMeoG9O05-po2Y--sWOctQI");
        if (!defined('FIREBASE_FCM_URL')) define("FIREBASE_FCM_URL", "https://fcm.googleapis.com/fcm/send");

#$me = html_entity_decode($message,ENT_HTML5);
            $fields = array(
                'to' => $devicetoken ,
                'priority' => "high",
                'notification' => array( "tag"=>"chat", "title"=>$nameMe,"body" =>$message,"priority"=>"high","id"=> $id,"key"=>$key,
                                        "badge"=>$badge,"image"=>$image,"name"=>$name,"sound"=>$sound,"type"=>$type,"replyId"=>$replyId),
            );
// echo "<br>";
//json_encode($fields);
//echo "<br>";
            $headers = array(
                'Authorization: key=' . FIREBASE_API_KEY,
                'Content-Type: application/json'
            );
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, FIREBASE_FCM_URL);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

            $result = curl_exec($ch);

            if ($result === FALSE)
            {
                die('Problem occurred: ' . curl_error($ch));
            }
            curl_close($ch);

        }

        function replyLikeupdate()
        {

            $request = Slim::getInstance()->request();
            $data = json_decode($request->getBody());
            try{
// starts
                $db = getConnection();

                $getdata = "select * from ah_customer where id=:id";
                $stmt = $db->prepare($getdata);
                $stmt->bindParam("id", $data->userId);
                $stmt->execute();
                $userdet = $stmt->fetch(PDO::FETCH_OBJ);
                if($userdet){
                    $get = "select * from ah_comment where id=:commentId";
                    $stmt = $db->prepare($get);
                    $stmt->bindParam("commentId", $data->commentId);
                    $stmt->execute();
                    $det = $stmt->fetch(PDO::FETCH_OBJ);
                    if($det){
                        $getl = "select * from ah_commentlikes where messageId=:commentId and userId=:userId";
                        $stmt = $db->prepare($getl);
                        $stmt->bindParam("commentId", $data->commentId);
                        $stmt->bindParam("userId", $data->userId);
                        $stmt->execute();
                        $likedet = $stmt->fetch(PDO::FETCH_OBJ);
                        if($likedet){
                            if($data->type==1){
                                if($likedet->type==1){
                                    $sql="update ah_comment set likeCount=likeCount-1 where id=:commentId";
                                    $stmt = $db->prepare($sql);
                                    $stmt->bindParam("commentId", $data->commentId);
                                    $stmt->execute();
                                    $sql1="delete from ah_commentlikes where messageId=:commentId and userId=:userId";
                                    $stmt = $db->prepare($sql1);
                                    $stmt->bindParam("commentId", $data->commentId);
                                    $stmt->bindParam("userId", $data->userId);
                                    $stmt->execute();
                                }else{
                                    $sql="update ah_comment set likeCount=likeCount+2 where id=:commentId";
                                    $stmt = $db->prepare($sql);
                                    $stmt->bindParam("commentId", $data->commentId);
                                    $stmt->execute();
                                    $sql1="update ah_commentlikes set type=1 where messageId=:commentId and userId=:userId";
                                    $stmt = $db->prepare($sql1);
                                    $stmt->bindParam("commentId", $data->commentId);
                                    $stmt->bindParam("userId", $data->userId);
                                    $stmt->execute();
                                }
                            }else{
                                if($likedet->type==1){
                                    $sql="update ah_comment set likeCount=likeCount-2 where id=:commentId";
                                    $stmt = $db->prepare($sql);
                                    $stmt->bindParam("commentId", $data->commentId);
                                    $stmt->execute();
                                    $sql1="update ah_commentlikes set type=2 where messageId=:commentId and userId=:userId";
                                    $stmt = $db->prepare($sql1);
                                    $stmt->bindParam("commentId", $data->commentId);
                                    $stmt->bindParam("userId", $data->userId);
                                    $stmt->execute();
                                }else{
                                    $sql="update ah_comment set likeCount=likeCount+1 where id=:commentId";
                                    $stmt = $db->prepare($sql);
                                    $stmt->bindParam("commentId", $data->commentId);
                                    $stmt->execute();
                                    $sql1="delete from ah_commentlikes where messageId=:commentId and userId=:userId";
                                    $stmt = $db->prepare($sql1);
                                    $stmt->bindParam("commentId", $data->commentId);
                                    $stmt->bindParam("userId", $data->userId);
                                    $stmt->execute();
                                }
                            }
                        }else{
                            if($data->type==1){
                                $sql="update ah_comment set likeCount=likeCount+1 where id=:commentId";
                                $stmt = $db->prepare($sql);
                                $stmt->bindParam("commentId", $data->commentId);
                                $stmt->execute();
                                $sql1="insert into  ah_commentlikes(userId,messageId,type) values(:userId,:commentId,1)";
                                $stmt = $db->prepare($sql1);
                                $stmt->bindParam("commentId", $data->commentId);
                                $stmt->bindParam("userId", $data->userId);
                                $stmt->execute();
                            }else{
                                $sql="update ah_comment set likeCount=likeCount-1 where id=:commentId";
                                $stmt = $db->prepare($sql);
                                $stmt->bindParam("commentId", $data->commentId);
                                $stmt->execute();

                                $sql1="insert into  ah_commentlikes(userId,messageId,type) values(:userId,:commentId,2)";
                                $stmt = $db->prepare($sql1);
                                $stmt->bindParam("commentId", $data->commentId);
                                $stmt->bindParam("userId", $data->userId);
                                $stmt->execute();
                            }
                        }
                        $get = "select * ,ifnull((select type from ah_commentlikes where userId=:userId and messageId=:messageId),0) as useroption from ah_comment where id=:commentId";
                        $stmt = $db->prepare($get);
                        $stmt->bindParam("userId", $data->userId);
                        $stmt->bindParam("messageId", $data->commentId);
                        $stmt->bindParam("commentId", $data->commentId);
                        $stmt->execute();
                        $det = $stmt->fetch(PDO::FETCH_OBJ);

                        $res = array('Result'=>'Success','details'=>$det);
                        echo json_encode($res); exit;
                    }else{
                        $res = array('Result'=>'Failed',
                                     'message'=>'Comment not found');
                        echo json_encode($res); exit;
                    }
                }else{
                    $res = array('Result'=>'Failed',
                                 'message'=>'Customer not found');
                    echo json_encode($res); exit;
                }
            }catch(PDOException $e){
                echo $e;
                $res = array('Result'=>'Failed');
                echo json_encode($res); exit;
            }
        }

        function commandsCount()
        {
            $request = Slim::getInstance()->request();
            $data = json_decode($request->getBody());
            $categoryId = $data->categoryId;
            try
            {
                $db = getConnection();
$getdata = "select count(*) as commentCount,questionId from ah_message where categoryId =:categoryId group by questionId";#check Where Cusomer in table
$stmt = $db->prepare($getdata);
//$stmt->bindParam("questionId", $questionId);
$stmt->bindParam("categoryId", $categoryId);
$stmt->execute();
$userdet = $stmt->fetchAll(PDO::FETCH_OBJ);
$res = array('Result'=>'Success',
             'details'=>$userdet);
echo json_encode($res);exit;
}
catch(PDOException $e)
{
    echo $e;
    $res = array('Result'=>'Failed');
    echo json_encode($res); exit;
}

}

function commentLikeupdate(){

    $request = Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    try{
// starts
        $db = getConnection();

$getdata = "select * from ah_customer where id=:id";#check Where Cusomer in table
$stmt = $db->prepare($getdata);
$stmt->bindParam("id", $data->userId);
$stmt->execute();
$userdet = $stmt->fetch(PDO::FETCH_OBJ);
if($userdet)# if customer is Availabele
{
$get = "select * from ah_message where id=:messageId";#check the Message is found in DB
$stmt = $db->prepare($get);
$stmt->bindParam("messageId", $data->messageId);
$stmt->execute();
$det = $stmt->fetch(PDO::FETCH_OBJ);
if($det)#if message
{
$getl = "select * from ah_messagelikes where messageId=:messageId and userId=:userId"; #1 and 1
$stmt = $db->prepare($getl);
$stmt->bindParam("messageId", $data->messageId);
$stmt->bindParam("userId", $data->userId);
$stmt->execute();
$likedet = $stmt->fetch(PDO::FETCH_OBJ);
if($likedet){
    if($data->type==1){
        if($likedet->type==1){
            $sql="update ah_message set likeCount=likeCount-1 where id=:messageId";
            $stmt = $db->prepare($sql);
$stmt->bindParam("messageId", $data->messageId);#messsge Id 1
$stmt->execute();
$sql1="delete from ah_messagelikes where messageId=:messageId and userId=:userId";
$stmt = $db->prepare($sql1);
$stmt->bindParam("messageId", $data->messageId);
$stmt->bindParam("userId", $data->userId);
$stmt->execute();
}else{
    $sql="update ah_message set likeCount=likeCount+2 where id=:messageId";
    $stmt = $db->prepare($sql);
$stmt->bindParam("messageId", $data->messageId);#messsge Id 1
$stmt->execute();
$sql1="update ah_messagelikes set type=1 where messageId=:messageId and userId=:userId";
$stmt = $db->prepare($sql1);
$stmt->bindParam("messageId", $data->messageId);
$stmt->bindParam("userId", $data->userId);
$stmt->execute();
}
}else{
    if($likedet->type==1){
        $sql="update ah_message set likeCount=likeCount-2 where id=:messageId";
        $stmt = $db->prepare($sql);
$stmt->bindParam("messageId", $data->messageId);#messsge Id 1
$stmt->execute();
$sql1="update ah_messagelikes set type=2 where messageId=:messageId and userId=:userId";
$stmt = $db->prepare($sql1);
$stmt->bindParam("messageId", $data->messageId);
$stmt->bindParam("userId", $data->userId);
$stmt->execute();
}else{
    $sql="update ah_message set likeCount=likeCount+1 where id=:messageId";
    $stmt = $db->prepare($sql);
$stmt->bindParam("messageId", $data->messageId);#messsge Id 1
$stmt->execute();
$sql1="delete from ah_messagelikes where messageId=:messageId and userId=:userId";
$stmt = $db->prepare($sql1);
$stmt->bindParam("messageId", $data->messageId);
$stmt->bindParam("userId", $data->userId);
$stmt->execute();
}
}
}else
{
    if($data->type==1)
    {
        $sql="update ah_message set likeCount=likeCount+1 where id=:messageId";
        $stmt = $db->prepare($sql);
        $stmt->bindParam("messageId", $data->messageId);
        $stmt->execute();
        $sql1="insert into  ah_messagelikes(userId,messageId,type) values(:userId,:messageId,1)";
        $stmt = $db->prepare($sql1);
        $stmt->bindParam("messageId", $data->messageId);
        $stmt->bindParam("userId", $data->userId);
        $stmt->execute();
    }
    else
    {
        $sql="update ah_message set likeCount=likeCount-1 where id=:messageId";
        $stmt = $db->prepare($sql);
        $stmt->bindParam("messageId", $data->messageId);
        $stmt->execute();
        $sql1="insert into  ah_messagelikes(userId,messageId,type) values(:userId,:messageId,2)";
        $stmt = $db->prepare($sql1);
        $stmt->bindParam("messageId", $data->messageId);
        $stmt->bindParam("userId", $data->userId);
        $stmt->execute();
    }
}
$get = "select *,ifnull((select type from ah_messagelikes where userId=:userId and messageId= :messageId),0) as useroption from ah_message  where id=:messageId";#check the Message is found in DB
$stmt = $db->prepare($get);
$stmt->bindParam("userId", $data->userId);
$stmt->bindParam("messageId", $data->messageId);
$stmt->execute();
$det = $stmt->fetch(PDO::FETCH_OBJ);

$res = array('Result'=>'Success','details'=>$det);
echo json_encode($res); exit;
}
else
{
    $res = array('Result'=>'Failed',
                 'message'=>'Comment not found');
    echo json_encode($res); exit;
}
}else{
    $res = array('Result'=>'Failed',
                 'message'=>'Customer not found');
    echo json_encode($res); exit;
}
}catch(PDOException $e){
    echo $e;
    $res = array('Result'=>'Failed');
    echo json_encode($res); exit;
}
}


function updatePassword()
{

    $request = Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    try{
// starts

        $db = getConnection();

        $getdata = "select * from ah_customer where id=:id";
        $stmt = $db->prepare($getdata);
        $stmt->bindParam("id", $data->id);
        $stmt->execute();
        $userdet = $stmt->fetch(PDO::FETCH_OBJ);
        if($userdet)
        {
            if($userdet->type == 1)
            {
                $key = hash('sha256', '!@#123');
                $iv = substr(hash('sha256', 'as12345'), 0, 16);
                $output = openssl_encrypt($data->oldpassword, "AES-256-CBC", $key, 0, $iv);
                $opassword = base64_encode($output);

                $getpwd = "select * from ah_customer where id=:id and password=:password";
                $stmt = $db->prepare($getpwd);
                $stmt->bindParam("id", $data->id);
                $stmt->bindParam("password", $opassword);
                $stmt->execute();
                $pwddet = $stmt->fetch(PDO::FETCH_OBJ);
                if($pwddet)
                {
                    $key = hash('sha256', '!@#123');
                    $iv = substr(hash('sha256', 'as12345'), 0, 16);
                    $output = openssl_encrypt($data->newpassword, "AES-256-CBC", $key, 0, $iv);
                    $npassword = base64_encode($output);


                }
                else
                {
                    $res = array('Result'=>'Failed',
                                 'message'=>'Old password does not match');
                    echo json_encode($res); exit;
                }

            }
            else
            {
                $key = hash('sha256', '!@#123');
                $iv = substr(hash('sha256', 'as12345'), 0, 16);
                $output = openssl_encrypt($data->newpassword, "AES-256-CBC", $key, 0, $iv);
                $npassword = base64_encode($output);

            }
            $setpwd = "UPDATE ah_customer 
            set password=:password,type=1 
            where id=:id";
            $stmt = $db->prepare($setpwd);
            $stmt->bindParam("id", $data->id);
            $stmt->bindParam("password", $npassword);
            $stmt->execute();

            $res = array('Result'=>'Success');
            echo json_encode($res); exit;
        }


        else
        {
            $res = array('Result'=>'Failed',
                         'message'=>'Customer not found');
            echo json_encode($res); exit;
        }
    }
    catch(PDOException $e)
    {
        echo $e;
        $res = array('Result'=>'Failed');
        echo json_encode($res); exit;
    }
}




function insertComment()
{

    $request = Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $date=date("Y-m-d H:i:s");
    try{
// starts
        $reply = ($data->reply ==""?0:$data->reply);
        $db = getConnection();


        if($data->isColor == 1)
        {
            $isColorQry = "UPDATE ah_favourite set isColor=1 where  
            tempMessageId= $data->messageId and userId= $data->userId";
            $colQry = $db->prepare($isColorQry);
            $colQry->execute();
        }

        $insertQuery = "INSERT into ah_comment
        (userId,messageId,comment,reply,created_at,updated_at,estTime)
        values(:userId,:messageId,:comment,:reply,'$date','$date',:estTime)";
        $stmt = $db->prepare($insertQuery);
        $stmt->bindParam("userId", $data->userId);
        $stmt->bindParam("messageId", $data->messageId);
        $stmt->bindParam("comment", $data->comment);
        $stmt->bindParam("reply", $data->reply);
        $stmt->bindParam("estTime", $data->estTime);
        $stmt->execute();

/* if replies is 0 then get the message id and get user info
update these information on notifucation table */
$getQuery = "SELECT * FROM ah_comment ORDER BY id DESC LIMIT 1";
$stmt = $db->prepare($getQuery);
$stmt->execute();
$userdet = $stmt->fetch(PDO::FETCH_OBJ);
$res = array('Result'=>'Success',
             'Status'=>'message posted successfully',
             'userDetails'=>$userdet);
/*If reply of reply then Notification will send*/
$key="new Commend";
if($reply)
{

    $getQuery = "SELECT * FROM ah_customer
    where id = (select userId from ah_comment where id = $data->reply) LIMIT 1";
    $stmt = $db->prepare($getQuery);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_OBJ);

    $replyQuery = "SELECT * from ah_comment where id = $data->reply LIMIT 1";
    $reqry = $db->prepare($replyQuery);
    $reqry->execute();
    $replyqry = $reqry->fetch(PDO::FETCH_OBJ);



    $getQueryid = "SELECT * FROM ah_message
    where id = $data->messageId";
    $qry = $db->prepare($getQueryid);
    $qry->execute();

    $qryData = $qry->fetch(PDO::FETCH_OBJ);

    $post = $replyqry->comment;
    $questionId = $qryData->questionId;
    $categoryId = $qryData->categoryId;

    $userId = $user->id;
    $commanderId = $data->userId;
    $replyTo = $data->reply;
    $deviceToken = $user->deviceToken;


}
else
{


    $getQueryid = "SELECT ah_message.categoryId,ah_message.userid,ah_message.questionId,ah_message.message,
    ah_customer.deviceToken,ah_customer.firstName,ah_customer.lastName,ah_customer.profilePicture
    from ah_message
    inner join ah_customer on ah_customer.id = ah_message.userid
    where ah_message.id = $data->messageId";


    $qry = $db->prepare($getQueryid);
    $qry->execute();
    $qryData = $qry->fetch(PDO::FETCH_OBJ);



    $questionId = $qryData->questionId;
    $categoryId = $qryData->categoryId;
    $userId = $qryData->userid;
    $deviceToken = $qryData->deviceToken;
    $post = $qryData->message;



    $notificationTime = $data->estTime;
    $commanderId = $data->userId;
    $replyTo = "";

}
$getUser = "SELECT * from ah_customer
where id = $commanderId ";


$qryUser = $db->prepare($getUser);
$qryUser->execute();
$qryUserData = $qryUser->fetch(PDO::FETCH_OBJ);

$name = $qryUserData->firstName." ".$qryUserData->lastName;
$image = $qryUserData->profilePicture;

$notificationTitle =$key;
#$fcode = html_entity_decode("<b>yes</b> :",ENT_HTML5,"UTF-8");
$notificationDesc = $data->comment;
$notificationTime = $data->estTime;
$messageId = $data->messageId;


if($userId != $commanderId)
{

    $replyQuery = "SELECT * from ah_comment where userId = $data->userId AND messageId = $data->messageId ORDER BY id DESC LIMIT 1";
    $reqry = $db->prepare($replyQuery);
    $reqry->execute();
    $replyqry = $reqry->fetch(PDO::FETCH_OBJ);


    $notify = "insert into ah_notification
    (userId,message,notificationTitle,notificationDesc,notificationTime,commanderId,replyTo,currentReplyId,messageId,isRead,
        questionId,categoryId,created_at,updated_at)
        values(:userId,:message,:notificationTitle,:notificationDesc,:notificationTime,:commanderId,:replyTo,:currentReplyId,:messageId,'0',
        :questionId,:categoryId,'$date','$date')";


        $st = $db->prepare($notify);
        $st->bindParam("userId",$userId);
        $st->bindParam("message",$post);
        $st->bindParam("notificationTitle",$notificationTitle);
        $st->bindParam("notificationDesc", $notificationDesc);
        $st->bindParam("notificationTime",$notificationTime);
        $st->bindParam("commanderId", $commanderId);
        $st->bindParam("replyTo", $replyTo);
        $st->bindParam("currentReplyId", $replyqry->id);
        $st->bindParam("messageId", $messageId);
        $st->bindParam("questionId", $questionId);
        $st->bindParam("categoryId", $categoryId);
        $st->execute();
        $replyIds = $replyqry->id;
        $getCount = "select count(*) as batch from ah_notification where isRead = 0 and userId = $userId";
        $qryCount = $db->prepare($getCount);
        $qryCount->execute();
        $qryCountData = $qryCount->fetch(PDO::FETCH_OBJ);

        $badge = $qryCountData->batch;
        $sound ="NotificationTune.mp3";


        $getUserMe = "SELECT * from ah_customer
        where id = $commanderId";

        $qryUserMe = $db->prepare($getUserMe);
        $qryUserMe->execute();
        $qryUserDataMe = $qryUserMe->fetch(PDO::FETCH_OBJ);
        $nameMe = $qryUserDataMe->firstName." ".$qryUserDataMe->lastName;


        $notificationDecode = json_decode('"'.$data->comment.'"');
        send_gcm_notify($deviceToken,$notificationDecode,$questionId,$categoryId,$notificationTitle,$badge,$image,$name,$sound,"sec",$nameMe,$replyIds);

    }
    echo json_encode($res); exit;

}catch(PDOException $e){
    echo $e;
    $res = array('Result'=>'Failed');
    echo json_encode($res); exit;
}
}


function parking_lot_search($userId,$searchValue)
{
# code...
    $sql = "SELECT a.id as messageid,a.userId,a.created_at,a.estTime,a.message,a.likeCount,
    a.unlikeCount,a.status,
    ah_customer.firstName,ah_customer.lastName,
    ah_customer.agileCertification,ah_customer.email,ah_customer.profilePicture,
    ah_customer.mobile,
    ah_customer.title,ah_customer.company,
    ifnull(cat.favouriteId,0) as favouriteId,ifnull(cat.type,0) as type ,

    (select count(message) from ah_message where userId=ah_customer.id) as msgcount,
    (select count(comment) from ah_comment where userId=ah_customer.id) as cmdcount,
    (select count(*) from ah_message

    where categoryId=0 and questionId=0 and created_at between '2017-10-03 00:00:00' and '2017-10-04 00:00:00') as totalcount,
    ifnull((select type from ah_messagelikes where userId=$userId and messageId=a.id),0) as useroption FROM ah_message a
    INNER JOIN ah_customer  ON a.userId = ah_customer.id
    LEFT JOIN (SELECT * from ah_favourite where userId = $userId) as cat
    on a.id = cat.messageId

    where a.message LIKE '%$searchValue%' ORDER BY a.created_at DESC limit 20";

    $stmt = $db->prepare($queryMostRecent);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_OBJ);
    echo json_encode($result);
}


function parkingLot()
{

    $request = Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    try{
        $db = getConnection();

        $users_sql = "SELECT * FROM ah_customer";
        $stmt = $db->prepare($users_sql);
        $stmt->execute();
        $users_list = $stmt->fetchAll(PDO::FETCH_OBJ);
//print_r($users_list); exit;
        $likeKey = '';



        if($data->searchValue)
        {
            $likeKey = $data->searchValue;
        }

if(date("H:i:s")>"06:00:00") #Current time is Greater then 6 AM
{
$date1=date("Y-m-d H:i:s",strtotime($data->searchDate." 00:00:00")); #Search Start date 6am
$date2=date("Y-m-d H:i:s",strtotime("+1 day".$date1)); #Search Date +1
}
else
{
$date2=date("Y-m-d H:i:s",strtotime($data->searchDate." 23:59:59")); #End with Start Date
$date1=date("Y-m-d H:i:s",strtotime("-1 day".$date2)); #Start with Yester Day

}

$start = $data->type*20;
// if($data->searchValue){
//   $start = 1000;
// }

$end= 10;
if($data->sortingType == 1)
{

// most recent
    $queryMostRecent = "SELECT a.id as messageid,a.userId,a.created_at,a.estTime,a.message,a.likeCount,
    a.unlikeCount,a.status,
    ah_customer.firstName,ah_customer.lastName,
    ah_customer.agileCertification,ah_customer.email,ah_customer.profilePicture,
    ah_customer.mobile,
    ah_customer.title,ah_customer.company,
    ifnull(cat.favouriteId,0) as favouriteId,ifnull(cat.type,0) as type ,

    (select count(message) from ah_message where userId=ah_customer.id) as msgcount,
    (select count(comment) from ah_comment where userId=ah_customer.id) as cmdcount,
    (select count(*) from ah_message

    where categoryId=0 and questionId=0 and estTime between '$date1' and '$date2') as totalcount,
    ifnull((select type from ah_messagelikes where userId=$data->userId and messageId=a.id),0) as useroption FROM ah_message a
    INNER JOIN ah_customer  ON a.userId = ah_customer.id
    LEFT JOIN (SELECT * from ah_favourite where userId = $data->userId) as cat
    on a.id = cat.messageId

    where a.message LIKE '%".$likeKey."%' AND a.categoryId='0' and a.questionId='0' and a.estTime between '$date1' and '$date2'
    ORDER BY a.estTime DESC limit $start"; 

    $stmt = $db->prepare($queryMostRecent);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_OBJ);


    $notLike = "SELECT a.id as messageid,a.userId,a.created_at,a.estTime,a.message,a.likeCount,
    a.unlikeCount,a.status,
    ah_customer.firstName,ah_customer.lastName,
    ah_customer.agileCertification,ah_customer.email,ah_customer.profilePicture,
    ah_customer.mobile,
    ah_customer.title,ah_customer.company,
    ifnull(cat.favouriteId,0) as favouriteId,ifnull(cat.type,0) as type ,

    (select count(message) from ah_message where userId=ah_customer.id) as msgcount,
    (select count(comment) from ah_comment where userId=ah_customer.id) as cmdcount,
    (select count(*) from ah_message

    where categoryId=0 and questionId=0 and a.estTime between '$date1' and '$date2') as totalcount,
    ifnull((select type from ah_messagelikes where userId=$data->userId and messageId=a.id),0) as useroption FROM ah_message a
    INNER JOIN ah_customer  ON a.userId = ah_customer.id
    LEFT JOIN (SELECT * from ah_favourite where userId = $data->userId) as cat
    on a.id = cat.messageId

    where a.message NOT LIKE '%".$likeKey."%' AND a.categoryId='0' and a.questionId='0' and a.estTime between '$date1' and '$date2'
    ORDER BY a.estTime DESC limit $start";

    $stmt = $db->prepare($notLike);
    $stmt->execute();
    $resultNotLike = $stmt->fetchAll(PDO::FETCH_OBJ);

    $userReport = array();

    foreach ($resultNotLike as $value)
    {
        $queryMostRecent = "SELECT ah_customer.firstName,ah_customer.lastName,
        ah_customer.agileCertification,
        ah_customer.email,ah_customer.profilePicture,ah_customer.mobile,ah_customer.title,
        ah_customer.company,a.id as commentId,a.comment,a.likeCount,a.unlikeCount
        ,a.created_at,a.estTime,a.reply,
        ifnull(cat.favouriteId,0) as favouriteId,ifnull(cat.type,0) as type ,

        (select count(message) from ah_message where userId=ah_customer.id) as msgcounts,
        (select count(comment) from ah_comment where userId=ah_customer.id) as cmdcounts,

        ifnull((select type from ah_commentlikes where userId=$data->userId and messageId=a.id),0) as useroption
        FROM ah_comment a INNER JOIN ah_customer ON a.userId = ah_customer.id
        LEFT JOIN (SELECT * from ah_favourite where userId = $data->userId) as cat
        on a.id = cat.messageId
        where a.comment LIKE '%".$likeKey."%' AND a.messageId =$value->messageid order by a.estTime ASC";

        $stmt = $db->prepare($queryMostRecent);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_OBJ);



        /*Newly addded*/
        if(count($results)>0)
        {
            for($i=0;$i<count($results);$i++)
            {
                $id = $results[$i]->reply;

                $qryReply = "SELECT * from ah_customer
                where id = (SELECT userId from ah_comment where id= $id)";


                $qry = $db->prepare($qryReply);
                $qry->execute();
                $qryResult = $qry->fetchAll(PDO::FETCH_OBJ);

                if(count($qryResult)>0)
                {
                    $idss = $qryResult[0]->id;
                    $qryReplyMsg = "SELECT count(message) as replymsgcnt from ah_message where id=$idss";
                    $qryReplycmd = "SELECT count(comment) as replycmdcnt from ah_comment where id=$idss";

                    $qrymsg = $db->prepare($qryReplyMsg);
                    $qrymsg->execute();
                    $qryResultmsg = $qrymsg->fetch(PDO::FETCH_OBJ);

                    $qrycmd = $db->prepare($qryReplycmd);
                    $qrycmd->execute();
                    $qryResultcmd = $qrycmd->fetch(PDO::FETCH_OBJ);

                    $name = $qryResult[0]->firstName.' '.$qryResult[0]->lastName;
                    $jobtitle = $qryResult[0]->title;
                    $Company = $qryResult[0]->company;
                    $Certifications = $qryResult[0]->agileCertification;
                    $profilePicture = $qryResult[0]->profilePicture;
                    $msgCount = $qryResultmsg->replymsgcnt;
                    $cmdCount = $qryResultcmd->replycmdcnt;

                }
                else
                {
                    $name = "";
                    $jobtitle = "";
                    $Company = "";
                    $Certifications = "";
                    $profilePicture = "";
                    $msgCount = 0;
                    $cmdCount = 0;
                }
/*echo $results[$i]->firstName;
print_r($results);exit;*/
$results[$i]->replyName = $name;
$results[$i]->replyJobTitle = $jobtitle;
$results[$i]->replyCompany = $Company;
$results[$i]->replyCertifications = $Certifications;
$results[$i]->replyPicture = $profilePicture;
$results[$i]->replyMsgCount = $msgCount;
$results[$i]->replyCmdCount = $cmdCount;

}
$userReport[]=array('messageId'=> $value->messageid,'msgcount'=>$value->msgcount,
                    'agileCertification'=>$value->agileCertification,
                    'favouriteId'=>$value->favouriteId,
                    'type'=>$value->type,
                    'cmdcount'=>$value->cmdcount,
                    'userId'=>$value->userId,
                    'useroption'=>$value->useroption,'created_at'=> $value->created_at,
                    'estTime'=>$value->estTime,'message'=>$value->message,'totalcount'=>$value->totalcount,
                    'likeCount'=>$value->likeCount,'unlikeCount'=>$value->unlikeCount,'status'=>$value->status,
                    'firstName'=>$value->firstName,'lastName'=>$value->lastName,
                    'email'=>$value->email,'profilePicture'=>$value->profilePicture,
                    'mobile'=>$value->mobile,'title'=>$value->title,'company'=>$value->company,
                    'commands'=>$results);
}
/*For commends of commend*/






}

foreach ($result as $value)
{
    $queryMostRecent = "SELECT ah_customer.firstName,ah_customer.lastName,
    ah_customer.agileCertification,
    ah_customer.email,ah_customer.profilePicture,ah_customer.mobile,ah_customer.title,
    ah_customer.company,a.id as commentId,a.comment,a.likeCount,a.unlikeCount
    ,a.created_at,a.estTime,a.reply,
    ifnull(cat.favouriteId,0) as favouriteId,ifnull(cat.type,0) as type ,

    (select count(message) from ah_message where userId=ah_customer.id) as msgcounts,
    (select count(comment) from ah_comment where userId=ah_customer.id) as cmdcounts,

    ifnull((select type from ah_commentlikes where userId=$data->userId and messageId=a.id),0) as useroption
    FROM ah_comment a INNER JOIN ah_customer ON a.userId = ah_customer.id
    LEFT JOIN (SELECT * from ah_favourite where userId = $data->userId) as cat
    on a.id = cat.messageId
    where a.comment LIKE '%".$likeKey."%' AND a.messageId =$value->messageid order by a.estTime ASC";

    $stmt = $db->prepare($queryMostRecent);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_OBJ);



    /*Newly addded*/
    if(count($results)>0)
    {
        for($i=0;$i<count($results);$i++)
        {
            $id = $results[$i]->reply;

            $qryReply = "SELECT * from ah_customer
            where id = (SELECT userId from ah_comment where id= $id)";

            $qry = $db->prepare($qryReply);
            $qry->execute();
            $qryResult = $qry->fetchAll(PDO::FETCH_OBJ);

            if(count($qryResult)>0)
            {
                $idss = $qryResult[0]->id;
                $qryReplyMsg = "SELECT count(message) as replymsgcnt from ah_message where id=$idss";
                $qryReplycmd = "SELECT count(comment) as replycmdcnt from ah_comment where id=$idss";

                $qrymsg = $db->prepare($qryReplyMsg);
                $qrymsg->execute();
                $qryResultmsg = $qrymsg->fetch(PDO::FETCH_OBJ);

                $qrycmd = $db->prepare($qryReplycmd);
                $qrycmd->execute();
                $qryResultcmd = $qrycmd->fetch(PDO::FETCH_OBJ);

                $name = $qryResult[0]->firstName.' '.$qryResult[0]->lastName;
                $jobtitle = $qryResult[0]->title;
                $Company = $qryResult[0]->company;
                $Certifications = $qryResult[0]->agileCertification;
                $profilePicture = $qryResult[0]->profilePicture;
                $msgCount = $qryResultmsg->replymsgcnt;
                $cmdCount = $qryResultcmd->replycmdcnt;

            }
            else
            {
                $name = "";
                $jobtitle = "";
                $Company = "";
                $Certifications = "";
                $profilePicture = "";
                $msgCount = 0;
                $cmdCount = 0;
            }
/*echo $results[$i]->firstName;
print_r($results);exit;*/
$results[$i]->replyName = $name;
$results[$i]->replyJobTitle = $jobtitle;
$results[$i]->replyCompany = $Company;
$results[$i]->replyCertifications = $Certifications;
$results[$i]->replyPicture = $profilePicture;
$results[$i]->replyMsgCount = $msgCount;
$results[$i]->replyCmdCount = $cmdCount;

}
}
/*For commends of commend*/





$userReport[]=array('messageId'=> $value->messageid,'msgcount'=>$value->msgcount,
                    'agileCertification'=>$value->agileCertification,
                    'favouriteId'=>$value->favouriteId,
                    'type'=>$value->type,
                    'cmdcount'=>$value->cmdcount,
                    'userId'=>$value->userId,
                    'useroption'=>$value->useroption,'created_at'=> $value->created_at,
                    'estTime'=>$value->estTime,'message'=>$value->message,'totalcount'=>$value->totalcount,
                    'likeCount'=>$value->likeCount,'unlikeCount'=>$value->unlikeCount,'status'=>$value->status,
                    'firstName'=>$value->firstName,'lastName'=>$value->lastName,
                    'email'=>$value->email,'profilePicture'=>$value->profilePicture,
                    'mobile'=>$value->mobile,'title'=>$value->title,'company'=>$value->company,
                    'commands'=>$results);
}

$res = array('Result'=>'Success',
             'Status'=>'message posted successfully',
             'messageDetails'=>$userReport,
             'users_list'=>$users_list);
echo json_encode($res);exit;
}
else
{
// most liked
    $queryMostLiked = "SELECT a.id as messageid,a.userId,a.created_at as messagedate,
    a.estTime,a.message,a.likeCount,a.unlikeCount,a.status,ah_customer.firstName,ah_customer.lastName,
    ah_customer.agileCertification,
    ah_customer.email,ah_customer.profilePicture,ah_customer.mobile,
    ah_customer.title,ah_customer.company,
    ifnull(cat.favouriteId,0) as favouriteId,ifnull(cat.type,0) as type ,

    (select count(message) from ah_message where userId=ah_customer.id) as msgcount,
    (select count(comment) from ah_comment where userId=ah_customer.id) as cmdcount,
    (select count(*) from ah_message where categoryId=0 and questionId=0  and a.estTime between '$date1' and '$date2') as totalcount,
    ifnull((select type from ah_messagelikes where userId=$data->userId and messageId=a.id),0) as useroption FROM ah_message a
    INNER JOIN ah_customer
    ON a.userId=ah_customer.id
    LEFT JOIN (SELECT * from ah_favourite where userId = $data->userId) as cat
    on a.id = cat.messageId
    where a.message LIKE '%".$likeKey."%' AND a.categoryId='0' and a.questionId='0' and a.estTime between '$date1' and '$date2'  ORDER BY likeCount DESC limit $start";
//echo $queryMostLiked; exit;
    $stmt = $db->prepare($queryMostLiked);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_OBJ);

//print_r($result); exit;


    $notLike = "SELECT a.id as messageid,a.userId,a.created_at as messagedate,
    a.estTime,a.message,a.likeCount,a.unlikeCount,a.status,ah_customer.firstName,ah_customer.lastName,
    ah_customer.agileCertification,
    ah_customer.email,ah_customer.profilePicture,ah_customer.mobile,
    ah_customer.title,ah_customer.company,
    ifnull(cat.favouriteId,0) as favouriteId,ifnull(cat.type,0) as type ,

    (select count(message) from ah_message where userId=ah_customer.id) as msgcount,
    (select count(comment) from ah_comment where userId=ah_customer.id) as cmdcount,
    (select count(*) from ah_message where categoryId=0 and questionId=0  and created_at between '$date1' and '$date2') as totalcount,
    ifnull((select type from ah_messagelikes where userId=$data->userId and messageId=a.id),0) as useroption FROM ah_message a
    INNER JOIN ah_customer
    ON a.userId=ah_customer.id
    LEFT JOIN (SELECT * from ah_favourite where userId = $data->userId) as cat
    on a.id = cat.messageId
    where a.message NOT LIKE '%".$likeKey."%' AND a.categoryId='0' and a.questionId='0' and a.estTime between '$date1' and '$date2'  ORDER BY likeCount DESC limit $start";
//echo $queryMostLiked; exit;
    $stmt = $db->prepare($notLike);
    $stmt->execute();
    $resultNotLike = $stmt->fetchAll(PDO::FETCH_OBJ);

    $userReport = array();

    foreach ($resultNotLike as $value)
    {
        $queryMostRecent = "SELECT ah_customer.firstName,ah_customer.lastName,
        ah_customer.agileCertification,ah_customer.email,
        ah_customer.profilePicture,
        ah_customer.mobile,ah_customer.title,ah_customer.company,a.id as commentId,a.comment,a.likeCount,a.unlikeCount
        ,a.created_at,a.estTime,a.reply,
        ifnull(cat.favouriteId,0) as favouriteId,ifnull(cat.type,0) as type ,

        (select count(message) from ah_message where userId=ah_customer.id) as msgcounts,
        (select count(comment) from ah_comment where userId=ah_customer.id) as cmdcounts,

        ifnull((select type from ah_commentlikes where userId=$data->userId and messageId=a.id),0) as useroption FROM ah_comment a
        INNER JOIN ah_customer ON a.userId = ah_customer.id
        LEFT JOIN (SELECT * from ah_favourite where userId = $data->userId) as cat
        on a.id = cat.messageId
        where a.comment LIKE '%".$likeKey."%' AND a.messageId =$value->messageid order by likeCount desc";

//echo $queryMostRecent; exit;
        $stmt = $db->prepare($queryMostRecent);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_OBJ);

        /*Newly addded*/
        if(count($results)>0)
        {
            for($i=0;$i<count($results);$i++)
            {
                $id = $results[$i]->reply;

                $qryReply = "SELECT * from ah_customer where
                id = (SELECT userId from ah_comment where id= $id)";

                $qry = $db->prepare($qryReply);
                $qry->execute();
                $qryResult = $qry->fetchAll(PDO::FETCH_OBJ);

                if(count($qryResult)>0)
                {
                    $idss = $qryResult[0]->id;

                    $qryReplyMsg = "SELECT count(message) as replymsgcnt from ah_message where id=$idss";
                    $qryReplycmd = "SELECT count(comment) as replycmdcnt from ah_comment where id=$idss";

                    $qrymsg = $db->prepare($qryReplyMsg);
                    $qrymsg->execute();
                    $qryResultmsg = $qrymsg->fetch(PDO::FETCH_OBJ);

                    $qrycmd = $db->prepare($qryReplycmd);
                    $qrycmd->execute();
                    $qryResultcmd = $qrycmd->fetch(PDO::FETCH_OBJ);

                    $name = $qryResult[0]->firstName.' '.$qryResult[0]->lastName;
                    $jobtitle = $qryResult[0]->title;
                    $Company = $qryResult[0]->company;
                    $Certifications = $qryResult[0]->agileCertification;
                    $profilePicture = $qryResult[0]->profilePicture;
                    $msgCount = $qryResultmsg->replymsgcnt;
                    $cmdCount = $qryResultcmd->replycmdcnt;

                }
                else
                {
                    $name = "";
                    $jobtitle = "";
                    $Company = "";
                    $Certifications = "";
                    $profilePicture = "";
                    $msgCount = 0;
                    $cmdCount = 0;
                }

/*echo $results[$i]->firstName;
print_r($results);exit;*/
$results[$i]->replyName = $name;
$results[$i]->replyJobTitle = $jobtitle;
$results[$i]->replyCompany = $Company;
$results[$i]->replyCertifications = $Certifications;
$results[$i]->replyPicture = $profilePicture;
$results[$i]->replyMsgCount = $msgCount;
$results[$i]->replyCmdCount = $cmdCount;

}

$userReport[]=array('messageId'=> $value->messageid,'msgcount'=>$value->msgcount,
                    'agileCertification'=>$value->agileCertification,
                    'favouriteId'=>$value->favouriteId,
                    'type'=>$value->type,
                    'cmdcount'=>$value->cmdcount,
                    'userId'=>$value->userId,
'useroption'=>$value->useroption,//'created_at'=> $value->created_at,
'estTime'=>$value->estTime,'message'=>$value->message,'totalcount'=>$value->totalcount,
'likeCount'=>$value->likeCount,'unlikeCount'=>$value->unlikeCount,'status'=>$value->status,
'firstName'=>$value->firstName,'lastName'=>$value->lastName,
'email'=>$value->email,'profilePicture'=>$value->profilePicture,
'mobile'=>$value->mobile,'title'=>$value->title,'company'=>$value->company,
'commands'=>$results);
}
}
foreach ($result as $value)
{
    $queryMostRecent = "SELECT ah_customer.firstName,ah_customer.lastName,
    ah_customer.agileCertification,ah_customer.email,
    ah_customer.profilePicture,
    ah_customer.mobile,ah_customer.title,ah_customer.company,a.id as commentId,a.comment,a.likeCount,a.unlikeCount
    ,a.created_at,a.estTime,a.reply,
    ifnull(cat.favouriteId,0) as favouriteId,ifnull(cat.type,0) as type ,

    (select count(message) from ah_message where userId=ah_customer.id) as msgcounts,
    (select count(comment) from ah_comment where userId=ah_customer.id) as cmdcounts,

    ifnull((select type from ah_commentlikes where userId=$data->userId and messageId=a.id),0) as useroption FROM ah_comment a
    INNER JOIN ah_customer ON a.userId = ah_customer.id
    LEFT JOIN (SELECT * from ah_favourite where userId = $data->userId) as cat
    on a.id = cat.messageId
    where a.comment LIKE '%".$likeKey."%' AND a.messageId =$value->messageid order by likeCount desc";

//echo $queryMostRecent; exit;
    $stmt = $db->prepare($queryMostRecent);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_OBJ);

    /*Newly addded*/
    if(count($results)>0)
    {
        for($i=0;$i<count($results);$i++)
        {
            $id = $results[$i]->reply;

            $qryReply = "SELECT * from ah_customer where
            id = (SELECT userId from ah_comment where id= $id)";

            $qry = $db->prepare($qryReply);
            $qry->execute();
            $qryResult = $qry->fetchAll(PDO::FETCH_OBJ);

            if(count($qryResult)>0)
            {
                $idss = $qryResult[0]->id;

                $qryReplyMsg = "SELECT count(message) as replymsgcnt from ah_message where id=$idss";
                $qryReplycmd = "SELECT count(comment) as replycmdcnt from ah_comment where id=$idss";

                $qrymsg = $db->prepare($qryReplyMsg);
                $qrymsg->execute();
                $qryResultmsg = $qrymsg->fetch(PDO::FETCH_OBJ);

                $qrycmd = $db->prepare($qryReplycmd);
                $qrycmd->execute();
                $qryResultcmd = $qrycmd->fetch(PDO::FETCH_OBJ);

                $name = $qryResult[0]->firstName.' '.$qryResult[0]->lastName;
                $jobtitle = $qryResult[0]->title;
                $Company = $qryResult[0]->company;
                $Certifications = $qryResult[0]->agileCertification;
                $profilePicture = $qryResult[0]->profilePicture;
                $msgCount = $qryResultmsg->replymsgcnt;
                $cmdCount = $qryResultcmd->replycmdcnt;

            }
            else
            {
                $name = "";
                $jobtitle = "";
                $Company = "";
                $Certifications = "";
                $profilePicture = "";
                $msgCount = 0;
                $cmdCount = 0;
            }
/*echo $results[$i]->firstName;
print_r($results);exit;*/
$results[$i]->replyName = $name;
$results[$i]->replyJobTitle = $jobtitle;
$results[$i]->replyCompany = $Company;
$results[$i]->replyCertifications = $Certifications;
$results[$i]->replyPicture = $profilePicture;
$results[$i]->replyMsgCount = $msgCount;
$results[$i]->replyCmdCount = $cmdCount;

}
}
/*For commends of commend*/







$userReport[]=array('messageId'=> $value->messageid,
                    'agileCertification'=>$value->agileCertification,
                    'favouriteId'=>$value->favouriteId,
                    'type'=>$value->type,
                    'msgcount'=>$value->msgcount,
                    'cmdcount'=>$value->cmdcount,
                    'userId'=>$value->userId,
                    'useroption'=>$value->useroption,
                    'created_at'=> $value->messagedate,
                    'estTime'=>$value->estTime,
                    'message'=>$value->message,
                    'totalcount'=>$value->totalcount,
                    'likeCount'=>$value->likeCount,
                    'unlikeCount'=>$value->unlikeCount,
                    'status'=>$value->status,
                    'firstName'=>$value->firstName,'lastName'=>$value->lastName,
                    'email'=>$value->email,'profilePicture'=>$value->profilePicture,
                    'mobile'=>$value->mobile,'title'=>$value->title,'company'=>$value->company,
                    'commands'=>$results);
}
$res = array('Result'=>'Success',
             'Status'=>'message posted successfully',
             'messageDetails'=>$userReport,
             'users_list'=>$users_list);
echo json_encode($res);exit;

}


}catch(PDOException $e){
    echo $e;
    $res = array('Result'=>'Failed');
    echo json_encode($res); exit;
}

}


function getMessage()
{

    $request = Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    try{
        $db = getConnection();

        $users_sql = "SELECT * FROM ah_customer WHERE isLogin = 1";
        $stmt = $db->prepare($users_sql);
        $stmt->execute();
        $users_list = $stmt->fetchAll(PDO::FETCH_OBJ);

        $start=$data->type*20;
        $end=10;
        $likeKey = '';
        if($data->searchValue){
            $likeKey = $data->searchValue;
        }
        if($data->sortingType == 1)
        {
// most recent


            $queryMostRecent = "SELECT a.id as messageid,a.userId,a.created_at,a.estTime,a.message,
            a.likeCount,a.unlikeCount,a.status,ah_customer.firstName,ah_customer.lastName,
            ah_customer.agileCertification,ifnull(ah_favourite.favouriteId,0) as favouriteId,ifnull(ah_favourite.type,0) as type ,
            ah_customer.email,
            ah_customer.profilePicture,ah_customer.mobile,
            ah_customer.title,ah_customer.company,

            (select count(message) from ah_message where userId=ah_customer.id) as msgcount,
            (select count(comment) from ah_comment where userId=ah_customer.id) as cmdcount,

            (select count(*) from ah_message where categoryId='$data->categoryId' and questionId='$data->questionId') as totalcount,
            ifnull((select type from ah_messagelikes where userId=$data->userId and messageId= a.id),0) as useroption FROM ah_message a
            INNER JOIN ah_customer  ON a.userId=ah_customer.id
            LEFT JOIN ah_favourite ON a.id = ah_favourite.messageId

            where a.message LIKE '%".$likeKey."%' AND a.categoryId='$data->categoryId' and a.questionId='$data->questionId' ORDER BY a.created_at DESC limit $start"; 

            $notLike = "SELECT a.id as messageid,a.userId,a.created_at,a.estTime,a.message,
            a.likeCount,a.unlikeCount,a.status,ah_customer.firstName,ah_customer.lastName,
            ah_customer.agileCertification,ifnull(ah_favourite.favouriteId,0) as favouriteId,ifnull(ah_favourite.type,0) as type ,
            ah_customer.email,
            ah_customer.profilePicture,ah_customer.mobile,
            ah_customer.title,ah_customer.company,

            (select count(message) from ah_message where userId=ah_customer.id) as msgcount,
            (select count(comment) from ah_comment where userId=ah_customer.id) as cmdcount,

            (select count(*) from ah_message where categoryId='$data->categoryId' and questionId='$data->questionId') as totalcount,
            ifnull((select type from ah_messagelikes where userId=$data->userId and messageId= a.id),0) as useroption FROM ah_message a
            INNER JOIN ah_customer  ON a.userId=ah_customer.id
            LEFT JOIN ah_favourite ON a.id = ah_favourite.messageId

            where a.message NOT LIKE '%".$likeKey."%' AND a.categoryId='$data->categoryId' and a.questionId='$data->questionId' ORDER BY a.created_at DESC limit $start";


            $stmt = $db->prepare($queryMostRecent);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_OBJ);

            $stmt = $db->prepare($notLike);
            $stmt->execute();
            $resultNotLike = $stmt->fetchAll(PDO::FETCH_OBJ);

            $userReport = array();
            foreach ($resultNotLike as $value) {
# code...
                $queryMostRecent = "SELECT ah_customer.firstName,
                ah_customer.lastName,ah_customer.agileCertification,ah_customer.email,
                ah_customer.profilePicture,
                ah_customer.mobile,ah_customer.title,ah_customer.company,
                a.id as commentId,ifnull(ah_favourite.favouriteId,0) as favouriteId,ifnull(ah_favourite.type,0) as type,
                a.reply,
                a.comment,a.likeCount,a.unlikeCount,
                a.created_at,a.estTime,

                (select count(message) from ah_message where userId=ah_customer.id) as msgcounts,
                (select count(comment) from ah_comment where userId=ah_customer.id) as cmdcounts,

                ifnull((select type from ah_commentlikes where userId=$data->userId and messageId=a.id),0) as useroption
                FROM ah_comment a
                INNER JOIN ah_customer ON a.userId = ah_customer.id
                LEFT JOIN ah_favourite ON a.id = ah_favourite.messageId
                where a.comment LIKE '%".$likeKey."%' AND a.messageId =$value->messageid ORDER BY a.created_at DESC";


                $stmt = $db->prepare($queryMostRecent);
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_OBJ);



                /*Newly addded*/
                if(count($results)>0)
                {
                    for($i=0;$i<count($results);$i++)
                    {
                        $id = $results[$i]->reply;

                        $qryReply = "SELECT firstName ,lastName from ah_customer
                        where id = (SELECT userId from ah_comment where id= $id)";

                        $qry = $db->prepare($qryReply);
                        $qry->execute();
                        $qryResult = $qry->fetchAll(PDO::FETCH_OBJ);

                        if(count($qryResult)>0)
                        {
                            $idss = $qryResult[0]->id;
                            $qryReplyMsg = "SELECT count(message) as replymsgcnt from ah_message where id=$idss";
                            $qryReplycmd = "SELECT count(comment) as replycmdcnt from ah_comment where id=$idss";

                            $qrymsg = $db->prepare($qryReplyMsg);
                            $qrymsg->execute();
                            $qryResultmsg = $qrymsg->fetch(PDO::FETCH_OBJ);

                            $qrycmd = $db->prepare($qryReplycmd);
                            $qrycmd->execute();
                            $qryResultcmd = $qrycmd->fetch(PDO::FETCH_OBJ);

                            $name = $qryResult[0]->firstName.' '.$qryResult[0]->lastName;
                            $jobtitle = $qryResult[0]->title;
                            $Company = $qryResult[0]->company;
                            $Certifications = $qryResult[0]->agileCertification;
                            $profilePicture = $qryResult[0]->profilePicture;
                            $msgCount = $qryResultmsg->replymsgcnt;
                            $cmdCount = $qryResultcmd->replycmdcnt;

                        }
                        else
                        {
                            $name = "";
                            $jobtitle = "";
                            $Company = "";
                            $Certifications = "";
                            $profilePicture = "";
                            $msgCount = 0;
                            $cmdCount = 0;
                        }
/*echo $results[$i]->firstName;
print_r($results);exit;*/
$results[$i]->replyName = $name;
$results[$i]->replyJobTitle = $jobtitle;
$results[$i]->replyCompany = $Company;
$results[$i]->replyCertifications = $Certifications;
$results[$i]->replyPicture = $profilePicture;
$results[$i]->replyMsgCount = $msgCount;
$results[$i]->replyCmdCount = $cmdCount;

}
$userReport[]=array('messageId'=> $value->messageid,
                    'agileCertification'=>$value->agileCertification,
                    'favouriteId'=>$value->favouriteId,
                    'type'=>$value->type,
                    'msgcount'=>$value->msgcount,
                    'cmdcount'=>$value->cmdcount,
                    'userId'=>$value->userId,
                    'useroption'=>$value->useroption,
                    'created_at'=> $value->created_at,'estTime'=>$value->estTime,'message'=>$value->message,'totalcount'=>$value->totalcount,
                    'likeCount'=>$value->likeCount,'unlikeCount'=>$value->unlikeCount,'status'=>$value->status,
                    'firstName'=>$value->firstName,'lastName'=>$value->lastName,
                    'email'=>$value->email,'profilePicture'=>$value->profilePicture,
                    'mobile'=>$value->mobile,'title'=>$value->title,'company'=>$value->company,
                    'commands'=>$results);
}
/*For commends of commend*/

// $userReport[]=array('messageId'=> $value->messageid,
//     'agileCertification'=>$value->agileCertification,
//     'favouriteId'=>$value->favouriteId,
//     'type'=>$value->type,
//     'msgcount'=>$value->msgcount,
//     'cmdcount'=>$value->cmdcount,
//     'userId'=>$value->userId,
//     'useroption'=>$value->useroption,
//     'created_at'=> $value->created_at,'estTime'=>$value->estTime,'message'=>$value->message,'totalcount'=>$value->totalcount,
//     'likeCount'=>$value->likeCount,'unlikeCount'=>$value->unlikeCount,'status'=>$value->status,
//     'firstName'=>$value->firstName,'lastName'=>$value->lastName,
//     'email'=>$value->email,'profilePicture'=>$value->profilePicture,
//     'mobile'=>$value->mobile,'title'=>$value->title,'company'=>$value->company,
//     'commands'=>$results);
}




foreach ($result as $value)
{

    $queryMostRecent = "SELECT ah_customer.firstName,
    ah_customer.lastName,ah_customer.agileCertification,ah_customer.email,
    ah_customer.profilePicture,
    ah_customer.mobile,ah_customer.title,ah_customer.company,
    a.id as commentId,ifnull(ah_favourite.favouriteId,0) as favouriteId,ifnull(ah_favourite.type,0) as type,
    a.reply,
    a.comment,a.likeCount,a.unlikeCount,
    a.created_at,a.estTime,

    (select count(message) from ah_message where userId=ah_customer.id) as msgcounts,
    (select count(comment) from ah_comment where userId=ah_customer.id) as cmdcounts,

    ifnull((select type from ah_commentlikes where userId=$data->userId and messageId=a.id),0) as useroption
    FROM ah_comment a
    INNER JOIN ah_customer ON a.userId = ah_customer.id
    LEFT JOIN ah_favourite ON a.id = ah_favourite.messageId
    where a.comment LIKE '%".$likeKey."%' AND a.messageId =$value->messageid ORDER BY a.created_at DESC";


    $stmt = $db->prepare($queryMostRecent);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_OBJ);



    /*Newly addded*/
    if(count($results)>0)
    {
        for($i=0;$i<count($results);$i++)
        {
            $id = $results[$i]->reply;

            $qryReply = "SELECT * from ah_customer
            where id = (SELECT userId from ah_comment where id= $id)";

            $qry = $db->prepare($qryReply);
            $qry->execute();
            $qryResult = $qry->fetchAll(PDO::FETCH_OBJ);

            if(count($qryResult)>0)
            {
                $idss = $qryResult[0]->id;
                $qryReplyMsg = "SELECT count(message) as replymsgcnt from ah_message where id=$idss";
                $qryReplycmd = "SELECT count(comment) as replycmdcnt from ah_comment where id=$idss";

                $qrymsg = $db->prepare($qryReplyMsg);
                $qrymsg->execute();
                $qryResultmsg = $qrymsg->fetch(PDO::FETCH_OBJ);

                $qrycmd = $db->prepare($qryReplycmd);
                $qrycmd->execute();
                $qryResultcmd = $qrycmd->fetch(PDO::FETCH_OBJ);

                $name = $qryResult[0]->firstName.' '.$qryResult[0]->lastName;
                $jobtitle = $qryResult[0]->title;
                $Company = $qryResult[0]->company;
                $Certifications = $qryResult[0]->agileCertification;
                $profilePicture = $qryResult[0]->profilePicture;
                $msgCount = $qryResultmsg->replymsgcnt;
                $cmdCount = $qryResultcmd->replycmdcnt;

            }
            else
            {
                $name = "";
                $jobtitle = "";
                $Company = "";
                $Certifications = "";
                $profilePicture = "";
                $msgCount = 0;
                $cmdCount = 0;
            }
/*echo $results[$i]->firstName;
print_r($results);exit;*/
$results[$i]->replyName = $name;
$results[$i]->replyJobTitle = $jobtitle;
$results[$i]->replyCompany = $Company;
$results[$i]->replyCertifications = $Certifications;
$results[$i]->replyPicture = $profilePicture;
$results[$i]->replyMsgCount = $msgCount;
$results[$i]->replyCmdCount = $cmdCount;

}
}
/*For commends of commend*/

$userReport[]=array('messageId'=> $value->messageid,
                    'agileCertification'=>$value->agileCertification,
                    'favouriteId'=>$value->favouriteId,
                    'type'=>$value->type,
                    'msgcount'=>$value->msgcount,
                    'cmdcount'=>$value->cmdcount,
                    'userId'=>$value->userId,
                    'useroption'=>$value->useroption,
                    'created_at'=> $value->created_at,'estTime'=>$value->estTime,'message'=>$value->message,'totalcount'=>$value->totalcount,
                    'likeCount'=>$value->likeCount,'unlikeCount'=>$value->unlikeCount,'status'=>$value->status,
                    'firstName'=>$value->firstName,'lastName'=>$value->lastName,
                    'email'=>$value->email,'profilePicture'=>$value->profilePicture,
                    'mobile'=>$value->mobile,'title'=>$value->title,'company'=>$value->company,
                    'commands'=>$results);
}
$res = array('Result'=>'Success',
             'Status'=>'',
             'messageDetails'=>$userReport,
             'users_list'=>$users_list
         );
echo json_encode($res);exit;


}
else
{
// most liked
    $queryMostLiked = "SELECT a.id as messageid,a.userId,a.created_at,a.estTime,a.message,a.likeCount,
    a.unlikeCount,a.status,ah_customer.firstName,ah_customer.lastName,ah_customer.agileCertification,
    ah_customer.email,ifnull(ah_favourite.favouriteId,0) as favouriteId,ifnull(ah_favourite.type,0) as type,
    ah_customer.profilePicture,ah_customer.mobile,
    ah_customer.title,ah_customer.company,
    (select count(message) from ah_message where userId=ah_customer.id) as msgcount,
    (select count(comment) from ah_comment where userId=ah_customer.id) as cmdcount,

    (select count(*) from ah_message where categoryId='$data->categoryId' and questionId='$data->questionId') as totalcount,
    ifnull((select type from ah_messagelikes where userId=$data->userId and messageId=a.id),0) as useroption
    FROM ah_message a
    INNER JOIN ah_customer ON a.userId=ah_customer.id
    LEFT JOIN ah_favourite ON a.id = ah_favourite.messageId
    where a.message LIKE '%".$likeKey."%' AND a.categoryId='$data->categoryId' and a.questionId='$data->questionId'
    ORDER BY likeCount DESC  limit $start";
    $stmt = $db->prepare($queryMostLiked);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_OBJ);


    $notLike = "SELECT a.id as messageid,a.userId,a.created_at,a.estTime,a.message,a.likeCount,
    a.unlikeCount,a.status,ah_customer.firstName,ah_customer.lastName,ah_customer.agileCertification,
    ah_customer.email,ifnull(ah_favourite.favouriteId,0) as favouriteId,ifnull(ah_favourite.type,0) as type,
    ah_customer.profilePicture,ah_customer.mobile,
    ah_customer.title,ah_customer.company,
    (select count(message) from ah_message where userId=ah_customer.id) as msgcount,
    (select count(comment) from ah_comment where userId=ah_customer.id) as cmdcount,

    (select count(*) from ah_message where categoryId='$data->categoryId' and questionId='$data->questionId') as totalcount,
    ifnull((select type from ah_messagelikes where userId=$data->userId and messageId=a.id),0) as useroption
    FROM ah_message a
    INNER JOIN ah_customer ON a.userId=ah_customer.id
    LEFT JOIN ah_favourite ON a.id = ah_favourite.messageId
    where a.message NOT LIKE '%".$likeKey."%' AND a.categoryId='$data->categoryId' and a.questionId='$data->questionId'
    ORDER BY likeCount DESC  limit $start";
    $stmt = $db->prepare($notLike);
    $stmt->execute();
    $resultNotLike = $stmt->fetchAll(PDO::FETCH_OBJ);

    $userReport = array();


    foreach ($resultNotLike as $value)
    {
        $queryMostRecent = "SELECT ah_customer.firstName,ah_customer.lastName,

        ah_customer.agileCertification,
        ah_customer.email,ifnull(ah_favourite.favouriteId,0) as favouriteId,ifnull(ah_favourite.type,0) as type,
        ah_customer.profilePicture,ah_customer.mobile,ah_customer.title,ah_customer.company,a.id as commentId,
        a.reply,a.comment,a.likeCount,a.unlikeCount
        ,a.created_at,a.estTime,
        (select count(message) from ah_message where userId=ah_customer.id) as msgcounts,
        (select count(comment) from ah_comment where userId=ah_customer.id) as cmdcounts,

        ifnull((select type from ah_commentlikes where userId=$data->userId and messageId=a.id),0) as useroption
        FROM ah_comment a
        INNER JOIN ah_customer ON a.userId = ah_customer.id
        LEFT JOIN ah_favourite ON a.id = ah_favourite.messageId
        where a.comment LIKE '%".$likeKey."%' AND a.messageId =$value->messageid ORDER BY  likeCount DESC";
        $stmt = $db->prepare($queryMostRecent);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_OBJ);

        if(count($results)>0)
        {
            for($i=0;$i<count($results);$i++)
            {
                $id = $results[$i]->reply;

                $qryReply = "SELECT * from ah_customer where id = (SELECT userId from ah_comment where id= $id)";

                $qry = $db->prepare($qryReply);
                $qry->execute();
                $qryResult = $qry->fetchAll(PDO::FETCH_OBJ);

                if(count($qryResult)>0)
                {
                    $idss = $qryResult[0]->id;
                    $qryReplyMsg = "SELECT count(message) as replymsgcnt from ah_message where id=$idss";
                    $qryReplycmd = "SELECT count(comment) as replycmdcnt from ah_comment where id=$idss";

                    $qrymsg = $db->prepare($qryReplyMsg);
                    $qrymsg->execute();
                    $qryResultmsg = $qrymsg->fetch(PDO::FETCH_OBJ);

                    $qrycmd = $db->prepare($qryReplycmd);
                    $qrycmd->execute();
                    $qryResultcmd = $qrycmd->fetch(PDO::FETCH_OBJ);

                    $name = $qryResult[0]->firstName.' '.$qryResult[0]->lastName;
                    $jobtitle = $qryResult[0]->title;
                    $Company = $qryResult[0]->company;
                    $Certifications = $qryResult[0]->agileCertification;
                    $profilePicture = $qryResult[0]->profilePicture;
                    $msgCount = $qryResultmsg->replymsgcnt;
                    $cmdCount = $qryResultcmd->replycmdcnt;

                }
                else
                {
                    $name = "";
                    $jobtitle = "";
                    $Company = "";
                    $Certifications = "";
                    $profilePicture = "";
                    $msgCount = 0;
                    $cmdCount = 0;
                }
/*echo $results[$i]->firstName;
print_r($results);exit;*/
$results[$i]->replyName = $name;
$results[$i]->replyJobTitle = $jobtitle;
$results[$i]->replyCompany = $Company;
$results[$i]->replyCertifications = $Certifications;
$results[$i]->replyPicture = $profilePicture;
$results[$i]->replyMsgCount = $msgCount;
$results[$i]->replyCmdCount = $cmdCount;

}
$userReport[]=array('messageId'=> $value->messageid,
                    'agileCertification'=>$value->agileCertification,
                    'favouriteId'=>$value->favouriteId,
                    'type'=>$value->type,
                    'msgcount'=>$value->msgcount,
                    'cmdcount'=>$value->cmdcount,
                    'userId'=>$value->userId,'useroption'=>$value->useroption,
                    'created_at'=> $value->created_at,'estTime'=>$value->estTime,'message'=>$value->message,'totalcount'=>$value->totalcount,
                    'likeCount'=>$value->likeCount,'unlikeCount'=>$value->unlikeCount,'status'=>$value->status,
                    'firstName'=>$value->firstName,'lastName'=>$value->lastName,
                    'email'=>$value->email,'profilePicture'=>$value->profilePicture,
                    'mobile'=>$value->mobile,'title'=>$value->title,'company'=>$value->company,
                    'commands'=>$results);
}



// $userReport[]=array('messageId'=> $value->messageid,
//     'agileCertification'=>$value->agileCertification,
//     'favouriteId'=>$value->favouriteId,
//     'type'=>$value->type,
//     'msgcount'=>$value->msgcount,
//     'cmdcount'=>$value->cmdcount,
//     'userId'=>$value->userId,'useroption'=>$value->useroption,
//     'created_at'=> $value->created_at,'estTime'=>$value->estTime,'message'=>$value->message,'totalcount'=>$value->totalcount,
//     'likeCount'=>$value->likeCount,'unlikeCount'=>$value->unlikeCount,'status'=>$value->status,
//     'firstName'=>$value->firstName,'lastName'=>$value->lastName,
//     'email'=>$value->email,'profilePicture'=>$value->profilePicture,
//     'mobile'=>$value->mobile,'title'=>$value->title,'company'=>$value->company,
//     'commands'=>$results);

}

foreach ($result as $value)
{
    $queryMostRecent = "SELECT ah_customer.firstName,ah_customer.lastName,

    ah_customer.agileCertification,
    ah_customer.email,ifnull(ah_favourite.favouriteId,0) as favouriteId,ifnull(ah_favourite.type,0) as type,
    ah_customer.profilePicture,ah_customer.mobile,ah_customer.title,ah_customer.company,a.id as commentId,
    a.reply,a.comment,a.likeCount,a.unlikeCount
    ,a.created_at,a.estTime,
    (select count(message) from ah_message where userId=ah_customer.id) as msgcounts,
    (select count(comment) from ah_comment where userId=ah_customer.id) as cmdcounts,

    ifnull((select type from ah_commentlikes where userId=$data->userId and messageId=a.id),0) as useroption
    FROM ah_comment a
    INNER JOIN ah_customer ON a.userId = ah_customer.id
    LEFT JOIN ah_favourite ON a.id = ah_favourite.messageId
    where a.comment LIKE '%".$likeKey."%' AND a.messageId =$value->messageid ORDER BY  likeCount DESC";
    $stmt = $db->prepare($queryMostRecent);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_OBJ);

    if(count($results)>0)
    {
        for($i=0;$i<count($results);$i++)
        {
            $id = $results[$i]->reply;

            $qryReply = "SELECT * from ah_customer where id = (SELECT userId from ah_comment where id= $id)";

            $qry = $db->prepare($qryReply);
            $qry->execute();
            $qryResult = $qry->fetchAll(PDO::FETCH_OBJ);

            if(count($qryResult)>0)
            {
                $idss = $qryResult[0]->id;
                $qryReplyMsg = "SELECT count(message) as replymsgcnt from ah_message where id=$idss";
                $qryReplycmd = "SELECT count(comment) as replycmdcnt from ah_comment where id=$idss";

                $qrymsg = $db->prepare($qryReplyMsg);
                $qrymsg->execute();
                $qryResultmsg = $qrymsg->fetch(PDO::FETCH_OBJ);

                $qrycmd = $db->prepare($qryReplycmd);
                $qrycmd->execute();
                $qryResultcmd = $qrycmd->fetch(PDO::FETCH_OBJ);

                $name = $qryResult[0]->firstName.' '.$qryResult[0]->lastName;
                $jobtitle = $qryResult[0]->title;
                $Company = $qryResult[0]->company;
                $Certifications = $qryResult[0]->agileCertification;
                $profilePicture = $qryResult[0]->profilePicture;
                $msgCount = $qryResultmsg->replymsgcnt;
                $cmdCount = $qryResultcmd->replycmdcnt;

            }
            else
            {
                $name = "";
                $jobtitle = "";
                $Company = "";
                $Certifications = "";
                $profilePicture = "";
                $msgCount = 0;
                $cmdCount = 0;
            }
/*echo $results[$i]->firstName;
print_r($results);exit;*/
$results[$i]->replyName = $name;
$results[$i]->replyJobTitle = $jobtitle;
$results[$i]->replyCompany = $Company;
$results[$i]->replyCertifications = $Certifications;
$results[$i]->replyPicture = $profilePicture;
$results[$i]->replyMsgCount = $msgCount;
$results[$i]->replyCmdCount = $cmdCount;

}
}



$userReport[]=array('messageId'=> $value->messageid,
                    'agileCertification'=>$value->agileCertification,
                    'favouriteId'=>$value->favouriteId,
                    'type'=>$value->type,
                    'msgcount'=>$value->msgcount,
                    'cmdcount'=>$value->cmdcount,
                    'userId'=>$value->userId,'useroption'=>$value->useroption,
                    'created_at'=> $value->created_at,'estTime'=>$value->estTime,'message'=>$value->message,'totalcount'=>$value->totalcount,
                    'likeCount'=>$value->likeCount,'unlikeCount'=>$value->unlikeCount,'status'=>$value->status,
                    'firstName'=>$value->firstName,'lastName'=>$value->lastName,
                    'email'=>$value->email,'profilePicture'=>$value->profilePicture,
                    'mobile'=>$value->mobile,'title'=>$value->title,'company'=>$value->company,
                    'commands'=>$results);

}
$res = array('Result'=>'Success',
             'Status'=>'',
             'messageDetails'=>$userReport,'users_list'=>$users_list);
echo json_encode($res);exit;
}

}catch(PDOException $e){
    echo $e;
    $res = array('Result'=>'Failed');
    echo json_encode($res); exit;
}

}



function postMessage()
{

    $request = Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $date=date("Y-m-d H:i:s");
    $notiDate = date("Y-m-d");
    try

    {
        $db = getConnection();
        $me = "SELECT * from ah_customer 
        where ah_customer.id= $data->userId";
        $stmtme = $db->prepare($me);
        $stmtme->execute();
        $userMe = $stmtme->fetch(PDO::FETCH_OBJ);
        $nameMe = $userMe->firstName.' '.$userMe->lastName;

        $insertquery="INSERT into ah_message(userId,categoryId,questionId,message,created_at,updated_at,estTime)
        values(:userId,:categoryId,:questionId,:message,:created_at,:updated_at,:estTime)";
        $stmt = $db->prepare($insertquery);
        $stmt->bindParam("userId", $data->userId);
        $stmt->bindParam("categoryId", $data->categoryId);
        $stmt->bindParam("questionId", $data->questionId);
        $stmt->bindParam("message", $data->message);
        $stmt->bindParam("created_at", $date);
        $stmt->bindParam("updated_at", $date);
        $stmt->bindParam("estTime", $data->estTime);
        $stmt->execute();

        $getQuery = "SELECT * FROM ah_message ORDER BY id DESC LIMIT 1";
        $stmt = $db->prepare($getQuery);
        $stmt->execute();
        $userdet = $stmt->fetch(PDO::FETCH_OBJ);



        $notiCheck = "SELECT count(*) as data from ah_message 
        where date(estTime) = '$notiDate'";

        $notify = $db->prepare($notiCheck);
        $notify->execute();
        $notifyData = $notify->fetch(PDO::FETCH_OBJ);

        if($notifyData->data == 1)
        {

            $datatum = '{"id": "6"}';
#firebase Work
            $url = "https://agilehealth-560eb.firebaseio.com/notification/status/true.json";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);                               
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $datatum);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/plain'));
            curl_exec($ch);
            curl_close($ch);






            $getQueryid = "SELECT * from ah_customer 
            where ah_customer.id!= $data->userId";

            $qry = $db->prepare($getQueryid);
            $qry->execute();
            $qryData = $qry->fetchAll(PDO::FETCH_OBJ);


            foreach ($qryData as $user) 
            {
# code...



                $notify = "insert into ah_notification
                (userId,message,notificationTitle,notificationDesc,notificationTime,commanderId,replyTo,messageId,isRead,
                    questionId,categoryId,created_at,updated_at)
                    values(:userId,:message,:notificationTitle,:notificationDesc,:notificationTime,:commanderId,:replyTo,:messageId,'1',
                    :questionId,:categoryId,'$date','$date')";

                    $deviceToken = $user->deviceToken;
                    $notificationTime = $data->estTime;
                    $commanderId = 0;
                    $replyTo = 0;
                    $userId = $user->id;
                    $name = $user->firstName." ".$user->lastName;
                    $image = $user->profilePicture;
                    $notificationTitle ="New Message";
                    $notificationDesc = $data->message;
                    $notificationTime = $data->estTime;
                    $messageId = 0;


                    $st = $db->prepare($notify);
                    $st->bindParam("userId",$userId);
                    $st->bindParam("message",$data->message);
                    $st->bindParam("notificationTitle",$notificationTitle);
                    $st->bindParam("notificationDesc", $notificationDesc);
                    $st->bindParam("notificationTime",$notificationTime);
                    $st->bindParam("commanderId", $commanderId);
                    $st->bindParam("replyTo", $replyTo);
                    $st->bindParam("messageId", $messageId);

                    $st->bindParam("questionId", $data->questionId);
                    $st->bindParam("categoryId", $data->categoryId);
                    $st->execute();

                    $getCount = "select count(*) as batch from ah_notification where isRead = 0 and userId = $userId";
                    $qryCount = $db->prepare($getCount);
                    $qryCount->execute();
                    $qryCountData = $qryCount->fetch(PDO::FETCH_OBJ);

                    $badge = $qryCountData->batch -1;
                    $sound ="NotificationTune.mp3";
                    $type="first";



                    send_gcm_notify($deviceToken,$notificationDesc,$data->questionId,$data->categoryId,$notificationTitle,$badge,$image,$name,$sound,$type,$nameMe);
                }

            }
            $res = array('Result'=>'Success',
                         'Status'=>'message posted successfully',
                         'messageDetails'=>$userdet);
            echo json_encode($res); exit;
        }
        catch(PDOException $e)
        {
            echo $e;
            $res = array('Result'=>'Failed');
            echo json_encode($res); exit;
        }
    }

    function imageUpload()
    {
        if(isset($_POST["type"]))
        {
#1 file 2. no change 3.remove

            $date=date("Y-m-d H:i:s");
            $id = $_POST["id"];
            $db = getConnection();
            $getuser="select * from ah_customer where id=$id";
            $stmt = $db->prepare($getuser);
            $stmt->execute();
            $get_det = $stmt->fetch(PDO::FETCH_OBJ);
            if($get_det)
            {
                $qry1="update ah_customer set firstName = :firstName,lastName=:lastName,email=:email,mobile=:mobile,title=:title,company=:company,agileCertification=:agileCertification,updated_at=:updated_at where id=:id";
                $stmt = $db->prepare($qry1);
                $stmt->bindParam("firstName", $_POST['firstName']);
                $stmt->bindParam("lastName", $_POST['lastName']);
                $stmt->bindParam("email", $_POST['email']);
                $stmt->bindParam("mobile", $_POST['mobile']);
                $stmt->bindParam("title", $_POST['title']);
                $stmt->bindParam("company", $_POST['company']);
                $stmt->bindParam("agileCertification", $_POST['agileCertification']);
                $stmt->bindParam("updated_at", $date);
                $stmt->bindParam("id", $id);
                $stmt->execute();

                if($_POST["type"] == 1)
                {
                    $target_dir = "uploads/";
                    $target_file = $target_dir . basename($_FILES["image"]["name"]);
$imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);#new add
$newName = date('YmdHis');
$target_files = $target_dir.$newName.'.'.$imageFileType ;
$fileName = $target_files;
move_uploaded_file($_FILES["image"]["tmp_name"], $target_files);

// update image url on database
$qry="update ah_customer set profilePicture = :profilePicture where id=:id";
$stmt = $db->prepare($qry);
$stmt->bindParam("profilePicture", $fileName);
$stmt->bindParam("id", $id);
$stmt->execute();

}
if($_POST["type"] == 3)
{
    $empty = "";
    $qry="update ah_customer set profilePicture = :profilePicture where id=:id";
    $stmt = $db->prepare($qry);  
    $stmt->bindParam("profilePicture",$empty);
    $stmt->bindParam("id", $id);
    $stmt->execute();

}

$stmt = $db->prepare($getuser);
$stmt->execute();
$get_det = $stmt->fetch(PDO::FETCH_OBJ);

$res = array('Result'=>'Success',
             'details'=> $get_det);
echo json_encode($res); exit;


}
}
else

{
    $date=date("Y-m-d H:i:s");
    $id = $_POST["id"];
    if(!$id){
        echo '{"Result":"Failed"}';
    }
    if(!$_FILES["image"]["name"]){
        echo '{"Result":"Failed"}';
    }
    $db = getConnection();
    $getuser="select * from ah_customer where id=$id";
    $stmt = $db->prepare($getuser);
    $stmt->execute();
    $get_det = $stmt->fetch(PDO::FETCH_OBJ);
    if($get_det){
        $qry1="update ah_customer set firstName = :firstName,lastName=:lastName,email=:email,mobile=:mobile,title=:title,company=:company,agileCertification=:agileCertification,updated_at=:updated_at where id=:id";
        $stmt = $db->prepare($qry1);
        $stmt->bindParam("firstName", $_POST['firstName']);
        $stmt->bindParam("lastName", $_POST['lastName']);
        $stmt->bindParam("email", $_POST['email']);
        $stmt->bindParam("mobile", $_POST['mobile']);
        $stmt->bindParam("title", $_POST['title']);
        $stmt->bindParam("company", $_POST['company']);
        $stmt->bindParam("agileCertification", $_POST['agileCertification']);
        $stmt->bindParam("updated_at", $date);
        $stmt->bindParam("id", $id);
        $stmt->execute();



        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["image"]["name"]);
$imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);#new add
$newName = date('YmdHis');
$target_files = $target_dir.$newName.'.'.$imageFileType ;

$fileName = $target_files;
if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_files))
#move_file(file,location)
{
// update image url on database
    $qry="update ah_customer set profilePicture = :profilePicture where id=:id";
    $stmt = $db->prepare($qry);
    $stmt->bindParam("profilePicture", $fileName);
    $stmt->bindParam("id", $id);
    $stmt->execute();


    $stmt = $db->prepare($getuser);
    $stmt->execute();
    $get_det = $stmt->fetch(PDO::FETCH_OBJ);

    $res = array('Result'=>'Success',
                 'details'=> $get_det);
    echo json_encode($res); exit;
}
else
{
    $res = array('Result'=>'Success',
                 'details'=> $get_det);
    echo json_encode($res); exit;
}
}else{
    $res = array('Result'=>'Failed',
                 'message'=>'Customer not found');
    echo json_encode($res); exit;
}
}
}

function checkEmailStatus($id){

    $db = getConnection();
// 1-customer, 2-driver

    $get_tkn = "select * from ah_customer where id=:id";
    $stmt = $db->prepare($get_tkn);
    $stmt->bindParam("id", $id);
    $stmt->execute();
    $get_det = $stmt->fetch(PDO::FETCH_OBJ);
    if($get_det->profileStatus == 1){
        $res = array('Result'=>'Success','Status'=>$get_det->profileStatus,
                     'message'=>'0 - not verified, 1 - verified'
                 );
        echo json_encode($res); exit;
    }
    if($get_det){
        $res = array('Result'=>'Success',
                     'Status'=>$get_det->profileStatus
                 );
        echo json_encode($res); exit;
    }else{
        $res = array('Result'=>'Failed',
                     'message'=>'Customer not found');
        echo json_encode($res); exit;
    }
}

//checking authentication of user
function check_authtoken($authToken,$auth_id)
{
    $db = getConnection();
// 1-customer, 2-driver

    $get_tkn = "select id from ah_customer where authToken=:authToken and id=:id";
    $stmt = $db->prepare($get_tkn);
    $stmt->bindParam("authToken", $authToken);
    $stmt->bindParam("id", $auth_id);
    $stmt->execute();
    $get_det = $stmt->fetch(PDO::FETCH_OBJ);
    if($get_det)
    {
        return 1;
    }
    else
    {
        return 0;
    }

}

function UserRegister(){

    $request = Slim::getInstance()->request();
    $register = json_decode($request->getBody());

    $date=date('Y-m-d H:i:s');
    $date1=date('Y-m-d');
    $sql="select * from ah_customer where email=:email";
// $sql1="select id from ah_customer where mobile=:mobile";
    $sql2="select id from ah_customer where email=:email";
    $insertquery="INSERT into ah_customer(firstName,lastName,email,mobile,password,profilePicture,
    authToken,tempToken,deviceType,deviceToken,environment,title,company,
    agileCertification,type,created_at,updated_at)
    values(:firstName,:lastName,:email,:mobile,:password,:profilePicture,
    :authToken,:tempToken,:deviceType,:deviceToken,:environment,:title,:company,
    :agileCertification,:type,:created_at,:updated_at)";
    $tempToken= rand(11111,99991);
    $tempToken = base64_encode($tempToken);

    $authToken = sha1($date);
    $key = hash('sha256', '!@#123');
    $iv = substr(hash('sha256', 'as12345'), 0, 16);
    $output = openssl_encrypt($register->password, "AES-256-CBC", $key, 0, $iv);
    $password = base64_encode($output);
    try{

        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("email", $register->email);
        $stmt->execute();
        $user = $stmt->fetchAll(PDO::FETCH_OBJ);
        if(!$user){
            $stmt = $db->prepare($sql2);
            $stmt->bindParam("email", $register->email);
            $stmt->execute();
            $emil = $stmt->fetchAll(PDO::FETCH_OBJ);
            if($emil){
                $res = array('Result'=>'Failed',
                             'Status'=>'Email already registered'
                         );
                echo json_encode($res); exit;
            }
            else
            {
                $id = $db->lastInsertId();

// $key = hash('sha256', 'wrydes');
// $iv = substr(hash('sha256', 'dispatch'), 0, 16);
// $output = openssl_encrypt($register->password, "AES-256-CBC", $key, 0, $iv);
// $password = base64_encode($output);


//* Registration Checking
                $subject="User Register Verification";
                $username = $register->firstName;
                $token = $tempToken;
                $id = urlencode(base64_encode($register->email));
                $content = "Welcom to agilehealth  <br><br>
                <a href='52.35.102.74/agilehealth/index.php/verify/$id/$token'>Click here to Verify Account</a>";
                $sign = "Thank you<br>
                AgileHealth Team<br><br>
                © 2017 AgileHealth. All right reserved.";
                $html='<html class="no-js" lang="en">
                <body>
                <div style="
                width: auto;
                border: 15px solid #efc01a;
                padding: 20px;
                margin: 10px;
                ">
                <div class="container">
                <div class="navbar-header">
                <div style="text-align: center;">
                <a href="" title="" style="margin-top:0px"><img src="http://52.35.102.74/agilehealth/uploads/logo-new.png"  class="img-responsive logo-new" ></a>
                </div>

                <span style="float:right; text-align:right;">

                </span>

                <div style="clear:both;" ></div>
                <hr width="100%" />
                </div>
                <div class="mail-container">
                <br />
                <b> '.$username.' </b>
                <br />
                <br />
                '.$content.'
                <br />
                </div>
                <br />
                <hr width="100%" />
                <footer class="navbar-inverse">
                <div class="row">
                '.$sign.'
                <div class="collapse navbar-collapse"></div>
                </div>
                </footer>
                </div>
                </body>
                </html>';
                $mail       = new PHPMailer();
$mail->IsSMTP(); // enable SMTP
$mail->SMTPAuth = true; // authentication enabled
$mail->SMTPSecure = 'ssl'; // secure transfer enabled REQUIRED for Gmail
$mail->Host = "smtp.gmail.com";
$mail->Port = 465; // or 587
$mail->IsHTML(true);
$mail->Username = 'codekhadimail@gmail.com';
$mail->Password = '!@#qweasd';
$mail->From = $mail->Username; //Default From email same as smtp user
$mail->FromName = "agilehealth";
$mail->AddAddress($register->email, '');
$mail->CharSet = 'UTF-8';
$mail->Subject    = $subject;
$mail->MsgHTML($html);
if(!$mail->Send())
{
    $res = array('Result'=>'Failed',
                 'Status'=>'Email sent failed'
             );
    echo json_encode($res); exit;
}

$stmt = $db->prepare($insertquery);

$stmt->bindParam("firstName",  $register->firstName);
$stmt->bindParam("lastName",  $register->lastName);
$stmt->bindParam("email",  $register->email);
$stmt->bindParam("mobile",  $register->mobile);
$stmt->bindParam("password",  $password);
$stmt->bindParam("profilePicture",$register->profilePicture);
$stmt->bindParam("authToken",  $authToken);
$stmt->bindParam("company",  $register->company);
$stmt->bindParam("title",  $register->title);
$stmt->bindParam("agileCertification",  $register->agileCertification);
$stmt->bindParam("tempToken",  $tempToken);
$stmt->bindParam("deviceType",  $register->deviceType);
$stmt->bindParam("deviceToken",  $register->deviceToken);
$stmt->bindParam("environment",  $register->environment);
$stmt->bindParam("type",  $register->type);
$stmt->bindParam("created_at",  $date);
$stmt->bindParam("updated_at",  $date);
$stmt->execute();

$stmt = $db->prepare($sql);
$stmt->bindParam("email", $register->email);
$stmt->execute();
$userdet = $stmt->fetch(PDO::FETCH_OBJ);

$res = array('Result'=>'Success',
             'Status'=>'Account verification link has been sent to your mail',
             'userdetails' => $userdet);
echo json_encode($res); exit;
}
}else{
    $res = array('Result'=>'Failed',
                 'Status'=>'Already registered, please login');
    echo json_encode($res); exit;
}

}catch(PDOException $e){
    echo $e;
    $res = array('Result'=>'Failed');
    echo json_encode($res); exit;
}
}

function login(){

    $request = Slim::getInstance()->request();
    $register = json_decode($request->getBody());

    $date=date('Y-m-d H:i:s');
    $sql1="SELECT * from ah_customer where email=:email";
    $profile = "SELECT profileStatus from ah_customer where email=:email";
    $key = hash('sha256', '!@#123');
    $iv = substr(hash('sha256', 'as12345'), 0, 16);
    $output = openssl_encrypt($register->password, "AES-256-CBC", $key, 0, $iv);
    $password = base64_encode($output);
    $auth_token = sha1($date);

    try
    {

        $db = getConnection();
        $stmt = $db->prepare($sql1);
        $stmt->bindParam("email", $register->email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_OBJ);

        $pro = $db->prepare($profile);
        $pro-> bindParam("email", $register->email);
        $pro->execute();
        $prodata = $pro->fetch(PDO::FETCH_OBJ);
        if($user)
        {

            if($user->type == 1)
            {

                $sql3="SELECT * from ah_customer 
                where email=:email and password=:password";
                $stmt = $db->prepare($sql3);
                $stmt->bindParam("email", $register->email);
                $stmt->bindParam("password", $password);
                $stmt->execute();
                $userdet = $stmt->fetch(PDO::FETCH_OBJ);
            }
            else
            {
                $res = array('Result'=>'Failed',
                             'Status'=> 'Invalid Password');
                echo json_encode($res); exit;
            }

            if($userdet)
            {
                $qry="UPDATE ah_customer set isLogin=1,authToken=:authToken,
                deviceType=:deviceType,deviceToken=:deviceToken,
                environment=:environment,updated_at='$date' where id=:id";
                $stmt = $db->prepare($qry);
                $stmt->bindParam("id", $userdet->id);
                $stmt->bindParam("authToken", $authToken);
                $stmt->bindParam("deviceType", $register->deviceType);
                $stmt->bindParam("deviceToken", $register->deviceToken);
                $stmt->bindParam("environment", $register->environment);
                $stmt->execute();
/*$sql3="SELECT * from ah_customer 
where email=:email and password=:password";
$stmt = $db->prepare($sql3);
$stmt->bindParam("email", $register->email);
$stmt->bindParam("password", $password);
$stmt->execute();
$userdet = $stmt->fetch(PDO::FETCH_OBJ);*/

$res = array('Result'=>'Success',
             'Status'=>'Login successfully',
             'userdetails' => $userdet);
echo json_encode($res); exit;
}
else
{
    $res = array('Result'=>'Failed',
                 'Status'=> 'Invalid password');
    echo json_encode($res); exit;
}
/*}
else
{
$res = array('Result'=>'Failed',
'Status'=> 'Account Not Verified');
echo json_encode($res); exit;
}*/
}
else
{
    $res = array('Result'=>'Failed',
                 'Status'=> 'Invalid username');
    echo json_encode($res); exit;
}
}
catch(PDOException $e){
    echo $e;
    $res = array('Result'=>'Failed');
    echo json_encode($res); exit;
}
}

function checkEmail()
{
    $request = Slim::getInstance()->request();
    $register = json_decode($request->getBody());
    $sql="select * from ah_customer where email=:email";/*Check emial Exsist*/
    try
    {
        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("email", $register->email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_OBJ);
        if($user)
        {
            if(isset($register->deviceToken))
            {
                $upQry = "UPDATE ah_customer set deviceToken = '$register->deviceToken' ,
                deviceType = $register->deviceType,environment =$register->environment
                where email= '$register->email'";

                $update = $db->prepare($upQry);
                $update->execute();

            }
            $res = array('Result'=>'Success',
                         'message'=>$user);
            echo json_encode($res); exit;
        }
        else
        {
            $json = '{"yenna vitru thaeee":1}';
            $use = json_decode($json);
            $res = array('Result'=>'Failed',
                         'message'=>$use);
            echo json_encode($res); exit;
        }
    }
    catch(PDOException $e)
    {
        echo $e;
        $res = array('Result'=>'Failed');
        echo json_encode($res); exit;
    }
}


function logout($id){
    $request = Slim::getInstance()->request();
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
            $sql1 = "update ah_customer set isLogin=0,deviceToken=0 where id=:id";
            $stmt = $db->prepare($sql1);
            $stmt->bindParam("id", $id);
            $stmt->execute();
            $res = array('Result'=>'Success',
                         'message'=>'Loggout successfully');
            echo json_encode($res); exit;

        }else
        {
            $res = array('Result'=>'Failed',
                         'message'=>'Customer not found');
            echo json_encode($res); exit;
        }
    }catch(PDOException $e) {
        $res = array('Result'=>'Failed');
        echo json_encode($res); exit;
    }
}

function forgotpassword(){


    $request = Slim::getInstance()->request();
    $register = json_decode($request->getBody());
    $date=date('Y-m-d H:i:s');
    $sql = "select firstName,lastName,tempToken from ah_customer where email=:email";

    try{
        $db = getConnection();

        $stmt = $db->prepare($sql);
        $stmt->bindParam("email", $register->email);
        $stmt->execute();
        $customer = $stmt->fetch(PDO::FETCH_OBJ);
        if($customer)
        {
            $subject="Forgot Password";
            $username = $customer->firstName;
            $token = $customer->tempToken;
            $id = urlencode(base64_encode($register->email));
            $content = "So you lost your password? No problem! Please click the link below to reset your password<br><br>
            <a href='52.35.102.74/agilehealth/reset.php/?id=$id'>Click here</a>";
            $sign = "Thank you<br>
            AgileHealth<br><br>
            © 2017AgileHealth. All right reserved.";
            $html='<html class="no-js" lang="en">
            <body>
            <div style="
            width: auto;
            border: 15px solid #efc01a;
            padding: 20px;
            margin: 10px;
            ">
            <div class="container">
            <div class="navbar-header">
            <div style="text-align: center;">
            <a href="" title="" style="margin-top:0px"><img src="http://52.35.102.74/logo-new.png"  class="img-responsive logo-new" ></a>
            </div>

            <span style="float:right; text-align:right;">

            </span>

            <div style="clear:both;" ></div>
            <hr width="100%" />
            </div>
            <div class="mail-container">
            <br />
            <b> '.$username.' </b>
            <br />
            <br />
            '.$content.'
            <br />
            </div>
            <br />
            <hr width="100%" />
            <footer class="navbar-inverse">
            <div class="row">
            '.$sign.'
            <div class="collapse navbar-collapse"></div>
            </div>
            </footer>
            </div>
            </body>
            </html>';
            $mail       = new PHPMailer();
$mail->IsSMTP(); // enable SMTP
$mail->SMTPAuth = true; // authentication enabled
$mail->SMTPSecure = 'ssl'; // secure transfer enabled REQUIRED for Gmail
$mail->Host = "smtp.gmail.com";
$mail->Port = 465; // or 587
$mail->IsHTML(true);
$mail->Username = 'codekhadimail@gmail.com';//codekhadimail@gmail.com
$mail->Password = '!@#qweasd';//'!@#qweasd';//
$mail->From = $mail->Username; //Default From email same as smtp user
$mail->FromName = "agilehealth";
$mail->AddAddress($register->email, '');
$mail->CharSet = 'UTF-8';
$mail->Subject    = $subject;
$mail->MsgHTML($html);
if($mail->Send())
{
    $res = array('Result'=>'Success',
                 'Status'=>'Password reset link has been sent to your mail');
    echo json_encode($res); exit;
//echo '{ "Result": "Success","Status":"Password reset link has been sent to your mail"}';
} else {
    $res = array('Result'=>'Failed',
                 'Status'=>'Email sent failed');
    echo json_encode($res); exit;

    echo '{"Result":"Failed","Status":"Email sent failed"}';
}
}

else
{
    $res = array('Result'=>'Failed',
                 'Status'=>'User not found');
    echo json_encode($res); exit;
}
}
catch(PDOException $e)
{

    $res = array('Result'=>'Failed','error'=>$e);
    echo json_encode($res); exit;
}
}

function verify($id,$token)
{
    $date=date('Y-m-d H:i:s');
    $email = base64_decode($id);
//echo $email;
//echo $token;
    echo "<br>";
    $qry="update ah_customer set profileStatus=1,updated_at='$date' where email=:email and tempToken=:tempToken";
    try
    {
        $db = getConnection();
        $stmt = $db->prepare($qry);
        $stmt->bindParam("email", $email);
        $stmt->bindParam("tempToken", $token);
        $stmt->execute();
//$user = $stmt->fetch(PDO::FETCH_OBJ);
        $html = "<center><h2>Your account has been verified successfully.</h2></center>";
        echo $html;
    }
//header('Location : verify.php');
    catch(PDOException $e)
    {
        echo $e;
        $res = array('Result'=>'Failed');
        echo json_encode($res); exit;
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
