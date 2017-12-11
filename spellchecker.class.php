<?php
class SpellChecker{
 /**
     * URL of Google service
     * @var string
     */
    private $_googleServiceUrl = "https://www.google.com/tbproxy/spell?lang=";

	public function getCorrectWord( $word, $lang ){
		$url = $this->_googleServiceUrl . $lang;

		$body = '';
		$body .= '<spellrequest textalreadyclipped="0" ignoredups="1" ignoredigits="' . $_GET['ignoredigits'] . '" ignoreallcaps="' . $_GET['ignorecaps'] . '">';
		$body .= '<text>' . $word . '</text>';
		$body .= '</spellrequest>';

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$contents = curl_exec($ch);
		curl_close($ch);

		$correct_string = "";

		$xml = new SimpleXMLElement($contents);
		$correct_string = $xml->c[0];

		$d = strpos( $correct_string, "\t" );
		
		if ( $correct_string ){
			$wordArray = explode( "\t", $correct_string );
			return $wordArray[0];
		} else {
			return $word;
		}
	}
}
?>
