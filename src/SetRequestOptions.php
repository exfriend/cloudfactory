<?php

namespace Exfriend\CloudFactory;


trait SetRequestOptions
{
    /**
     * @var Options
     */
    public $options;

    public function sendHeaders($headers = [])
    {
        if (!count($headers)) {
            return $this;
        }

        if ($this->isAssoc($headers)) {
            $new_headers = [];
            foreach ($headers as $key => $value) {
                $new_headers [] = $key . ': ' . $value;
            }
            $headers = $new_headers;
        }

        return $this->setOpt(CURLOPT_HTTPHEADER, $headers);
    }

    protected function isAssoc($arr)
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    public function setOpt($name, $value)
    {
        $this->options->set($name, $value);
        return $this;
    }

    /**
     * We need to override standard curl behavior for consistency
     * @param array $postdata
     * @return Request
     */
    public function post(array $postdata)
    {
        foreach ($postdata as $k => $v) {
            $postdata[ $k ] = rawurlencode($k) . '=' . rawurlencode($v);
        }

        $postdata_string = implode('&', $postdata);

        return $this->setOpt(CURLOPT_POSTFIELDS, $postdata_string);
    }

    public function withReferer($referer = false)
    {
        if ($referer !== 'auto') {
            return $this->setOpt(CURLOPT_REFERER, $referer ? $referer : $this->getOpt(CURLOPT_URL));
        }

        return $this->setOpt(CURLOPT_AUTOREFERER, true);
    }

    public function getOpt($name)
    {
        return $this->options->get($name);
    }

    public function useGzip()
    {
        return $this->setOpt(CURLOPT_ENCODING, 'gzip');
    }

    public function withUserAgent(
        $ua = 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36'
    ) {
        return $this->setOpt(CURLOPT_USERAGENT, $ua);
    }

    public function withSsl()
    {
        return $this->setOpt(CURLOPT_SSL_VERIFYHOST, 0)
                    ->setOpt(CURLOPT_SSL_VERIFYPEER, 0);
    }

    public function withTimeouts($load, $conn = null)
    {
        return $this->withLoadTimeout($load)
                    ->withConnectTimeout($conn);
    }

    public function withConnectTimeout($conn)
    {
        return $this->setOpt(CURLOPT_CONNECTTIMEOUT, $conn);
    }

    public function withLoadTimeout($load)
    {
        return $this->setOpt(CURLOPT_TIMEOUT, $load);
    }

    public function withCookies($file)
    {
        if (!file_exists($file)) {
            file_put_contents($file, '');
        }

        return $this->setOpt(CURLOPT_COOKIEJAR, $file)
                    ->setOpt(CURLOPT_COOKIEFILE, $file);
    }

    public function withBasicAuth($username, $password)
    {
        return $this->setOpt(CURLOPT_USERPWD, $username . ':' . $password);
    }

    public function withProxy($ip, $type = CURLPROXY_HTTP)
    {
        return $this->setOpt(CURLOPT_PROXY, $ip)
                    ->setOpt(CURLOPT_PROXYTYPE, $type);
    }


}