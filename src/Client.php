<?php
namespace Websolve\Leads;

/**
 * Class Client
 *
 * @package   Websolve\Leads
 * @copyright Websolve B.V. <support@websolve.eu>
 */
class Client
{
    /**
     * @var string $provider_code Code for Your LeadProvider->IP you can set these in the eCRM -> configuration -> leads providers
     */
    private $provider_code;

    /**
     * @var \SoapClient $connection WLS Api Client
     */
    private $connection;

    /**
     * @var array $options SoapClient Options
     */
    private $options;

    /**
     * @var string $wsdl_uri WSDL URI holder
     */
    private $wsdl_uri;

    /**
     * @var string $endpoint_uri SoapClient Location holder
     */
    private $endpoint_uri;

    /**
     * @var array $valid_options Options Keys accepted by SoapClient
     */
    private $valid_options = array(
        'soap_version',
        'compression',
        'encoding',
        'trace',
        'cache_wsdl',
        'exceptions',
        'login',
        'password',
        'proxy_host',
        'proxy_port',
        'proxy_login',
        'proxy_password',
        'user_agent',
        'connection_timeout',
        'stream_context',
        'features',
        'keep_alive',
        'ssl_method',
        'classmap',
        'typemap'
    );

    /**
     * @var array Keys That Will Be Mapped To Corresponding Entity
     */
    public $data_keys = array(
        'lead'     => array(
            'automotive_leads',
            'lead'
        ),
        'customer' => array(
            'automotive_leads_info_customer',
            'customer'
        )
    );

    /**
     * @var array $cache Temporary Cache
     */
    private $cache;

    /**
     * WLSApi constructor.
     *
     * @param mixed  $environment [live | ...dev]
     * @param array  $options     SoapClient options
     *
     * @throws \Exception
     */
    public function __construct($environment = 'live', array $options = array())
    {
        if (!class_exists('\SoapClient')) {
            throw new \Exception('Missing required PHP SoapClient Class');
        }

        if ($environment !== 'live') {
            $this->wsdl_uri     = 'http://websolve-dev.nl/webservices/automotiveLeads.php?WSDL';
            $this->endpoint_uri = 'http://websolve-dev.nl/webservices/automotiveLeads.php';
        }
        else {
            $this->wsdl_uri     = 'https://websolve.nl/webservices/automotiveLeads.php?WSDL';
            $this->endpoint_uri = 'https://websolve.nl/webservices/automotiveLeads.php';
        }

        $this->options = array_intersect_key(array_replace(array(
                                                               'soap_version' => SOAP_1_2 | SOAP_1_1,
                                                               'compression'  => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP,
                                                               'encoding'     => 'UTF-8',
                                                               'trace'        => true,
                                                               'cache_wsdl'   => WSDL_CACHE_NONE
                                                           ), $options), array_flip($this->valid_options));

        try {
            $this->connection = new \SoapClient($this->wsdl_uri, $this->options);
            $this->connection->__setLocation($this->endpoint_uri); # not strictly necessary, info contained in WSDL
        }
        catch (\Exception $e) {
            /* TODO handle exception */
            return false;
        }
    }

    /**
     * Set the Provider Code to be used globally withing this class
     *
     * @param string $code
     *
     * @return Client $this
     */
    public function setProviderCode($code)
    {
        $this->provider_code = $code;
        return $this;
    }

    /**
     * Can be Used To Set the Keys with which the Data is Sent to the setLead() function
     * By default these keys are
     *      'automotive_leads' for the Lead Information (already extended to 'lead')
     *      'automotive_leads_info_customer' for the Customer Information (already extended to 'customer')
     *
     * @param array $keys
     */
    public function setDataKeys(array $keys)
    {
        $this->data_keys = array_replace_recursive($this->data_keys, $keys);
        foreach (array('lead', 'customer') as $entity) {
            if (!is_array($this->data_keys[$entity])) {
                $this->data_keys[$entity] = array($this->data_keys[$entity]);
            }
        }
    }

    /**
     * Get Leads Data Format
     *
     * @param string $response_output_format
     * @param null   $provider_code
     *
     * @return array|bool|mixed
     * @throws \Exception
     */
    public function getLeadHeaders($response_output_format = 'xml', $provider_code = null)
    {
        if(empty($this->cache['lead_headers'])) {
            $provider_code = $this->_code($provider_code);

            try {
                $xml = $this->connection->__soapCall('getLeadHeaders', array(
                    $provider_code
                ));
            }
            catch (\Exception $e) {
                /* TODO handle exception */
                return false;
            }

            $this->cache['lead_headers'] = $xml;
        }

        switch ($response_output_format) {
            case 'xml':
                return $this->cache['lead_headers'];
                break;
            case 'array':
                return $this->xmlToArray($this->cache['lead_headers']);
                break;
            default:
                throw new \Exception('implement XML to ' . $response_output_format . ' conversion in ' . __METHOD__);
        }
    }

    /**
     * Get Lead Status
     *
     * @param        $refID
     * @param string $response_output_format [xml|array]
     * @param null   $provider_code
     *
     * @return array|bool|mixed
     * @throws \Exception
     */
    public function getLeadStatus($refID, $response_output_format = 'xml', $provider_code = null)
    {
        $provider_code = $this->_code($provider_code);

        try {
            $xml = $this->connection->__soapCall('getLead', array(
                $provider_code,
                $refID
            ));
        }
        catch (\Exception $e) {
            /* TODO handle exception */
            return false;
        }

        switch ($response_output_format) {
            case 'xml':
                return $xml;
                break;
            case 'array':
                return $this->xmlToArray($xml);
                break;
            default:
                throw new \Exception('implement XML to ' . $response_output_format . ' conversion in ' . __METHOD__);
        }
    }

    /**
     * Submit a New Lead
     *
     * @param array  $data                   An Array Containing The Lead/Customer Information
     * @param string $response_output_format Response Preferred Format
     * @param null   $provider_code          Your Desired WLS Provider Code
     *
     * @calls getLeadHeaders                 Preload Headers to be used in [ fields matching + validation ]
     *
     * @return array|bool|mixed
     * @throws \Exception
     */
    public function setLead($data, $response_output_format = 'xml', $provider_code = null)
    {
        $provider_code = $this->_code($provider_code);
        $this->getLeadHeaders('xml', $provider_code);
        $xml_request = $this->_array2SetLeadXML($data);

        try {
            $xml = $this->connection->__soapCall('setLead', array(
                $provider_code,
                $xml_request
            ));
        }
        catch (\Exception $e) {
            /* TODO handle exception */
            return false;
        }

        switch ($response_output_format) {
            case 'xml':
                return $xml;
                break;
            case 'array':
                return $this->xmlToArray($xml);
                break;
            case 'bool':
            case 'boolean':
                try {
                    $tmp = new \SimpleXMLElement($xml);
                    return ($tmp->request_status == 'request processed' && $tmp->created == 1 && intval($tmp->returnID->__toString()) > 0);
                }
                catch (\Exception $e) {
                    /* TODO handle exception */
                }

                return false;
                break;
            case 'int':
            case 'integer':
                try {
                    $tmp = new \SimpleXMLElement($xml);
                    if ($tmp->request_status == 'request processed' && $tmp->created == 1) {
                        return $tmp->returnID->__toString();
                    }
                }
                catch (\Exception $e) {
                    /* TODO handle exception */
                }

                return false;
                break;
            default:
                throw new \Exception('implement XML to ' . $response_output_format . ' conversion in ' . __METHOD__);
        }
    }

    /**
     * Convert an XML (response) to Array
     *
     * @param string|\SimpleXMLElement $xml   XML to Convert
     * @param array|null              $array The Array To Be Filled
     *
     * @return array|bool The Data from XML | false on failure
     */
    public function xmlToArray($xml, array &$array = array())
    {
        if (is_string($xml)) {
            try {
                $xml = new \SimpleXMLElement($xml);
            }
            catch (\Exception $e) {
                /* TODO handle exception */
                return false;
            }
        }
        if ($xml instanceof \SimpleXMLElement) {
            $xml = (array) $xml;
        }
        else {
            return false;
        }

        foreach ($xml as $key => $value) {
            if (is_object($value)) {
                $array[$key] = $this->xmlToArray($value);
            }
            elseif (is_string($value)) {
                $array[$key] = $value;
            }
        }

        return $array;
    }

    /**
     * Converts the LeadData to the XML request accepted by the API
     * N.B. 'Source' key is available for both lead/customer. If data is not divided into 'table' arrays this information (in customer) will be lost
     *
     * @param array $array Lead Data
     *
     * @return string Compiled XML
     * @throws \Exception If Data Keys Have Been Set Wrongly (empty)
     */
    private function _array2SetLeadXML(array $array)
    {
        $headers    = $this->xmlToArray($this->cache['lead_headers']);
        $doc        = new \DOMDocument('1.0', 'UTF-8');
        $lead_root  = $doc->createElement('lead');
        $lead       = $doc->createElement('automotive_leads');
        $customer   = $doc->createElement('automotive_leads_info_customer');

        foreach ($array as $key => $val){
            if (in_array($key, array_merge($this->data_keys['lead'], $this->data_keys['customer']))) { # is data stored based on table keys ?
                foreach ($val as $field => $value){
                    switch (true) {
                        case in_array($key, $this->data_keys['lead']):
                            if (($value = $this->_validate($value, $headers['automotive_leads'][$field]))) {
                                $node = $doc->createElement($field, $value);
                                $lead->appendChild($node);
                            }
                            break;
                        case in_array($key, $this->data_keys['customer']):
                            if (($value = $this->_validate($value, $headers['automotive_leads_info_customer'][$field]))) {
                                $node = $doc->createElement($field, $value);
                                $customer->appendChild($node);
                            }
                            break;
                    }
                }
            }
            else {
                $this->_searchInsertValue($key, $val, $headers, $doc, $lead, $customer);
            }
        }

        $lead_root->appendChild($lead);
        $lead_root->appendChild($customer);
        $doc->appendChild($lead_root);

        return $doc->saveXML();
    }

    /**
     * Search a Key for matching the target schema [lead/customer], if found, the data is inserted into the XML
     *
     * @param string       $key
     * @param string|mixed $value
     * @param array        $headers  Data Headers
     * @param \DOMDocument $doc      XML Document
     * @param \DOMElement  $lead     Lead Node
     * @param \DOMElement  $customer Customer Node
     */
    private function _searchInsertValue($key, $value, $headers, \DOMDocument $doc, \DOMElement $lead, \DOMElement $customer)
    {
        if (array_key_exists($key, $headers['automotive_leads'])) {
            if(($value = $this->_validate($value, $headers['automotive_leads'][$key]))) {
                $lead->appendChild($doc->createElement($key, $value));
            }
        }
        elseif (array_key_exists($key, $headers['automotive_leads_info_customer'])) {
            if(($value = $this->_validate($value, $headers['automotive_leads_info_customer'][$key]))){
                $customer->appendChild($doc->createElement($key, $value));
            }
        }
    }

    /**
     * Validate/Fix Values
     *
     * @param $value
     * @param $field_properties
     *
     * @return mixed boolean false on failure string if value is OK or could be fixed
     */
    private function _validate($value, $field_properties)
    {
        switch ($field_properties['type_name']) {
            case 'INT':
            case 'TEXT':
            case 'VARCHAR':
                $value = trim($value);
                if (strlen($value) > $field_properties['length']) {
                    return substr($value, 0, $field_properties['length']);
                }
                if (!empty($value)) {
                    return $value;
                }
                break;
            case 'DATE':
                if ($value instanceof \DateTime) {
                    $value = $value->format('Y-m-d');
                }
                if (($timestamp = strtotime($value))) {
                    return date('Y-m-d', $timestamp);
                }
                break;
            case 'DATETIME':
                if ($value instanceof \DateTime) {
                    $value = $value->format('Y-m-d H:i:s');
                }
                if (($timestamp = strtotime($value))) {
                    return date('Y-m-d H:i:s', $timestamp);
                }
                break;
            case 'ENUM':
                $value = trim($value);
                if (!empty(array_intersect(array(
                                               $value,
                                               strtolower($value),
                                               strtoupper($value)
                                           ),
                                           $field_properties['values']
                ))
                ) {
                    return $value;
                }
                elseif (is_bool($value) AND substr($field_properties['name'], 0, 5) == 'tpson') {
                    return ($value ? 'Y' : 'N');
                }
                break;
        }

        return false;
    }

    /**
     * @param string|null $code
     *
     * @return null|string
     * @throws \Exception
     */
    private function _code($code = null)
    {
        $code = $code ? : $this->provider_code;
        if (empty($code)) {
            throw new \Exception('missing required provider code, you can either set it globally calling the function setProviderCode(string) or provide it to the function as last parameter');
        }

        return $code;
    }
}