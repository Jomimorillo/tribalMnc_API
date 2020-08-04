<?php
require_once '../libs/unirest-php/src/Unirest.php';

// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, X-Requested-With");

$metodo = $_SERVER['REQUEST_METHOD'];

if($metodo != "POST"){
    http_response_code(405);
    echo json_encode(array(
        "code" => 405,
        "message" => "Método no permitido ($metodo) - solo 'POST' ",
    ));
    exit();
}
if (!isset($_POST['search']) || (trim($_POST['search']) == false)){
    //Si no existe critério de búsqueda se devuelve error 400
    http_response_code(400);
    echo json_encode(array(
        "code" => 400,
        "message" => "Criterio de búsqueda vacío."
    ));
    exit();
}else{
    $resultados = [];
    $criteria = trim($_POST['search']);
    //Resultados de WebService: Crcind
    $cn = searchCrcind($criteria);
    //Resultados de API: iTunes
    $iTunes = searchITunes($criteria);
    //Resultados de API: TvMaze
    $tvMaze = searchTvMaze($criteria);

    //Verifica si existen registros
    if ($iTunes['totalCount'] > 0 || $tvMaze['totalCount'] > 0 || $cn['totalCount'] > 0){
        //Array utilizado para mezclar resultados
        $resultArray = [];
        //Variable utilizada como contador de resultados
        $totalCount = 0;

        //Se mezclan resultados de diferentes APIS
        if ($iTunes['status'] != 400 && $iTunes['status'] != 500 ){
            $resultArray = array_merge($resultArray,$iTunes['registros'] );
            $totalCount = $totalCount + $iTunes['totalCount'];
        }
        if ($tvMaze['status'] != 400 && $tvMaze['status'] != 500 ){
            $resultArray = array_merge($resultArray,$tvMaze['registros'] );
            $totalCount = $totalCount + $tvMaze['totalCount'];
        }
        if ($cn['status'] != 400 && $cn['status'] != 500 ){
            $resultArray = array_merge($resultArray,$cn['registros'] );
            $totalCount = $totalCount + $cn['totalCount'];
        }

        //Se ordenan los resultados alfabéticamente por nombre de manera Ascendiente
        array_multisort (array_column($resultArray, 'nombre'), SORT_ASC, $resultArray);

        http_response_code(200);
        echo json_encode(array(
         "code" => 200,
         "message" => "Búsqueda Éxitosa",
         "data" => $resultArray,
         "totalCount" => $totalCount
        ));


        //Si no existen registros
    }else if ($iTunes['totalCount'] == 0 && $tvMaze['totalCount'] == 0 && $cn['totalCount'] == 0){
        echo json_encode(array(
         "code" => 204,
         "message" => "Registros no Encontrados",
         "data" => $resultados,
         "totalCount" => 0
        ));
        //En caso de algún error desconocido
    }else{
        http_response_code(500);
        echo json_encode(array(
            "code" => 500,
            "message" => "Ha ocurrido un error inesperado"
            ));
    }
    
}

function searchTvMaze($criteria){
    //URL a consultar
    $url = "http://api.tvmaze.com/search/shows?q=$criteria";
    try{
        $response = Unirest\Request::get($url, null, null);
        $respuesta = [
            "status"  => 200,
            "registros" => [],
            "totalCount" => 0
        ];
        
        $estructura = [];
        //Si el response es exitoso
        if ($response->code == 200){
            //En caso de que el Body del response retorne vacío
            if (!$response->raw_body){
                $respuesta["status"] = 400;
                return $respuesta;
            }

            //Decoding de resultados
            $resultados = json_decode($response->raw_body);

            //Verifica que existan resultados
            if ( isset($resultados) && (count($resultados) > 0)){
                foreach ($resultados as $key => $resultado){
                    $estructura[$key]['nombre'] = $resultado->show->name;
                    $estructura[$key]['url'] = $resultado->show->url;
                    $estructura[$key]['tipo'] = "TV Show";
                    $estructura[$key]['fuente'] = "TVMaze";
                }
                
                $respuesta['registros'] = $estructura;
                $respuesta['totalCount'] = count($resultados);
            //Si no existen resultados
            }else if ( (count($resultados) == 0) || !isset($resultados)){
                $respuesta["status"] = 204;
            //Si existe algún error inesperado
            }else{
                $respuesta["status"] = 500;
            }

            return $respuesta;

        //Si el response falla
        }else{
            $respuesta["status"] = 400;
            return $respuesta;
        }
    }catch (Throwable $t)
    {
        $respuesta["status"] = 500;
        return $respuesta;
    }
    
}

function searchITunes($criteria){
    //URL Encoding del criterio de busqueda (requerido por el API de iTunes).
    $criterio = urlencode($criteria); 

    //URL a consultar
    $url = "https://itunes.apple.com/search?term=$criterio&limit=200";
    try{
        $response = Unirest\Request::get($url, null, null);
        $respuesta = [
            "status"  => 200,
            "registros" => [],
            "totalCount" => 0
        ];
        $estructura = []; 
        //Si el response es exitoso
        if ($response->code == 200){
            //En caso de que el Body del response retorne vacío
            if (!$response->raw_body){
                $respuesta["status"] = 400;
                return $respuesta;
            }

            $countResultados = 0;
            //Decoding de resultados
            $resultados = json_decode($response->raw_body);
            //Verifica que existan resultados
            if ( isset($resultados->results) && ($resultados->resultCount > 0)){
                foreach ($resultados->results as $resultado){
                    //Solo los resultados de tipo "peliculas","canciones" y "eBooks" poseen parámetro "kind" | Verifico que exista 
                    if(isset($resultado->kind)){

                        //Verifico que el critério de búsqueda se adapte al nombre del artista o nombre/título del elemento | iTunes utiliza el critério de búsqueda en información no mostrada
                        //Por lo que mostraba resultados no congruentes.
                        if( (strpos(strtoupper($resultado->artistName), strtoupper($criteria)) !== false) || (strpos(strtoupper($resultado->trackName), strtoupper($criteria)) !== false)){
                            if ($resultado->kind == "song"){
                                $estructura[$countResultados]['nombre'] = $resultado->artistName. " - " .$resultado->trackName;
                                $estructura[$countResultados]['url'] = $resultado->trackViewUrl;
                                $estructura[$countResultados]['tipo'] = "Cancion";
                                $estructura[$countResultados]['fuente'] = "iTunes";
                                $countResultados++;
                            }elseif($resultado->kind == "feature-movie"){
                                $estructura[$countResultados]['nombre'] = $resultado->artistName. " - " .$resultado->trackName;
                                $estructura[$countResultados]['url'] = $resultado->trackViewUrl;
                                $estructura[$countResultados]['tipo'] = "Pelicula";
                                $estructura[$countResultados]['fuente'] = "iTunes";
                                $countResultados++;
                            }elseif($resultado->kind == "ebook"){
                                $estructura[$countResultados]['nombre'] = $resultado->artistName. " - " .$resultado->trackName;
                                $estructura[$countResultados]['url'] = $resultado->trackViewUrl;
                                $estructura[$countResultados]['tipo'] = "Libro";
                                $estructura[$countResultados]['fuente'] = "iTunes";
                                $countResultados++;
                            }
                        } 
                        
                    }
                }
                
                $respuesta['registros'] = $estructura;
                $respuesta['totalCount'] = $countResultados;
                //Si no existen resultados
            }else if ( (count($resultados->results) == 0) || !isset($resultados->results)){
                $respuesta["status"] = 204;
                //Si existe algún error inesperado
            }else{
                $respuesta["status"] = 500;
            }

            return $respuesta;
            //Si el response falla
        }else{
            $respuesta["status"] = 400;
            return $respuesta;
        }
    }catch (Throwable $t)
    {
        $respuesta["status"] = 500;
        return $respuesta;
    }
    
}

function searchCrcind($criteria){
    $soapClient = new SoapClient("http://www.crcind.com/csp/samples/SOAP.Demo.CLS?wsdl");
    
    $respuesta = [
        "status"  => 200,
        "registros" => [],
        "totalCount" => 0
    ];
    
    $estructura = [];

    $param = array(
        'name'     =>    $criteria);
    try {
        $soapResult = $soapClient->__soapCall("GetListByName", array($param));

        if (isset($soapResult) && isset($soapResult->GetListByNameResult) && isset($soapResult->GetListByNameResult->PersonIdentification)){
            $resultados = $soapResult->GetListByNameResult->PersonIdentification;
            //Verifica que existan resultados
            if ( isset($resultados) && (count($resultados) > 0)){
                foreach ($resultados as $key => $resultado){
                    $estructura[$key]['nombre'] = $resultado->Name;
                    $estructura[$key]['url'] = "";
                    $estructura[$key]['tipo'] = "Persona";
                    $estructura[$key]['fuente'] = "Crcind";
                }
                
                $respuesta['registros'] = $estructura;
                $respuesta['totalCount'] = count($resultados);
            //Si no existen resultados
            }else if ( (count($resultados) == 0) || !isset($resultados)){
                $respuesta["status"] = 204;
            //Si existe algún error inesperado
            }else{
                $respuesta["status"] = 400;
            }

            return $respuesta;

        }else{
            $respuesta["status"] = 204;
            return $respuesta;
        }
    } catch (SoapFault $fault) {
        $respuesta["status"] = 500;
        return $respuesta;
    }
}

?>