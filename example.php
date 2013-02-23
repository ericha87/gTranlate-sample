<?php
require_once('spellchecker.class.php');
require_once('googleTranslate.class.php');

$gt = new GoogleTranslateWrapper();
$sc = new SpellChecker();

//post values
$from = ( isset($_POST['from']) ? $_POST['from'] : '你好' );
$enWord = ( isset($_POST['enWord']) ? $_POST['enWord'] : 'hello' );

$targetLanguage = ( isset($_POST['targetLanguage']) ? $_POST['targetLanguage'] : 'zh-cn' );

//target languages
$targetLanguages[0]="zh-cn";
$targetLanguages[1]="ko";
$targetLanguages[2]="fr";

/* option setting for curl */
$apiKey = "AIzaSyCYG2aLrIYaugEGhYEqe_67LJZ232-ghrc";
$ip = $_SERVER['REMOTE_ADDR'];

$gt->setCredentials($apiKey, $ip);

$referer_url = (!empty($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : '');
$gt->setReferrer($referer_url);

/* language detection */
$detectArray = $gt->detectLanguage($from);

$fromLanguage = $detectArray['language'];

//spell checker
$isSentence = strpos( $from, ' ' );   //check sentence or word
$correct_sentence = "";

if ( $isSentence ){
	$sentence = explode( ' ', $from );
	
	for ( $i = 0; $i < count( $sentence ); $i++ ){
		$result = $sc->getCorrectWord( $sentence[$i], $fromLanguage );		
		$correct_sentence .= $result;
		$correct_sentence .= " ";
	}
} else {
	$correct_sentence = $sc->getCorrectWord( $from, $fromLanguage );
}

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Google Translate</title>
</head>
<body>
<form action="" method="post">
	This is translated inputed word to english. please input word : 
	<input type="text" name = "from" id = "from" value = "<?php echo $from; ?>" /><br />
	<?php 
		if( $correct_sentence ){
			echo '<span style="font-style:italic">Did you mean : </span>' . $correct_sentence . '<br />';
		}
	?>
	<?php if( isset( $fromLanguage ) ) ?>	
		Detected Language : <?php echo $fromLanguage; ?>
	result : <?php echo $gt->translate($from, "en", $fromLanguage); ?>
	<input type="submit" value="translate" />
</form>
<br /><br /><br />
<form action="" method="post">
	This is translated english to selected language. please input word : 
	<input type="text" name = "enWord" id = "enWord" value = "<?php echo $enWord; ?>" />
	please selcet language:
	<select name="targetLanguage" id="targetLanguage">
		<?php
		for ($i=0; $i<count($targetLanguages); $i++)
	     {
	        if($targetLanguage == $targetLanguages[$i]){
	          echo "<option selected value='$targetLanguages[$i]'>$targetLanguages[$i]</option>";
	        }else {
	          echo "<option value='$targetLanguages[$i]'>$targetLanguages[$i]</option>";
	        }
	     }
		?>
	</select>
	result : <?php echo $gt->translate($enWord, $targetLanguage, "en"); ?>
	<input type="submit" value="translate" />
</form>
</body>
</html>

