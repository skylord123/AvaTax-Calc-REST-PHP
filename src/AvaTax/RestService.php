<?php

namespace AvaTax;


class RestService
{
    protected $config;

    /**
     * AddressServiceRest constructor.
     *
     * @param $url string - domain for API endpoint
     * @param $account string - API account
     * @param $license string - API license
     * @param bool $ssl - whether to use SSL connecting to the API (Windows users read below)
     * Some Windows users have had trouble with our SSL Certificates. Uncomment the following line to NOT use SSL.
     * This is not recommended, see below ($ssl_ca_path) for better alternative*
     * @param null $ssl_ca_path - Manually set an SSL path to a cert (Windows users read below)
     * $ssl_ca_path: Other Windows users may prefer to download the certificate from our site (detail here: http://developer.avalara.com/api-docs/designing-your-integration/errors-and-outages/ssl-certificates) and manually set the cert path.
     * To set the path manually, uncomment the following two lines and ensure you are telling curl where it can find the root certificate. If you choose to manually set the path, make sure you have reenabled cURL by commenting out the line above
     * that tells curl to NOT use SSL.
     * ex: $ssl_ca_path = "C:/curl/curl-ca-bundle.crt";
     * @param null $curl_options
     */
    public function __construct($url, $account, $license, $ssl = true, $ssl_ca_path = null, $curl_options = null)
    {
        $this->config = array(
            'url' => $url,
            'account' => $account,
            'license' => $license,
            'ssl' => $ssl,
            'ssl_ca_path' => $ssl_ca_path,
            'curl_options' => $curl_options
        );
    }

    /**
     * Send a request to the API endpoint
     *
     * @param $path
     * @param null $data
     * @return mixed
     * @throws AvaException
     */
    protected function processRequest($path, $data = null)
    {
        if(!$this->config['url'] || !(filter_var($this->config['url'], FILTER_VALIDATE_URL))) {
            throw new AvaException("A valid service URL is required.", AvaException::MISSING_INFO);
        }

        if(empty($this->config['account'])) {
            throw new AvaException("Account number or username is required.", AvaException::MISSING_INFO);
        }

        if(empty($this->config['license'])) {
            throw new AvaException("License key or password is required.", AvaException::MISSING_INFO);
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, $this->config['account'].":".$this->config['license']);
        curl_setopt($curl, CURLOPT_URL, $this->config['url'].$path);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $this->config['ssl']);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);

        if($this->config['ssl_ca_path']) {
            curl_setopt($curl, CURLOPT_CAINFO, $this->config['ssl_ca_path']);
        }

        if($data) {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }

        if($this->config['curl_options']) {
            foreach($this->config['curl_options'] as $name => $value) {
                curl_setopt($curl, $name, $value);
            }
        }

        $response = curl_exec($curl);

        if($error_number = curl_errno($curl)) {
            $error_msg = curl_strerror($error_number);
            throw new AvaException("AddressServiceRest cURL error ({$error_number}): {$error_msg}", AvaException::CURL_ERROR);
        }

        if(!$response) {
            throw new AvaException('AddressServiceRest received empty result from API', AvaException::INVALID_API_RESPONSE);
        }

        curl_close($curl);

        return $response;
    }
}