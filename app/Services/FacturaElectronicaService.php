<?php

namespace App\Services;

use Exception;
use DOMDocument;
use Illuminate\Support\Facades\File;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use GuzzleHttp\Client;

class FacturaElectronicaService
{
    public function generarFacturaXML($data)
    {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        $fechaEmisionFormateada = date('d/m/Y', strtotime($data['fechaEmision']));

        // Crear el elemento raíz 'factura'
        $factura = $xml->createElement('factura');
        $factura->setAttribute('id', 'comprobante');
        $factura->setAttribute('version', '1.0.0');

        // Crear 'infoTributaria'
        $infoTributaria = $xml->createElement('infoTributaria');
        $infoTributaria->appendChild($xml->createElement('ambiente', env('FACTURACION_AMBIENTE')));
        $infoTributaria->appendChild($xml->createElement('tipoEmision', '1'));
        $infoTributaria->appendChild($xml->createElement('razonSocial', $data['razonSocial']));
        $infoTributaria->appendChild($xml->createElement('ruc', $data['ruc']));
        $infoTributaria->appendChild($xml->createElement('claveAcceso', $this->generarClaveAcceso($data)));
        $infoTributaria->appendChild($xml->createElement('codDoc', '01')); // 01: Factura
        $infoTributaria->appendChild($xml->createElement('estab', $data['estab']));
        $infoTributaria->appendChild($xml->createElement('ptoEmi', $data['ptoEmi']));
        $infoTributaria->appendChild($xml->createElement('secuencial', $data['secuencial']));
        $infoTributaria->appendChild($xml->createElement('dirMatriz', $data['dirMatriz']));

        $factura->appendChild($infoTributaria);

        // Crear 'infoFactura'
        $infoFactura = $xml->createElement('infoFactura');
        $infoFactura->appendChild($xml->createElement('fechaEmision', $fechaEmisionFormateada));
        $infoFactura->appendChild($xml->createElement('dirEstablecimiento', $data['dirEstablecimiento']));
        $infoFactura->appendChild($xml->createElement('obligadoContabilidad', $data['obligadoContabilidad']));
        $infoFactura->appendChild($xml->createElement('tipoIdentificacionComprador', $data['tipoIdentificacionComprador']));
        $infoFactura->appendChild($xml->createElement('razonSocialComprador', $data['razonSocialComprador']));
        $infoFactura->appendChild($xml->createElement('identificacionComprador', $data['identificacionComprador']));
        $infoFactura->appendChild($xml->createElement('totalSinImpuestos', $data['totalSinImpuestos']));
        $infoFactura->appendChild($xml->createElement('totalDescuento', $data['totalDescuento']));

        // Crear 'totalConImpuestos'
        $totalConImpuestos = $xml->createElement('totalConImpuestos');
        foreach ($data['totalConImpuestos'] as $impuesto) {
            $totalImpuesto = $xml->createElement('totalImpuesto');
            $totalImpuesto->appendChild($xml->createElement('codigo', $impuesto['codigo']));
            $totalImpuesto->appendChild($xml->createElement('codigoPorcentaje', $impuesto['codigoPorcentaje']));
            $totalImpuesto->appendChild($xml->createElement('baseImponible', $impuesto['baseImponible']));
            $totalImpuesto->appendChild($xml->createElement('valor', $impuesto['valor']));
            $totalConImpuestos->appendChild($totalImpuesto);
        }
        $infoFactura->appendChild($totalConImpuestos);

        $infoFactura->appendChild($xml->createElement('propina', $data['propina']));
        $infoFactura->appendChild($xml->createElement('importeTotal', $data['importeTotal']));
        $infoFactura->appendChild($xml->createElement('moneda', $data['moneda']));

        $factura->appendChild($infoFactura);

        // Crear 'detalles'
        $detalles = $xml->createElement('detalles');
        foreach ($data['detalles'] as $detalle) {
            $detalleElement = $xml->createElement('detalle');
            $detalleElement->appendChild($xml->createElement('codigoPrincipal', $detalle['codigoPrincipal']));
            $detalleElement->appendChild($xml->createElement('descripcion', $detalle['descripcion']));
            $detalleElement->appendChild($xml->createElement('cantidad', $detalle['cantidad']));
            $detalleElement->appendChild($xml->createElement('precioUnitario', $detalle['precioUnitario']));
            $detalleElement->appendChild($xml->createElement('descuento', $detalle['descuento']));
            $detalleElement->appendChild($xml->createElement('precioTotalSinImpuesto', $detalle['precioTotalSinImpuesto']));

            // Crear 'impuestos' dentro del detalle
            $impuestos = $xml->createElement('impuestos');
            foreach ($detalle['impuestos'] as $impuesto) {
                $impuestoElement = $xml->createElement('impuesto');
                $impuestoElement->appendChild($xml->createElement('codigo', $impuesto['codigo']));
                $impuestoElement->appendChild($xml->createElement('codigoPorcentaje', $impuesto['codigoPorcentaje']));
                $impuestoElement->appendChild($xml->createElement('tarifa', $impuesto['tarifa']));
                $impuestoElement->appendChild($xml->createElement('baseImponible', $impuesto['baseImponible']));
                $impuestoElement->appendChild($xml->createElement('valor', $impuesto['valor']));
                $impuestos->appendChild($impuestoElement);
            }
            $detalleElement->appendChild($impuestos);
            $detalles->appendChild($detalleElement);
        }
        $factura->appendChild($detalles);

        $xml->appendChild($factura);

        // Guardar el XML
        // $xml->save('ruta/donde/guardar/factura.xml');




          // Definir la ruta de la carpeta 'facturas' dentro de 'resources'
          $facturasPath = resource_path('facturas');

          // Verificar si la carpeta 'facturas' existe, si no, crearla
          if (!File::exists($facturasPath)) {
              File::makeDirectory($facturasPath, 0777, true); // Crea la carpeta con permisos adecuados
          }
        // Definir el nombre del archivo XML
        $xmlFileName = 'factura_' . time() . '.xml'; // Usamos el timestamp para evitar sobrescribir archivos
        $xmlFilePath = $facturasPath . '/' . $xmlFileName;

        // Guardar el XML en la carpeta 'facturas'
        $xml->save($xmlFilePath);

        $xsdFile = resource_path('xsd/factura_V1.1.0.xsd');
        // $xsdFile = resource_path('xsd/factura_V1.0.0.xsd'); // Utiliza resource_path() para obtener la ruta correcta
        // if (!file_exists($xsdFile)) {
        //     echo "El archivo XSD no existe en la ruta especificada: " . $xsdFile;
        // } else {
        //     echo "El archivo XSD existe, procediendo con la validación.";
        // }
        // die;
        if ($xml->schemaValidate($xsdFile)) {
            return $xmlFileName;
            // echo "El XML es válido según el esquema XSD.";
        } else {
            return null;
            // echo "El XML no es válido. Verifica los errores.";
        }

    }
    public function generarClaveAcceso($data)
    {
        // 1. Concatenar los campos
        // $fechaEmision = str_replace('-', '', trim($data['fechaEmision'])); // Eliminar los guiones de la fecha
        // Convertir la fecha de emisión al formato ddmmaaaa
        $fechaEmision = \DateTime::createFromFormat('Y-m-d', trim($data['fechaEmision']))->format('dmY');

        $estab = str_pad(trim($data['estab']), 3, '0', STR_PAD_LEFT); // Asegurarse de que el establecimiento tenga 3 dígitos
        $ptoEmi = str_pad(trim($data['ptoEmi']), 3, '0', STR_PAD_LEFT); // Asegurarse de que el punto de emisión tenga 3 dígitos
        $secuencial = str_pad(trim($data['secuencial']), 9, '0', STR_PAD_LEFT); // Asegurarse de que el secuencial tenga 9 dígitos
        // dd($estab, $ptoEmi);
        // Concatenar los campos
        $clave = $fechaEmision // AAAAMMDD
            . '01'               // Tipo de comprobante (Factura)
            . env('FACTURACION_RUC')  // RUC del emisor
            . '1'                 // Ambiente (1 = pruebas, 2 = producción)
            . $estab . $ptoEmi    // Serie
            . $secuencial         // Secuencial (ahora con 9 dígitos)
            . '12345678'// Agregar 8 caracteres adicionales (puedes cambiar esto si es necesario)
            .'1';

        // Asegurarse de que la longitud de la clave antes de agregar el dígito verificador sea 48
        if (strlen($clave) !== 48) {
            throw new Exception("La clave de acceso debe tener exactamente 48 dígitos antes de agregar el dígito verificador. Longitud actual: " . strlen($clave));
        }

        // 2. Calcular el dígito verificador (módulo 11)
        $digitoVerificador = $this->calcularDigitoVerificador($clave);

        // Agregar el dígito verificador a la clave
        $clave .= $digitoVerificador;

        // 3. Verificar que la longitud final de la clave sea 49
        if (strlen($clave) !== 49) {
            throw new Exception("La clave de acceso generada no tiene 49 dígitos. Longitud final: " . strlen($clave));
        }

        // dd($clave);

        return $clave;
    }

    public function calcularDigitoVerificador($clave)
    {
        $peso = [2, 3, 4, 5, 6, 7];
        $suma = 0;
        $longitud = strlen($clave);

        // Invertir la clave y aplicar los pesos
        for ($i = $longitud - 1, $j = 0; $i >= 0; $i--, $j++) {
            $suma += (int)$clave[$i] * $peso[$j % count($peso)];
        }

        $modulo = $suma % 11;
        $digitoVerificador = ($modulo == 0) ? 0 : 11 - $modulo;

        // Si el dígito verificador es 10, se devuelve 1
        return ($digitoVerificador == 10) ? 1 : $digitoVerificador;
    }


    public function firmarXML($xmlPath, $certPath, $certPassword)
    {
        // Cargar el contenido del archivo .p12 (certificado digital)
        $pfx = file_get_contents($certPath);
        // dd($pfx);

        // Procesar el archivo .p12 para obtener la clave privada y el certificado público
        $certData = [];
        if (!openssl_pkcs12_read($pfx, $certData, $certPassword)) {
            throw new Exception("No se pudo leer el certificado. Verifica la ruta y la contraseña.");
        }

        // Obtener la clave privada y el certificado público
        $privateKey = $certData['pkey']; // Clave privada
        $publicCert = $certData['cert']; // Certificado público

        // Crear el objeto DOM para el XML
        $xml = new DOMDocument();
        $xml->load($xmlPath);

        // Crear el objeto de firma
        $objDSig = new XMLSecurityDSig();
        $objDSig->setCanonicalMethod(XMLSecurityDSig::C14N);

        // Agregar referencia al documento XML
        $objDSig->addReference(
            $xml,
            XMLSecurityDSig::SHA1,
            ['http://www.w3.org/2000/09/xmldsig#enveloped-signature']
        );

        // Crear una clave de seguridad para la firma
        $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, ['type' => 'private']);
        $objKey->loadKey($privateKey, false);

        // Firmar el XML
        $objDSig->sign($objKey);

        // Agregar el certificado público al XML
        $objDSig->add509Cert($publicCert);

        // Insertar la firma en el XML
        $objDSig->appendSignature($xml->documentElement);

        // Guardar el XML firmado
        $signedXmlPath = str_replace('.xml', '_signed.xml', $xmlPath);
        $xml->save($signedXmlPath);

        return $signedXmlPath;
    }

    public function enviarAlSRIConGuzzle($signedXmlPath)
    {
        // URL del servicio web del SRI para pruebas
        // $sriEndpoint = "https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantes";
        $sriEndpoint = "https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl";

        // Leer el contenido del XML firmado
        $xmlContent = file_get_contents($signedXmlPath);

        // Convertir el XML firmado a base64
        $xmlBase64 = base64_encode($xmlContent);
        // dd($xmlBase64);

        // Crear una instancia de Guzzle
        $client = new Client();

        // try {
            // Realizar la solicitud POST al servicio del SRI
            $response = $client->post($sriEndpoint, [
                'headers' => [
                    'Content-Type' => 'text/xml; charset=utf-8',
                ],
                'body' => $this->crearSolicitudSoap($xmlBase64),
            ]);
            // dd($response);

            // Obtener el cuerpo de la respuesta
            $responseBody = $response->getBody()->getContents();

            // Analizar la respuesta
            return $this->procesarRespuestaSRI($responseBody);

        // } catch (Exception $e) {
        //     // Manejar errores de conexión o de la solicitud
        //     return [
        //         'estado' => 'error',
        //         'mensaje' => $e->getMessage(),
        //     ];
        // }
    }

    private function crearSolicitudSoap($xmlBase64)
    {
        $soapRequest =
            '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ec="http://ec.gob.sri.ws.recepcion">' .
                '<soapenv:Header/>' .
                '<soapenv:Body>' .
                    '<ec:validarComprobante>' .
                        '<xml>' . $xmlBase64 . '</xml>' .
                    '</ec:validarComprobante>' .
                '</soapenv:Body>' .
            '</soapenv:Envelope>';

        return $soapRequest;
    }


    private function procesarRespuestaSRI($responseBody)
    {
        $xml = new \SimpleXMLElement($responseBody);
        // dd($xml);
        // Navegar en el XML para extraer el estado y mensajes
        $estado = (string) $xml->xpath('//estado')[0];
        $mensajes = $xml->xpath('//mensaje');
        $detalles = [];
        foreach ($mensajes as $mensaje) {
            $detalles[] = [
                'identificador' => (string) $mensaje->identificador,
                'mensaje' => (string) $mensaje->mensaje,
                'informacionAdicional' => (string) $mensaje->informacionAdicional,
                'tipo' => (string) $mensaje->tipo,
            ];
        }

        return [
            'estado' => $estado,
            'detalles' => $detalles,
        ];
    }


    public function consultarEstadoComprobante($claveAcceso)
    {
        // Seleccionar el endpoint según el ambiente
        // https://celcer.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl
        $url = env('FACTURACION_AMBIENTE') == 1
            ? 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl'
            : 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantes';

        // Crear el cuerpo de la solicitud SOAP
        $soapBody = "
        <soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:ec=\"http://ec.gob.sri.ws.autorizacion\">
            <soapenv:Header/>
            <soapenv:Body>
                <ec:autorizacionComprobante>
                    <claveAccesoComprobante>$claveAcceso</claveAccesoComprobante>
                </ec:autorizacionComprobante>
            </soapenv:Body>
        </soapenv:Envelope>
        ";

        // Configurar Guzzle
        $client = new Client([
            'headers' => [
                'Content-Type' => 'text/xml; charset=utf-8',
            ],
        ]);

        try {
            // Enviar la solicitud
            $response = $client->post($url, [
                'body' => $soapBody,
            ]);

            // Obtener el cuerpo de la respuesta
            $body = $response->getBody()->getContents();
            dd($body);
            // Procesar la respuesta SOAP
            $xml = simplexml_load_string($body);
            dd($xml);
            $namespaces = $xml->getNamespaces(true);

            // Extraer el contenido del cuerpo de la respuesta
            $soapBody = $xml->children($namespaces['soapenv'])->Body;
            $responseContent = $soapBody->children($namespaces['ns2'])->RespuestaAutorizacionComprobante;

            return $responseContent;
        } catch (Exception $e) {
            // Manejar errores
            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }


}
