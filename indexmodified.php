
<?php
//defining the constants of "EPO" and "GOOGLE"
	define("EPO", 0);
	define("GOOGLE", 1);
// Presenting the function "printTable"  Why is if (empty) better than if (isset)
	function printTable() {
		if(empty($_POST['submit'])) {
			return;
		}
		//sets forth the string fieldnames as an array having additional nested arrays  why two values in each sub array such as 'ti', 'intitle'
		$fieldNames = array('title' => array('ti', 'intitle:'), 'applicants' => array('ap', 'assignee:'), 'inventors' => array('in', 'ininventor:'), 
			'abstract' => array('ab', ''), 'publicationNumber:' => array('pn', 'patent:'), 'applicationNumber:' => array('ap', 'application:') );
			//What is the purpose of the line below
		$firstTerm = true; $noFields = true;
		//sets the initial values for $epoQueryString and $googleQueryString
		$epoQueryString = ''; $googleQueryString = '';
		//what i going on below in this foreach statement  I understand that it allows for iteration over arrays is the field name associated with for example 'title' and fieldQueryName associated with 'ti' and/or'intitle'
		foreach($fieldNames as $fieldName => $fieldQueryName) {
			if(!empty($_POST[$fieldName])) {
				$noFields = false;
				if(!$firstTerm) {
					$queryString .= '+';
				}
				//Sets the values of the epoQueryString and the GoogleQueryString and posts that to the end of the CURLOPT_URL extension
				$epoQueryString .= preg_replace('/\s+/', ',', $fieldQueryName[EPO].'+all+"'.$_POST[$fieldName].'"');
				$googleQueryString .= preg_replace('/\s+/', ',', $fieldQueryName[GOOGLE].'"'.$_POST[$fieldName].'"');
				$firstTerm = false;
			}
		}
		if($noFields) {
			echo '<p>No search terms included!</p>';
			return;
		}
		//curl procedure for EPO  The base string is listed below at CURLOPT_URL and the epoQuerySTring is concatinated onto this string.
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'http://ops.epo.org/3.1/rest-services/published-data/search/biblio/?q='.$epoQueryString);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_REFERER, 'http://www.willcasper.tk');
		$epoXml = new DOMDocument();
		if($epoXml->loadXML(curl_exec($ch))) {
			// Parse XML here
			echo '<h2>EPO</h2>'.htmlspecialchars($epoXml->saveXML());
		}
		else {
			echo '<p>Could not load EPO data!</p>';
		}
		//Curl Procedure for google  The base string is listed below at the CURLOPT_URL and the googleQuerySTring is concatinated onto that
		curl_setopt($ch, CURLOPT_URL, 'https://ajax.googleapis.com/ajax/services/search/patent?v=1.0&q='.$googleQueryString);
		$googleJSON = NULL;
		if($googleJSON = curl_exec($ch)) {
			// Parse JSON here
			echo '<h2>Google</h2>'.htmlspecialchars($googleJSON);
		}
		curl_close($ch);
	}

//below is the html which outputs the values
?>
<html>
<head>
</head>
<body>
	<p>Enter in search terms (don't put and between them) i.e. "Collard McCauley"</p>
	<form action="index.php" method="POST">
		<!-- each input text that is inserted is sent up to be inserted into the $googleQueryString and/or the $epoQueryString -->
		<label>Title <input type="text" id="title" name="title" /></label><br />
		<label>Applicant(s) <input type="text" id="applicants" name="applicants" /></label><br />
		<label>Inventor(s) <input type="text" id="inventors" name="inventors" /></label><br />
		<label>Abstract <input type="text" id="abstract" name="abstract" /></label><br />
		<label>Publication Number <input type="text" id="publicationNumber" name="publicationNumber" /></label><br />
		<label>Application Number <input type="text" id="applicationNumber" name="applicationNumber" /></label><br />
		<label>Priority Number <input type="text" id="priorityNumber" name="priorityNumber" /></label><br />
		<input type="submit" name="submit" value="Search" /><br />
	</form>
	<?php printTable(); ?>
</body>
</html>