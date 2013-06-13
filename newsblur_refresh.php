<?php
    $username = "_USERNAME_";
    $password = "_PASSWORD_";

    $cookies_file = "newsblur_cookies.txt";
	if(is_file($cookies_file)){
		unlink($cookies_file);
	}

    getCookies($username, $password, $cookies_file);
    $feeds = getFeeds($cookies_file);
    forceUpdateFeeds($feeds, $cookies_file);
    if(is_file($cookies_file)){
		unlink($cookies_file);
	}

    function getCookies($username, $password, $cookies_file) {
		$newsblurLogin = "http://newsblur.com/reader/login";
        $res = sendRequest("POST", $newsblurLogin, NULL, "login-username=" . rawurlencode($username) . "&login-password=" . rawurlencode($password).'&next=/&submit=log%20in', true, false, false, $cookies_file, true);
		
		if(strpos($res['data'],'<ul class="errorlist">') !== false){
			echo 'Failed connecting! Exiting!';
			exit;
		}
	}

    function getFeeds($cookies_file) {
        $feedsURL = "http://newsblur.com/reader/feeds?v=2";
        $res = sendRequest("GET", $feedsURL, null, null, false, false, false, $cookies_file, null);
		$data = json_decode($res['data']);
		
		$response = array(
			'feeds' => array()
		);
		
		foreach($data->feeds as $feed){
			$seconds_ago_updated = intVal($feed->updated_seconds_ago);
			$id = $feed->id;
			if($seconds_ago_updated < 1800){
				continue;
			}
			
			$response['feeds'][] = array(
				'id' => $id
			);
		}
		
		echo "Number of needing update Feeds: " . count($response['feeds']) . "\n";

		return $response;
    }

    function forceUpdateFeeds($data, $cookies_file) {
		if(empty($data['feeds'] )){return;}
		
		foreach($data['feeds'] as $feed){
			$id = $feed['id'];
			echo "Processing feeds {$id}\n";
			
			$updateUrl = "http://newsblur.com/rss_feeds/exception_retry";
			$res = sendRequest("POST", $updateUrl, array(
				'Accept-Language: en-US,en;q=0.5',
				'Cache-Control: no-cache',
				'Connection: keep-alive',
				'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
				'Host: newsblur.com',
				'Pragma: no-cache',
				'X-Requested-With: XMLHttpRequest'
			), "feed_id=" . rawurlencode($id) . "&reset_fetch=false", true, false, false, $cookies_file);
			
			$refreshUrl = "http://newsblur.com/reader/refresh_feeds";
			$res = sendRequest("POST", $refreshUrl, array(
				'Accept-Language: en-US,en;q=0.5',
				'Cache-Control: no-cache',
				'Connection: keep-alive',
				'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
				'Host: newsblur.com',
				'Pragma: no-cache',
				'X-Requested-With: XMLHttpRequest'
			), "feed_id=" . rawurlencode($id), true, false, false, $cookies_file);
		}
    }

    function sendRequest($http_method, $url, $extraHeaders=null, $postData=null, $returnResponseHeaders=false, $returnRequestHeaders=false, $cookies=null, $cookieFile=false, $saveCookies=null) {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
		
		$agent= 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
		curl_setopt($curl, CURLOPT_USERAGENT, $agent);

        if ($cookies) {
            curl_setopt($curl, CURLOPT_COOKIE, $cookies);
        }

		if ($cookieFile) {
			curl_setopt($curl, CURLOPT_COOKIEFILE, $cookieFile);
			curl_setopt($curl, CURLOPT_COOKIEJAR, $cookieFile);
		}

            //curl_setopt($curl, CURLOPT_HEADER, true);

        $headers = array();
		if (is_array($extraHeaders)) {
            $headers = array_merge($headers, $extraHeaders);
        }

        switch($http_method) {
            case 'GET':
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            break;
            case 'POST':
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
            break;
            case 'PUT':
                $headers[] = 'If-Match: *';
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $http_method);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
            break;
            case 'DELETE':
                $headers[] = 'If-Match: *';
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $http_method);
            break;
            default:
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }

		curl_setopt($curl, CURLINFO_HEADER_OUT, true);
		
		$response = array();
        $response['data'] = curl_exec($curl);

        if (!$response) {
            $response['error'] .= curl_error($curl);
        }

		$response['header_out'] = curl_getinfo($curl, CURLINFO_HEADER_OUT);

        curl_close($curl);
		
		if(is_file($cookieFile)){
			$cooky = file_get_contents($cookieFile);
			$cooky = str_replace('#HttpOnly_', '', $cooky);
			file_put_contents($cookieFile, $cooky);
		}

        return $response;
    }

