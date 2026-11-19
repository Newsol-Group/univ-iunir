<?php
/**
 *
 * 
 * @package		Unidades de Gestión
 * @subpackage	Actualizaciones
 * 
 */
class ci_nav_libros_actas extends ci_navegacion
{
	//-----------------------------------------------------------------------------------
	//---- PANTALLA seleccion
	//-----------------------------------------------------------------------------------

	function conf__seleccion(toba_ei_pantalla $pantalla)
	{
		$this->cn()->resetear_atributos_parametros();
	}
	
	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('actividad')->set_condicion_fija('contiene');
	}

	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			$filtro = 'TRUE';
			if ((isset($datos['propuesta']))) {
				$propuesta = $datos['propuesta']['valor'];
				$filtro .= ' AND EXISTS (SELECT 1 FROM sga_libros_actas_propuesta as p WHERE p.libro = sga_libros_actas.libro AND p.propuesta ';
				if ($datos['propuesta']['condicion'] == 'es_igual_a') {
					$filtro .= " = $propuesta )";
				} else {
					$filtro .= " <> $propuesta )";
				}
				$this->dep('filtro')->columna('propuesta')->resetear_estado();
			}

			if ((isset($datos['actividad']))) {
				$expresion_nombre = $this->dep('filtro')->get_expresion('sga_elementos', 'nombre', $datos['actividad']['valor'], $datos['actividad']['condicion_cs'], $datos['actividad']['condicion_as']);
				$expresion_codigo = $this->dep('filtro')->get_expresion('sga_elementos', 'codigo', $datos['actividad']['valor'], $datos['actividad']['condicion_cs'], $datos['actividad']['condicion_as']);
				$filtro .= " AND EXISTS (SELECT 1
										 FROM 	sga_libros_actas_actividad as a,
										 		sga_elementos
										 WHERE 	a.libro = sga_libros_actas.libro
										 AND	a.elemento = sga_elementos.elemento
										 AND   ($expresion_nombre OR $expresion_codigo))";
				$this->dep('filtro')->columna('actividad')->resetear_estado();
			}

			if ((isset($datos['ubicacion']))) {
				$ubicacion = $datos['ubicacion']['valor'];
				$filtro .= ' AND EXISTS (SELECT 1 FROM sga_libros_actas_ubicacion as u WHERE u.libro = sga_libros_actas.libro AND u.ubicacion ';
				if ($datos['ubicacion']['condicion'] == 'es_igual_a') {
					$filtro .= " = $ubicacion )";
				} else {
					$filtro .= " <> $ubicacion )";
				}
				$this->dep('filtro')->columna('ubicacion')->resetear_estado();
			}

			if ((isset($datos['origen']))) {
				$origen = toba::db()->quote($datos['origen']['valor']);
				$filtro .= ' AND EXISTS (SELECT 1 FROM sga_libros_actas_alcance as a WHERE a.libro = sga_libros_actas.libro AND a.origen ';
				if ($datos['origen']['condicion'] == 'es_igual_a') {
					$filtro .= " = $origen )";
				} else {
					$filtro .= " <> $origen )";
				}
				$this->dep('filtro')->columna('origen')->resetear_estado();
			}

			$this->s__filtro = "$filtro AND {$this->dep('filtro')->get_sql_where()} ";
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA edicion
	//-----------------------------------------------------------------------------------

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		parent::conf__edicion($pantalla);
		$mensaje = "Si se definen alcances por propuestas, las mismas se usarán para obtener los valores específicos de los parámetros consumidos por esta operación. Si no, se usarán los valores definidos <strong>Para toda la Institución</strong>.";
		$pantalla->agregar_notificacion($mensaje);
	}

}
?>