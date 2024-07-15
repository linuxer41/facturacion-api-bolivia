const https = require('https');
const fs = require('fs');

class API {
    constructor(baseUrl, apikey) {
        this.baseUrl = baseUrl;
        this.apikey = apikey;
    }

    fetch(location, method, data = null, responseType = 'json') {
        const url = this.baseUrl + location;
        const options = {
            method: method,
            headers: {
                'api_key': this.apikey,
                'Content-Type': 'application/json',
                ...(responseType === 'binary' ? { 'Accept': 'application/octet-stream' } : {})
            },
            rejectUnauthorized: false // Deshabilitar verificación SSL
        };

        return new Promise((resolve, reject) => {
            const req = https.request(url, options, (res) => {
                let responseData = '';

                res.on('data', (chunk) => {
                    responseData += chunk;
                });

                res.on('end', () => {
                    if (res.statusCode >= 200 && res.statusCode < 300) {
                        resolve(responseData);
                    } else {
                        resolve(JSON.stringify({
                            error: `Error en la solicitud: Código de estado HTTP ${res.statusCode}`,
                            response: JSON.parse(responseData)
                        }, null, 2));
                    }
                });
            });

            req.on('error', (error) => {
                reject(JSON.stringify({ error: `Error en la solicitud: ${error.message}` }));
            });

            if (data) {
                if (method === 'GET') {
                    // put params in query string
                    req.path += '?' + new URLSearchParams(data).toString();
                } else{
                    req.write(JSON.stringify(data));
                }
                
            }

            req.end();
        });
    }
}

function getCurrentDateTimeISO() {
    const datetime = new Date();
    const milliseconds = datetime.getMilliseconds().toString().padStart(3, '0');
    return datetime.toISOString().replace('T', 'T').replace('Z', `.${milliseconds}Z`);
}

async function main() {
    const currentDate = getCurrentDateTimeISO();
    console.log(`Fecha de emision: ${currentDate} Hora Bolivia`);

    const baseUrl = 'https://factugest-api.iathings.com/api/v1';
    const apikey = 'da0545637bc3905a1fd0abcc239a1bd21e0e38e94435fdd3d78cd2019669f0142c868cd375135f1d28be8ee303eda528e9601189e6929be4fff911cd451b9046d2ad70210ce1f8d8f24af69124e6f287448d5af445fac50c1d0886aaadffd14eb4ddaabad6bdc991ed97fdb5ad8ffe09966fa296b34f9c18df9c063fe12e2810cefe102cb48b6b0fb258bc5f2e50278a7a44595b3a2dee2b49d74b1adcfab75c6a09bb4fff0e7a3aad8bdd2299f6f4c3b5c813e6ada7e7f551a4b996bb2b9e89a42e9f47f8a901d77e0b936075ba6484c86549c6a46c8863cfbef8eebe58300759dd5e16b0141851fdb4406d9840bc6ce93a031a494f7a314182fa7cc0bddab123af35d886547231a94123fd754e58d0';

    const api = new API(baseUrl, apikey);

    const jsonForParams = {
        codigoPuntoVenta: 0,
        codigoSucursal: 0,
        codigoAmbiente: 2,
        codigoModalidad: 1,
        nit: "3136291018"
    };

    try {
        // Ejemplo de solicitud parametricas
        let parametricas = await api.fetch('/invoice-utils/parametricas', 'POST', jsonForParams, 'json');
        let parsedParametricas = JSON.parse(parametricas);
        if (parsedParametricas.error) {
            console.log(parametricas);
            process.exit(1);
        }

        fs.writeFileSync('parametricas.json', JSON.stringify(parsedParametricas, null, 2));
        console.log("Sincronizacion exitosa\n");

        // Ejemplo de facturacion
        const jsonForInvoice = {
            solicitud: {
                codigoModalidad: 2,
                codigoEmision: 1,
                codigoDocumentoSector: 1,
                codigoSucursal: 0,
                codigoAmbiente: 2,
                codigoPuntoVenta: 0,
                codigoActividad: 471110,
                nitEmisor: "3136291018",
                codigoTipoEvento: 0,
                numeroDocumento: "7474483",
                codigoTipoDocumento: 1,
                razonSocial: "JOSE MANUEL GARCIA",
                correoCliente: "linuxer41@gmail.com",
                formatoPdf: 1
            },
            cabecera: {
                nitEmisor: 3136291018,
                razonSocialEmisor: "Juan Perez",
                municipio: "Sucre",
                telefono: "04241234567",
                numeroFactura: 10,
                codigoSucursal: 0,
                direccion: "Calle 1",
                codigoPuntoVenta: 0,
                fechaEmision: currentDate,
                nombreRazonSocial: "JOSE MANUEL GARCIA",
                codigoTipoDocumentoIdentidad: 1,
                numeroDocumento: "7474483",
                complemento: "",
                codigoCliente: "7474483-SDF",
                codigoMetodoPago: 1,
                numeroTarjeta: null,
                montoTotal: 10,
                montoTotalSujetoIva: 10,
                codigoMoneda: 1,
                tipoCambio: 1,
                montoTotalMoneda: 10,
                montoGiftCard: 0,
                descuentoAdicional: 0,
                codigoExcepcion: 0,
                cafc: null,
                leyenda: "Esta factura se emite por medio de una aplicación móvil",
                usuario: "JPEREZ",
                codigoDocumentoSector: 1,
                camposAdicionales: []
            },
            detalle: [
                {
                    actividadEconomica: 471110,
                    codigoProductoSin: 99100,
                    codigoProducto: "001",
                    descripcion: "Juego de dados",
                    cantidad: 1,
                    unidadMedida: 64,
                    precioUnitario: 10,
                    montoDescuento: 0,
                    subTotal: 10,
                    camposAdicionales: []
                }
            ],
            extraInfo: []
        };

        let factura = await api.fetch('/invoice-utils/third-party-create', 'POST', jsonForInvoice, 'json');
        let parsedFactura = JSON.parse(factura);
        if (parsedFactura.error) {
            console.log(factura);
            process.exit(1);
        }

        fs.writeFileSync('factura.json', JSON.stringify(parsedFactura, null, 2));
        console.log("Factura creada exitosamente\n");
        // Ejemplo de solicitud para obtener el PDF de la factura
        const jsonForPdf = {
            cuf: parsedFactura.cuf,
            formato: 1
        };
        console.log(jsonForPdf);

        let fileData = await api.fetch('/invoice-utils/pdf', 'GET', jsonForPdf, 'binary');
        // if (JSON.parse(fileData).error) {
        //     console.log(fileData);
        //     process.exit(1);
        // }

        fs.writeFileSync('factura.pdf', fileData);
        console.log("PDF generado exitosamente\n");

        // Ejemplo de solicitud para anular una factura
        const jsonForCancel = {
            cuf: parsedFactura.cuf,
            codigoMotivo: 1
        };

        let anular = await api.fetch('/invoice-utils/anular', 'POST', jsonForCancel, 'json');
        let parsedAnular = JSON.parse(anular);
        if (parsedAnular.error) {
            console.log(anular);
            process.exit(1);
        }

        console.log("Factura anulada exitosamente\n");
    } catch (error) {
        console.error(error);
    }
}

main();