<?php
namespace siu\modelo\transacciones;

use siu\modelo\datos\catalogo;
use siu\modelo\entidades\rs_consulta;
use SIU\Chulupi\kernel;

class zona_examenes extends transaccion
{
	//--------------------------------------------------------------------------
	//---- INFO
	//--------------------------------------------------------------------------

        function get_parametros(){

            $filtrar_por = kernel::sesion()->esta_seteada('filtrar_por')? kernel::sesion()->get('filtrar_por') : null;
            $fecha_examen = null;
            $fecha_examen_desde = null;
            $fecha_examen_hasta = null;
            if ($filtrar_por == 'f') {
                    $fecha_examen = kernel::sesion()->esta_seteada('date_time_examen')? kernel::sesion()->get('date_time_examen') : null;
                    $fecha_examen_desde = null;
                    $fecha_examen_hasta = null;
            } elseif ($filtrar_por == 'r') {
                    $fecha_examen = null;
                    $fecha_examen_desde = kernel::sesion()->esta_seteada('date_time_examen_desde')? kernel::sesion()->get('date_time_examen_desde') : null;
                    $fecha_examen_hasta = kernel::sesion()->esta_seteada('date_time_examen_hasta')? kernel::sesion()->get('date_time_examen_hasta') : null;
            }

            // Para los combos de la zona
            $parametros = [
                'persona' => $this->persona,
                'filtrar_por' => $filtrar_por,
                'fecha_examen' => $fecha_examen,
                'fecha_examen_desde' => $fecha_examen_desde,
                'fecha_examen_hasta' => $fecha_examen_hasta,
            ];

            return $parametros;
        }

	function info__examenes()
	{ 
		$parametros = $this->get_parametros();

		$rs = new rs_consulta('zona_examenes', 'lista_mesas_examen_docente', $parametros);
		$rs->set_columna_id('llamado_mesa');
		$rs->agregar_entidad('llamado_mesa');
		return $rs;
	}

        function invalidar_examenes()
	{
                $parametros = $this->get_parametros();
		catalogo::limpiar_cache('zona_examenes', 'lista_mesas_examen_docente', $parametros);
	}

	function info__mesa_cabecera($datos)
	{
		if (!isset($this->request_cabecera)) {
			$parametros = array('llamado_mesa' => $datos['llamado_mesa']);
			$this->request_cabecera = catalogo::consultar('carga_notas_mesa_examen', 'get_datos_encabezado_mesa', $parametros);
		}
		return $this->request_cabecera;
	}

	function info__mesa_zona($seleccion)
	{
		$data = catalogo::consultar('docente', 'datos_mesa_examen', array('llamado_mesa' => $seleccion['llamado_mesa']));
		return $data;
	}

}