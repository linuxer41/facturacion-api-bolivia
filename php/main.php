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
                // curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
                break;

            case "GET": 
                if($data) $url .= "?".http_build_query($data);
                break;

            case "PUT":
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
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

        if ($result === FALSE) {
            $error = curl_error($curl);
            curl_close($curl);
            return json_encode(["error" => "Error en la solicitud: $error"]);
        }

        curl_close($curl);

        return $result;
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
$baseUrl = 'https://e-docs-api.iathings.com/api/v1';
$apikey = '0d21d1a882c3c4290d7ee174888148b5ecac53b6008a0f94f35ede67d5216836c3b95882cab454f82f9f7a365c7465891b5e785bd63e20cd52e4378759a96f91c1c67d69cffb40eab0b15bb0d3dcf64aba5da83381a054f89a83b3cb8f28dfbffb3134a575f7c4d5e3bf6a0b9354037f2d4abe190abcd08cc09aae85e66de08a2ea2b0d2b629d081b0b6fdb928548460174a16c8a07d3151cdc59d1d44fb9497abb1bd3b590a26e34b671a32b17bf12d6c0e53aec93ca384be0fa383783889493f57928280e8a66ed9695fd6be80ddcc3400601587ba15f89a0dcac8c93322830cd3796a1f54663dcdb3391ac646ce42d507c162be3240ba24fc87cf769f8e11368c0dbe76f31fd5beab4319946c9845';

$api = new API($baseUrl, $apikey);

$jsonForParams = '{
    "codigoPuntoVenta": 0,
    "codigoSucursal": 0,
    "codigoAmbiente": 2,
    "codigoModalidad": 1,
    "nit": "7474483013"
}';

// Ejemplo de solicitud parametricas => esto deberia sincronizarse almenos una vez al dia
$parametricas = $api->fetch('/invoice-utils/parametricas', 'POST', json_decode($jsonForParams), API::RESPONSE_JSON);
// escribir en archivo llamado parametricas.json

if (isset($parametricas['error'])) {
    echo json_encode($parametricas);
} else {
    echo "Sincronizacion exitosa\n";
    file_put_contents('parametricas.json', json_encode(json_decode($parametricas), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

// Ejemplo de facturacion

$jsonForInvoice = [
    "solicitud" => [
        "codigoModalidad" => 1,
        "codigoEmision" => 1,
        "codigoDocumentoSector" => 1,
        "codigoSucursal" => 0,
        "codigoAmbiente" => 2,
        "codigoPuntoVenta" => 0,
        "codigoActividad" => 620100,
        "nitEmisor" => "7474483013",
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
        "nitEmisor" => 7474483013,
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
            "actividadEconomica" => 620100,
            "codigoProductoSin" => 83141,
            "codigoProducto" => 61162,
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

if (isset($factura['error'])) {
    echo json_encode($factura);
} else {
    echo "Factura creada exitosamente\n";
    // Escribir en archivo llamado factura.json
    file_put_contents('factura.json', json_encode(json_decode($factura), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

// Ejemplo de solicitud para optener el pdf de la factura
$jsonForPdf = [
    'cuf' => json_decode($factura, true)['cuf'],
    'formato' => 1
];

$fileData = $api->fetch('/invoice-utils/pdf', 'GET', $jsonForPdf, API::RESPONSE_BINARY);

if (isset($fileData['error'])) {
    echo json_encode($fileData);
} else {
    echo "PDF generado exitosamente\n"; 
    file_put_contents('factura.pdf', $fileData);
}

// Ejemplo de solicitud para anular una factura

$jsonForCancel = [
    'cuf' => json_decode($factura, true)['cuf'],
    'codigoMotivo' => 1
];

$anular = $api->fetch('/invoice-utils/anular', 'POST', $jsonForCancel, API::RESPONSE_JSON);

if (isset($anular['error'])) {
    echo json_encode($anular);
} else {
    echo "Anulacion exitosa\n";   
};




?>