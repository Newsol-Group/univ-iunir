<?php
/**
 *
 * 
 * @package		Egresados
 * @subpackage 	Reportes
 * 
 */
class ci_rep_cambios_estado_tramites_certificacion extends ci_impresion 
{
	// Componente visual de información
	protected $ev_info;
	
	function ini()
	{
		parent::ini();
		toba::memoria()->desactivar_reciclado();
		$this->s__titulo = 'Estado de Trámites de Certificación';
	}
	
	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__pant_inicial()
	{
		$nro_solicitud = toba::db()->quote($this->controlador()->get_tramite_seleccionado());
		$where = "sga_certificados_otorg.nro_solicitud = $nro_solicitud";
		$datos = toba::consulta_php('co_tramites_certificacion')->get_listado($where);
		$this->ev_info()->set_datos(current($datos));
	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('nro_solicitud', 'apellido_y_nombres', 'documento_principal', 'certificado_nombre', 'plan_nombre'));
			$this->ev_info->set_columnas_fila(array('nro_solicitud'), 1);
			$this->ev_info->set_columnas_fila(array('apellido_y_nombres', 'documento_principal'), 2);
			$this->ev_info->set_columnas_fila(array('certificado_nombre', 'plan_nombre'), 3);
			$this->ev_info->set_max_columnas_por_fila(2);
			$this->ev_info->set_etiqueta('nro_solicitud', 'Trámite Nro.');
			$this->ev_info->set_etiqueta('apellido_y_nombres', 'Alumno');
			$this->ev_info->set_etiqueta('documento_principal', 'Identificación');
			$this->ev_info->set_etiqueta('certificado_nombre', 'Certificado');
			$this->ev_info->set_etiqueta('plan_nombre', 'Plan');
		}
		return $this->ev_info;
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_msg_eof($tabla=null)
	{
		$tabla = 'sga_certificados_otorg_cmb';
		$indx_msg = '800_eof_cuadro';
		$etiquetas = guarani_etiquetas::get_tabla($tabla);
		return guarani_mensajes::get($indx_msg, array($etiquetas->get_enf('plural')));
	}

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$nro_solicitud = $this->controlador()->get_tramite_seleccionado();
		$datos = toba::consulta_php('co_tramites_certificacion')->get_cambios_estado($nro_solicitud);
		$cuadro->set_datos($datos);
		$cuadro->set_eof_mensaje($this->get_msg_eof());
		
		if (guarani_arreglos::count($datos) == 0) {
			$this->pantalla('pant_inicial')->evento('pdf')->desactivar();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- vista_xml --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function vista_xml($inicial = false, $xmlns = NULL)
	{
		$this->xml_set_alto_cabecera('5cm');

		// ----------------------------------------------------------
		// 	ENCABEZADO
		// ----------------------------------------------------------

		$path_logo = guarani::get_path_logo_impresion_chico();
		$this->xml_set_logo($path_logo);

		//Tabla para el título y logo
		$this->xml_set_tabla_cols();

		//Seteo los datos de la tabla
		$filas[] = array(array('valor'=>$this->xml_imagen($path_logo), 'border'=>'none','number-columns-spanned'=>2),array('valor'=>'', 'border'=>'none','number-columns-spanned'=>2));
		$filas[] = array(array('valor'=>$this->xml_texto($this->s__titulo, array('font-weight'=>'bold', 'font-size'=>12, 'text-align'=>'center')),'border-bottom'=>'solid black', 'text-align'=>'center', 'number-columns-spanned'=>4));

		//Genero/Obtengo la tabla.
		$cabecera = $this->xml_tabla($filas, false);

		//Tabla que reemplaza a la cabecera
		$this->xml_set_tabla_cols();
		
		$nro_solicitud = toba::db()->quote($this->controlador()->get_tramite_seleccionado());
		$where = "sga_certificados_otorg.nro_solicitud = $nro_solicitud";
		$datos = current(toba::consulta_php('co_tramites_certificacion')->get_listado($where));
		
		$filas = array();
		$filas[] = array(
						array('valor'=>'TRÁMITE NRO.: ', 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>$datos['nro_solicitud'], 'number-columns-spanned'=>'2', 'border'=>'none', 'font-weight'=>'normal', 'text-align'=>'left')
						);
		$filas[] = array(						
						array('valor'=>'ALUMNO: ', 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>$datos['apellido_y_nombres'], 'border'=>'none', 'font-weight'=>'normal', 'text-align'=>'left'),
						array('valor'=>'IDENTIFICACIÓN: ', 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>$datos['documento_principal'], 'border'=>'none', 'font-weight'=>'normal', 'text-align'=>'left')
						);
		$filas[] = array(
						array('valor'=>'CERTIFICADO: ', 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>$datos['certificado_nombre'], 'border'=>'none', 'font-weight'=>'normal', 'text-align'=>'left'),
						array('valor'=>'PLAN: ', 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>$datos['plan_nombre'], 'border'=>'none', 'font-weight'=>'normal', 'text-align'=>'left'),
						);

		//Se genera la segunda tabla.
		$cabecera .= $this->xml_tabla($filas, false);
		$this->xml_set_cabecera($cabecera);

		// ----------------------------------------------------------
		//	CUERPO
		// ----------------------------------------------------------

		$xml = $this->xml_get_tag_inicio();
		if ($this->dep('cuadro')->get_datos()) {
			$xml .= $this->dep('cuadro')->vista_xml(false, $xmlns);
		}

		$xml .= $this->xml_get_tag_fin();
		return $xml;
	}
}
?>