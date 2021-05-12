<html>
<body>

<?php

getInventorsAndDates();

function getInventorsAndDates() {
	if(!empty($_POST['submit'])) {
		if(empty($_POST['patentNumbers'])) {
			echo 'No patent numbers specified! <hr />';
			return;
		}

		if(empty($_POST['database'])) {
			echo 'No database specified! <hr />';
			return;
		}

		if(empty($_POST['zipFileLabel'])) {
			$zipFileName = '';
		}

		else {
			$zipFileName = $_POST['zipFileLabel'] . '_';
		}
		date_default_timezone_set('America/New_York');
		$zipFileName .= date('Hia_d-M-Y');

		if($_POST['database'] === 'USPTO') {
			$zipFileName .= '_USPTO.zip';
			$fileNameArrays = array();
			$downloadUrls = array();
			$patentNumbers = preg_split('/\n/', $_POST['patentNumbers']);

			foreach($patentNumbers as $patentNumber) {
				if($patentNumber == '') {
					continue;
				}
				$fileNameArray = array();
				$patentNumber = preg_replace('/[^0-9]*/', '', $patentNumber);
				$patentNumberPadded = str_pad($patentNumber, 8, '0', STR_PAD_LEFT);
				$patentNumberPadded1 = substr($patentNumberPadded, 0, 3);
				$patentNumberPadded2 = substr($patentNumberPadded, 3, 3);
				$patentNumberPadded3 = substr($patentNumberPadded, 6, 2);

				$patentApplicationNumberPadded = str_pad($patentNumber, 11, '0', STR_PAD_LEFT);
				$patentApplicationNumber1 = substr($patentApplicationNumberPadded, 0, 4);
				$patentApplicationNumber2 = substr($patentApplicationNumberPadded, 4, 3);
				$patentApplicationNumber3 = substr($patentApplicationNumberPadded, 7, 2);
				$patentApplicationNumber4 = substr($patentApplicationNumberPadded, 9, 2);
				
				$downloadOnePageNumberString = "http://pdfpiw.uspto.gov/$patentNumberPadded3/$patentNumberPadded2/$patentNumberPadded1/1.pdf";
				$downloadAllPagesNumberString = "http://pimg-fpiw.uspto.gov/fdd/$patentNumberPadded3/$patentNumberPadded2/$patentNumberPadded1/0.pdf";
				$downloadOnePageApplicationString = "http://pdfaiw.uspto.gov/$patentApplicationNumber4/$patentApplicationNumber1/$patentApplicationNumber3/$patentApplicationNumber2/1.pdf";
				$downloadAllPagesApplicationString = "http://pimg-faiw.uspto.gov/fdd/$patentApplicationNumber4/$patentApplicationNumber1/$patentApplicationNumber3/$patentApplicationNumber2/0.pdf";
				
				$fileNameArray['patentNumberFirst'] = array('fileName'=>$patentNumber.'_first.pdf', 'downloadUrl'=>$downloadOnePageNumberString, 'patentNumber'=>$patentNumber);
				$fileNameArray['patentNumberFull'] = array('fileName'=>$patentNumber.'_full.pdf', 'downloadUrl'=>$downloadAllPagesNumberString, 'patentNumber'=>$patentNumber);
				$fileNameArray['patentApplicationFirst'] = array('fileName'=>$patentNumber.'_first.pdf', 'downloadUrl'=>$downloadOnePageApplicationString, 'patentNumber'=>$patentNumber);
				$fileNameArray['patentApplicationFull'] = array('fileName'=>$patentNumber.'_full.pdf', 'downloadUrl'=>$downloadAllPagesApplicationString, 'patentNumber'=>$patentNumber);

				if(!empty($_POST['firstPagePdf'])) {
					if(strlen(preg_replace('/[^0-9]*/', '', $patentNumber)) <= 8) {
						$downloadUrls[$downloadOnePageNumberString] = $patentNumber.'_first.pdf';
					}
					else {
						$downloadUrls[$downloadOnePageApplicationString] = $patentNumber.'_first.pdf';
					}
				}
				if(!empty($_POST['allPagesPdf'])) {
					if(strlen(preg_replace('/[^0-9]*/', '', $patentNumber)) <= 8) {
						$downloadUrls[$downloadAllPagesNumberString] = $patentNumber.'_full.pdf';
					}
					else {
						$downloadUrls[$downloadAllPagesApplicationString] = $patentNumber.'_full.pdf';
					}
				}
			}



			unlink('uspto-noids.zip');
			mkdir('tmp_uspto');
			$downloadFileString = '';
			foreach( $downloadUrls as $url => $file ) {
				$downloadFileString .= $url."\n";
				$downloadFileString .= ' out='.$file."\n";
			}
			file_put_contents('downloadUrls.txt', $downloadFileString);
			shell_exec('aria2c -i downloadUrls.txt -d tmp_uspto -j 200');

			if(!empty($_POST['firstPagePdf']) && !empty($_POST['firstPageBundle'])) {
				shell_exec('pdfunite tmp_uspto/*_first.pdf tmp_uspto/_firstPageBundle.pdf');
			}

			if(!empty($_POST['allPagesPdf']) && !empty($_POST['allPagesBundle'])) {
				shell_exec('pdfunite tmp_uspto/*_full.pdf tmp_uspto/_fullBundle.pdf');
			}

			if(!empty($_POST['firstPagePdf']) && !empty($_POST['firstPageBundle']) && !empty($_POST['firstPageOCR'])) {
				shell_exec('convert -density 300 tmp_uspto/_firstPageBundle.pdf -depth 8 tmp_uspto/_firstPageBundle.tiff'.
					' && tesseract tmp_uspto/_firstPageBundle.tiff tmp_uspto/_firstPageBundle && rm tmp_uspto/_firstPageBundle.tiff
');
			}

			if(!empty($_POST['allPagesPdf']) && !empty($_POST['allPagesBundle']) && !empty($_POST['allPagesOCR'])) {
				shell_exec('convert -density 300 tmp_uspto/_fullBundle.pdf -depth 8 tmp_uspto/_fullBundle.tiff'.
					' && tesseract tmp_uspto/_fullBundle.tiff tmp_uspto/_fullBundle && rm tmp_uspto/_fullBundle.tiff');
			}


			shell_exec('zip -j '.$zipFileName.' tmp_uspto/*.pdf');
			shell_exec('zip -j '.$zipFileName.' tmp_uspto/*.txt');
			shell_exec('rm -r tmp_uspto');
			echo '<a href="'.$zipFileName.'">Download Zip</a><br />';
		}

		else if($_POST['database'] === 'Espacenet') {
			$zipFileName .= '_Espacenet.zip';
			// get an authorization token
			$ch = curl_init('https://ops.epo.org/3.1/auth/accesstoken');
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			    'Authorization: Basic UHVtbVA2OTdqZFNBNzZBc3F0UWF5NTcxUmttTVRyUHQ6Q0hjdDhJOGJvVEF0VjBNZg==',
			    'Content-Type: application/x-www-form-urlencoded'
		    ));
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		    curl_setopt($ch, CURLOPT_POST, 1);
		    curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
		    $token = json_decode(curl_exec($ch), true)['access_token'];

			$fileNameArrays = array();
			$downloadUrls = array();
			$patentNumbers = preg_split('/\n/', $_POST['patentNumbers']);

			$numRequests = 0;
			$requestString = '';

			foreach($patentNumbers as $patentNumber) {
				if($patentNumber == '') {
					continue;
				}

				$url = preg_replace('/[^\da-z\/.:\-]/i', '', 'https://ops.epo.org/3.1/rest-services/published-data/publication/epodoc/'.$patentNumber.'/images');
				$requestString .= "$url\n\tout=$numRequests.json\n";
				$numRequests++;
			}
			file_put_contents('jsonrequests.txt', $requestString);
			shell_exec('aria2c -i jsonrequests.txt --header="Accept: application/json" --header="Content-Type: plain/text" --header="Authorization: Bearer '.$token.'"');
			shell_exec('rm jsonrequests.txt');

			$allDocuments = array();
			for($i=0; $i<$numRequests; $i++) {
				$responseJson = file_get_contents($i.'.json');
				if($responseJson == '') {
					die ('Could not download all files!');
				}
				$response = json_decode($responseJson, true);

				$documents = array();
				$documentInstanceArray = $response['ops:world-patent-data']['ops:document-inquiry']['ops:inquiry-result']['ops:document-instance'];
				$numDocuments = 0;
				foreach($documentInstanceArray as $documentInstance) {
					if($documentInstance['@desc'] == 'FullDocument') {
						$documentName = $response['ops:world-patent-data']['ops:document-inquiry']['ops:publication-reference']['document-id']['doc-number']['$'];
						$documents[] = array('fileName'=>$documentName."_$numDocuments", 'numPages'=>intval($documentInstance['@number-of-pages']), 'link'=>'http://ops.epo.org/3.1/rest-services/'.$documentInstance['@link']);
						$numDocuments++;
					}
				}
				$allDocuments[] = $documents;
			}

			$documentImagesArray = array();
			foreach($allDocuments as $documents) {
				foreach($documents as $document) {
					if(!empty($_POST['firstPagePdf'])) {
						$documentPageArray = array();
						$documentPageArray['fileName'] = $document['fileName'].'_first.pdf';

						$documentPages = array();
						$documentPages[] = array('pageFileName'=>$document['fileName'].'_first.pdf', 'pageLink'=>$document['link'].'.pdf?Range=1');
						$documentPageArray['pages'] = $documentPages;
						$documentImagesArray[] = $documentPageArray;
					}
					if(!empty($_POST['allPagesPdf'])) {
						$documentPageArray = array();
						$documentPageArray['fileName'] = $document['fileName'].'_full.pdf';

						$documentPages = array();
						for($i=0; $i<$document['numPages']; $i++) {
							$range = $i + 1;
							$documentPages[] = array('pageFileName'=>$document['fileName']."_{$i}_full.pdf", 'pageLink'=>$document['link'].".pdf?Range=$range");
						}
						$documentPageArray['pages'] = $documentPages;
						$documentImagesArray[] = $documentPageArray;
					}
				}
			}
			shell_exec('rm *.json');

			$allDownloadsString = '';
			$joinPdfsString = '';
			foreach($documentImagesArray as $documentImageSet) {
				$joinPdfsString .= 'pdfunite ';
				foreach($documentImageSet['pages'] as $documentImage) {
					$allDownloadsString .= $documentImage['pageLink']."\n\tout=".$documentImage['pageFileName']."\n";
					$joinPdfsString .= $documentImage['pageFileName'].' ';
				}
				$joinPdfsString .= 'espacedownloads/'.$documentImageSet['fileName']."\n";
			}

			file_put_contents('allDownloads.txt', $allDownloadsString);

			shell_exec('aria2c -i allDownloads.txt --header="Authorization: Bearer '.$token.'"');
			shell_exec('rm -r espacedownloads');
			shell_exec('mkdir espacedownloads');

			$joinPdfsArray = preg_split('/\n/', $joinPdfsString);
			foreach($joinPdfsArray as $joinPdf) {
				shell_exec($joinPdf);
			}

			if(!empty($_POST['firstPagePdf']) && !empty($_POST['firstPageBundle'])) {
				shell_exec('pdfunite *_first.pdf _firstPageBundle.pdf');
			}

			if(!empty($_POST['allPagesPdf']) && !empty($_POST['allPagesBundle'])) {
				shell_exec('pdfunite *_full.pdf _fullBundle.pdf');
			}

			if(!empty($_POST['firstPagePdf']) && !empty($_POST['firstPageBundle']) && !empty($_POST['firstPageOCR'])) {
				shell_exec('convert -density 300 _firstPageBundle.pdf -depth 8 _firstPageBundle.tiff && tesseract _firstPageBundle.tiff _firstPageBundle && rm _firstPageBundle.tiff
');
			}

			if(!empty($_POST['allPagesPdf']) && !empty($_POST['allPagesBundle']) && !empty($_POST['allPagesOCR'])) {
				shell_exec('convert -density 300 _fullBundle.pdf -depth 8 _fullBundle.tiff && tesseract _fullBundle.tiff _fullBundle && rm _fullBundle.tiff');
			}

			shell_exec('rm allDownloads.txt');
			shell_exec('rm *full.pdf');
			shell_exec('mv *.pdf espacedownloads');
			shell_exec('zip -j '.$zipFileName.' espacedownloads/*.pdf');
			shell_exec('zip -j '.$zipFileName.' espacedownloads/*.txt');
			shell_exec('rm -r espacedownloads');

			echo '<a href="'.$zipFileName.'">Download Zip</a><br />';

			/*
			file_put_contents('downloadUrls.txt', $downloadFileString);
			shell_exec('aria2c -i downloadUrls.txt -d tmp_uspto -j 200');
			shell_exec('zip -j uspto-noids.zip tmp_uspto/*.pdf');
			shell_exec('rm -r tmp_uspto');
			echo '<a href="uspto-noids.zip">Download Zip</a><br />';*/
		}
	}
}

?>

<form method="POST">
Zip file label (optional): <input type='text' name='zipFileLabel' /><br />
Database: <label>USPTO <input type="radio" name="database" value="USPTO" /></label> <label>Espacenet<input type="radio" name="database" value="Espacenet" /></label><br />
Enter any patent numbers here, delimited by newline: <br />
<textarea rows="5" name="patentNumbers"></textarea><br />
Include first page <input type="checkbox" name="firstPagePdf" /><br />
Include whole document <input type="checkbox" name="allPagesPdf" /><br />
Bundle first pages <input type="checkbox" name="firstPageBundle" /><br />
Bundle whole documents <input type="checkbox" name="allPagesBundle" /><br />
Run OCR on first page bundle <input type="checkbox" name="firstPageOCR" /><br />
Run OCR on full bundle <input type="checkbox" name="allPagesOCR" /><br />
<input type="submit" name="submit" value="submit" />
</form>

</body>
</html>
