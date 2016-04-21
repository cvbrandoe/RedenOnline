<?php 

    $basedir = "/var/www/html/WebREDEN/web";
    $errors = array();
    $data   = array();

    // if any of these variables don't exist, add an error to our $errors array
    //if (empty($_POST['teitext']))
    //    $errors['teitext'] = 'Text is required.';
    
    //text2process = $_POST['teitext'];

    if ( ! empty($errors)) {
        $data['success'] = false;
        $data['errors']  = $errors;
    } else {
        
	$date = new DateTime();
	$name_file = $date->getTimestamp();
	echo $basedir."/teis/".$name_file.".xml";
	file_put_contents($basedir."/teis/".$name_file.".xml", $_POST['teitext']);	
	//echo json_encode($_POST['teitext']);
	// TODO
	// 1. mettre string dans fichier xml côté serveur
	// 2. lancer le jar de reden avec les arguments: fichier, outputDir
	// 3. mettre ce fichier disponible en telechargement pour l'utilisateur
	
	// 4. lancer jar geolocalisation qui prend en param le fichier xml annoté et avec l'uri obtient les geocoords et produit en geojson
	// 5. ce dernier geojson sera lu par le code plus bas pour afficher la carte

        // show a message of success and provide a true success variable
        $data['success'] = true;
        $data['message'] = 'Success!';
    }

    // return all our data to an AJAX call
    echo json_encode($data);

?>
