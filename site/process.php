<?php 
	
	if($_SERVER['REQUEST_METHOD']=='POST'){
		
		$date = new DateTime();		
		//Path to store files on server
		define('DS', DIRECTORY_SEPARATOR);		
		$redenWebpath = 'D:/EasyPHP-DevServer-14.1VC9/data/localweb/RedenOnline/site/';
		$redenpath = 'D:/EasyPHP-DevServer-14.1VC9/data/localweb/RedenOnline/Reden/';	
		$serverURL = 'http://localhost/RedenOnline/site/';
		$path = 'teis/';
		$pathjson = 'json/';
		
		$message = "";
		
		//Getting TEI content from user
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
				$message = $message . "File upload successfully<br>";
			} else{
				//If file not selected displaying a message to choose a file
				//echo "Please choose a file<br>";
			}
		} elseif (!empty($_POST['teitext'])) {
			//copy content from text area to file in server
			file_put_contents($path.$date->getTimestamp().".xml", $_POST['teitext']);
			$teifilename = $date->getTimestamp().".xml";
			$message = $message . "Text upload successfully<br>";
		} else {
			$message = $message . "<b>No file was provided</b><br>";
		}
		
		//The TEI was correctly copied to the server
		if (isset($teifilename)) {
			
			if (isset($_POST['process'])) {
				
				//TODO create copy and modify config with paramters provided by user
					
				//execute REDEN
				chdir($redenpath);
				set_time_limit(0);
				
				if ($_POST['entity-type'] == "Authors") {
					exec("java -Dfile.encoding=UTF-8 -jar REDEN.jar config/config-authors.properties ".
						$redenWebpath.$path.$teifilename ." -outDir=".$redenWebpath.$path,
						$outputreden, $returnreden);
				} else {
					exec("java -Dfile.encoding=UTF-8 -jar REDEN.jar config/config-places.properties ".
							$redenWebpath.$path.$teifilename ." -outDir=".$redenWebpath.$path,
							$outputreden, $returnreden);					
				}
					
				// Return will return non-zero upon an error
				if (!$returnreden) {
					$message = $message . "Finished: OK <br>";
				
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
					$message = $message . "<b>You can find your results following this <a href='".$serverURL.$path.$teifilename.".zip'>link</a></b><br>";
					
					//produce data for visualization purposes
					//TODO modify config file for visualization, create a copy
					chdir($redenpath);
					set_time_limit(0);					
					if ($_POST['entity-type'] == "Authors") {
						$message = $message . "Processing authors: OK <br>";
						$jsonfile = $date->getTimestamp()."authorsInformation.json";
						$propsFile = "config/authors.properties";
						$configFileE = "config/config-authors-enrich.properties";
					} else {
						$message = $message . "Processing places: OK <br>";
						$jsonfile = $date->getTimestamp()."places-in-tei.json";
						$propsFile = "config/latlong.properties";
						$configFileE = "config/config-places-enrich.properties";
					}					
					exec("java -Dfile.encoding=UTF-8 -jar REDEN.jar " . $configFileE ." ".
							$redenWebpath.$path.str_replace(".xml","-outV3.xml", $teifilename) .
							" -produceData4Visu=".$redenWebpath.$pathjson.$jsonfile." -propsFile=".$propsFile,
							$outputreden, $returnreden);
					
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
					$configFileE = "config/config-authors-enrich.properties";
				} else {
					$jsonfile = $date->getTimestamp()."places-in-tei.json";
					$propsFile = "config/latlong.properties";
					$configFileE = "config/config-places-enrich.properties";
				}					
				exec("java -Dfile.encoding=UTF-8 -jar REDEN.jar " . $configFileE ." ".
						$redenWebpath.$path.str_replace(".xml","-outV3.xml", $teifilename) .
						" -produceData4Visu=".$redenWebpath.pathjson.$jsonfile." -propsFile=".$propsFile,
						$outputreden, $returnreden);				
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
	
	function process_parameters($param) {
		//TODO		
	}