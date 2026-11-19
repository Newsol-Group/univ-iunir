<?php
namespace siu\modelo\transacciones;

use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;

class constancias_cursos extends transaccion{

    protected $constancias = null;

    function info__constancias(){

        if(!isset($this->constancias)){

            $constancias = catalogo::consultar('cursos', 'lista_constancias', array('persona' => $this->persona));

            $fecha_actual = new \DateTime('today');

            foreach($constancias as $key => $col){

                $constancias[$key]['fecha_solicitud'] = date('d/m/Y', strtotime($col['fecha_solicitud']));

                $certificado_vencido = false;

                if($col['fecha_fin_vigencia']){
                    $fecha_fin_vigencia = \DateTime::createFromFormat('!Y-m-d', $col['fecha_fin_vigencia']);
                    $constancias[$key]['fecha_fin_vigencia'] = $fecha_fin_vigencia->format('d/m/Y');

                    if($fecha_fin_vigencia < $fecha_actual){
                        $certificado_vencido = true;
                    }
                }

                //Si permite descarga y la interfaz es autogestion y el estado es online y no esta vencido muestro el boton para descargar certificado...
                $permite_descargar = $col['permite_descargar'] && ($col['interfaz'] == \comunes::interfaz_autogestion) && ($col['estado'] == \constancias::estado_solicitud_online) && (!$certificado_vencido);
                $constancias[$key]['permite_descargar'] = $permite_descargar;

                if($permite_descargar){
                    $operacion = kernel::ruteador()->get_id_operacion();
                    $constancias[$key]['url_descarga'] = kernel::vinculador()->crear($operacion, 'descargar_comprobante', $col[catalogo::id]);
                }

            }

            $this->constancias = $constancias;
        }
        klog2('transaccion: constancias_cursos/info__constancias', $this->constancias);
        return $this->constancias;
    }

}