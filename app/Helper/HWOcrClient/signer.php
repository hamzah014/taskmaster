<?php

namespace App\Helper\HWOcrClient;

define("BasicDateFormat", "Ymd\THis\Z");
define("Algorithm", "SDK-HMAC-SHA256");
define("HeaderXDate", "X-Sdk-Date");
define("HeaderHost", "host");
define("HeaderAuthorization", "Authorization");
define("HeaderContentSha256", "X-Sdk-Content-Sha256");

class Request
{
    public $method = '';
    public $scheme = '';
    public $host = '';
    public $uri = '';
    public $query = array();
    public $headers = array();
    public $body = '';
}

class Signer
{
    public $AppKey = '';
    public $AppSecret = '';

    function escape($string)
    {
        $entities = array('+', "%7E");
        $replacements = array('%20', "~");
        return str_replace($entities, $replacements, urlencode($string));
    }

    function findHeader($r, $header)
    {
        foreach ($r->headers as $key => $value) {
            if (!strcasecmp($key, $header)) {
                return $value;
            }
        }
        return NULL;
    }

    function CanonicalRequest($r, $signedHeaders)
    {
        $CanonicalURI = $this->CanonicalURI($r);
        $CanonicalQueryString = $this->CanonicalQueryString($r);
        $canonicalHeaders = $this->CanonicalHeaders($r, $signedHeaders);
        $signedHeadersString = join(";", $signedHeaders);
        $hash = $this->findHeader($r, HeaderContentSha256);
        if (!$hash) {
            $hash = hash("sha256", $r->body);
        }
        return "$r->method\n$CanonicalURI\n$CanonicalQueryString\n$canonicalHeaders\n$signedHeadersString\n$hash";
    }

    function CanonicalURI($r)
    {
        $pattens = explode("/", $r->uri);
        $uri = array();
        foreach ($pattens as $v) {
            array_push($uri, $this->escape($v));
        }
        $urlpath = join("/", $uri);
        if (substr($urlpath, -1) != "/") {
            $urlpath = $urlpath . "/";
        }
        return $urlpath;
    }

    function CanonicalQueryString($r)
    {
        $keys = array();
        foreach ($r->query as $key => $value) {
            array_push($keys, $key);
        }
        sort($keys);
        $a = array();
        foreach ($keys as $key) {
            $k = $this->escape($key);
            $kv = "$k=" . $this->escape($r->query[$key]);
            array_push($a, $kv);
        }
        return join("&", $a);
    }

// CanonicalHeaders
    function CanonicalHeaders($r, $signedHeaders)
    {
        $headers = array();
        foreach ($r->headers as $key => $value) {
            $headers[strtolower($key)] = trim($value);
        }
        $a = array();
        foreach ($signedHeaders as $key) {
            array_push($a, $key . ':' . $headers[$key]);
        }
        return join("\n", $a) . "\n";
    }

    function curlHeaders($r)
    {
        $header = array();
        foreach ($r->headers as $key => $value) {
            array_push($header, strtolower($key) . ':' . trim($value));
        }
        return $header;
    }

// SignedHeaders
    function SignedHeaders($r)
    {
        $a = array();
        foreach ($r->headers as $key => $value) {
            array_push($a, strtolower($key));
        }
        sort($a);
        return $a;
    }

// Create a "String to Sign".
    function StringToSign($canonicalRequest, $t)
    {
        date_default_timezone_set('UTC');
        $date = date(BasicDateFormat, $t);
        $hash = hash("sha256", $canonicalRequest);
        return "SDK-HMAC-SHA256\n$date\n$hash";
    }

// Create the HWS Signature.
    function SignStringToSign($stringToSign, $signingKey)
    {
        return hash_hmac("sha256", $stringToSign, $signingKey);
    }

// Get the finalized value for the "Authorization" header. The signature parameter is the output from SignStringToSign
    function AuthHeaderValue($signature, $accessKey, $signedHeaders)
    {
        $signedHeadersString = join(";", $signedHeaders);
        return "SDK-HMAC-SHA256 Access=$accessKey, SignedHeaders=$signedHeadersString, Signature=$signature";
    }

    public function Sign($r)
    {
        date_default_timezone_set('UTC');
        $date = $this->findHeader($r, HeaderXDate);
        if ($date) {
            $t = date_timestamp_get(date_create_from_format(BasicDateFormat, $date));
        } else {
            $t = time();
        }
        $queryString = $this->CanonicalQueryString($r);
        if ($queryString != "") {
            $queryString = "?" . $queryString;
        }

        $signedHeaders = $this->SignedHeaders($r);
        $canonicalRequest = $this->CanonicalRequest($r, $signedHeaders);
        $stringToSign = $this->StringToSign($canonicalRequest, $t);
        $signature = $this->SignStringToSign($stringToSign, $this->AppSecret);
        $authValue = $this->AuthHeaderValue($signature, $this->AppKey, $signedHeaders);
        $r->headers[HeaderAuthorization] = $authValue;

        $curl = curl_init();
        $url = $r->scheme . '://' . $r->host . $r->uri . $queryString;

        $headers = $this->curlHeaders($r);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $r->method);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $r->body);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_NOBODY, FALSE);
        return $curl;
    }
}
