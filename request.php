<?php

//Primer identifiquem el bot i definim on volem que vagui la informació que respongui.
$botToken= "";
$website = "https://api.telegram.org/bot".$botToken;
$update = file_get_contents('php://input');
$update =  json_decode($update, TRUE);

//Tot seguit fem que el bot identifiqui la id de l'usuari que escriu al bot.
$chatId= $update["message"]["chat"]["id"];
$chatType = $update["message"]["chat"]["type"];
$message = $update["message"]["text"];

//Posteriorment afegim les opcions,(comandos), que el bot llegirà i interpretarà, per tal de contestar-los.
switch ($message) {
		
        //Aquest opció serà l'encarregada d'iniciar el joc i et posarà en situació, explicant que darrere de dues portes hi ha un burro i a darrere l'altre hi ha un cotxe.		
        case '/start':  
        
		    //Repartim els rucs i el cotxe per les diferents portes.
            $prizes = array('ruc', 'ruc', 'cotxe');
            shuffle($prizes);				
        
            //Definim on es el nostre servidor per tal de que envii la informació allà. Aquesta acció es repeteix a cada $link que apareix al codi.
            $link = mysqli_connect("localhost","USER","PASSWD","DATABASE") or die("Error " . mysqli_error($link));
       
            // Insertem a la base de dades les tres portes i que hi ha darrere de cada porta. Auesta acció es repeteix a cada $query.
            $query = "INSERT INTO monthyhallprizes (CHAT_ID, PORTAA, PORTAB, PORTAC, MYDOOR, NOWIN) VALUES ('".$chatId."','".$prizes[0]."','".$prizes[1]."','".$prizes[2]."',null,null)" or die("Error in the consult.." . mysqli_error($link));				
			$result = mysqli_query($link, $query);
			$res_num = mysqli_num_rows($result);		
			mysqli_close($link);
	
	        sendImage($chatId,"portes");
	
		    //Una vegada escribim /start el bot contestarà el seguent: 'Tria una porta, dues estan premiades amb un ruc i una amb un cotxe.' i obrirà un teclat amb les opcions: /portaa, /portab i /portac.						
			$response = 'Tria una porta, dues estan premiades amb un ruc i una amb un cotxe.';
			$keyboard = '["/portaa"],["/portab"],["/portac"]';
								
			sendMessage($chatId, $response, $keyboard);
     			break;
        
        //Aqusta opció escollirà la porta A i et dirà on es un ruc.
		case '/portaa':  
				$link = mysqli_connect("localhost","USER","PASSWORD","DATABASE") or die("Error " . mysqli_error($link));				
				$query = "SELECT PortaB, PortaC, isnull(mydoor) AS esnul FROM monthyhallprizes WHERE CHAT_ID = ".$chatId."" or die("Error in the consult.." . mysqli_error($link));
				$result = mysqli_query($link, $query);				
				$res_num = mysqli_num_rows($result);
				$mydoor = "A";
				
				//Si no s'ha fet el /start el bot contestarà joc no inicialitzat.
				if ($res_num == 0) 
					sendMessage($chatId, "Joc no inicialitzat");
				else {											
				   	$row = mysqli_fetch_array($result); 
				    //Aquesta operació evitarà que una vegada triada una porta poguem escollir una altre. Si ho intentem ens enviarà el missatge: "Ja has triat porta, només pots canviar-la per la que no t'he ensenyat".
				    
				    if  (!$row["esnul"]) 
				        $response = "Ja has triat porta, només pots canviar-la per la que no t'he ensanyat.";
				    else {
				    
        					//Si a la porta B hi ha un ruc et dirà: "Has escollit la porta A. Si et dic que a darrera la porta B hi ha un ruc. Vols canviar la teva porta A per la porta C?".
        					if ($row["PortaB"] == 'ruc') {
        						$response = 'Has escollit la porta A. Si et dic que a darrera la porta B hi ha un ruc. Vols canviar la teva porta A per la porta C?';
        						$nowin = "C";
        					}
        					//Si a la porta B no hi ha un ruc et dirà: "Has escollit la porta A. Si et dic que a darrera la porta C hi ha un ruc. Vols canviar la teva porta A per la porta B?".
        					else {
        						$response = 'Has escollit la porta A. Si et dic que a darrera la porta C hi ha un ruc. Vols canviar la teva porta A per la porta B?';
        						$nowin = "B";
        					}
        						
        					$query = "UPDATE monthyhallprizes SET MYDOOR = '".$mydoor."', NOWIN = '".$nowin."' WHERE CHAT_ID = ".$chatId."" or die("Error in the consult.." . mysqli_error($link));
        					$result = mysqli_query($link, $query);				
        					$res_num = mysqli_num_rows($result);
        			}
				}
			
	            //Obrim un teclat amb les opcions si i no i enviem el missatge que correspon, el del "if" o el de l'"else".
        		$keyboard = '["/si"],["/no"]';					
                sendMessage($chatId, $response, $keyboard);				
    		
				mysqli_close($link);
    			break;        
    		
    	//Responem si a la pregunta "Vols canviar la teva porta X per la Y", on la X és la meva porta i la Y és la porta que no ens ha revelat un ruc.
		case '/si':
    			$link = mysqli_connect("localhost","USER","PASSWD","DATABASE") or die("Error " . mysqli_error($link));				
				$query = "SELECT PortaA, PortaB, PortaC, mydoor, nowin FROM monthyhallprizes WHERE CHAT_ID = ".$chatId."" or die("Error in the consult.." . mysqli_error($link));												
				$result = mysqli_query($link, $query);				
				$res_num = mysqli_num_rows($result);
			
				//El bot ens enviarà un missatge: "Has triat canviar la porta X per la Y", On la X és la meva porta i la Y és la que no ens ha revelat un ruc.										
				if ($res_num > 0) {
														
					$row = mysqli_fetch_array($result);
																					
					$response = "Has triat canviar la porta ".$row["mydoor"];
					$response = $response . " per la porta ".$row["nowin"];									
					
					//En tots els casos, A, B i C, el bot envirà: "Has guanyat un" i tot seguit el que hi ha darrere de la porta seleccionada.
					switch ($row["nowin"]) {
				    case "A":
				        $response = $response .". Has guanyat un ". $row["PortaA"];
				        $resultat=$row["PortaA"];
				        break;
				    case "B":
				    	$response = $response .". Has guanyat un ". $row["PortaB"];
				    	$resultat=$row["PortaB"];
				        break;
				    case "C":
				    	$response = $response .". Has guanyat un ". $row["PortaC"];
				    	$resultat=$row["PortaC"];
				    	break;
					}
										
					//Enviarem una imatge del premi que hem aconseguit. La finció la trobarem al final del codi.
					sendImage($chatId,$resultat);
				} 
				
				//Respondrà sense informació si no s'ha inicialitzat el joc.
				else  {
					$response = "sense informació";
				}
				sendMessage($chatId, $response, null);
				mysqli_close($link);
				
				//Una vegada hem acabat el joc, esborrem la base de dades per tal de que el cotxe no estigui sempre al mateix lloc.
				esborrarPartida($chatId);
    				break;            
    					
    		//Responem no a la pregunta "Vols canviar la teva porta X per la Y", on la X és la meva porta i la Y és la porta que no ens ha revelat un ruc.
			case '/no':
   				$link = mysqli_connect("localhost","USER","PASSWD","DATABASE") or die("Error " . mysqli_error($link));				
				$query = "SELECT PortaA, PortaB, PortaC, mydoor, nowin FROM monthyhallprizes WHERE CHAT_ID = ".$chatId."" or die("Error in the consult.." . mysqli_error($link));												
				$result = mysqli_query($link, $query);				
				$res_num = mysqli_num_rows($result);
    			
    			//El bot respondrà: "Has triat no canviar la porta".
				if ($res_num > 0) {
				    
					$row = mysqli_fetch_array($result);
					
					$response = "Has triat no canviar la porta ". $row["mydoor"];
										
					//En tots els casos, A, B i C, el bot envirà: "Has guanyat un" i tot seguit el que hi ha darrere de la porta seleccionada.
				    switch ($row["mydoor"]) {
				    case "A":
				        $response = $response .". Has guanyat un ". $row["PortaA"];
				        $resultat=$row["PortaA"];
				        break;
				    case "B":
				    		$response = $response .". Has guanyat un ". $row["PortaB"];
				        $resultat=$row["PortaB"];
				        break;
				    case "C":
				    		$response = $response .". Has guanyat un ". $row["PortaC"];
				    		$resultat=$row["PortaC"];
				    		break;
					}	
					
					//El bot enviarà la imatge del nostre premi.
					sendImage($chatId,$resultat);																
				}
								
				sendMessage($chatId, $response);
				mysqli_close($link);
				
				//Una vegada s'acaba el joc, esborrem la base de dades per tal de que el cotxe no surti sempre al mateix lloc.
				esborrarPartida($chatId);
				
				break;            
    		    
		//Aqusta opció escollirà la porta B i et dirà on es un ruc.
		case '/portab':
				$link = mysqli_connect("localhost","USER","PASSWD","DATABASE") or die("Error " . mysqli_error($link));				
				$query = "SELECT PortaA, PortaC, isnull(mydoor) AS esnul FROM monthyhallprizes WHERE CHAT_ID = ".$chatId."" or die("Error in the consult.." . mysqli_error($link));				
				$result = mysqli_query($link, $query);				
				$res_num = mysqli_num_rows($result);
	
				$mydoor = "B";
				//Si no s'ha fet el /start el bot contestarà joc no inicialitzat.
				if ($res_num == 0) 
					sendMessage($chatId, "Joc no inicialitzat");
					
				else { $row = mysqli_fetch_array($result);										
				    
				    //Aquesta operació evitarà que una vegada triada una porta poguem escollir una altre. Si ho intentem ens enviarà el missatge: "Ja has triat porta, només pots canviar-la per la que no t'he ensenyat".
				    if  (!$row["esnul"]) 
				        $response = "Ja has triat porta, només pots canviar-la per la que no t'he ensanyat.";
				    else {
					
    			            //Si a la porta A hi ha un ruc et dirà: "Has escollit la porta B. Si et dic que a darrera la porta A hi ha un ruc. Vols canviar la teva porta B per la porta C?".
    					    if ($row["PortaA"] == 'ruc') {
    						    $response = 'Has escollit la porta B. Si et dic que a darrera la porta A hi ha un ruc. Vols canviar la teva porta B per la porta C?';
    						    $nowin = "C";
    				    	}
    				    	
    				    	//Si a la porta A no hi ha un ruc et dirà: "Has escollit la porta B. Si et dic que a darrera la porta C hi ha un ruc. Vols canviar la teva porta B per la porta A?".
    					    else {
    						$response = 'Has escollit la porta B. Si et dic que a darrera la porta C hi ha un ruc. Vols canviar la teva porta B per la porta A?';
    						$nowin = "A";
    					    }
    						
    				    	$query = "UPDATE monthyhallprizes SET MYDOOR = '".$mydoor."', NOWIN = '".$nowin."' WHERE CHAT_ID = ".$chatId."" or die("Error in the consult.." . mysqli_error($link));
    				    	$result = mysqli_query($link, $query);				
    				    	$res_num = mysqli_num_rows($result);	
    					
    				       }
    				}
    	
    			    //Obrim un teclat amb les opcions si i no i enviem el missatge que correspon, el del "if" o el de l'"else".
    			    $keyboard = '["/si"],["/no"]';					
    		    	sendMessage($chatId, $response, $keyboard);	
    				    
    			    mysqli_close($link);
        			break; 
  
  		//Aqusta opció escollirà la porta C i et dirà on es un ruc.       
		case '/portac':
				$link = mysqli_connect("localhost","USER","PASSWD","DATABASE") or die("Error " . mysqli_error($link));				
				$query = "SELECT PortaA, PortaB, isnull(mydoor) AS esnul FROM monthyhallprizes WHERE CHAT_ID = ".$chatId."" or die("Error in the consult.." . mysqli_error($link));				
				$result = mysqli_query($link, $query);				
				$res_num = mysqli_num_rows($result);
	
				$mydoor = "C";
                //Si no s'ha fet el /start el bot contestarà joc no inicialitzat.
				if ($res_num == 0) 
					sendMessage($chatId, "Joc no inicialitzat");
					
				else { $row = mysqli_fetch_array($result);										
				    
				        //Aquesta operació evitarà que una vegada triada una porta poguem escollir una altre. Si ho intentem ens enviarà el missatge: "Ja has triat porta, només pots canviar-la per la que no t'he ensenyat".
				        if  (!$row["esnul"]) 
				        $response = "Ja has triat porta, només pots canviar-la per la que no t'he ensanyat.";
				        else {
					
					        //Si a la porta B hi ha un ruc et dirà: "Has escollit la porta C. Si et dic que a darrera la porta B hi ha un ruc. Vols canviar la teva porta C per la porta A?".
					        if ($row["PortaB"] == 'ruc') {
    						$response = 'Has escollit la porta C. Si et dic que a darrera la porta B hi ha un ruc. Vols canviar la teva porta C per la porta A?';
    						$nowin = "C";
        					}
        					//Si a la porta B no hi ha un ruc et dirà: "Has escollit la porta C. Si et dic que a darrera la porta A hi ha un ruc. Vols canviar la teva porta C per la porta B?".
        					else {
        						$response = 'Has escollit la porta C. Si et dic que a darrera la porta A hi ha un ruc. Vols canviar la teva porta C per la porta B?';
        						$nowin = "B";
        					}
        						
        				$query = "UPDATE monthyhallprizes SET MYDOOR = '".$mydoor."', NOWIN = '".$nowin."' WHERE CHAT_ID = ".$chatId."" or die("Error in the consult.." . mysqli_error($link));
    					$result = mysqli_query($link, $query);				
    					$res_num = mysqli_num_rows($result);	
				    }
					
					//Obrim un teclat amb les opcions si i no i enviem el missatge que correspon, el del "if" o el de l'"else".
					$keyboard = '["/si"],["/no"]';					
				
        			sendMessage($chatId, $response, $keyboard);				
				}
				
				mysqli_close($link);
    				break;
    	//Aquesta opció realitzarà la operació 1000000 vegades per tal d'aconseguir una simulació.			
		case '/calcul':  
		    
    	//Definim els valors inicials
        $iterations = 1000000; //vegades que es fa la operació
        $echo = false;
        $lose = 'ruc'; //Perdre = Ruc
        $win = 'car'; //Guanyar = Cotxe
        $stats = array();
        $stats['tot'] = 0;
        $stats['stay']['tot'] = 0;
        $stats['stay']['wins'] = 0;
        $stats['stay']['losses'] = 0;
        $stats['switch']['tot'] = 0;
        $stats['switch']['wins'] = 0;
        $stats['switch']['losses'] = 0;
        
        //Determinem que hi haurà dues portes perdedores i una guanyadora.
        $prizes = array($lose,$lose,$win);
        $i = 0;
    
        //Aquesta operació repetirà l'operació el numero de vagades establert més adalt on diu $iterations 
        while ($i < $iterations) {
            $i++;
            $doors = array();
            //Barrejem l'ordre dels premis alatoriament.
            shuffle($prizes);
            $j = 1;
            //Assignem els premis a les seves portes.
            foreach ($prizes as $prize) {
                $name = 'door_' . $j;
                $doors[$name] = $prize;
                $j++;
            }
            //Identifiquem la porta guanyadora.
            $win_door = array_search($win, $doors, 1);
            if ($echo) {
                echo "winning door is $win_door\n";
            }
            //Aleatoriament seleccionem una porta.
            $initial_choice = array_rand($doors);
            if ($echo) {
                echo "initial choice is $initial_choice\n";
            }
            //Aleatoriament perdem una porta no premiada, equival a la porta que ensenya el presentador.
            $remaining_doors = $doors;
            unset($remaining_doors[$initial_choice]);
            $host_door = array_search($lose, $remaining_doors, 1);
            unset($doors[$host_door]);
            if ($echo) {
                echo 'the host opens ' . $host_door . "\n";
            }
            //Aleatoriament escollim si canvair de porta o no. El numero 2 es el numero d'opcions que tenim i l'1 el nombre d'opcions que ha de seleccionar aleatoriament.
            if (rand()%2 == 1) {
                //El jugador escull quedar-se la porta escollida al principi.
                $type = 'stay';
                $final_choice = $doors[$initial_choice];
                $win_status = checkWin($final_choice);
            } 
            else {
                //El jugador escull canviar de porta.
                $type = 'switch';
                unset($doors[$initial_choice]);
                $final_choice = reset($doors);
                $win_status = checkWin($final_choice);
            }
            $stats['tot']++;
            $stats[$type]['tot']++;
            if ($echo) {
                echo "player chooses to $type\n";
            }
            //Contem els resultats.
            if ($win_status == true) {
                if ($echo) {
                    echo "player wins\n\n";
                }
                $stats[$type]['wins']++;
            } 
            else
            if ($win_status == false) {
                if ($echo) {
                    echo "player loses\n\n";
                }
                $stats[$type]['losses']++;
            }
        }
        
        //Enviem un missatge amb percentatges com a resposta.
        $winRateStaying = round($stats['stay']['wins'] / $stats['stay']['tot'],4)*100;
        $stayWinTotal = round($stats['stay']['wins'] / $stats['tot'],4)*100;
        $str .= '-De les ' . number_format($stats['stay']['tot']) . ' vegades que es tria no canviar';
        $str .= ' es guanya el ' . $winRateStaying . '% de les vegades.';
        $winRateSwitching = round($stats['switch']['wins'] / $stats['switch']['tot'],4)*100;
        $switchWinTotal = round($stats['switch']['wins'] / $stats['tot'],4)*100;
        $str .= chr(10). '-De les ' .number_format($stats['switch']['tot']) . ' vegades que es tria canviar ';  
        $str .= 'es guanya el ' . $winRateSwitching . '% de les vegades.';
        $str .= chr(10). '-Canviant es guanya un ' . $switchWinTotal . '% de les 1000000 vegades que es repeteix la simulació.';
        $str .= chr(10).'-Sense canviar es guanya un ' . $stayWinTotal . '% de les 1000000 vegades que es repeteix la simulació.';
    
                sendMessage($chatId, $str, "");     
   
                break;			
        }
        //Determina quan el jugador guanya i quan perd en la opció /calcul
        function checkWin($choice) {
            global $win;
            if ($choice == $win) {
                return true;
            } 
            else
            if ($choice != $win) {
                return false;
            }
        }
        //Aquesta funció esta definida aquí però funciona cada vegada que s'utilitza el /si i el /no. S'encarrega d'esborrar les posicions, dels premis, de la base de dades per tal de que canviin de lloc.
        function esborrarPartida($chatId) {
    	        $link = mysqli_connect("localhost","USER","PASSWD","DATABASE") or die("Error " . mysqli_error($link));				
		        $query = "DELETE FROM monthyhallprizes WHERE CHAT_ID = ".$chatId."" or die("Error in the consult.." . mysqli_error($link));
		        $result = mysqli_query($link, $query);
                mysqli_close($link);
        }
        //Auesta funció s'utilitza diverses vagades durant el codi. S'encarrega d'enviar els missatges.
        function sendMessage($chatId, $response, $keyboard = NULL){
            if (isset($keyboard)) {
                $teclado = '&reply_markup={"keyboard":['.$keyboard.'], "resize_keyboard":true, "one_time_keyboard":true}';
            }
            $url = $GLOBALS[website].'/sendMessage?chat_id='.$chatId.'&parse_mode=HTML&text='.urlencode($response).$teclado;
            file_get_contents($url);
        }
        //Aquesta funció es l'encarregada d'enviar les imatges de les portes, els rucs i el cotxe.
        function sendImage ($chatId, $cotxeoruc){

            switch ($cotxeoruc) {
                case 'cotxe':  $imatge="./cotxe.jpg"; break;
                case 'ruc':  $imatge="./ruc.png"; break;
                case 'portes': $imatge="./tresportes.jpg"; break;
            }

            $botToken= "703703713:AAEPDnyozC447zzeKbTy2ImuqNCi_EaxCEY";
    
            $website = "https://api.telegram.org/bot".$botToken;
    
            $bot_url    = $website."/";
            $url        = $bot_url . "sendPhoto?chat_id=" . $chatId ;
    
            $post_fields = array('chat_id'   => $chatId,
                'photo'     => new CURLFile(realpath($imatge))
            );
    
            $ch = curl_init(); 
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type:multipart/form-data"
            ));
            curl_setopt($ch, CURLOPT_URL, $url); 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields); 
                $output = curl_exec($ch);

        }   


?>