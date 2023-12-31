Ejemplo de uso de api FACTUGEST
============================

FACTUGEST API proporciona una interfaz para interactuar con el sistema de impuestos nacionales. Permite realizar operaciones como la emisión de facturas, consulta de parámetros y generación de documentos PDF.

Instrucciones de Uso
--------------------

### Requisitos Previos

Antes de comenzar, asegúrate de tener acceso a un cliente HTTP (como cURL o Postman) y la URL base de la API.

### Configuración

1. Clona o descarga este repositorio en tu sistema local.

2. Abre el archivo `main.php` o el de tu lenguaje de programación y modifica tu API_KEY.

    ```
    $baseUrl = 'https://e-docs-api.iathings.com/api/v1';
    $apikey = 'TU_CLAVE_DE_API';
    ```

Ejemplos de Uso
---------------

### Sincronizar Parámetros
```
POST /invoice-utils/parametricas

Body:
{
"codigoPuntoVenta": 0,
"codigoSucursal": 0,
"codigoAmbiente": 2,
"codigoModalidad": 1,
"nit": "7474483013"
}
```

### Emitir Facturas
```
POST /invoice-utils/third-party-create
Body:
{
// (definición del objeto JSON para la factura)
}
```

Endpoints Disponibles
----------------------

- `POST /invoice-utils/parametricas`: Sincroniza los parámetros necesarios para la emisión de facturas.

- `POST /invoice-utils/third-party-create`: Emite una factura de terceros.

- `GET /invoice-utils/pdf`: Obtiene el PDF de una factura.

- `POST /invoice-utils/anular`: Anula una factura.