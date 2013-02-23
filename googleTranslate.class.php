<?php

class GoogleTranslateWrapper
{
    /**
     * URL of Google translate
     * @var string
     */
    private $_googleTranslateUrl = 'https://www.googleapis.com/language/translate/v2';
    
    /**
     * URL of Google language detection
     * @var string
     */
    private $_googleDetectUrl = 'https://www.googleapis.com/language/translate/v2/detect';
    
    /**
     * Language to translate from
     * @var string
     */
    private $_fromLang = '';
    
    /**
     * Language to translate to
     * @var string
     */
    private $_toLang = '';
    
    /**
     * API version
     * @var string
     */
    private $_version = '2.0';
    
    /**
     * Text to translate
     * @var string
     */
    private $_text = '';
    
    /**
     * Site url using the code
     * @var string
     */
    private $_siteUrl = '';
    
    /**
     * Google API key
     * @var string
     */
    private $_apiKey = '';
    
    /**
     * Host IP address
     * @var string
     */
    private $_ip = '';
    
    /**
     * POST fields
     * @var string
     */
    private $_postFields;
    
    /**
     * Translated Text
     * @var string
     */
    private $_translatedText;
    
    /**
     * Service Error
     * @var string
     */
    private $_serviceError = "";
    
    /**
     * Translation success
     * @var boolean
     */
    private $_success = false;
    
    /**
     * Translation character limit.
     * Currently the limit set by Google is 5000
     * @var integer
     */
    private $_stringLimit = 5000;
    
    /**
     * Chunk array
     * @var array
     */
    private $_chunks = 0;
    
    /**
     * Current data chunk
     * @var string
     */
    private $_currentChunk = 0;
    
    /**
     * Total chunks
     * @var integer
     */
    private $_totalChunks = 0;
    
    /**
     * Detected source language
     * @var string
     */
    private $_detectedSourceLanguage = "";
    
    const DETECT = 1;
    const TRANSLATE = 2;

    
    /**
     * Build a POST url to query Google
     *
     */
    private function _composeUrl($type) 
    {
        if($type == self::TRANSLATE)
        {
            $fields = array('q'         => $this->_text,
                            'source'    => $this->_fromLang,
							'target'    => $this->_toLang);
        }
        elseif($type == self::DETECT)
        {
            $fields = array('q'         => $this->_text);
        }
        
        if($this->_apiKey != "") $fields['key'] = $this->_apiKey;
        if($this->_ip != "") $fields['userip'] = $this->_ip;

        $this->_postFields = http_build_query($fields, '', "&");
    }

    
    /**
     * Process the built query using cURL and POST
     *
     * @param string POST fields
     * @return string response
     */
    private function _remoteQuery($query)
    {
        if(!function_exists('curl_init'))
        {
            return "";
        }
        
        /* Setup CURL and its options*/
        $ch = curl_init();
		$url = $this->_googleTranslateUrl . "?" . $query;
		curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, $this->_siteUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-HTTP-Method-Override: GET'));
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        $response = curl_exec($ch); 
        return $response;
    }
    
    
    /**
     * Process the built query using cURL and GET
     *
     * @param string GET fields
     * @return string response
     */
    private function _remoteQueryDetect($query)
    {
        if(!function_exists('curl_init'))
        {
            return "";
        }
        
        $ch = curl_init();
        $url = $this->_googleDetectUrl . "?" . $query;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_REFERER, $this->_siteUrl);

        $response = curl_exec($ch); 
		return $response;
    }
    
    
    /**
     * Self test the class
     *
     * @return boolean
     */
    public function selfTest()
    {
        if(!function_exists('curl_init'))
        {
            echo "cURL not installed.";
        }
        else
        {
            $testText = $this->translate("hello", "fr", "en");
            echo ($testText == "bonjour") ? "Test Ok." : "Test Failed.";
        }
    }
    
    /**
     * Check if the last translation was a success
     *
     * @return boolean
     */
    public function isSuccess()
    {
        return $this->_success;
    }
    
    /**
     * Get the last generated service error
     *
     * @return String
     */
    public function getLastError()
    {
        return $this->_serviceError;
    }
    
    
    /**
     * Get the detected source language, if the source is not provided
     * during query
     *
     * @return String
     */
    public function getDetectedSource()
    {
        return $this->_detectedSourceLanguage;
    }
    
    
    /**
     * Set credentials (optional) when accessing Google translation services
     *
     * @param string $apiKey your google api key
     */
    public function setCredentials($apiKey, $ip)
    {
        $this->_apiKey = $apiKey;
        $this->_ip = $ip;
    }
    
    
    /**
     * Set Referrer header
     *
     * @param string $siteUrl your website url
     */
    public function setReferrer($siteUrl)
    {
        $this->_siteUrl = $siteUrl;
    }
    
    
    /**
     * Translate the given text
     * @param string $text text to translate
     * @param string $to language to translate to
     * @param string $from optional language to translate from
     * @return boolean | string
     */
    public function translate($text = '', $to, $from = '')
    {
        $this->_success = false;
        
        if($text == '' || $to == '')
        {
            return false;
        }
        else
        {
            if($this->_chunks == 0)
            {
                $this->_chunks = str_split($text, $this->_stringLimit);
                $this->_totalChunks = count($this->_chunks);
                $this->_currentChunk = 0;
             
                $this->_text = $this->_chunks[$this->_currentChunk];
                $this->_toLang = $to;
                $this->_fromLang = $from;
            }
            else
            {
                $this->_text = $text;
                $this->_toLang = $to;
                $this->_fromLang = $from;
            }
        }
        
        $this->_composeUrl(self::TRANSLATE);
        
        if($this->_text != '' && $this->_postFields != '')
        {
			$contents = $this->_remoteQuery($this->_postFields);
            $json = json_decode($contents, true);

			if($json['error']['code'] == 400)
            {   
				$this->_serviceError = 	$json['error']['errors']['reason'];
                return false;
            }
            else
            { 
                return $json['data']['translations'][0]['translatedText'];
            }
        }
        else
        {
            return false;
        }
    }
    
    /**
     * Detect the language of the given text
     * @param string $text text language to detect
     * @return boolean | string
     */
    public function detectLanguage($text)
    {
    
        if($text == '')
        {
            return false;
        }
        else
        {
            $this->_text = $text;
        }
        
        
        $this->_composeUrl(self::DETECT);
        
        if($this->_text != '' && $this->_postFields != '')
        {
			$contents = $this->_remoteQueryDetect($this->_postFields);
			$json = json_decode($contents, true);

			if($json['error']['code'] == 400)
            {
				$this->_serviceError = 	$json['error']['errors'][0]['reason'];
                return false;
			}
            else
            { 
                return $json['data']['detections'][0][0];
            }
        }
        else
        {
            return false;
        }

    }
    
}


?>