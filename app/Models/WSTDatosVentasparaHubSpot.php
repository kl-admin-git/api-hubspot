<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WSTDatosVentasparaHubSpot extends Model
{
    protected $table = 'WST_DatosVentasparaHubSpot';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'nit',
        'client_name',
        'document_type',
        'document_number',
        'nro_DV',
        'document_date',
        'product_code',
        'product_name',
        'precio_neto',
        'trm',
        'money',
        'cod_rtc',
        'rtc_name',
        'division'
    ];

    protected $connection = 'sqlsrv';
}
