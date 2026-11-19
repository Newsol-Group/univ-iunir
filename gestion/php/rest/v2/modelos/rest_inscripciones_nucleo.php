<?php

namespace SIU\Guarani\php\rest\v2\modelos;

use SIUToba\rest\lib\rest_error;
use SIUToba\rest\lib\rest_filtro_sql;
use SIUToba\rest\lib\rest_hidratador;
use SIUToba\rest\rest;
use SIU\Guarani\php\rest\v2\inscripciones\recurso_inscripciones;

class rest_inscripciones_nucleo
{
    /**
     * @var \co_inscripciones_cursadas
     */
    protected $modelo;

    function __construct()
    {
        $this->modelo = \guarani::co('co_inscripciones_cursadas');
    }

    protected function get_modelo($nombre)
    {
        $modelos = recurso_inscripciones::_get_modelos();
        return $modelos[$nombre];
    }

    /**
     * @return rest_filtro_sql
     */
    function get_filtro_base(){
        $filtro = new rest_filtro_sql();
        return $filtro;
    }

    function get($id_inscripcion)
    {
        $id_inscripcion = trim($id_inscripcion);
        if($id_inscripcion == "") throw new rest_error(400, "Parámetro 'id_inscripcion' requerido");

        if(!preg_match('/^[0-9]+$/', $id_inscripcion)) throw new rest_error(400, "Parámetro 'id_inscripcion' debe ser un número entero");

        $where = ' inscripcion = '.quote($id_inscripcion);
        $rs = $this->modelo->get_listado_vista($where, false, '', true, true);

        $inscripcion = $rs[0] ?? null;

        if ($inscripcion) {
            $inscripcion = rest_hidratador::hidratar($this->get_modelo('Inscripcion'), array($inscripcion));
            return $inscripcion[0];
        } else {
            throw new rest_error(404, "La inscripción '{$id_inscripcion}' no existe");
        }
    }
	
	function get_list()
    {
		
		$comision			= trim(rest::request()->get('comision'));
		$nro_transaccion	= trim(rest::request()->get('nro_transaccion'));
		$historicas			= trim(rest::request()->get('historicas', 'false'));
        /**
         * Aceptadas y pendientes (AP), Rechazadas y dadas de baja (RB) o Todas las Inscripciones (T). Por defecto trae todas (T).
         *
         * @var string
         */
        $estado_inscripcion = trim(rest::request()->get('estado_inscripcion', 'T'));

        if(!in_array($historicas, ['true', 'false'])) {
			throw new rest_error(400, "Parámetro 'historicas' debe ser 'true' o 'false'");
		}
        
        if (!in_array($estado_inscripcion, ['AP', 'RB', 'T'])) {
            throw new rest_error(400, "Parámetro 'estado_inscripcion' debe ser 'AP', 'RB' o 'T'");
        }

        // Para inscripciones históricas debe filtra por 'comision' o 'nro_transaccion' por cuestiones de performance  
		if(filter_var($historicas, FILTER_VALIDATE_BOOLEAN) && (empty($comision) && empty($nro_transaccion))){
			throw new rest_error(400, "Para inscripciones históricas debe filtra por 'comision' o 'nro_transaccion' por cuestiones de performance");
		}
		
		$where = '';
		$and = false;
        if(!empty($comision)) {
			if (!preg_match('/^[0-9]+$/', $comision)) {
				throw new rest_error(400, "Parámetro 'comision' debe ser un número entero");
			} else {
				$where .= ' sga_comisiones.comision = '.quote($comision);
				$and = true;
			}
		}
		
        if(!empty($nro_transaccion)) {
			if (!preg_match('/^[0-9]+$/', $nro_transaccion)) {
				throw new rest_error(400, "Parámetro 'nro_transaccion' debe ser un número entero");
			} else { 
				if ($and) {
					$where .= ' AND ';
				}
				$where .= ' nro_transaccion = '.quote($nro_transaccion);	
			}
		}
		
        // $historicas es un string con el valor 'true' (Inscripciones históricas) o 'false' (Inscripciones vigentes)
        $solo_vigentes = ($historicas == 'false');

        $query_aceptadas_pendientes = true;
        $query_rechazadas_bajas = true;
        switch ($estado_inscripcion) {
            case 'AP':
                $query_aceptadas_pendientes = true;
                $query_rechazadas_bajas = false;
                break;
            case 'RB':
                $query_aceptadas_pendientes = false;
                $query_rechazadas_bajas = true;
                break;
        }

        $rs = $this->modelo->get_listado_vista($where, $solo_vigentes, '', $query_aceptadas_pendientes, $query_rechazadas_bajas);

        $datos = rest_hidratador::hidratar($this->get_modelo('Inscripcion'), $rs);
		return $datos;
    }
	
}