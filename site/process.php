<?php 
	
	if($_SERVER['REQUEST_METHOD']=='POST'){
		
		$date = new DateTime();		
		//Path to store files on server
		define('DS', DIRECTORY_SEPARATOR);		
		$redenWebpath = 'D:/EasyPHP-DevServer-14.1VC9/data/localweb/RedenOnline/site/';
		$redenpath = 'D:/EasyPHP-DevServer-14.1VC9/data/localweb/RedenOnline/Reden/';	
		$serverURL = 'http://localhost/RedenOnline/site/';
		$path = 'teis/';
		
		/*if ($_POST['submit']) {
			
			echo $_POST['number'] . " + 10 = " . ($_POST['number']+10); 
		} else if ($_POST['submitOnlyVisu']) {
		
			echo $_POST['number'] . " - 10 = " . ($_POST['number']-10); 
		}*/
		
				
		if (!empty($_FILES['teifile']['name'])) { //default option when both text and file are provided
			//Getting actual file name
			$name = $_FILES['teifile']['name'];
			
			//Getting temporary file name stored in php tmp folder 
			$tmp_name = $_FILES['teifile']['tmp_name'];
			
			//checking file available or not 
			if(!empty($name)){
				//Moving file to temporary location to upload path
				move_uploaded_file($tmp_name,$redenWebpath.$path.$date->getTimestamp().$name);
				$teifilename = $date->getTimestamp().$name; 
				//Displaying success message 
				echo "File upload successfully<br>";
			} else{
				//If file not selected displaying a message to choose a file 
				echo "Please choose a file<br>";
			}
		} elseif (!empty($_POST['teitext'])) {
			//copy content from text area to file in server
			file_put_contents($path.$date->getTimestamp().".xml", $_POST['teitext']);
			$teifilename = $date->getTimestamp().".xml";
			echo "Text upload successfully<br>";
		} else {
			//return null;
		}
		
		//The TEI was correctly copied to the server
		if (isset($teifilename)) {
			
			//TODO check xml is valid			
			//TODO modify config with paramters provided by user
				
			//execute REDEN
			chdir($redenpath);				
			set_time_limit(0);
			exec("java -Dfile.encoding=UTF-8 -jar REDEN.jar config/config-authors.properties ".
					$redenWebpath.$path.$teifilename ." -outDir=".$redenWebpath.$path,
					$outputreden, $returnreden);
					
			// Return will return non-zero upon an error
			if (!$returnreden) {
				echo "Finished: OK <br>";
				
				//create Zip file with results
				$zipfile = new ZipArchive();
				$zipfilename = $redenWebpath.$path.$teifilename.".zip";				
				if ($zipfile->open($zipfilename, ZipArchive::CREATE) !== TRUE) {
					exit("Impossible to open file <$zipfilename>\n");
				}				
				$zipfile->addFile($redenWebpath.$path.str_replace(".xml","-ambigousMentions.txt", $teifilename),
						str_replace(".xml","-ambigousMentions.txt", $teifilename));
				$zipfile->addFile($redenWebpath.$path.str_replace(".xml","-outV3.xml", $teifilename),
						str_replace(".xml","-outV3.xml", $teifilename));
				$zipfile->addFile($redenWebpath.$path.str_replace(".xml","-relFrequency.txt", $teifilename),
						str_replace(".xml","-relFrequency.txt", $teifilename));
				$zipfile->addFile($redenWebpath.$path.str_replace(".xml","-resFinalGraphsV3.txt", $teifilename),
						str_replace(".xml","-resFinalGraphsV3.txt", $teifilename));
				$zipfile->close();		
				echo "<b>You can find your results following this <a href='".$serverURL.$path.$teifilename.".zip'>link</a></b><br>";
								
			} else {
				echo "Error: ". $returnreden . "<br>";
			}
		}		
			
	}