<?php

namespace App\Helper\HWOcrClient;


class HWOcrClientToken{

    private $tokenTimes = 3;

    var  $username;
    var  $password;
    var  $domainName;
    var  $regionName;
    var  $endPoint;
    var  $uri;

    private $ocrToken = "";

    /* HWOcrClientToken init method */
    public function __construct($username, $password, $domainName, $regionName, $uri)
    {
        if (empty($username)){
            echo "username can not be empty" . "<br>";
            return;
        }
        if (empty($password)){
            echo "password can not be empty" . "<br>";
            return;
        }
        if (empty($domainName)){
            echo "domainName can not be empty" . "<br>";
            return;
        }
        if (empty($regionName)){
            echo "regionName can not be empty" . "<br>";
            return;
        }
        if (empty($uri)) {
            echo "uri can not be empty" . "<br>";
            return;
        }
        $this->username = $username;
        $this->password = $password;
        $this->domainName = $domainName;
        $this->regionName = $regionName;
        $this->endPoint = "ocr." . $regionName . ".myhuaweicloud.com";
        $this->uri = $uri;

        $this->ocrToken = $this->GetToken();
    }

    /* get Token */
    function GetToken()
    {
        while (true) {
            if (!empty($this->ocrToken)) {
                return $this->ocrToken;
            }
            if ($this->tokenTimes > 0) {
                $requestBody =  $this->RequestBodyForGetToken();
                $_url = "https://iam.".$this->regionName.".myhuaweicloud.com/v3/auth/tokens" ;
                $headers = array(
                    "Content-Type:application/json"
                );

                /* 设置请求体 */
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, $_url);
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $requestBody);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($curl, CURLOPT_NOBODY, FALSE);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_HEADER, true);
                curl_setopt($curl, CURLOPT_TIMEOUT, 15);

                $response = curl_exec($curl);

                $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
                $headers = substr($response, 0, $headerSize);
                curl_close($curl);
                $this->ocrToken = $this-> GetTokenByHeaders($headers);

                if (!empty($this->ocrToken)){
                    break;
                }
            }
        }
        if (empty($this->ocrToken)) {
            return null;
        }
        return $this->ocrToken;
    }

    /* set the body of get token request */
    function RequestBodyForGetToken()
    {
        $param = array(
            "auth" => array(
                "identity" => array(
                    "password" => array(
                        "user" => array(
                            "password" => $this->password,
                            "domain" => array(
                                "name" => $this->domainName
                            ),
                            "name" => $this->username
                        )
                    ),
                    "methods" => array("password")

                ),
                "scope" => array(
                    "project" => array(
                        "name" => $this->regionName
                    )
                )
            )
        );
        return json_encode($param);

    }

    /* get the value of token */
    function GetTokenByHeaders($headers)
    {
        $headArr = explode("\r\n", $headers);
        foreach ($headArr as $loop) {
            if (strpos($loop, "X-Subject-Token") !== false) {
                $token = trim(substr($loop, 17));
                return $token;
            }
        }
        return null;
    }

    /* OCR recognise method */
    public function RequestOcrResult($imageBase64, $options=array()){
        $error = [];
        if (empty($this->uri)){
            echo "uri can not be empty";
            $error["msg"] = "uri can not be empty";
            $error["code"] = 4000;
            return json_encode($error);
        }
        if (empty($imageBase64)){
            echo "imagePath can not be empty";
            $error["msg"] = "imagePath can not be empty";
            $error["code"] = 4000;
            return json_encode($error);
        }

        $url = "https://" . $this->endPoint . $this->uri;

        $data = array();
        /*if (stripos($imagePath, 'http://') !== false || stripos($imagePath, 'https://') !== false) {
            $data['url'] = $imagePath;
        } else {
            if($fp = fopen($imagePath,"rb", 0))
            {
                $gambar = fread($fp,filesize($imagePath));
                fclose($fp);

                $fileBase64 = chunk_split(base64_encode($gambar));
            } else {
                $error["msg"] = "get image failure";
                $error["code"] = 4001;
                return json_encode($error);
            }
            $data['image'] = $fileBase64;
        }*/
		$data['image'] = $imageBase64;

        if(!empty($options)){
            $data = array_merge($data, $options);
        }

        $curl = curl_init();
        $headers = array(
            "Content-Type:application/json",
            "X-Auth-Token:" . $this->ocrToken
        );

        /* 设置请求体 */
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_NOBODY, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($status == 200) {
            curl_close($curl);
            return $response;
        } else {
            $error["code"] = $status;
            if (empty($response)) {
                $error["msg"] = curl_error($curl);
            } else {
                $error["msg"] = $response;
            }
            curl_close($curl);
            return json_encode($error);
        }
    }
}
