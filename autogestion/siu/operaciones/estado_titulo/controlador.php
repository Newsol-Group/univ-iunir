<?php
namespace siu\operaciones\estado_titulo;

use SIU\Chulupi\kernel;
use siu\extension_kernel\controlador_g3w2;
use siu\modelo\datos\catalogo;

class controlador extends controlador_g3w2
{
    protected $datos_filtro = array('titulos_en_tramite' => "");

    function modelo()
    {
        return null;
    }

    function accion__index()
    {

        $pagelet_certificacion = $this->vista()->pagelet('reporte');
        $parametros = array('alumno' => kernel::persona()->get_id_alumno_activo(),
                'propuesta' => kernel::persona()->get_id_propuesta_activa()
        );

        $titulos_solicitados = catalogo::consultar('alumno','titulo_solicitudes', $parametros);

        foreach ($titulos_solicitados as $key => $titulo)
        {
            $titulos_solicitados[$key]['movimientos'] = catalogo::consultar('alumno', 'titulo_cambios_de_estado', array('nro_solicitud' => $titulo['nro_solicitud']));
            $titulos_solicitados[$key]['datos_solicitud'] = catalogo::consultar('alumno', 'titulo_datos_solicitud', array('nro_solicitud' => $titulo['nro_solicitud']));
        }

        $pagelet_certificacion->data['titulos_solicitados'] = $titulos_solicitados;

    }

}
?>