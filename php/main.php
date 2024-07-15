<?php
class API {
    const RESPONSE_JSON = 'json';
    const RESPONSE_TEXT = 'text';
    const RESPONSE_BINARY = 'binary';

    private $baseUrl;
    private $apikey;

    public function __construct($baseUrl, $apikey){
        $this->baseUrl = $baseUrl;
        $this->apikey = $apikey;
    }

    public function fetch($location, $method, $data = null, $responseType = self::RESPONSE_JSON){
        $url = $this->baseUrl . $location;

        $curl = curl_init();

        switch ($method){
            case "POST":
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
                break;

            case "GET": 
                if($data) $url .= "?".http_build_query($data);
                break;

            case "PUT":
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
                break;
        }

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => [
                'api_key: ' . $this->apikey,
                'Content-Type: application/json',
                ($responseType === self::RESPONSE_BINARY) ? 'Accept: application/octet-stream' : ''
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0
        ]);

        $result = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($result === FALSE) {
            $error = curl_error($curl);
            curl_close($curl);
            return json_encode(["error" => "Error en la solicitud: $error"]);
        }

        curl_close($curl);

        if ($httpCode >= 200 && $httpCode < 300) {
            return $result;
        } else {
            return json_encode(["error" => "Error en la solicitud: Código de estado HTTP $httpCode", "response" => json_decode($result)], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
    }
}

function getCurrentDateTimeISO() {
    date_default_timezone_set('America/La_Paz'); // Establece la zona horaria de Bolivia

    $datetime = new DateTime();
    $milliseconds = sprintf('%03d', round(microtime(true) * 1000) % 1000);

    return $datetime->format('Y-m-d\TH:i:s.') . $milliseconds;
}

$currentDate = getCurrentDateTimeISO();

echo "Fecha de emision: " . $currentDate." Hora Bolivia\n";

// Ejemplo de uso
$baseUrl = 'https://factugest-api.iathings.com/api/v1';
$apikey = 'da0545637bc3905a1fd0abcc239a1bd21e0e38e94435fdd3d78cd2019669f0142c868cd375135f1d28be8ee303eda528e9601189e6929be4fff911cd451b9046d2ad70210ce1f8d8f24af69124e6f287448d5af445fac50c1d0886aaadffd14eb4ddaabad6bdc991ed97fdb5ad8ffe09966fa296b34f9c18df9c063fe12e2810cefe102cb48b6b0fb258bc5f2e50278a7a44595b3a2dee2b49d74b1adcfab75c6a09bb4fff0e7a3aad8bdd2299f6f4c3b5c813e6ada7e7f551a4b996bb2b9e89a42e9f47f8a901d77e0b936075ba6484c86549c6a46c8863cfbef8eebe58300759dd5e16b0141851fdb4406d9840bc6ce93a031a494f7a314182fa7cc0bddab123af35d886547231a94123fd754e58d0';

$api = new API($baseUrl, $apikey);

$jsonForParams = '{
    "codigoPuntoVenta": 0,
    "codigoSucursal": 0,
    "codigoAmbiente": 2,
    "codigoModalidad": 1,
    "nit": "3136291018"
}';

// Ejemplo de solicitud parametricas => esto deberia sincronizarse almenos una vez al dia
$parametricas = $api->fetch('/invoice-utils/parametricas', 'POST', json_decode($jsonForParams), API::RESPONSE_JSON);
// escribir en archivo llamado parametricas.json
if (isset(json_decode($parametricas, true)['error'])) {
    echo $parametricas;
    exit;
} 

file_put_contents('parametricas.json', json_encode(json_decode($parametricas), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo "Sincronizacion exitosa\n";

// Ejemplo de facturacion

$jsonForInvoice = [
    "solicitud" => [
        "codigoModalidad" => 2, // 1 = Electronica, 2 = Computarizada
        "codigoEmision" => 1, // 1 = En linea, 2 = Fuera de linea, 3 = Masivo
        "codigoDocumentoSector" => 1, // 1 = Factura compra-venta, .... consultar documentacion
        "codigoSucursal" => 0,
        "codigoAmbiente" => 2, // 1 = Produccion, 2 = Pruebas
        "codigoPuntoVenta" => 0,
        "codigoActividad" => 471110, // Cambiar de acuerdo al nit
        "nitEmisor" => "3136291018",
        "codigoTipoEvento" => 0,
        // "leyenda" => "string", Generado automaticamente por la api
        "numeroDocumento" => "7474483", // CI / NIT/ PASAPORTE
        "codigoTipoDocumento" => 1,
        // "complementoDocumento" => "",
        "razonSocial" => "JOSE MANUEL GARCIA",
        "correoCliente" => "linuxer41@gmail.com",
        "formatoPdf" => 1 // 1 = rollo, 2 = documento
    ],
    "cabecera" => [
        "nitEmisor" => 3136291018,
        "razonSocialEmisor" => "Juan Perez",
        "municipio" => "Sucre",
        "telefono" => "04241234567",
        "numeroFactura" => 10,
        // "cuf" => "",  Generado automaticamente por la api
        // "cufd" => "",  Generado automaticamente por la api
        "codigoSucursal" => 0,
        "direccion" => "Calle 1",
        "codigoPuntoVenta" => 0,
        "fechaEmision" => $currentDate,
        "nombreRazonSocial" => "JOSE MANUEL GARCIA",
        "codigoTipoDocumentoIdentidad" => 1,
        "numeroDocumento" => "7474483",  // CI / NIT/ PASAPORTE
        "complemento" => "",
        "codigoCliente" => "7474483-SDF",
        "codigoMetodoPago" => 1,
        "numeroTarjeta" => NULL,
        "montoTotal" => 10,
        "montoTotalSujetoIva" => 10,
        "codigoMoneda" => 1,
        "tipoCambio" => 1,
        "montoTotalMoneda" => 10,
        "montoGiftCard" => 0,
        "descuentoAdicional" => 0,
        "codigoExcepcion" => 0,
        "cafc" => null,
        "leyenda" => "Esta factura se emite por medio de una aplicación móvil", // Generado y reemplazado automaticamente por la api
        "usuario" => "JPEREZ",
        "codigoDocumentoSector" => 1,
        "camposAdicionales" => []
    ],
    "detalle" => [
        [
            "actividadEconomica" => 471110,
            "codigoProductoSin" => 99100,
            "codigoProducto" => 001,
            "descripcion" => "Juego de dados",
            "cantidad" => 1,
            "unidadMedida" => 64,
            "precioUnitario" => 10,
            "montoDescuento" => 0,
            "subTotal" => 10,
            "camposAdicionales" => []
        ]
    ],
    "extraInfo" => []
];

$factura = $api->fetch('/invoice-utils/third-party-create', 'POST', $jsonForInvoice, API::RESPONSE_JSON);

if (isset(json_decode($factura, true)['error'])) {
    echo $factura;
    exit;
} 

// Escribir en archivo llamado factura.json
file_put_contents('factura.json', json_encode(json_decode($factura), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo "Factura creada exitosamente\n";


// Ejemplo de solicitud para optener el pdf de la factura
$jsonForPdf = [
    'cuf' => json_decode($factura, true)['cuf'],
    'formato' => 1
];

$fileData = $api->fetch('/invoice-utils/pdf', 'GET', $jsonForPdf, API::RESPONSE_BINARY);

if (isset(json_decode($fileData, true)['error'])) {
    echo $fileData;
    exit;
}
echo "PDF generado exitosamente\n"; 
file_put_contents('factura.pdf', $fileData);

// Ejemplo de solicitud para anular una factura

$jsonForCancel = [
    'cuf' => json_decode($factura, true)['cuf'],
    'codigoMotivo' => 1
];

$anular = $api->fetch('/invoice-utils/anular', 'POST', $jsonForCancel, API::RESPONSE_JSON);

if (isset(json_decode($anular, true)['error'])) {
    echo $anular;
    exit;
}

echo "Factura anulada exitosamente\n";
?>