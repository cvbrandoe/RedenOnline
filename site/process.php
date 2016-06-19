<?php 
	
	date_default_timezone_set('Europe/Paris');
	
	//creates a copy of the configuration and update it with user input
	function createConfigFile($datestr, $redenpath, $redenWebpath, $path) {
		
		//choose base config file
		if ($_POST['entity-type'] == "Authors") {
			$confF = "config-authors.properties";
			$confD = "config/";
		} else {
			$confF = "config-places.properties";
			$confD = "config/";
		}
		
		$configBase=fopen($redenpath.$confD.$confF, 'r') or die('Sorry, unable to open config REDEN file ');

		//read properties
		while (($line = fgets($configBase)) !== false) {
			if (substr( $line, 0, 1 ) !== "#") {
				$pieces = explode("=", $line);
				$properties[rtrim($pieces[0])] = rtrim($pieces[1]);
				
			}	
    		}

		//replace values with user input in array
		foreach ($properties as $key => $value) {
			if ($key == 'xpathExpresion' && !empty($_POST['context-type'])) {
				if ($_POST['context-type'] === 'Paragraph') {				
					$properties[$key] = "//body//head|//body//item|//body//l|//body//p";
				} elseif ($_POST['context-type'] === 'Chapter') {				
					$properties[$key] = "//body/div";
				}  elseif ($_POST['context-type'] === 'Whole-text') {				
					$properties[$key] = "//body";
				}

			} elseif ($key == 'namedEntityTag' && !empty($_POST['xpathexp'])) {
				$properties[$key] = $_POST['xpathexp'];

			} elseif ($key == 'propertyTagRef' && !empty($_POST['propertyTagRef'])) {
				$properties[$key] = $_POST['propertyTagRef'];

			} /*elseif ($key == 'addScores' && !empty($_POST['scores'])) {
				$properties[$key] = $_POST['scores'];

			}*/ elseif ($key == 'centralityMeasure' && !empty($_POST['centrality'])) {
				$properties[$key] = $_POST['centrality'];

			} elseif ($key == 'crawlSameAs' && !empty($_POST['crawlSameAs'])) {
				$properties[$key] = $_POST['crawlSameAs'];
			} 
						
		}
		
		//rewrite array values in file
		$configREDENW=fopen($redenWebpath.$path.$datestr.$confF, 'w') or die('Sorry, unable to open '.$path.$datestr.$confF);
		foreach ($properties as $key => $value) {
			if ($value == "Yes") {
				$value = "true";
			} 
			if ($value == "No") {
				$value = "false";
			}
			fwrite($configREDENW, $key."=".$value. "\n");
		}
		fclose($configREDENW);
		return $redenWebpath.$path.$datestr.$confF;
	}

	if($_SERVER['REQUEST_METHOD']=='POST'){
		
		$date = new DateTime();		
		//Path to store files on server
		define('DS', DIRECTORY_SEPARATOR);		
		//$redenWebpath = '/var/www/html/RedenOnline/site/';
		$redenWebpath = '/var/www/media/reden/RedenOnline/site/';
		//$redenpath = '/var/www/html/RedenOnline/tool/';
		$redenpath = '/var/www/media/reden/RedenOnline/tool/';		
		//$serverURL = 'http://localhost/RedenOnline/site/';
		$serverURL = 'http://obvil-dev.paris-sorbonne.fr/reden/RedenOnline/site/';
		$path = 'teis/';
		$pathjson = 'json/';
		//$cmdjava = '/usr/java/jdk1.7.0_03/bin/java';
		$cmdjava = 'java -Xmx2g';
		
		$message = "";
		
		//Getting TEI content from user, input area has priority over file selection
		if (!empty($_POST['teitext'])) {
			//copy content from text area to file in server
			file_put_contents($path.$date->getTimestamp().".xml", $_POST['teitext']);
			$teifilename = $date->getTimestamp().".xml";
			$message = $message . "Text upload successfully<br>";		
		} elseif (!empty($_FILES['teifile']['name'])) {
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
				$message = $message . "File upload successfully<br>";
			} else{
				//If file not selected displaying a message to choose a file
				//echo "Please choose a file<br>";
			}
		} else {
			$message = $message . "<b>No file was provided</b><br>";
		}
		
		//The TEI was correctly copied to the server
		if (isset($teifilename)) {
			
			//create copy and modify config with paramters provided by user
			$configredenfile = createConfigFile($date->getTimestamp(), $redenpath, $redenWebpath, $path);
			if (isset($_POST['process'])) {
								
				//execute REDEN
				chdir($redenpath);
				set_time_limit(0);				
				if ($_POST['entity-type'] == "Authors") {
					exec($cmdjava . " -Dfile.encoding=UTF-8 -jar REDEN.jar " . $configredenfile . " ".
						$redenWebpath.$path.$teifilename ." -outDir=".$redenWebpath.$path,
						$outputreden, $returnreden);
				} else {
					exec($cmdjava . " -Dfile.encoding=UTF-8 -jar REDEN.jar " . $configredenfile ." ".
							$redenWebpath.$path.$teifilename ." -outDir=".$redenWebpath.$path,
							$outputreden, $returnreden);					
				}
					
				// Return will return non-zero upon an error
				if (!$returnreden) {
					$message = $message . "Finished: OK <br>";
				
					chdir($redenpath);
					set_time_limit(0);
					if ($_POST['entity-type'] == "Authors") {
						$jsonfile = $date->getTimestamp()."authorsInformation.json";
						$propsFile = "config/authors.properties";
					} else {
						$jsonfile = $date->getTimestamp()."places-in-tei.json";
						$propsFile = "config/latlong.properties";
					}
					exec($cmdjava . " -Dfile.encoding=UTF-8 -jar REDEN.jar " . $configredenfile ." ".
							$redenWebpath.$path.str_replace(".xml","-outV3.xml", $teifilename) .
							" -produceData4Visu=".$redenWebpath.$pathjson.$jsonfile." -propsFile=".$propsFile,
							$outputreden, $returnreden);
					
					//create Zip file with results
					$zipfile = new ZipArchive();
					$zipfilename = $redenWebpath.$path.$teifilename.".zip";
					if ($zipfile->open($zipfilename, ZipArchive::CREATE) !== TRUE) {
						exit("Impossible to open file <$zipfilename>\n");
					}
					$zipfile->addFile($redenWebpath.$path."README.txt", "README.txt");
					$zipfile->addFile($configredenfile, "reden.properties");
					$zipfile->addFile($redenWebpath.$path.str_replace(".xml","-ambigousMentions.txt", $teifilename),
							str_replace(".xml","-ambigousMentions.txt", $teifilename));
					$zipfile->addFile($redenWebpath.$path.str_replace(".xml","-outV3.xml", $teifilename),
							str_replace(".xml","-outV3.xml", $teifilename));
					$zipfile->addFile($redenWebpath.$path.str_replace(".xml","-relFrequency.txt", $teifilename),
							str_replace(".xml","-relFrequency.txt", $teifilename));
					$zipfile->addFile($redenWebpath.$path.str_replace(".xml","-resFinalGraphsV3.txt", $teifilename),
							str_replace(".xml","-resFinalGraphsV3.txt", $teifilename));
					$zipfile->addFile($redenWebpath.$pathjson.$jsonfile, $jsonfile);
					$zipfile->close();
					$message = $message . "<b>You can find the results following this <a target='_blank' href='".$serverURL.$path.$teifilename.".zip'>link</a></b><br>";
					
				} else {
					$message = $message . "Error: ". $returnreden . "<br>";
				}
				$result = array("message" => $message, "type" => $_POST['entity-type'], "jsonfile" => $pathjson.$jsonfile);
				
			} else if (isset($_POST['visualize'])) {
				
				//no need to launch Reden, it is already annotated
				chdir($redenpath);
				set_time_limit(0);					
				if ($_POST['entity-type'] == "Authors") {
					$jsonfile = $date->getTimestamp()."authorsInformation.json";
					$propsFile = "config/authors.properties";
				} else {
					$jsonfile = $date->getTimestamp()."places-in-tei.json";
					$propsFile = "config/latlong.properties";
				}					
				exec($cmdjava . " -Dfile.encoding=UTF-8 -jar REDEN.jar " . $configredenfile ." ".
						$redenWebpath.$path.$teifilename .
						" -produceData4Visu=".$redenWebpath.$pathjson.$jsonfile." -propsFile=".$propsFile,
						$outputreden, $returnreden);	
				
				$message = $message . "<b>You can find the data used for this visualization following this <a href='".$serverURL.$pathjson.$jsonfile."'>link</a></b><br>";
				
				$result = array("message" => $message, "type" => $_POST['entity-type'], "jsonfile" => $pathjson.$jsonfile);
				
			} else {				
				//cancel button pressed
				$result = array("type" => "Unknown", "message" => $message, "jsonfile" => "");
			}									
		} else {
			$result = array("type" => "Unknown", "message" => $message, "jsonfile" => "");
		}
		echo json_encode($result);
	}
