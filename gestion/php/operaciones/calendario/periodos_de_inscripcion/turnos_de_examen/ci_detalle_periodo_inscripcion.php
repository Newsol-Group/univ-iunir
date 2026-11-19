<?php
/**
 * 
 *
 * @package 	Calendario
 * @subpackage 	Turnos de Examen
 *
 */
class ci_detalle_periodo_inscripcion extends ci_impresion_popup
{
	protected $ev_info_llamado_turno;
	protected $ev_info_per_inscripcion;

	protected $s__etiqueta_entidades_subtipos = null;
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA DETALLE -------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	//---- Configuraciones --------------------------------------------------------------
	
	function conf__pant_detalle_periodo(toba_ei_pantalla $pantalla)
	{
		$datos = guarani_datos_impresion::instancia()->get_datos();

		$etiquetas = guarani_etiquetas::get_tabla(g3entidad::get_tabla_tipo($datos['periodo_inscripcion']['entidad_tipo']));
    	switch ($datos['periodo_inscripcion']['tipo']) {
		    case g3entidad::nivel_aplicacion_tipo_entidad:
		    	$this->s__etiqueta_entidades_subtipos = ($datos['periodo_inscripcion']['entidad_tipo'] == g3entidad::tipo_institucion) ? null : $etiquetas->get_plural();
		    	break;
		    case g3entidad::nivel_aplicacion_tipo_subtipo:
		    	$this->s__etiqueta_entidades_subtipos = 'Tipos de '.$etiquetas->get_plural();
		}

		$this->ev_info_llamado_turno()->set_datos($datos['llamado']);
		$this->ev_info_per_inscripcion()->set_datos($datos['periodo_inscripcion']);
	}

	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function ev_info_llamado_turno()
	{
		if (!isset($this->ev_info_llamado_turno)) {
			$this->ev_info_llamado_turno = guarani::ev_info_datos();
			$this->ev_info_llamado_turno->set_controlador($this);
			$this->ev_info_llamado_turno->set_columnas_a_mostrar(array('anio_academico', 'turno_nombre', 'turno_cant_mesas', 'turno_fecha_inicio_visual', 'turno_fecha_fin_visual', 'llamado_nombre', 'llamado_fecha_inicio_visual', 'llamado_fecha_fin_visual'));
			
			$this->ev_info_llamado_turno->set_columnas_fila(array('anio_academico', 'turno_nombre', 'turno_cant_mesas'), 1);
			$this->ev_info_llamado_turno->set_columnas_fila(array('turno_fecha_inicio_visual', 'turno_fecha_fin_visual'), 2);
			$this->ev_info_llamado_turno->set_columnas_fila(array('llamado_nombre', 'llamado_fecha_inicio_visual', 'llamado_fecha_fin_visual'), 3);
			
			$this->ev_info_llamado_turno->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info_llamado_turno->set_etiqueta('turno_nombre', 'Turno');
			$this->ev_info_llamado_turno->set_etiqueta('turno_cant_mesas', 'Mesas');
			
			$this->ev_info_llamado_turno->set_etiqueta('turno_fecha_inicio_visual', 'Inicio Turno');
			$this->ev_info_llamado_turno->set_etiqueta('turno_fecha_fin_visual', 'Fin Turno');
			
			$this->ev_info_llamado_turno->set_etiqueta('llamado_nombre', 'Llamado');
			$this->ev_info_llamado_turno->set_etiqueta('llamado_fecha_inicio_visual', 'Inicio Llamado');
			$this->ev_info_llamado_turno->set_etiqueta('llamado_fecha_fin_visual', 'Fin Llamado');
		}
		return $this->ev_info_llamado_turno;
	}
	
	function ev_info_per_inscripcion()
	{
		if (!isset($this->ev_info_per_inscripcion)) {
			$this->ev_info_per_inscripcion = guarani::ev_info_datos();
			$this->ev_info_per_inscripcion->set_controlador($this);
			$columnas = array('fecha_inicio_visual', 'fin_descripcion', 'fin_bajas_descripcion', 'lista_modalidades', 'lista_ubicaciones', 'nivel_aplicacion');
			
			if (isset($this->s__etiqueta_entidades_subtipos)) {
				array_push($columnas, 'lista_subtipos_entidades');
			}
			
			$this->ev_info_per_inscripcion->set_columnas_a_mostrar($columnas);
			
			$this->ev_info_per_inscripcion->set_etiqueta('fecha_inicio_visual', 'Inicio');
			$this->ev_info_per_inscripcion->set_etiqueta('fin_descripcion', 'Fin');
			$this->ev_info_per_inscripcion->set_etiqueta('fin_bajas_descripcion', 'Fin Bajas');
			$this->ev_info_per_inscripcion->set_etiqueta('lista_modalidades', 'Modalidad');
			$this->ev_info_per_inscripcion->set_etiqueta('lista_ubicaciones', 'Ubicación');
			$this->ev_info_per_inscripcion->set_etiqueta('nivel_aplicacion', 'Nivel de Aplicación');
			
			if (isset($this->s__etiqueta_entidades_subtipos)) {
				$this->ev_info_per_inscripcion->set_etiqueta('lista_subtipos_entidades', $this->s__etiqueta_entidades_subtipos);
			}
		}
		
		return $this->ev_info_per_inscripcion;
	}
	
	//-----------------------------------------------------------------------------------
	//---- vista_xml --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function vista_xml($inicial = false, $xmlns = NULL)
	{
		$this->xml_set_alto_cabecera('3.5cm');

		// ----------------------------------------------------------
		// 	ENCABEZADO
		// ----------------------------------------------------------

		$path_logo = guarani::get_path_logo_impresion_chico();
		$this->xml_set_logo($path_logo);

		//Tabla para el título y logo
		$this->xml_set_tabla_cols();

		//Seteo los datos de la tabla
		$filas[] = array(array('valor'=>$this->xml_imagen($path_logo), 'border'=>'none','number-columns-spanned' => 1),array('valor'=>'', 'border'=>'none','number-columns-spanned' => 2));
		$filas[] = array(array('valor'=>'', 'border'=>'none'),array('valor'=>$this->xml_texto('Detalle Período Inscripción', array('font-weight' => 'bold', 'font-size' => 12, 'text-align' => 'center', 'number-columns-spanned' => 3)),'border'=>'none'));

		//Genero/Obtengo la tabla.
		$cabecera = $this->xml_tabla($filas, false);
		$this->xml_set_cabecera($cabecera);

		// ----------------------------------------------------------
		//	CUERPO
		// ----------------------------------------------------------

		$xml = $this->xml_get_tag_inicio();
		
		if ($this->ev_info_llamado_turno()->get_datos()) {
			$xml .= $this->ev_info_llamado_turno()->vista_xml();
		}
		
		if ($this->ev_info_per_inscripcion()->get_datos()) {
			$xml .= $this->xml_texto('PERÍODO INSCRIPCIÓN', array('font-weight'=>'bold', 'text-decoration'=>'underline', 'font-size'=>12, 'margin-bottom'=>'0.5cm', 'margin-top'=>'1cm', 'text-align'=>'left'));
			$xml .= $this->ev_info_per_inscripcion()->vista_xml();
		}

		$xml .= $this->xml_get_tag_fin();
		return $xml;
	}
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		parent::extender_objeto_js();
		echo "
		{$this->objeto_js}.evt__pdf = function()
		{
			window.open(vinculador.get_url(null,null,'vista_xslfo', null, [{$this->objeto_js}._id])); 
			return false;
		}
		";
	}
}
?>