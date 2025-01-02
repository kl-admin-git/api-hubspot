<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\WSTDatosVentasparaHubSpot;

class HubSpotController extends Controller
{
    public function __construct()
    {
        $this->url_hubspot = "https://api.hubapi.com/crm/v3/objects/";
        $this->bearer = ENV("HUBSPOT_KEY");
        $this->propertiesAllowed = ["name", "nit", "industry", "website", "address", "phone", "canal", "city", "hubspot_owner_id", "annualrevenue", "razon_social", "potencial_de_crecimiento", "tipo_de_venta"];
    }

    /**
     * Guardado de logs
     *
     * Guarda Logs en carpeta Storage para ver los parámetros que se envían desde el
     * cliente
     *
     * @author Roney Rodriguez
     */
    function PrintLog($name_function, $data)
    {
        $bodyContent = json_encode($data, JSON_PRETTY_PRINT);
        $filename = 'request_body_'.$name_function."_" . date('Y_m_d_H_i_s') . '.txt';
        Storage::disk('local')->put($filename, $bodyContent);
    }

    /**
     * Consulta factura
     *
     * Se busca consultar la factura para saber en que estado se encuentra y así retornar
     * un verdadero o falso para que se realice un a lógica especial en HubSpot
     *
     * @author Roney Rodriguez
     */
    public function OrderValidate(Request $request)
    {
        try
        {
            //REGISTRAR LOG
            $data = $request->all();
            $this->PrintLog("OrderValidate",$data);

            $properties_valids = ["creacion_de_cliente", "orden_de_compra", "compromiso_de_entrega", "numero_de_factura"];
            if(COUNT($this->ValidPropertiesAllowedKlHb($data, $properties_valids)) != 0)
            {
                return response()->json([
                    'success' => 1,
                    'responseCode' => 400,
                    'message' => 'Las propiedades que envías, no son correctas, comunícate con el administrador.',
                    'data' => null
                ]);
            }

            // GET INVOICE
            $invoice = $request->get("numero_de_factura");
            $query = \DB::table("hb_invoices")
            ->where("document_number", "=", $invoice)
            ->get()
            ->map(function ($item) {
                foreach ($item as $key => $value) {
                    if (is_string($value)) {
                        $item->$key = trim($value); // Aplica trim a los valores string
                    }
                }
                return $item;
            });

            // LOGICA PLANTEADA POR JUAN (RETORNA UN SI O UN NO, DEPENDIENDO SI LA ENCUENTRA)
            if(COUNT($query) != 0)
            {
                return response()->json([
                    'success' => 1,
                    'responseCode' => 200,
                    'message' => 'Factura encontrada en el sistema.',
                    'data' => [
                        'factura_validada' => true,
                        'factura_datos' => $query
                    ]
                ]);
            }
            else
            {
                return response()->json([
                    'success' => 1,
                    'responseCode' => 200,
                    'message' => 'Factura no encontrada en el sistema',
                    'data' => [
                        'factura_validada' => false,
                        'factura_datos' => null
                    ]
                ]);
            }
        }
        catch (\Throwable $th)
        {
            return "Error al procesar la API: " . $th;
        }
    }

    function GetDataByInvoice($invoice_number)
    {
        $query = DB::connection("sqlsrv")
        ->table("WST_DatosVentasparaHubSpot")
        ->where('NumeroDocumento', "=", $invoice_number)
        ->get()
        ->map(function ($item) {
            foreach ($item as $key => $value) {
                if (is_string($value)) {
                    $item->$key = trim($value); // Aplica trim a los valores string
                }
            }
            return $item;
        });

        return $query;
    }

    /**
     * Consulta de facturas
     *
     * Se recibe una lista de facturas para buscarlas y retornar la información
     * necesaria para hubgspot
     *
     * @author Roney Rodriguez
     */
    public function GetInvoicesInfo(Request $request)
    {
        try
        {
            //REGISTRAR LOG
            $data = $request->all();
            // $results = DB::connection("sqlsrv")->select('SELECT * FROM WST_DatosVentasparaHubSpot');
            $this->PrintLog("GetInvoicesInfo",$data);

            $properties_valids = ["numero_de_facturas"];
            if(COUNT($this->ValidPropertiesAllowedKlHb($data, $properties_valids)) != 0)
            {
                return response()->json([
                    'success' => 1,
                    'responseCode' => 400,
                    'message' => 'Las propiedades que envías, no son correctas, comunícate con el administrador.',
                    'data' => null
                ]);
            }

            // GET INVOICES
            $invoices = json_decode($request->get("numero_de_facturas"));

            $array_invoices_data = [];
            foreach($invoices as $key_invoice => $value_invoice)
            {
                $data = $this->GetDataByInvoice($value_invoice);
                if(COUNT($data) != 0)
                    $array_invoices_data[$value_invoice] = (object)$data;
            }

            // LOGICA PLANTEADA POR JUAN (RETORNA UN SI O UN NO, DEPENDIENDO SI LA ENCUENTRA)
            if(COUNT($array_invoices_data) != 0)
            {
                return response()->json([
                    'success' => 1,
                    'responseCode' => 200,
                    'message' => 'Factura(s) encontrada(s) en el sistema.',
                    'data' => $array_invoices_data
                ]);
            }
            else
            {
                return response()->json([
                    'success' => 1,
                    'responseCode' => 200,
                    'message' => 'Ninguna factura se encontró en el sistema',
                    'data' => null
                ]);
            }
        }
        catch (\Throwable $th)
        {
            return "Error al procesar la API: " . $th;
        }
    }

    /**
     * Retorno de facturas por fechas
     *
     * Se realiza la consulta por fecha de las facturas existentes en la base de datos
     *
     * @author Roney Rodriguez
     */
    public function ListInvoicesByDate(Request $request)
    {
        try
        {
            //REGISTRAR LOG
            $data = $request->all();
            $this->PrintLog("ListInvoicesByDate", $data);

            $properties_valids = ["fecha_inicial", "fecha_final"];
            if(COUNT($this->ValidPropertiesAllowedKlHb($data, $properties_valids)) != 0)
            {
                return response()->json([
                    'success' => 1,
                    'responseCode' => 400,
                    'message' => 'Las propiedades que envías, no son correctas, comunícate con el administrador.',
                    'data' => null
                ]);
            }

            // GET INIT DATE AND END DATE
            $date_initial = $request->get("fecha_inicial")." 00:00:00";
            $date_end = $request->get("fecha_final")." 23:59:59";

            $query = \DB::table("hb_invoices")
            ->whereBetween('document_date', [$date_initial, $date_end])
            ->get()
            ->map(function ($item) {
                foreach ($item as $key => $value) {
                    if (is_string($value)) {
                        $item->$key = trim($value); // Aplica trim a los valores string
                    }
                }
                return $item;
            });

            // LOGICA PLANTEADA POR CARLOS
            return response()->json([
                'success' => 1,
                'responseCode' => 200,
                'message' => 'Consulta realizada',
                'data' => $query
            ]);
        }
        catch (\Throwable $th)
        {
            return "Error al procesar la API: " . $th;
        }
    }

    ########################################## CONSULTAS HACIA EL SERVIDOR DE HUBSPOT ##########################################

    public function GetInfoQueryByNit(Request $request)
    {
        $nit = $request->get("nit");
        $answer = $this->CurlHubSpotQueryByNit($nit);

        return response()->json([
            'success' => 1,
            'responseCode' => 200,
            'message' => 'Datos recibidos correctamente.',
            'data' => $answer
        ]);
    }

    public function CreateCompanyHubspot(Request $request) // ID TEST CREATE COMPANY: 25730663202
    {
        $name = $request->get("name");
        $nit = $request->get("nit");
        $industry = $request->get("industry");
        $phone = $request->get("phone");
        $canal = $request->get("canal");
        $website = $request->get("website");
        $address = $request->get("address");

        $answer = $this->CurlHubSpotCreateCompay($name, $nit, $industry, $phone, $canal, $website, $address);

        return response()->json([
            'success' => 1,
            'responseCode' => 200,
            'message' => 'Datos recibidos correctamente.',
            'data' => $answer
        ]);
    }

    public function UpdateCompanyHubspot(Request $request)
    {
        $properties = $request->get("properties");
        $id_company = $request->get("id_company");
        $properties_invalid = $this->ValidPropertiesAllowed($properties);
        if(COUNT($properties_invalid) != 0)
            return response()->json([
                'success' => 1,
                'responseCode' => 400,
                'message' => 'Las siguientes propiedades para actualizar no son permitidas.',
                'data' => $properties_invalid
            ]);

        $answer = $this->CurlHubSpotUpdateCompany($properties, $id_company);

        return response()->json([
            'success' => 1,
            'responseCode' => 200,
            'message' => 'Datos recibidos correctamente.',
            'data' => $answer
        ]);
    }

    function ValidPropertiesAllowed($properties)
    {
        $properties_invalid = [];
        foreach($properties as $property => $value)
        {
            if (!array_search($property, $this->propertiesAllowed) && $property != "id_company")
                array_push($properties_invalid, $property);
        }

        return $properties_invalid;
    }

    function ValidPropertiesAllowedKlHb($properties_api, $properties_allowed)
    {
        $properties_invalid = [];
        foreach($properties_api as $property => $value)
        {
            if (!in_array($property, $properties_allowed))
                array_push($properties_invalid, $property);
        }

        return $properties_invalid;
    }

    function CurlHubSpotQueryByNit($nit)
    {
        try
        {
            $url = $this->url_hubspot."companies/search";

            $postDatos = [
                "filterGroups" => [
                    [
                        "filters" => [
                            [
                                "propertyName" => "nit",
                                "operator" => "EQ",
                                "value" => $nit
                            ]
                        ]
                    ]
                ],
                "properties" => ["name", "nit", "industry", "website", "address", "phone", "canal", "city", "hubspot_owner_id", "annualrevenue", "razon_social", "potencial_de_crecimiento", "tipo_de_venta"]
            ];

            $opciones = array();

            $opcionesDefault = array(
                CURLOPT_POST => 1,
                CURLOPT_HEADER => 0,
                CURLOPT_URL => $url,
                CURLOPT_HTTPHEADER => [
                        'Content-Type: application/json',
                        'Authorization: Bearer ' . $this->bearer
                ],
                CURLOPT_FRESH_CONNECT => 1,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_FORBID_REUSE => 1,
                CURLOPT_TIMEOUT => 4,
                CURLOPT_POSTFIELDS => json_encode($postDatos)
            );

            $inicializacionCurl = curl_init();

            curl_setopt_array($inicializacionCurl, ($opciones + $opcionesDefault));

            if(!$result = curl_exec($inicializacionCurl))
                trigger_error(curl_error($inicializacionCurl));

            curl_close($inicializacionCurl);

            $objetoServidor = json_decode($result);

            return $objetoServidor;
        }
        catch (\Throwable $th)
        {
            return "Error al consultar API Hubspot: " . $th;
        }
    }

    function CurlHubSpotCreateCompay($name, $nit, $industry, $phone, $canal, $website, $address)
    {
        try
        {
            $url = $this->url_hubspot."companies";

            $postDatos = [
                "properties" => [
                    "name" => $name,
                    "nit" => $nit,
                    "industry" => $industry,
                    "phone" => $phone,
                    "canal" => $canal,
                    "website" => $website,
                    "address" => $address
                ]
            ];

            $opciones = array();

            $opcionesDefault = array(
                CURLOPT_POST => 1,
                CURLOPT_HEADER => 0,
                CURLOPT_URL => $url,
                CURLOPT_HTTPHEADER => [
                        'Content-Type: application/json',
                        'Authorization: Bearer ' . $this->bearer
                ],
                CURLOPT_FRESH_CONNECT => 1,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_FORBID_REUSE => 1,
                CURLOPT_TIMEOUT => 4,
                CURLOPT_POSTFIELDS => json_encode($postDatos)
            );

            $inicializacionCurl = curl_init();

            curl_setopt_array($inicializacionCurl, ($opciones + $opcionesDefault));

            if(!$result = curl_exec($inicializacionCurl))
                trigger_error(curl_error($inicializacionCurl));

            curl_close($inicializacionCurl);

            $objetoServidor = json_decode($result);

            return $objetoServidor;
        }
        catch (\Throwable $th)
        {
            return "Error al consultar API Hubspot: " . $th;
        }
    }

    function CurlHubSpotUpdateCompany($properties, $id_company)
    {
        try
        {
            if(!ISSET($id_company))
            {
                return response()->json([
                    "success" => 1,
                    "responseCode" => 400,
                    "message" => "Es necesario el id de la empresa para su actualización",
                    "data" => null
                ]);
            }

            $url = $this->url_hubspot."companies/".$id_company;

            $postDatos = [
                "properties" => $properties
            ];

            $opciones = array();

            $opcionesDefault = array(
                CURLOPT_CUSTOMREQUEST => "PATCH",
                CURLOPT_HEADER => 0,
                CURLOPT_URL => $url,
                CURLOPT_HTTPHEADER => [
                        'Content-Type: application/json',
                        'Authorization: Bearer ' . $this->bearer
                ],
                CURLOPT_FRESH_CONNECT => 1,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_FORBID_REUSE => 1,
                CURLOPT_TIMEOUT => 4,
                CURLOPT_POSTFIELDS => json_encode($postDatos)
            );

            $inicializacionCurl = curl_init();

            curl_setopt_array($inicializacionCurl, ($opciones + $opcionesDefault));

            if(!$result = curl_exec($inicializacionCurl))
                trigger_error(curl_error($inicializacionCurl));

            curl_close($inicializacionCurl);

            $objetoServidor = json_decode($result);

            return $objetoServidor;
        }
        catch (\Throwable $th)
        {
            return "Error al consultar API Hubspot: " . $th;
        }
    }

}
