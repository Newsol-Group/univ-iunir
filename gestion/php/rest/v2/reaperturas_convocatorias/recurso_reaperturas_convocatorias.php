<?php

namespace SIU\Guarani\php\rest\v2\reaperturas_convocatorias;

use SIUToba\rest\rest;
use SIUToba\rest\lib\rest_validador;
use SIUToba\rest\lib\modelable;
use PERS\Guarani\php\rest\v2\modelos\rest_reaperturas_convocatorias;

class recurso_reaperturas_convocatorias implements modelable
{

    /**
     * @var rest_reaperturas_convocatorias
     */
    protected $modelo;

    public static function _get_modelos(){

        $reaperturasConvocatorias = [

            'id_convocatoria' => [
                'type'		=> 'integer',
                '_validar'	=> [
                    rest_validador::OBLIGATORIO,
                    rest_validador::TIPO_INT
                ],
                'example' => 46
            ],

            'cuil' => [
                'type'		=> 'string',
                '_validar'	=> [
                    rest_validador::OBLIGATORIO,
                    rest_validador::TIPO_ALPHANUM,
                    rest_validador::TIPO_LONGITUD => [
                        'min' => 11,
                        'max' => 11
                    ]
                ],
                'example' => '20302587419'
            ],

            'fecha_hasta'     => [
                'type'		=> 'string',
                '_validar'	=> [
                    rest_validador::OBLIGATORIO,
                    rest_validador::TIPO_DATE => [
                        'format' => 'Y-m-d'
                    ]
                ],
                'example' => '2024-03-30'
            ],

            'observaciones' => [
                'type'		=> 'string',
                '_validar'	=> [
                    rest_validador::OBLIGATORIO,
                    rest_validador::TIPO_TEXTO
                ],
                'example' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Suspendisse cursus massa ac sodales facilisis.'
            ],

            'datos_censales' => [
                'type'		=> 'array',
                'items'		=> [
                    'type' => 'string'
                ],
                '_validar'	=> [
                    rest_validador::TIPO_ARREGLO
                ],
                'example' => ['cantidad_hijos', 'cantidad_familia', 'trabajo_existe']
            ],

            'documentos' => [
                'type'		=> 'array',
                'items'		=> [
                    'type' => 'integer'
                ],
                '_validar'	=> [
                    rest_validador::TIPO_ARREGLO
                ],
                'example' => [501, 502, 503]
            ],

        ];

        return [
            'ReaperturasConvocatorias' => $reaperturasConvocatorias
        ];
    }

    function __construct()
    {
        $this->modelo = \guarani::rest(rest_reaperturas_convocatorias::class);
    }

    /**
     * POST /reaperturas-convocatorias
     *
     * Recibe reaperturas a convocatorias a becas emitidas por Tehuelche.
     *
     * @see https://repositorio.siu.edu.ar/trac/guarani/wiki/API_3 Caso de Uso
     *
     * @param_body $reapertura_convocatoria ReaperturasConvocatorias [required] Campos de una reapertura a convocatoria.
     *
     * @summary Recibe reaperturas a convocatorias a becas emitidas por Tehuelche.
     *
     * @responses  201 Éxito
     * @responses  400 Errores de validación
     * @responses  403 Error en los permisos
     * @responses  404 No existe el <b>id_convocatoria</b> o el <b>cuil</b>
     * @responses  422 Ya se llevó a cabo una reapertura para el <b>cuil</b> y el <b>id_convocatoria</b> dado
     * @responses  500 Falló
     **/
    function post_list()
    {
        $this->modelo->post(rest::request()->get_body_json());
    }

}