<?php

require "config/Conexion.php";


$res = sendDataSAP();

return $res;

function sendDataSAP()
{
 
        //ini_set('max_execution_time', 300);
		
		date_default_timezone_set('America/La_Paz');

		$fec_arranque = date('Y-m-d H:i:s');
        // ---------------------------------- //
        // LLamada principal al procedimiento //
        // ---------------------------------- //
        $clientes = readCJNData();

	
        // --------------------------------------------------------------- //
        // 1. Configuración de conexión al Service Layer de SAP B1 - LOGIN //
        // --------------------------------------------------------------- //

		$host = "https://52.177.52.183";
        $port = 50000;

        // $username = "GETSAP\\innova06";
        //$password = "MARCOlazarte#3872$";
		// $password = "Pablex@0369*2";
        // $companyDB = "INNOVASALUD";
        $username = "GETSAP\\innova07";
        $password = "Adm_SAP_2025$";
        $companyDB = "INNOVASALUD_TEST";


        // Datos de autenticación
        $authData = array(
                "UserName" => $username,
                "Password" => $password,
                "CompanyDB" => $companyDB
        );

        // Convertir los datos de autenticación a JSON
        $authJson = json_encode($authData);

        //dep($authJson);
        // Inicializar cURL para autenticación
        //echo "LLAMANDO CURL_INIT<br>";
        $ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "{$host}:{$port}/b1s/v1/Login");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $authJson);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen($authJson)
		));
		// curl_setopt($ch, CURLOPT_HEADER, true);
		// curl_setopt($ch, CURLINFO_HEADER_OUT, true);

		$response = curl_exec($ch);

		$loginResponse = json_decode($response, true);
		$sessionId = $loginResponse['SessionId'];
		//echo " Session ID: " . $sessionId . "<br>";
		//die("parada");
	
		// ----------------------------------------- //
		// 2. Empezamos a recorrer cliente x cliente //
		// ----------------------------------------- //
		$num_regs = 0;
		foreach ($clientes as $cliente) 
        {
		
			// ------------------------------------------- //
			// 3. Damos formato a los datos a ser tratados //
			// ------------------------------------------- //
			$benefs = getBenefsCred($cliente);			
			$dataBeneficiario = formatDataBenef($benefs);
			//dep($dataBeneficiario);
			//die();

			// ---------------------------------------- //
			// 4. Validamos si existe el cliente en SAP //
			// ---------------------------------------- //
			$cedula = $dataBeneficiario[0]->Cedula;
			$nombre = $dataBeneficiario[0]->CardName;
			echo "<br>CLIENTE: " . $nombre . "<br>";
			echo "CEDULA: " . $cedula . "<br>";
			//die();

			$endpoint = "/b1s/v1/BusinessPartners?\$filter=FederalTaxID%20eq%20'" . $cedula . "'";

			$host = "https://52.177.52.183";
			$port = 50000;

			// Construir la URL completa
			$url = "{$host}:{$port}{$endpoint}";
			
			//echo "URL: " . $url . "<br>";
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HTTPGET, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				"Cookie: B1SESSION={$sessionId}"
			));
			
			$response = curl_exec($ch);
			$clientes = json_decode($response, true);
			$cliente = $clientes['value'];
			$cant = count($cliente);
			echo "CANT: " . $cant . "<br>";
			//dep($cliente);
			//die('parada.....');
			
			if(!$cant){
				// ----------------------------------- //
				// 5. No existe en SAP => lo agregamos //
				// ----------------------------------- //
				$endpoint = "/b1s/v1/BusinessPartners";

				$host = "https://52.177.52.183";
				$port = 50000;
				
				// Construir la URL completa
				$url = "{$host}:{$port}{$endpoint}";
				
				// Datos del Cliente a se registrados //
				$pp = $dataBeneficiario;
				//dep($pp);
				//die();
				$custData = array(
					"CardCode" => $pp[0]->CardCode,
					"CardName" => $pp[0]->CardName,
					"CardForeignName" => $pp[0]->CardName,
					"CardType" => $pp[0]->CardType,
					"Currency" => $pp[0]->Currency,
					"Cellular" => $pp[0]->telefono,
					"EmailAddress" => $pp[0]->EmailAddr,
					"GroupCode" => $pp[0]->GroupCode,
					"FederalTaxID" => $pp[0]->Cedula,
					"UnifiedFederalTaxID" => $pp[0]->Cedula,
					"U_TIPDOC" => $pp[0]->U_TIPDOC,
					"U_CEDULA" => $pp[0]->Cedula,
					"U_EXTENSION" => $pp[0]->Extension,
					"U_EXPEDICION" => $pp[0]->Expedicion,
					"U_FechaNac" => $pp[0]->FechaNac,
					"U_Genero" => $pp[0]->Genero,
					"U_DOCTYPE" => $pp[0]->U_DOCTYPE,
					"U_DOCNUM" => $pp[0]->Cedula,
					"U_CI" => $pp[0]->Cedula,
					"ContactEmployees" => array (
						array(
							"Name" => "pp NATURAL",
							"FirstName" => $pp[0]->PriNombre,
							"MiddleName" => $pp[0]->SecNombre,
							"LastName" => $pp[0]->ApPaterno,
							"Position"  => $pp[0]->ApMaterno,
							"Phone1" => $pp[0]->Cellular,
							"MobilePhone" => $pp[0]->Cellular
						)
					)
				);
				
				//dep($custData);
				//die('xxxx');
			
				// Convertir los datos de autenticación a JSON //
				$custJson = json_encode($custData);
				
				// Inicializar cURL para el registro del cliente
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Usar solo en desarrollo, NO en producción
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Usar solo en desarrollo, NO en producción
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $custJson);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					'Content-Type: application/json',
				"Cookie: B1SESSION={$sessionId}",
					'Content-Length: ' . strlen($custJson)
				));

				// Ejecutar la solicitud de alta del cliente //
				$response = curl_exec($ch);
				//var_dump($response);
				//die();
				
			} // END if $cant 
			
			// ---------------------------------------- //
			// 6. Creamos la OV para el cliente elegido //
			// ---------------------------------------- //
			$pp = $dataBeneficiario;
			
			$canal 	    = $pp[0]->canal;
			$plan  	    = $pp[0]->cod_plan;
			$NumAtCard  = $pp[0]->NumAtCard;
			$num_tra    = $pp[0]->numTransaccion;
			$contrato   = $pp[0]->contrato;
			$nombre     = $pp[0]->CardName;
			$CardName   = $pp[0]->CardName;
			$CardCode   = $pp[0]->CardCode;
			$cedula     = $pp[0]->Cedula;
			$codAgencia = $pp[0]->codAgencia;
			
			// $DocDate = date('Y-m-d');
			// $DateSmall = date('Ymd');
			
			//$DocDate   = '2025-06-05';
			//$DateSmall = '20250605';
			
			// Obtenemos los datos del Canal //
			$datosCanal = obtenemosDatosCanal($canal);
			// dep($datosCanal);
			// die();
			
			// Obtenemos los Servicios del Plan //
			$servicios = obtenemosServicios($canal, $plan);
			// dep($servicios);
			// die();
			
			$precio   = $servicios[0]['precio'];
			$cod_plan = $servicios[0]['cod_plan'];
			$cod_serv = $servicios[0]['cod_servicio'];
			$vigencia = $servicios[0]['vigencia'];


			// Trabajamos las fechas del plan //
			$fechaInicio = $pp[0]->fechaInicio;

			// Asegurarse de que solo la parte YYYY-MM-DD se use
			$fechaInicio = substr($fechaInicio, 0, 10); // formato: 'YYYY-MM-DD'


            $DocDate   = $fechaInicio;
            $DateSmall = str_replace('-', '', $DocDate);

			// Convertir a objeto DateTime
			$fecha = new DateTime($fechaInicio);

			// Calcular fecha de fin según vigencia
			if ($vigencia == 12) {
				$fecha->modify('+1 year');
				$fechaFin = $fecha->format('Y-m-d');

			} else if ($vigencia == 1) {
				$fecha->modify('+1 month');
				$fechaFin = $fecha->format('Y-m-d');
			}

			echo "F INI: " . $fechaInicio . "<br>";
			echo "F FIN: " . $fechaFin . "<br>";
			
			if(empty($contrato)){
				$res = generaNumContrato($canal, $cod_plan);
				$contrato = $res['contrato'];
			}

            $rspta = actualizaContratoEnTemp($NumAtCard,$num_tra,$contrato);
            if(!$rspta)
            {
                echo "Error: Al actualizar el número de contrato en la TEMPS_JN <br>";
                echo "COD OPE: " . $NumAtCard . "<br>";
                echo "COD TRA: " . $num_tra . "<br>";
                die();
            }

			echo "CONTRATO: " . $contrato . "<br>";
			echo "CODIGO PLAN: " . $plan . "<br>";
			//die();
			
			if($plan == 'PPCE0120' || $plan == 'PPCE0119'){
				$ordenVenta = array(
					"DocType" => "dDocument_Items",
					"DocDate" => $DocDate,  //"2024-12-05",   //----------
					"DocDueDate" => $DocDate, //"2024-12-05",  //----------
					"TaxDate" => $DocDate,  //"2024-12-05",   //----------
					"CardCode" => $CardCode,
					"CardName" => $nombre,
					"NumAtCard" => $NumAtCard, //"CRS-PROVSP-240088",     //----------------
					"DocTotal" => $servicios[0]['precio'],   //450.0,   //----------
					"DocCurrency" => "BS",  
					"Comments" => $contrato, //"PPCE0001-LP-24-0001-0000088",   //----------  Generarlo
					"U_NIT" => $cedula,
					"U_RAZSOC" => $nombre,
					"U_ESTADOFC" => "V",
					"U_COMENTARIOS" => $contrato . "|" . $DateSmall,   //"PPCE0001-LP-24-0001-0000088|20241205",  //----------
					"U_CANAL" => $canal, //"C003",   //----------
					"U_CONSULTORIO" => "NOMBRE CONSULTORIO",
					"U_NumTransaccionExt" => $num_tra,  //"5588",  //----------
					"DiscountPercent" => 0.0,
					"TotalDiscount" => 0.0,
					"CashDiscountDateOffset" => 0,
					"TotalDiscountFC" => 0.0,
					"TotalDiscountSC" => 0.0,
					"DocumentLines" => [
						[

							"LineNum" => 0,
							"ItemCode" => $servicios[0]['cod_plan'], //"PPCE0001",  //----------
							"ItemDescription" => $servicios[0]['plan'], //"Salud Preventiva", //----------
							"Quantity" => 1,   
							"PriceAfterVAT" => 24,
							"Currency" => "BS",
							"WarehouseCode" => "LPZ-ON",
							"TaxCode" => "IVA",
							"U_RECETA" => 0,
							"U_FECHAINI" => $fechaInicio,  //"2024-12-05",
							"U_FECHAFIN" => substr($fechaFin,0,10), //"2025-12-05",
							"U_Contrato" => $contrato, //"CRS-CRSVSP-18988",
							"U_CodigoPlan" => $plan,   //"PPCE0001" 
							"DiscPrcnt" => 0,
							"DiscountPercent" => 0.0,
						],
						[
							"LineNum" => 1,
							"ItemCode" => $servicios[0]['cod_servicio'],   //"PPCE0002",
							"ItemDescription" => $servicios[0]['servicio'], //"Salud Preventiva2",
							"Quantity" => $servicios[0]['cantidad'],  //1.0,   
							"PriceAfterVAT" => 0,  //450.0,
							"Currency" => "BS",
							"WarehouseCode" => "LPZ-ON",
							"TaxCode" => "IVA",
							"U_RECETA" => 1,
							"U_FECHAINI" => $fechaInicio,  //"2024-12-05",
							"U_FECHAFIN" => substr($fechaFin,0,10), //"2025-12-05",
							"U_Contrato" => $contrato, //"CRS-CRSVSP-18988",
							"U_CodigoPlan" => $plan,   //"PPCE0001" 
						],
						[
							"LineNum" => 2,
							"ItemCode" => $servicios[1]['cod_servicio'],   //"PPCE0002",
							"ItemDescription" => $servicios[1]['servicio'], //"Salud Preventiva2",
							"Quantity" => $servicios[1]['cantidad'],  //1.0,   
							"PriceAfterVAT" => 0,  //450.0,
							"Currency" => "BS",
							"WarehouseCode" => "LPZ-ON",
							"TaxCode" => "IVA",
							"U_RECETA" => 1,
							"U_FECHAINI" => $fechaInicio,  //"2024-12-05",
							"U_FECHAFIN" => substr($fechaFin,0,10), //"2025-12-05",
							"U_Contrato" => $contrato, //"CRS-CRSVSP-18988",
							"U_CodigoPlan" => $plan,   //"PPCE0001" 
						]
					]
				);
				
			}			
			// echo "ORDEN DE VENTA A ENVIAR A SAP<br>";
			// dep($ordenVenta);
			// die();
			
			// Convertir los datos de autenticación a JSON //
			$ordenJson = json_encode($ordenVenta);

			// Preparamos la llamada a la OV //
			$endpoint = "/b1s/v1/Orders";
			$host = "https://52.177.52.183";
			$port = 50000;

			// Construir la URL completa
			$url = "{$host}:{$port}{$endpoint}";
		
			// Inicializar cURL para el registro de la OV
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			//curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Usar solo en desarrollo, NO en producción
			//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Usar solo en desarrollo, NO en producción
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $ordenJson);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				"Cookie: B1SESSION={$sessionId}",
				'Content-Length: ' . strlen($ordenJson)
			));
            // dep($ordenJson);exit;
			
			// Ejecutar la solicitud de alta del cliente //
			$response = curl_exec($ch);
			$respuesta = json_decode($response, true);

            // echo "RESPUESTA DE LA OV<br>";
			// dep($response);//die;
			
			if (curl_errno($ch)) {
				$agregaResponse = ([
					"estado" => 'X',
					"msg" => 'Error al crear la OV: ' . curl_error($ch),
					"error" => var_dump($response),
					"statusCode" => 404
				]);
				//curl_close($ch);
				dep($agregaResponse);
				die('Stop. Al crear la Orden de Venta!');
			}
			//die();
			
			
			if(isset($respuesta['DocEntry'])){
				$DocEntry = $respuesta['DocEntry'];
				$DocNum   = $respuesta['DocNum'];
			}else{
				$agregaResponse = ([
					"estado" => 'X',
					"msg" => 'Error al crear la OV: ' . curl_error($ch),
					"statusCode" => 404
				]);
				dep($agregaResponse);
				die('Stop. Al crear la Orden de Venta!');				
			}
			
			
			// -------------------------------------- //
			// 7. Insertamos reg Cabecera Certificado //
			// -------------------------------------- //
			$U_DOC_NUM = $respuesta['DocNum'];
			$U_DocEntry = $respuesta['DocEntry'];
			
			$U_NumAtCard = $NumAtCard;
			$U_Canal = $canal;

			$dataCabCertificado = array(

                "U_DOC_NUM" => $U_DOC_NUM,  //"240000004", // No Documento OV
                "U_NumAtCard" => $U_NumAtCard, //"CRS-VGSIBC-0002493",   //--- NumAtCard OV
                "U_DocEntry" => $U_DocEntry, //45, /// --- DocEntry OV
                "U_Canal" => $U_Canal, //"C003",    //--- Canal
                "U_Tipo" => null,
                "U_FormaComercializacion" => "WS",
                "U_DocType" => "17" //--- Tipo Documento OV 17

            );
			
			// Convertir los datos de autenticación a JSON //
			$cabCertificadoJson = json_encode($dataCabCertificado);
			//dep($dataCabCertificado);

			$host = "https://52.177.52.183";
			$port = 50000;

			// Construir la URL completa
			$endpoint = "/b1s/v1/U_CAB_CERTIFICADO";

			$url = "{$host}:{$port}{$endpoint}";
		
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Usar solo en desarrollo, NO en producción
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Usar solo en desarrollo, NO en producción
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $cabCertificadoJson);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				"Cookie: B1SESSION={$sessionId}",
				'Content-Length: ' . strlen($cabCertificadoJson)
			));

			// Ejecutar la solicitud de alta del cliente //
			$response = curl_exec($ch);
			//var_dump($response);
		
			// -------------------------------------------- //
			// 8. Obtenemos el data de Cabecera Certificado //
			// -------------------------------------------- //		
			$host = "https://52.177.52.183";
			$port = 50000;

			// Construir la URL completa
			$endpoint = "/b1s/v1/U_CAB_CERTIFICADO?\$filter=U_DocEntry%20eq%20'" . $DocEntry . "'%20and%20U_DocType%20eq%20'17'";
			$url = "{$host}:{$port}{$endpoint}";
			
			//echo "URL: " . $url . "<br>";
			//die();
			//$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HTTPGET, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				"Cookie: B1SESSION={$sessionId}"
			));			
			
			$response = curl_exec($ch);
			$dataCab = json_decode($response,true);
			//var_dump($dataCab);
			
			if (curl_errno($ch)) {
				$agregaResponse = ([
					"estado" => 'X',
					"msg" => 'Error al obtener Data Cab Certificado: ' . curl_error($ch),
					"statusCode" => 404
				]);
				//curl_close($ch);
				dep($agregaResponse);
				die('Stop. Al obtener data Cab certf.');
			}
			
			// ------------------------------------------- //
			// 9. Insertamos el Registro Linea Certificado //
			// ------------------------------------------- //
			$U_CardCode    = $CardCode;
			$U_CardName    = $CardName;
			$U_ItemCode    = $plan;
			$U_VatIdUnCmp  = $cedula;
			$U_Certificado = $dataCab['value'][0]['Code'];
			$U_Contrato    = $contrato;
			
			// echo "NUM CONTRATO LIN: " . $U_Contrato . "<br>";
			
			$dataLin = array(
				"U_CardCode" => $U_CardCode,  // "720803-LYMA",
				"U_CardName" => $U_CardName, //"MARCO ANTONIO LAZARTE YAPU",
				"U_VatIdUnCmp" => $U_VatIdUnCmp, //"2305905",
				"U_Certificado" => $U_Certificado, //"179460",
				"U_Contrato" => $U_Contrato, //"PPCE0001-LP-24-0001-0000001",
				"U_LINEA" => "0",
				"U_ItemCode" => $U_ItemCode, //"PPCE0001",
				"U_FormaComercializacion" => "COLECTIVO"
			);
			
			// echo "DATA LIN<br>";
			// dep($dataLin);
			
			$dataLinJson = json_encode($dataLin);
			
			$host = "https://52.177.52.183";
			$port = 50000;

			// Construir la URL completa
			$endpoint = "/b1s/v1/U_LIN_CERTIFICADO";

			$url = "{$host}:{$port}{$endpoint}";
			
			// Inicializar cURL para el registro del cliente
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Usar solo en desarrollo, NO en producción
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Usar solo en desarrollo, NO en producción
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $dataLinJson);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				"Cookie: B1SESSION={$sessionId}",
				'Content-Length: ' . strlen($dataLinJson)
			));

			// Ejecutar la solicitud de alta del cliente //
			$response = curl_exec($ch);			
			
			if (curl_errno($ch)) {
				$agregaResponse = ([
					"msg" => 'Error al insertar LIN CERTIFICADO: ' . curl_error($ch),
						"code" => 404 
				]);
				//curl_close($ch);
				dep($agregaResponse);
				die();
			}

			//die();

			// --------------------------- //
			// 10. Obtenemos Lin Data Code //
			// --------------------------- //
			$DocEntry = $U_DocEntry;
			$ItemCode = $plan;
			
			$endpoint = "/b1s/v1/\$crossjoin(U_CAB_CERTIFICADO,U_LIN_CERTIFICADO)?\$expand=U_CAB_CERTIFICADO(\$select=Code,U_DOC_NUM,U_DocEntry,U_DocType),U_LIN_CERTIFICADO(\$select=Code,U_Certificado,U_Contrato,U_ItemCode)&\$filter=U_CAB_CERTIFICADO/Code%20eq%20U_LIN_CERTIFICADO/U_Certificado%20and%20U_CAB_CERTIFICADO/U_DocEntry%20eq%20'". $DocEntry . "'%20and%20U_LIN_CERTIFICADO/U_ItemCode%20eq%20'". $ItemCode . "'";
			
			$host = "https://52.177.52.183";
			$port = 50000;
			
			// Construir la URL completa
			$url = "{$host}:{$port}{$endpoint}";
		
			curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                "Cookie: B1SESSION={$sessionId}"
            ));
		
			$response = curl_exec($ch);
			
			if (curl_errno($ch)) {
				$agregaResponse = ([
                    "msg" => 'Error al buscar la Llave de Linea Certificado: ' . curl_error($ch),
                    "statusCode" => 404
            	]);
				dep($agregaResponse);
				die();
            }
			
			// Decodificar la respuesta JSON del Beneficiario
			$llave_lineas = json_decode($response, true);
			$llave_linea = $llave_lineas['value'];
			$llave_linea_certificado = $llave_linea[0]['U_LIN_CERTIFICADO']['Code'];
		
			// ------------------------------------ //
			// 11. Inserta Data Cabeza Beneficiario //
			// ------------------------------------ //
			// PRIMERO HAREMOS PARA EL TITULAR //
			$titular = $dataBeneficiario[0];
			//$DocEntry    = $dataDocEntry[0]['DocEntry'];

			$titData = array (
				"U_DocEntry" => $DocEntry,
				"U_CardCode" => $titular->CardCode,
				"U_Tipo" => 0, // 0: titular
				"U_Linea" => $llave_linea_certificado,
				"Name" => 1
			);
			
			//dep($titData);
			//die();
			
			$host = "https://52.177.52.183";
			$port = 50000;

			// Construir la URL completa
			$endpoint = "/b1s/v1/U_CAB_BENEFICIARIO";

			$url = "{$host}:{$port}{$endpoint}";
			
			// Convertir los datos de autenticación a JSON
			$beneficiarioJson = json_encode($titData);
			
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Usar solo en desarrollo, NO en producción
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Usar solo en desarrollo, NO en producción
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $beneficiarioJson);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				"Cookie: B1SESSION={$sessionId}",
				'Content-Length: ' . strlen($beneficiarioJson)
			));
			
			$response = curl_exec($ch);
			
			if (curl_errno($ch)) {
				$agregaResponse = ([
                    "msg" => 'Error al insertar Cabecera Beneficiario: ' . curl_error($ch),
                    "statusCode" => 404
            	]);
				dep($agregaResponse);
				die();
            }
			
			$num_regs++;
			
		}
		$fec_parada = date('Y-m-d H:i:s');
		echo "<br>INI: " . $fec_arranque . "<br>";
		echo "FIN: " . $fec_parada . "<br>";
		
		$dtInicio = new DateTime($fec_arranque);
		$dtFin    = new DateTime($fec_parada);
		$diff = $dtInicio->diff($dtFin);
		$segundos = ($dtFin->getTimestamp() - $dtInicio->getTimestamp());
		
		echo "\n\nCANTIDAD DE REGISTROS: " . $num_regs . "\n";
		echo "SE REALIZO EN: " . $segundos . " segundos\n";
        return "SE REALIZO EN: " . $segundos . " segundos\n";
		//die('Terminó bien');
		
}


/*
        $cantidad = count($clientes);
        if($cantidad == 0){
            echo "No Hay registros para ennviar\n";
            return "Estado = 'X'";
        }

        // ----------------------------------------------------------- //
            //Configuración de conexión al Service Layer de SAP B1 - LOGIN //
            // ----------------------------------------------------------- //
            $host = "https://52.177.52.183";
            $port = 50000;

            $username = "GETSAP\\innova07";
            $password = "MARCOlazarte#3872$";
            $companyDB = "INNOVASALUD_TEST";

            // Datos de autenticación
            $authData = array(
                "UserName" => $username,
                "Password" => $password,
                "CompanyDB" => $companyDB
            );

            // Convertir los datos de autenticación a JSON
            $authJson = json_encode($authData);

            //dep($authData);
            // Inicializar cURL para autenticación
            //echo "LLAMANDO CURL_INIT<br>";
            $ch = curl_init();
            //echo " VOLVI DEL CURL_INIT<br>";
            curl_setopt($ch, CURLOPT_URL, "{$host}:{$port}/b1s/v1/Login");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Usar solo en desarrollo, NO en producción
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Usar solo en desarrollo, NO en producción
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $authJson);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($authJson)
            ));

            // Ejecutar la solicitud de autenticación
            $response = curl_exec($ch);
            //var_dump($response);

            if (curl_errno($ch)) {
                //echo 'Error en la solicitud curl:' . curl_error($ch);
                $agregaResponse = ([
                    "msg" => 'Error de autenticación: ' . curl_error($ch),
                    "statusCode" => 404
                ]);
                curl_close($ch);
                return $agregaResponse;
            }

            // Decodificar la respuesta JSON
            $loginResponse = json_decode($response, true);

            // Verificar si la autenticación fue exitosa
            if (!isset($loginResponse['SessionId'])) {
                //echo "Error de autenticación: " . $loginResponse['error']['message']['value'];
                $agregaResponse = ([
                    "msg" => 'Error de autenticación: ' . curl_error($ch),
                    "statusCode" => 404
                ]);
                curl_close($ch);
                return $agregaResponse;
            }

            // Obtener el SessionId para usar en las solicitudes futuras
            $sessionId = $loginResponse['SessionId'];
            //echo " Session ID: " . $sessionId . "\n";
            //return "Fin";

        foreach ($clientes as $cliente) 
        {
            $dataBeneficiario = $this->formatDataBenef($cliente);
            $id_registro = $cliente->id;
            $cedula = $cliente->num_documento;
            $numPrestamo = $cliente->numPrestamo;

            //return $dataBeneficiario;
            //return $cliente;


            // ------------------------------------------- //
            // 2. Agregamos a clientes que no estén en SAP //
            // ------------------------------------------- //
            // 2.1 Verificamos si el cliente esta en SAP   //
            // ------------------------------------------- //
            //$endpoint = "/b1s/v1/BusinessPartners?\$filter=FederalTaxID%20eq%20'".$cedula."'";
            $endpoint = "/b1s/v1/BusinessPartners?\$filter=FederalTaxID%20eq%20'".$cedula."'&\$select=CardCode,CardName,CardForeignName,U_FechaNac,U_Genero,Cellular";

            // Construir la URL completa
            $url = "{$host}:{$port}{$endpoint}";

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                "Cookie: B1SESSION={$sessionId}"
            ));

            $response = curl_exec($ch);
            //echo "RESPONSE:";
            //var_dump($response);
            //die('VERIFICA CLIENTE PARANDO.....');

            if (curl_errno($ch)) {
                    $agregaResponse = ([
                            "msg" => 'Error en la solicitud GET: ' . curl_error($ch),
                            "statusCode" => 404
                        ]);
                        curl_close($ch);
                        return $agregaResponse;
            }

            // Decodificar la respuesta JSON de los clientes
            $clientes = json_decode($response, true);
            //$cliente = $clientes['value'][0];
            $cliente = $clientes['value'];
            $cantidad = count($cliente);

            //echo "   Cantidad Cliente: " . $cantidad . "\n";

            if(!$cantidad){

                // ---------------------------------------------- //
                // No existe  el NUEVO Beneficiario -> Lo creamos //
                // ---------------------------------------------- //

                //echo " Creando cliente en SAP ...... ";

                $endpoint = "/b1s/v1/BusinessPartners";

                // Construir la URL completa
                $url = "{$host}:{$port}{$endpoint}";


                // Datos del Cliente a se registrados //
                $custData = array(
                    "CardCode" => $dataBeneficiario['CardCode'],
                    "CardName" => $dataBeneficiario['CardName'],
                    "CardForeignName" => $dataBeneficiario['CardName'],
                    "CardType" => $dataBeneficiario['CardType'],
                    "Currency" => $dataBeneficiario['Currency'],
                    "Cellular" => $dataBeneficiario['telefono'],
                    "EmailAddress" => $dataBeneficiario['EmailAddr'],
                    "GroupCode" => $dataBeneficiario['GroupCode'],
                    "FederalTaxID" => $dataBeneficiario['Cedula'],
                    "UnifiedFederalTaxID" => $dataBeneficiario['Cedula'],
                    "U_TIPDOC" => $dataBeneficiario['U_TIPDOC'],
                    "U_CEDULA" => $dataBeneficiario['Cedula'],
                    "U_EXTENSION" => $dataBeneficiario['Extension'],
                    "U_EXPEDICION" => $dataBeneficiario['Expedicion'],
                    "U_FechaNac" => $dataBeneficiario['FechaNac'],
                    "U_Genero" => $dataBeneficiario['Genero'],
                    "U_DOCTYPE" => $dataBeneficiario['U_DOCTYPE'],
                    "U_DOCNUM" => $dataBeneficiario['Cedula'],
                    "U_CI" => $dataBeneficiario['Cedula'],
                    "ContactEmployees" => array (
                        array(
                        "Name" => "pp NATURAL",
                        "FirstName" => $dataBeneficiario['PriNombre'],
                        "MiddleName" => $dataBeneficiario['SecNombre'],
                        "LastName" => $dataBeneficiario['ApPaterno'],
                        "Position"  => $dataBeneficiario['ApMaterno'],
                        "Phone1" => $dataBeneficiario['Cellular'],
                        "MobilePhone" => $dataBeneficiario['Cellular'],
                        )
                    )
                );


                // Convertir los datos de autenticación a JSON //
                $custJson = json_encode($custData);


                // Inicializar cURL para el registro del cliente
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Usar solo en desarrollo, NO en producción
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Usar solo en desarrollo, NO en producción
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $custJson);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    "Cookie: B1SESSION={$sessionId}",
                    'Content-Length: ' . strlen($custJson)
                ));

                // Ejecutar la solicitud de alta del cliente //
                $response = curl_exec($ch);

                //var_dump($response);
                //die();

                $error = 'error';
                $pos_error = strpos($response,$error);

                if($pos_error == 6){

                    $value = 'value';
                    $pos_value = strpos($response,$value);

                    echo "ERROR AL CREAR CLIENTE EN SAP";
                    die();

                }

            }else{ // End if else Cantidad
                echo "YA EXISTE EL TITULAR\n";
            }
            // ----------------------------- //
            // 3. Damos de Alta la OV en SAP //
            // ----------------------------- //
            date_default_timezone_set('America/La_Paz');
            $DocDate = date('Y-m-d');

            $fechaInicio = $dataBeneficiario['fechaInicio'];
            $fechaFin = Carbon::parse($fechaInicio)->addYear(); 
            $fechaFin = $fechaFin->format('Y-m-d');

            // Buscammos los servicios del PLAN //
            $canal     = $dataBeneficiario['canal'];
            $cod_plan  = $dataBeneficiario['cod_plan'];
            $NumAtCard = $dataBeneficiario['NumAtCard'];
            $CardName  = $dataBeneficiario['CardName'];
            $CardCode  = $dataBeneficiario['CardCode'];
            $cedula    = $dataBeneficiario['Cedula'];
            $U_CONSULTORIO = $dataBeneficiario['U_CONSULTORIO'];

            echo "CANAL: " . $canal . "\n";
            echo "PLAN: " . $cod_plan . "\n";
            $servicios = $this->obtenemosServicios($canal, $cod_plan);
            //return $servicios;

            $plan        = $servicios[0]->plan;
            $precio_plan = $servicios[0]->precio;

            $contrato  = isset($dataBeneficiario['contrato'])?$dataBeneficiario['contrato']:"";
            if(empty($contrato)){
                $res = $this->generaNumContrato($canal, $cod_plan);
                //return $res;
                $contrato = $res['contrato'];
            }

            //return $contrato;

	        $datosCanal = $this->obtenemosDatosCanal($canal);

            $CardName  = $datosCanal[0]->razon_social;
            $CardCode  = $datosCanal[0]->CardCode;
            $U_NIT	   = $datosCanal[0]->NIT;

            //return $datosCanal;
            
            if($cod_plan == 'PPCE0141' || $cod_plan == 'PPCE0142'){
                    $ordenVenta = array(
                        "DocType" => "dDocument_Items",
                        "DocDate" => $DocDate,  //"2024-12-05",   //----------
                        "DocDueDate" => $DocDate, //"2024-12-05",  //----------
                        "TaxDate" => $DocDate,  //"2024-12-05",   //----------
                        "CardCode" => $CardCode,
                        "CardName" => $CardName,
                        "DocTotal" => $precio_plan,
                        "DocCurrency" => "BS",
                        "Comments" => $contrato,
                        "NumAtCard" => $NumAtCard, //"CRS-PROVSP-240088",
                        "U_NIT" => $U_NIT,
                        "U_CONSULTORIO" => $U_CONSULTORIO,
                        "U_RAZSOC" => $CardName,
                        "U_CANAL" => $canal, //"C003",   //----------
                        "U_ESTADOFC" => "V",
                        "U_COMENTARIOS" => $contrato,   //"PPCE0001-LP-24-0001-0000088|20241205",  //----------
                        "U_NumTransaccionExt" => $NumAtCard,
                        "DocumentLines" => [
                            [
                                "LineNum" => 0,
                                "ItemCode" => $cod_plan, //"PPCE0001",  //----------
                                "ItemDescription" => $servicios[0]->plan,
                                "Quantity" => 1, //$servicios['cantidad'],
                                "PriceAfterVAT" => (float)$servicios[0]->precio,
                                "Currency" => "BS",
                                "WarehouseCode" => "LPZ-ON",
                                "TaxCode" => "IVA",
                                "U_RECETA" => 0,
                                "U_FECHAINI" => $fechaInicio,
                                "U_Contrato" => $contrato,
                                //"U_FECHAFIN" => "2025-12-11",
                                "U_FECHAFIN" => $fechaFin,
                            ],
                            [
                                "LineNum" => 1,
                                "ItemCode" => $servicios[0]->cod_servicio,
                                "ItemDescription" => $servicios[0]->servicio,
                                "Quantity" => (int)$servicios[0]->cantidad,  // 3,
                                "PriceAfterVAT" => 0,
                                "Currency" => "BS",
                                "WarehouseCode" => "LPZ-ON",
                                "TaxCode" => "IVA",
                                "U_RECETA" => 1,
                                "U_FECHAINI" => $fechaInicio,
                                "U_Contrato" => $contrato,
                                //"U_FECHAFIN" => "2025-12-11",
                                "U_FECHAFIN" => $fechaFin,
                            ],
                            [
                                "LineNum" => 2,
                                "ItemCode" => $servicios[1]->cod_servicio,
                                "ItemDescription" => $servicios[1]->servicio,
                                "Quantity" => (int)$servicios[1]->cantidad,  //50.0,
                                "PriceAfterVAT" => 0,
                                "Currency" => "BS",
                                "WarehouseCode" => "LPZ-ON",
                                "TaxCode" => "IVA",
                                "U_RECETA" => 1,
                                "U_FECHAINI" => $fechaInicio,
                                "U_Contrato" => $contrato,
                                //"U_FECHAFIN" => "2025-12-11",
                                "U_FECHAFIN" => $fechaFin,
                            ],
                            [
                                "LineNum" => 3,
                                "ItemCode" => $servicios[2]->cod_servicio,
                                "ItemDescription" => $servicios[2]->servicio,
                                "Quantity" => $servicios[2]->cantidad,  //50.0,
                                "PriceAfterVAT" => 0,
                                "Currency" => "BS",
                                "WarehouseCode" => "LPZ-ON",
                                "TaxCode" => "IVA",
                                "U_RECETA" => 1,
                                "U_FECHAINI" => $fechaInicio,  //"2024-12-05",
                                "U_Contrato" => $contrato,
                                "U_FECHAFIN" => substr($fechaFin,0,10), //"2025-12-05",
                            ],
                            [
                                "LineNum" => 4,
                                "ItemCode" => $servicios[3]->cod_servicio,
                                "ItemDescription" => $servicios[3]->servicio,
                                "Quantity" => $servicios[3]->cantidad,  //50.0,
                                "PriceAfterVAT" => 0,
                                "Currency" => "BS",
                                "WarehouseCode" => "LPZ-ON",
                                "TaxCode" => "IVA",
                                "U_RECETA" => 1,
                                "U_FECHAINI" => $fechaInicio,  //"2024-12-05",
                                "U_FECHAFIN" => substr($fechaFin,0,10), //"2025-12-05",
                            ],
                            [
                                "LineNum" => 5,
                                "ItemCode" => $servicios[4]->cod_servicio,
                                "ItemDescription" => $servicios[4]->servicio,
                                "Quantity" => $servicios[4]->cantidad,  //50.0,
                                "PriceAfterVAT" => 0,
                                "Currency" => "BS",
                                "WarehouseCode" => "LPZ-ON",
                                "TaxCode" => "IVA",
                                "U_RECETA" => 1,
                                "U_FECHAINI" => $fechaInicio,  //"2024-12-05",
                                "U_FECHAFIN" => substr($fechaFin,0,10), //"2025-12-05",
                            ],
                            [
                                "LineNum" => 6,
                                "ItemCode" => $servicios[5]->cod_servicio,
                                "ItemDescription" => $servicios[5]->servicio,
                                "Quantity" => $servicios[5]->cantidad,  //50.0,
                                "PriceAfterVAT" => 0,
                                "Currency" => "BS",
                                "WarehouseCode" => "LPZ-ON",
                                "TaxCode" => "IVA",
                                "U_RECETA" => 1,
                                "U_FECHAINI" => $fechaInicio,  //"2024-12-05",
                                "U_FECHAFIN" => substr($fechaFin,0,10), //"2025-12-05",
                            ],
                            [
                                "LineNum" => 7,
                                "ItemCode" => $servicios[6]->cod_servicio,
                                "ItemDescription" => $servicios[6]->servicio,
                                "Quantity" => $servicios[6]->cantidad,  //50.0,
                                "PriceAfterVAT" => 0,
                                "Currency" => "BS",
                                "WarehouseCode" => "LPZ-ON",
                                "TaxCode" => "IVA",
                                "U_RECETA" => 1,
                                "U_FECHAINI" => $fechaInicio,  //"2024-12-05",
                                "U_FECHAFIN" => substr($fechaFin,0,10), //"2025-12-05",
                            ],
                            [
                                "LineNum" => 8,
                                "ItemCode" => $servicios[7]->cod_servicio,
                                "ItemDescription" => $servicios[7]->servicio,
                                "Quantity" => $servicios[7]->cantidad,  //50.0,
                                "PriceAfterVAT" => 0,
                                "Currency" => "BS",
                                "WarehouseCode" => "LPZ-ON",
                                "TaxCode" => "IVA",
                                "U_RECETA" => 1,
                                "U_FECHAINI" => $fechaInicio,  //"2024-12-05",
                                "U_FECHAFIN" => substr($fechaFin,0,10), //"2025-12-05",
                            ],
                        ]
                    );

            }

            if($cod_plan == 'PPCE0143'){
                    $ordenVenta = array(
                        "DocType" => "dDocument_Items",
                        "DocDate" => $DocDate,  //"2024-12-05",   //----------
                        "DocDueDate" => $DocDate, //"2024-12-05",  //----------
                        "TaxDate" => $DocDate,  //"2024-12-05",   //----------
                        "CardCode" => $CardCode,
                        "CardName" => $CardName,
                        "DocTotal" => $precio_plan,
                        "DocCurrency" => "BS",
                        "Comments" => $contrato,
                        "NumAtCard" => $NumAtCard, //"CRS-PROVSP-240088",
                        "U_NIT" => $U_NIT,
                        "U_CONSULTORIO" => $U_CONSULTORIO,
                        "U_RAZSOC" => $CardName,
                        "U_CANAL" => $canal, //"C003",   //----------
                        "U_ESTADOFC" => "V",
                        "U_COMENTARIOS" => $contrato,   //"PPCE0001-LP-24-0001-0000088|20241205",  //----------
                        "U_NumTransaccionExt" => $NumAtCard,
                        "DocumentLines" => [
                            [
                                "LineNum" => 0,
                                "ItemCode" => $cod_plan, //"PPCE0001",  //----------
                                "ItemDescription" => $servicios[0]->plan,
                                "Quantity" => 1, //$servicios['cantidad'],
                                "PriceAfterVAT" => (float)$servicios[0]->precio,
                                "Currency" => "BS",
                                "WarehouseCode" => "LPZ-ON",
                                "TaxCode" => "IVA",
                                "U_RECETA" => 0,
                                "U_FECHAINI" => $fechaInicio,
                                "U_Contrato" => $contrato,
                                //"U_FECHAFIN" => "2025-12-11",
                                "U_FECHAFIN" => $fechaFin,
                            ],
                            [
                                "LineNum" => 1,
                                "ItemCode" => $servicios[0]->cod_servicio,
                                "ItemDescription" => $servicios[0]->servicio,
                                "Quantity" => (int)$servicios[0]->cantidad,  // 3,
                                "PriceAfterVAT" => 0,
                                "Currency" => "BS",
                                "WarehouseCode" => "LPZ-ON",
                                "TaxCode" => "IVA",
                                "U_RECETA" => 1,
                                "U_FECHAINI" => $fechaInicio,
                                "U_Contrato" => $contrato,
                                //"U_FECHAFIN" => "2025-12-11",
                                "U_FECHAFIN" => $fechaFin,
                            ],
                            [
                                "LineNum" => 2,
                                "ItemCode" => $servicios[1]->cod_servicio,
                                "ItemDescription" => $servicios[1]->servicio,
                                "Quantity" => (int)$servicios[1]->cantidad,  //50.0,
                                "PriceAfterVAT" => 0,
                                "Currency" => "BS",
                                "WarehouseCode" => "LPZ-ON",
                                "TaxCode" => "IVA",
                                "U_RECETA" => 1,
                                "U_FECHAINI" => $fechaInicio,
                                "U_Contrato" => $contrato,
                                //"U_FECHAFIN" => "2025-12-11",
                                "U_FECHAFIN" => $fechaFin,
                            ],
                            [
                                "LineNum" => 3,
                                "ItemCode" => $servicios[2]->cod_servicio,
                                "ItemDescription" => $servicios[2]->servicio,
                                "Quantity" => $servicios[2]->cantidad,  //50.0,
                                "PriceAfterVAT" => 0,
                                "Currency" => "BS",
                                "WarehouseCode" => "LPZ-ON",
                                "TaxCode" => "IVA",
                                "U_RECETA" => 1,
                                "U_FECHAINI" => $fechaInicio,  //"2024-12-05",
                                "U_Contrato" => $contrato,
                                "U_FECHAFIN" => substr($fechaFin,0,10), //"2025-12-05",
                            ],
                            [
                                "LineNum" => 4,
                                "ItemCode" => $servicios[3]->cod_servicio,
                                "ItemDescription" => $servicios[3]->servicio,
                                "Quantity" => $servicios[3]->cantidad,  //50.0,
                                "PriceAfterVAT" => 0,
                                "Currency" => "BS",
                                "WarehouseCode" => "LPZ-ON",
                                "TaxCode" => "IVA",
                                "U_RECETA" => 1,
                                "U_FECHAINI" => $fechaInicio,  //"2024-12-05",
                                "U_FECHAFIN" => substr($fechaFin,0,10), //"2025-12-05",
                            ],
                            [
                                "LineNum" => 5,
                                "ItemCode" => $servicios[4]->cod_servicio,
                                "ItemDescription" => $servicios[4]->servicio,
                                "Quantity" => $servicios[4]->cantidad,  //50.0,
                                "PriceAfterVAT" => 0,
                                "Currency" => "BS",
                                "WarehouseCode" => "LPZ-ON",
                                "TaxCode" => "IVA",
                                "U_RECETA" => 1,
                                "U_FECHAINI" => $fechaInicio,  //"2024-12-05",
                                "U_FECHAFIN" => substr($fechaFin,0,10), //"2025-12-05",
                            ],
                            [
                                "LineNum" => 6,
                                "ItemCode" => $servicios[5]->cod_servicio,
                                "ItemDescription" => $servicios[5]->servicio,
                                "Quantity" => $servicios[5]->cantidad,  //50.0,
                                "PriceAfterVAT" => 0,
                                "Currency" => "BS",
                                "WarehouseCode" => "LPZ-ON",
                                "TaxCode" => "IVA",
                                "U_RECETA" => 1,
                                "U_FECHAINI" => $fechaInicio,  //"2024-12-05",
                                "U_FECHAFIN" => substr($fechaFin,0,10), //"2025-12-05",
                            ],
                            [
                                "LineNum" => 7,
                                "ItemCode" => $servicios[6]->cod_servicio,
                                "ItemDescription" => $servicios[6]->servicio,
                                "Quantity" => $servicios[6]->cantidad,  //50.0,
                                "PriceAfterVAT" => 0,
                                "Currency" => "BS",
                                "WarehouseCode" => "LPZ-ON",
                                "TaxCode" => "IVA",
                                "U_RECETA" => 1,
                                "U_FECHAINI" => $fechaInicio,  //"2024-12-05",
                                "U_FECHAFIN" => substr($fechaFin,0,10), //"2025-12-05",
                            ],
                            [
                                "LineNum" => 8,
                                "ItemCode" => $servicios[7]->cod_servicio,
                                "ItemDescription" => $servicios[7]->servicio,
                                "Quantity" => $servicios[7]->cantidad,  //50.0,
                                "PriceAfterVAT" => 0,
                                "Currency" => "BS",
                                "WarehouseCode" => "LPZ-ON",
                                "TaxCode" => "IVA",
                                "U_RECETA" => 1,
                                "U_FECHAINI" => $fechaInicio,  //"2024-12-05",
                                "U_FECHAFIN" => substr($fechaFin,0,10), //"2025-12-05",
                            ],
                            [
                                "LineNum" => 9,
                                "ItemCode" => $servicios[8]->cod_servicio,
                                "ItemDescription" => $servicios[8]->servicio,
                                "Quantity" => $servicios[8]->cantidad,  //50.0,
                                "PriceAfterVAT" => 0,
                                "Currency" => "BS",
                                "WarehouseCode" => "LPZ-ON",
                                "TaxCode" => "IVA",
                                "U_RECETA" => 1,
                                "U_FECHAINI" => $fechaInicio,  //"2024-12-05",
                                "U_FECHAFIN" => substr($fechaFin,0,10), //"2025-12-05",
                            ],
                        ]
                    );

            }

            if($cod_plan == 'PPCE0144' || $cod_plan == 'PPCE0145'){
                    $ordenVenta = array(
                        "DocType" => "dDocument_Items",
                        "DocDate" => $DocDate,  //"2024-12-05",   //----------
                        "DocDueDate" => $DocDate, //"2024-12-05",  //----------
                        "TaxDate" => $DocDate,  //"2024-12-05",   //----------
                        "CardCode" => $CardCode,
                        "CardName" => $CardName,
                        "DocTotal" => $precio_plan,
                        "DocCurrency" => "BS",
                        "Comments" => $contrato,
                        "NumAtCard" => $NumAtCard, //"CRS-PROVSP-240088",
                        "U_NIT" => $U_NIT,
                        "U_RAZSOC" => $CardName,
                        "U_CONSULTORIO" => $U_CONSULTORIO,
                        "U_CANAL" => $canal, //"C003",   //----------
                        "U_ESTADOFC" => "V",
                        "U_COMENTARIOS" => $contrato,   //"PPCE0001-LP-24-0001-0000088|20241205",  //----------
                        "U_NumTransaccionExt" => $NumAtCard,
                        "DocumentLines" => [
                            [
                                "LineNum" => 0,
                                "ItemCode" => $cod_plan, //"PPCE0001",  //----------
                                "ItemDescription" => $servicios[0]->plan,
                                "Quantity" => 1, //$servicios['cantidad'],
                                "PriceAfterVAT" => (float)$servicios[0]->precio,
                                "Currency" => "BS",
                                "WarehouseCode" => "LPZ-ON",
                                "TaxCode" => "IVA",
                                "U_RECETA" => 0,
                                "U_FECHAINI" => $fechaInicio,
                                "U_Contrato" => $contrato,
                                //"U_FECHAFIN" => "2025-12-11",
                                "U_FECHAFIN" => $fechaFin,
                            ],
                            [
                                "LineNum" => 1,
                                "ItemCode" => $servicios[0]->cod_servicio,
                                "ItemDescription" => $servicios[0]->servicio,
                                "Quantity" => (int)$servicios[0]->cantidad,  // 3,
                                "PriceAfterVAT" => 0,
                                "Currency" => "BS",
                                "WarehouseCode" => "LPZ-ON",
                                "TaxCode" => "IVA",
                                "U_RECETA" => 1,
                                "U_FECHAINI" => $fechaInicio,
                                "U_Contrato" => $contrato,
                                //"U_FECHAFIN" => "2025-12-11",
                                "U_FECHAFIN" => $fechaFin,
                            ],
                            [
                                "LineNum" => 2,
                                "ItemCode" => $servicios[1]->cod_servicio,
                                "ItemDescription" => $servicios[1]->servicio,
                                "Quantity" => (int)$servicios[1]->cantidad,  //50.0,
                                "PriceAfterVAT" => 0,
                                "Currency" => "BS",
                                "WarehouseCode" => "LPZ-ON",
                                "TaxCode" => "IVA",
                                "U_RECETA" => 1,
                                "U_FECHAINI" => $fechaInicio,
                                "U_Contrato" => $contrato,
                                //"U_FECHAFIN" => "2025-12-11",
                                "U_FECHAFIN" => $fechaFin,
                            ],
                            [
                                "LineNum" => 3,
                                "ItemCode" => $servicios[2]->cod_servicio,
                                "ItemDescription" => $servicios[2]->servicio,
                                "Quantity" => $servicios[2]->cantidad,  //50.0,
                                "PriceAfterVAT" => 0,
                                "Currency" => "BS",
                                "WarehouseCode" => "LPZ-ON",
                                "TaxCode" => "IVA",
                                "U_RECETA" => 1,
                                "U_FECHAINI" => $fechaInicio,  //"2024-12-05",
                                "U_Contrato" => $contrato,
                                "U_FECHAFIN" => substr($fechaFin,0,10), //"2025-12-05",
                            ],
                            [
                                "LineNum" => 4,
                                "ItemCode" => $servicios[3]->cod_servicio,
                                "ItemDescription" => $servicios[3]->servicio,
                                "Quantity" => $servicios[3]->cantidad,  //50.0,
                                "PriceAfterVAT" => 0,
                                "Currency" => "BS",
                                "WarehouseCode" => "LPZ-ON",
                                "TaxCode" => "IVA",
                                "U_RECETA" => 1,
                                "U_FECHAINI" => $fechaInicio,  //"2024-12-05",
                                "U_FECHAFIN" => substr($fechaFin,0,10), //"2025-12-05",
                            ],
                            [
                                "LineNum" => 5,
                                "ItemCode" => $servicios[4]->cod_servicio,
                                "ItemDescription" => $servicios[4]->servicio,
                                "Quantity" => $servicios[4]->cantidad,  //50.0,
                                "PriceAfterVAT" => 0,
                                "Currency" => "BS",
                                "WarehouseCode" => "LPZ-ON",
                                "TaxCode" => "IVA",
                                "U_RECETA" => 1,
                                "U_FECHAINI" => $fechaInicio,  //"2024-12-05",
                                "U_FECHAFIN" => substr($fechaFin,0,10), //"2025-12-05",
                            ],
                            
                        ]
                    );

            }

            if($cod_plan == 'PPCE0146'){
                    $ordenVenta = array(
                        "DocType" => "dDocument_Items",
                        "DocDate" => $DocDate,  //"2024-12-05",   //----------
                        "DocDueDate" => $DocDate, //"2024-12-05",  //----------
                        "TaxDate" => $DocDate,  //"2024-12-05",   //----------
                        "CardCode" => $CardCode,
                        "CardName" => $CardName,
                        "DocTotal" => $precio_plan,
                        "DocCurrency" => "BS",
                        "Comments" => $contrato,
                        "NumAtCard" => $NumAtCard, //"CRS-PROVSP-240088",
                        "U_NIT" => $U_NIT,
                        "U_RAZSOC" => $CardName,
                        "U_CONSULTORIO" => $U_CONSULTORIO,
                        "U_CANAL" => $canal, //"C003",   //----------
                        "U_ESTADOFC" => "V",
                        "U_COMENTARIOS" => $contrato,   //"PPCE0001-LP-24-0001-0000088|20241205",  //----------
                        "U_NumTransaccionExt" => $NumAtCard,
                        "DocumentLines" => [
                            [
                                "LineNum" => 0,
                                "ItemCode" => $cod_plan, //"PPCE0001",  //----------
                                "ItemDescription" => $servicios[0]->plan,
                                "Quantity" => 1, //$servicios['cantidad'],
                                "PriceAfterVAT" => (float)$servicios[0]->precio,
                                "Currency" => "BS",
                                "WarehouseCode" => "LPZ-ON",
                                "TaxCode" => "IVA",
                                "U_RECETA" => 0,
                                "U_FECHAINI" => $fechaInicio,
                                "U_Contrato" => $contrato,
                                //"U_FECHAFIN" => "2025-12-11",
                                "U_FECHAFIN" => $fechaFin,
                            ],
                            [
                                "LineNum" => 1,
                                "ItemCode" => $servicios[0]->cod_servicio,
                                "ItemDescription" => $servicios[0]->servicio,
                                "Quantity" => (int)$servicios[0]->cantidad,  // 3,
                                "PriceAfterVAT" => 0,
                                "Currency" => "BS",
                                "WarehouseCode" => "LPZ-ON",
                                "TaxCode" => "IVA",
                                "U_RECETA" => 1,
                                "U_FECHAINI" => $fechaInicio,
                                "U_Contrato" => $contrato,
                                //"U_FECHAFIN" => "2025-12-11",
                                "U_FECHAFIN" => $fechaFin,
                            ],
                            [
                                "LineNum" => 2,
                                "ItemCode" => $servicios[1]->cod_servicio,
                                "ItemDescription" => $servicios[1]->servicio,
                                "Quantity" => (int)$servicios[1]->cantidad,  //50.0,
                                "PriceAfterVAT" => 0,
                                "Currency" => "BS",
                                "WarehouseCode" => "LPZ-ON",
                                "TaxCode" => "IVA",
                                "U_RECETA" => 1,
                                "U_FECHAINI" => $fechaInicio,
                                "U_Contrato" => $contrato,
                                //"U_FECHAFIN" => "2025-12-11",
                                "U_FECHAFIN" => $fechaFin,
                            ],
                            [
                                "LineNum" => 3,
                                "ItemCode" => $servicios[2]->cod_servicio,
                                "ItemDescription" => $servicios[2]->servicio,
                                "Quantity" => $servicios[2]->cantidad,  //50.0,
                                "PriceAfterVAT" => 0,
                                "Currency" => "BS",
                                "WarehouseCode" => "LPZ-ON",
                                "TaxCode" => "IVA",
                                "U_RECETA" => 1,
                                "U_FECHAINI" => $fechaInicio,  //"2024-12-05",
                                "U_Contrato" => $contrato,
                                "U_FECHAFIN" => substr($fechaFin,0,10), //"2025-12-05",
                            ],
                            [
                                "LineNum" => 4,
                                "ItemCode" => $servicios[3]->cod_servicio,
                                "ItemDescription" => $servicios[3]->servicio,
                                "Quantity" => $servicios[3]->cantidad,  //50.0,
                                "PriceAfterVAT" => 0,
                                "Currency" => "BS",
                                "WarehouseCode" => "LPZ-ON",
                                "TaxCode" => "IVA",
                                "U_RECETA" => 1,
                                "U_FECHAINI" => $fechaInicio,  //"2024-12-05",
                                "U_FECHAFIN" => substr($fechaFin,0,10), //"2025-12-05",
                            ],
                            [
                                "LineNum" => 5,
                                "ItemCode" => $servicios[4]->cod_servicio,
                                "ItemDescription" => $servicios[4]->servicio,
                                "Quantity" => $servicios[4]->cantidad,  //50.0,
                                "PriceAfterVAT" => 0,
                                "Currency" => "BS",
                                "WarehouseCode" => "LPZ-ON",
                                "TaxCode" => "IVA",
                                "U_RECETA" => 1,
                                "U_FECHAINI" => $fechaInicio,  //"2024-12-05",
                                "U_FECHAFIN" => substr($fechaFin,0,10), //"2025-12-05",
                            ],
                            [
                                "LineNum" => 6,
                                "ItemCode" => $servicios[5]->cod_servicio,
                                "ItemDescription" => $servicios[5]->servicio,
                                "Quantity" => $servicios[5]->cantidad,  //50.0,
                                "PriceAfterVAT" => 0,
                                "Currency" => "BS",
                                "WarehouseCode" => "LPZ-ON",
                                "TaxCode" => "IVA",
                                "U_RECETA" => 1,
                                "U_FECHAINI" => $fechaInicio,  //"2024-12-05",
                                "U_FECHAFIN" => substr($fechaFin,0,10), //"2025-12-05",
                            ],
                            
                        ]
                    );

            }


            //return $ordenVenta;

            // ------------------------------------ //
            // Ahora creamos la ORDEN DE VENTA (OV) //
            // ------------------------------------ //
            $endpoint = "/b1s/v1/Orders";
            $host = "https://52.177.52.183";
            $port = 50000;

            // Construir la URL completa
            $url = "{$host}:{$port}{$endpoint}";

            // Esta es la URL para las Ordenes de Venta //
            $url = "https://52.177.52.183:50000/b1s/v1/Orders";

            // Convertir los datos de autenticación a JSON //
            $ordenJson = json_encode($ordenVenta);


            // Inicializar cURL para el registro de la OV
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $ordenJson);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                "Cookie: B1SESSION={$sessionId}",
                'Content-Length: ' . strlen($ordenJson)
            ));

            // Ejecutar la solicitud de alta del cliente //
            $response = curl_exec($ch);

            //echo "JSON ENVIADO:\n" . $ordenJson . "\n";


            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (curl_errno($ch)) {
                    echo 'Error cURL: ' . curl_error($ch);
                    
            } else {
                    // Nos MUESTRA QUE RESPONDE EL SAP AL INSERTAR LA OV - BUENA RESPUESTA
                    //echo "Código HTTP: $httpCode\n";
                    //echo "Respuesta de SAP:\n$response\n";
            }


            //echo "ahora dump";
            //var_dump($response);

            //return $response;


            // ----------------------------------------------- //
            // 4. Obtenemos el DocEntry de la OV recien creada //
            // ----------------------------------------------- //
            //$NumAtCard = $dataBeneficiario->NumAtCard;
            $NumAtCard = $dataBeneficiario['NumAtCard'];

            $endpoint = "/b1s/v1/Orders?\$filter=NumAtCard%20eq%20'" . $NumAtCard . "'&\$select=DocNum,DocEntry,U_NumTransaccionExt,Comments";

            // Construir la URL completa
            $url = "{$host}:{$port}{$endpoint}";

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                "Cookie: B1SESSION={$sessionId}"
            ));

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                echo 'Error al obtener DocEntry de la OV:' . curl_error($ch);
                exit;
            }

            // Decodificar la respuesta JSON del DocEntry de la OV
            $respuestas = json_decode($response, true);
            $dataDocEntry = $respuestas['value'];

            echo "DOC ENTRY OV: " . $dataDocEntry[0]['DocEntry'] ."\n";
            //var_dump($dataDocEntry);
            //return $dataDocEntry;

            // ------------------------------------------------------- //
            // 5. Insertamos un registro en tabla Cabecera Certificado //
            // ------------------------------------------------------- //
            // Esto usamos información obtenida en el paso anterior    //
            // ------------------------------------------------------- //
            $U_DOC_NUM = $dataDocEntry[0]['DocNum'];
            $U_DocEntry = $dataDocEntry[0]['DocEntry'];
            $U_NumAtCard = $NumAtCard;
            $U_Canal = $dataBeneficiario['canal'];

            $dataCabCertificado = array(

                "U_DOC_NUM" => $U_DOC_NUM,  //"240000004", // No Documento OV
                "U_NumAtCard" => $U_NumAtCard, //"CRS-VGSIBC-0002493",   //--- NumAtCard OV
                "U_DocEntry" => $U_DocEntry, //45, /// --- DocEntry OV
                "U_Canal" => $U_Canal, //"C003",    //--- Canal
                "U_Tipo" => null,
                "U_FormaComercializacion" => "WS",
                "U_DocType" => "17" //--- Tipo Documento OV 17

            );

            // Convertir la información en formato JSON //
            $cabCertificadoJson = json_encode($dataCabCertificado);

            // Construir la URL completa
            $endpoint = "/b1s/v1/U_CAB_CERTIFICADO";
            $url = "{$host}:{$port}{$endpoint}";


            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Usar solo en desarrollo, NO en producción
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Usar solo en desarrollo, NO en producción
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $cabCertificadoJson);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                "Cookie: B1SESSION={$sessionId}",
                'Content-Length: ' . strlen($cabCertificadoJson)
            ));

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                echo 'Error al insertar CAB CERTIFICADO: ' . curl_error($ch);
                exit;
            }

            //return $response;

            // -------------------------------------------------- //
            // 6. Obtenemos el CODE de tabla Cabecera Certificado //
            // -------------------------------------------------- //
            $U_DocEntry = $dataDocEntry[0]['DocEntry'];

            // Construir la URL completa
            $endpoint = "/b1s/v1/U_CAB_CERTIFICADO?\$filter=U_DocEntry%20eq%20'" . $U_DocEntry . "'%20and%20U_DocType%20eq%20'17'";

            $url = "{$host}:{$port}{$endpoint}";

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                "Cookie: B1SESSION={$sessionId}"
            ));

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                echo 'Error al obtener CODE de CAB CERTIFICADO: ' . curl_error($ch);
                exit;
            }

            $respuesta = json_decode($response, true);
            $dataCode = $respuesta['value'];

            echo "CODE CAB CERTIFICADO: " . $dataCode[0]['Code'] . "\n";
            //var_dump($dataDocEntry);
            //return $dataCode;


            // ---------------------------------------------- //
            // 7. Insertamos el registro en Linea Certificado //
            // ---------------------------------------------- //
            $U_Certificado = $dataCode[0]['Code'];
            $U_CardName    = $dataBeneficiario['CardName'];
            $U_CardCode    = $dataBeneficiario['CardCode'];

            $U_VatIdUnCmp  = $dataBeneficiario['Cedula'];
            $U_Contrato    = $contrato;
            $U_ItemCode    = $dataBeneficiario['cod_plan'];

            $dataLin = array(
                "U_CardCode" => $U_CardCode,  // "720803-LYMA",
                "U_CardName" => $U_CardName, //"MARCO ANTONIO LAZARTE YAPU",
                "U_VatIdUnCmp" => $U_VatIdUnCmp, //"2305905",
                "U_Certificado" => $U_Certificado, //"179460",
                "U_Contrato" => $U_Contrato, //"PPCE0001-LP-24-0001-0000001",
                "U_LINEA" => "0",
                "U_ItemCode" => $U_ItemCode, //"PPCE0001",
                "U_FormaComercializacion" => "COLECTIVO"
            );

            $dataLinJson = json_encode($dataLin);
            //var_dump($dataLinJson);

            //return $dataLinJson;

            // Construir la URL completa
            $endpoint = "/b1s/v1/U_LIN_CERTIFICADO";

            $url = "{$host}:{$port}{$endpoint}";

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Usar solo en desarrollo, NO en producción
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Usar solo en desarrollo, NO en producción
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataLinJson);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                "Cookie: B1SESSION={$sessionId}",
                'Content-Length: ' . strlen($dataLinJson)
            ));

            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                echo 'Error al insertar LIN CERTIFICADO: ' . curl_error($ch);
                exit;
            }

            //return $response;

            // -------------------------------------------------- //
            // 8. Obtenemos el Data Code de tabla LIN CERTIFICADO //
            // -------------------------------------------------- //

            $DocEntry = $dataDocEntry[0]['DocEntry'];
            $ItemCode = $dataBeneficiario['cod_plan'];

            $endpoint = "/b1s/v1/\$crossjoin(U_CAB_CERTIFICADO,U_LIN_CERTIFICADO)?\$expand=U_CAB_CERTIFICADO(\$select=Code,U_DOC_NUM,U_DocEntry,U_DocType),U_LIN_CERTIFICADO(\$select=Code,U_Certificado,U_Contrato,U_ItemCode)&\$filter=U_CAB_CERTIFICADO/Code%20eq%20U_LIN_CERTIFICADO/U_Certificado%20and%20U_CAB_CERTIFICADO/U_DocEntry%20eq%20'". $DocEntry . "'%20and%20U_LIN_CERTIFICADO/U_ItemCode%20eq%20'". $ItemCode . "'";

            $url = "{$host}:{$port}{$endpoint}";

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                "Cookie: B1SESSION={$sessionId}"
            ));


            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                echo 'Error al obtener data code de LIN CERTIFICADO: ' . curl_error($ch);
                exit;
            }

            // Decodificar la respuesta JSON del Beneficiario
            $llave_lineas = json_decode($response, true);
            $llave_linea = $llave_lineas['value'];
            $llave_linea_certificado = $llave_linea[0]['U_LIN_CERTIFICADO']['Code'];

            //return $llave_linea_certificado;

            // -------------------------------------------------------------- //
            // 9. Insertamos Titular y Beneficiarios en Cabecera Bendficiario //
            // -------------------------------------------------------------- //
            $DocEntry = $dataDocEntry[0]['DocEntry'];
            $CardCode = $dataBeneficiario['CardCode'];

            $titData = array (
                    "U_DocEntry" => $DocEntry,
                    "U_CardCode" => $CardCode,
                    "U_Tipo" => 0, // 0: titular
                    "U_Linea" => $llave_linea_certificado,
                    "Name" => 1
                );

            // Construir la URL completa
            $endpoint = "/b1s/v1/U_CAB_BENEFICIARIO";
            $url = "{$host}:{$port}{$endpoint}";

            // Convertir los datos de autenticación a JSON
            $titularJson = json_encode($titData);

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Usar solo en desarrollo, NO en producción
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Usar solo en desarrollo, NO en producción
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $titularJson);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                "Cookie: B1SESSION={$sessionId}",
                'Content-Length: ' . strlen($titularJson)
            ));

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                echo 'Error al guardar Titular en tabla Cabecera Beneficiario: ' . curl_error($ch);
                exit;
            }

            echo "OV creada para numPrestamo: " . $numPrestamo . "\n\n";

       
        }

        $ret['estado'] = 'E';
        //$ret['id'] = $id_registro;

        return $ret;

    } 

*/


function readCJNData()
{

	// $sql = "SELECT * FROM jnmasivos WHERE procesado = 'NO' AND estado = 'success'";
	$sql = "SELECT * FROM jnmasivos WHERE id = 605";

	
	$rows = ejecutarConsulta($sql);
	
	while($row = mysqli_fetch_assoc($rows))
	{
		
		$data[] = $row;
	}
	
	return $data;
	
}


function getBenefsCred($cc)
{
        //return $cc;
        //$aa[] = $cc[0]['certificado'];
        $canal       = $cc['codCanal'];
        $plan        = $cc['codPlanElegido'];
        $certificado = $cc['numOperacion'];
        $numTransaccion = $cc['numTransaccion'];
        $fechaInicio = $cc['created_at'];
        $contrato    = ""; //$cc['contrato'];
        //$taxDate     = $cc['taxDate'];
        $codAgencia  = $cc['codAgencia'];

        // echo "DENTRO DE GET BENEFICIARIOS";
        // echo "CERTIFICADO: " . $certificado;
        // echo "NUM TRAN: " . $numTransaccion;

		date_default_timezone_set('America/La_Paz');
        $fecha_proc = date('Y-m-d');

        // OBTENEMOS TITULAR Y BENEFICIARIOS //
		$sql = "SELECT * FROM cred_beneficiarios
				WHERE numTransaccion = '$numTransaccion'
				AND certificado = '$certificado'";
				
		$rows = ejecutarConsulta($sql);
	
		while($row = mysqli_fetch_assoc($rows))
		{
			
			$benefs[] = $row;
		}

        //return $benefs;

        $data = (object) [
                'codCanal' => $canal,
                'codPlanElegido' => $plan,
                'certificado' => $certificado,
                'numTransaccion' => $numTransaccion,
                'fechaInicio' => $fechaInicio,
                'contrato' => $contrato,
            //    'taxDate' => $taxDate,
                'codAgencia' => $codAgencia,
                'beneficiarios' => []
        ];

//        foreach($benefs as $benef)
//        {
			$doc = substr($cc['numDocClean'],0,1);
			if($doc === '0')
			{
				$numDoc = substr($cc['numDocClean'],1,15);
			}else{
				$numDoc = $cc['numDocClean'];
			}
			
            $bb = array(
                    'numTransaccion' => $numTransaccion,
                    'certificado' => $certificado,
                //    'tipoBen' => $cc['tipoBen'],
                    'tipoDocBen' => $cc['tipoDoc'],
                    'numDocBen' => $numDoc,
                    'extensionBen' => $cc['extension'],
                    'expedidoBen' => $cc['expedicion'],
                    'pApellidoBen' => $cc['pApellido'],
                    'sApellidoBen' => $cc['sApellido'],
                    'pNombreBen' => $cc['pNombre'],
                    'sNombreBen' => $cc['sNombre'],
                    'generoBen' => $cc['genero'],
                    'telefonoBen' => $cc['telefono'],
                    'fechaNacimientoBen' => $cc['fechaNacimiento'],
                    'eMailBen' => $cc['eMail'],
                    'ciudadBen' => $cc['ciudad'],
                    'paisBen' => $cc['pais'],
                //    'parentescoBen' => $cc['parentescoBen'],
                //    'docIdentidadTitular' => $cc['docIdentidadTitular']
            );
            $data->beneficiarios[] = $bb;
            

            // MARCAMOS EN LA TABLA cred_original COMO PROCESADOS //
			/*
            try {
                DB::table('cred_original')
                    ->where('numTransaccion', $numTransaccion)
                    ->where('certificado', $certificado)
                    ->update([
                        'procesado' => 'SI',
                        'updated_at' => $fecha_proc
                    ]);
    
            } catch (Exception $e) {
    
                return response()->json([
                    'estado' => 'X',
                    'mensaje' => 'Registro NO encontrado!!',
                    'numTransaccion' => $numTransaccion,
                    'certificado' => $certificado,
                ], 404);
            }
			*/

  //      }

        return $data;

}

function formatDataBenef($request)
{
        
		//return $request;
        $NumAtCard = $request->certificado;
        $num_tra   = $request->numTransaccion;
        $plan      = $request->codPlanElegido;
        $canal     = $request->codCanal;
        $fechaInicio = $request->fechaInicio;
    //    $contrato  = $request->contrato;
        //$taxDate   = $request->taxDate;
        $codAgencia= $request->codAgencia;

        $benefs    = $request->beneficiarios;

        //return $benefs;

        $dataBeneficiarios = [];

        foreach($benefs as $benef)
        {
            if(strlen($benef['pNombreBen'])){
                $PriNombre = $benef['pNombreBen'] . " ";
                $PriNomb   = substr($benef['pNombreBen'],0,1);
            }else{
                    $PriNombre = '';
                    $PriNomb   = '';
            }
            
            if(strlen($benef['sNombreBen'])){
                    $SecNombre = $benef['sNombreBen'] . " ";
                    $SecNomb   =  substr($benef['sNombreBen'],0,1);
            }else{
                    $SecNombre = '';
                    $SecNomb   = '';
            }
                
            if(strlen($benef['pApellidoBen'])){
                    $ApPaterno = $benef['pApellidoBen'] . " ";
                    $ApPat     = substr($benef['pApellidoBen'],0,1);
            }else{
                    $ApPaterno = '';
                    $ApPat     = '';
            }
                
            if(strlen($benef['sApellidoBen'])){
                    $ApMaterno = $benef['sApellidoBen'];
                    $ApMat     = substr($benef['sApellidoBen'],0,1);
            }else{
                    $ApMaterno = '';
                    $ApMat     = '';
            }

            $CardCode = substr($benef['fechaNacimientoBen'],2,2).substr($benef['fechaNacimientoBen'],5,2).substr($benef['fechaNacimientoBen'],8,2). "-" .$ApPat.$ApMat.$PriNomb.$SecNomb;
            $CardName   = $PriNombre . $SecNombre . $ApPaterno . $ApMaterno;


            switch($benef['tipoDocBen']){
                case 'C';
                    $U_TIPDOC = 'CI';
                    $U_DOCTYPE = 'Carnet de Identidad';
                break;

                case 'O':
                    $U_TIPDOC = 'OTRO';
                    $U_DOCTYPE = 'OTRO';
                break;

                case 'E':
                    $U_TIPDOC = 'CEX';
                    $U_DOCTYPE = 'Carnet de Extranjero';
                break;

                case 'P':
                    $U_TIPDOC = 'PAS';
                    $U_DOCTYPE = 'Pasaporte';
                break;

            }

            $bb = (object) [
        //        'tipoBen'   => $benef['tipoBen'],
                'CardCode'  => $CardCode,
                'PriNombre' => $benef['pNombreBen'],
                'SecNombre' => $benef['sNombreBen'],
                'ApPaterno' => $benef['pApellidoBen'],
                'ApMaterno' => $benef['sApellidoBen'],
                'CardName'  => $CardName,
                'CardType'  => $benef['tipoDocBen'],
                'Cellular'  => $benef['telefonoBen'],
                'EmailAddr' => $benef['eMailBen'],
                'Cedula'    => $benef['numDocBen'],
                'Extension' => $benef['extensionBen'],
                'Expedicion'=> $benef['expedidoBen'],
                'FechaNac'  => $benef['fechaNacimientoBen'],
                'Genero'    => $benef['generoBen'],
                'Currency'  => "Bs",
                'GroupCode' => "100",
                'U_TIPDOC'  => $U_TIPDOC,
                'U_DOCTYPE' => $U_DOCTYPE,
                'U_FechaNac' => $benef['fechaNacimientoBen'],
                'NumAtCard'  => $NumAtCard,
                'contrato' => "", //$contrato,
                'cod_plan'   => $plan,
                'canal'      => $canal,
                'codAgencia' => $codAgencia,
                'numTransaccion' => $num_tra,
                'fechaInicio'=> $fechaInicio,
                'telefono'   => $benef['telefonoBen'],
        //        'taxDate'    => $taxDate
            ];

            $dataBeneficiarios[] = $bb;
        }

        return $dataBeneficiarios;

}

function formatDataBenefXXXX($data)
{
        date_default_timezone_set('America/La_Paz');
        $fechaInicio = date('Y-m-d');

        $NumAtCard = $data['numPrestamo'];
        $plan      = $data['codigo_plan_hijo'];
        $canal     = $data['codigo_canal'];
        $numPrestamo = $data['numPrestamo'];
        $num_id      = $data['id'];

        // Nombres
        $PriNombre = $data['nombre1'] ? $data['nombre1'] . " " : '';
        $PriNomb   = $data['nombre1'] ? substr($data['nombre1'], 0, 1) : '';

        $SecNombre = $data['nombre2'] ? $data['nombre2'] . " " : '';
        $SecNomb   = $data['nombre2'] ? substr($data['nombre2'], 0, 1) : '';

        $ApPaterno = $data['ap_paterno'] ? $data['ap_paterno'] . " " : '';
        $ApPat     = $data['ap_paterno'] ? substr($data['ap_paterno'], 0, 1) : '';

        $ApMaterno = $data['ap_materno'] ?? '';
        $ApMat     = $data['ap_materno'] ? substr($data['ap_materno'], 0, 1) : '';

        		
		$CardCode = substr($data['fecha_nacimiento'], 2, 2) . substr($data['fecha_nacimiento'], 5, 2) . substr($data['fecha_nacimiento'], 8, 2) . "-" . $ApPat . $ApMat . $PriNomb . $SecNomb;
		$CardName = $PriNombre . $SecNombre . $ApPaterno . $ApMaterno;


        switch ($data['tipo_documento']) {
            case 'C':
                $U_TIPDOC = 'CI';
                $U_DOCTYPE = 'Carnet de Identidad';
                break;
            case 'O':
                $U_TIPDOC = 'OTRO';
                $U_DOCTYPE = 'OTRO';
                break;
            case 'E':
                $U_TIPDOC = 'CEX';
                $U_DOCTYPE = 'Carnet de Extranjero';
                break;
            case 'P':
                $U_TIPDOC = 'PAS';
                $U_DOCTYPE = 'Pasaporte';
                break;
            default:
                $U_TIPDOC = '';
                $U_DOCTYPE = '';
        }

        date_default_timezone_set('America/La_Paz');
		$fecha_proc=date('Y-m-d H:i:s');
        
		
/*		
	try {
		$sql = "UPDATE temps_vit SET procesado = 'SI' WHERE numPrestamo = '$numPrestamo'";
		$res = ejecutarConsulta($sql);
	} catch (Exception $e) {
		return [
			'estado' => 'X',
			'mensaje' => 'Registro NO encontrado!!',
			'numPrestamo' => $numPrestamo,
		];
	}
*/        
	
	return [
		'CardCode'        => $CardCode,
		'PriNombre'         => $data['nombre1'],
		'SecNombre'         => $data['nombre2'],
		'ApPaterno'         => $data['ap_paterno'],
		'ApMaterno'         => $data['ap_materno'],
		'CardName'          => $CardName,
		'CardType'          => $data['tipo_documento'],
		'Cellular'          => $data['telefono'],
		'EmailAddr'         => $data['correo'],
		'Cedula'            => $data['num_documento'],
		'Extension'         => $data['extension'],
		'Expedicion'        => $data['expedido'],
		'FechaNac'          => $data['fecha_nacimiento'],
		'Genero'            => $data['genero'],
		'Currency'          => "Bs",
		'GroupCode'         => "100",
		'U_TIPDOC'          => $U_TIPDOC,
		'U_DOCTYPE'         => $U_DOCTYPE,
		'U_CONSULTORIO'     => $data['agencia_venta'],
		'U_FechaNac'        => $data['fecha_nacimiento'],
		'NumAtCard'         => $NumAtCard,
		'cod_plan'          => $plan,
		'canal'             => $canal,
		'numTransaccion'    => $data['codigo_tra'],
		'fechaInicio'       => $fechaInicio,
		'telefono'          => $data['telefono'],
		'num_ope'           => $data['codigo_ope'],
	];


}


function obtenemosServicios($canal,$cod_plan)
{
	$sql =  "SELECT * FROM planes_ov 
				WHERE canal = '$canal' 
				AND cod_plan = '$cod_plan'
				AND estado = 'A'";
	
	$data = [];
	$rows = ejecutarConsulta($sql);
	while($row = mysqli_fetch_assoc($rows))
	{
		$data[] = $row;
	}
		
	return $data;

}

function generaNumContrato($canal, $cod_plan)
{

        echo "CANAL: " . $canal . "<br>";
        echo "PLAN: " . $cod_plan . "<br>";
		
		$sql = "SELECT valor_actual, contrato 
					FROM contratos 
					WHERE contrato_sm = '$cod_plan' 
					AND id_canal = '$canal'";
		
		$data = ejecutarConsultaSimpleFila($sql);
		
		// var_dump($data);
        //$rowcount = count($data);
        //echo "CONTRATO ROW COUNT: " . $rowcount . "<br>";
		// die();
        if($data)
        {
            // echo "ACTUALIZANDO: " . $data['valor_actual'] . "\n";
            $valor = (int) $data['valor_actual'];
            // echo "VAL ANT: " . $valor . "<br>";
            $valor_actual = ++$valor;
            // echo "VAL NEW: " . $valor_actual . "<br>";
            $valor_actual_str = str_pad($valor_actual, 7, "0", STR_PAD_LEFT);

            $contrato = $data['contrato'] . "-" . $valor_actual_str;

			// echo "NEW CONTRATO: " . $contrato . "<br>";
			// die();

            // Ahora actualizamos la tabla CONTRATO al nuevo valor //
			$sql = "UPDATE contratos SET valor_actual = '$valor_actual'
						WHERE contrato_sm = '$cod_plan'";
			
			$affectedRows = ejecutarConsulta($sql);
			
            $ans['estado'] = 'E';
            $ans['contrato'] = $contrato;
			
        }else{
            $ans['estado'] = 'X';
            $ans['mensaje'] = "Error al buscar PLAN en tabla CONTRATOS";
        }

        return $ans;

}


function obtenemosDatosCanal($canal)
{

	$sql = "SELECT * FROM canal WHERE id_canal = '$canal'";
		
	return ejecutarConsultaSimpleFila($sql);

}

function actualizaContratoEnTemp($cod_ope,$cod_tra,$contrato)
{
    $sql = "UPDATE temps_jn SET contrato = '$contrato' WHERE codigo_ope = '$cod_ope' AND codigo_tra = '$cod_tra'";

    return ejecutarConsulta($sql);

}


function dep($data){
	$format = print_r('<pre>');
	$format .= print_r($data);
	$format .= print_r('</pre>');

	return $format;
}