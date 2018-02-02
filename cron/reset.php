<?php
	date_default_timezone_set('America/New_York');
	$time = "".date('his')."";
	#$times = '000000';
	
	if($time == '000000')
	{
		$url = "https://agilehealth-560eb.firebaseio.com/notification/status/true.json";
	        $ch = curl_init();
	        curl_setopt($ch, CURLOPT_URL, $url);
	        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
	        $result = curl_exec($ch);
	        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	        curl_close($ch);
	        Echo $result;exit;
    }
    else
    {
    	Echo "it works 00:00:00 sec".$time;exit;
    }