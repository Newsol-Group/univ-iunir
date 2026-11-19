<?php
/**
 *
 *
 * @package		Reconocimiento de Actividades
 * @subpackage	Otorgar equivalencias
 * 
 */
class form_tipo_equiv extends guarani_pers_ei_formulario
{
	/**
	 * Permite modificar la forma en que se grafica el formulario, por defecto un ef sobre el otro
	 */
	 function generar_layout()
    {
        echo '<fieldset title="Alumno"><legend>Alumno</legend>';
        echo '<table cellspacing="1" cellpadding="1" border="0" align="center" width="100%">';
            echo '<tbody>';
                echo '<tr>';
                    echo '<td>';
					$this->generar_html_ef('alumno_nombre', '130px');
                    echo '</td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td>';
					$this->generar_html_ef('alumno_legajo', '130px');
                    echo '</td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td colspan=2>';
                    $this->generar_html_ef('alumno_id', '130px');
                    echo '</td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td>';
                    $this->generar_html_ef('alumno_propuesta', '130px');
                    echo '</td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td>';
                    $this->generar_html_ef('alumno_plan', '130px');
                    echo '</td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td>';
                    $this->generar_html_ef('alumno_version', '130px');
                    echo '</td>';
                echo '</tr>';
            echo '</tbody>';
        echo '</table>';
        echo '</fieldset>';
        echo '<p> </p>';
        echo '<fieldset title="Tipo de Equivalencia"><legend>Tipo de Equivalencia</legend>';
        echo '<table cellspacing="1" cellpadding="1" border="0" align="center" width="100%">';
            echo '<tbody>';
                echo '<tr>';
                    echo '<td>';
                    $this->generar_html_ef('fecha', '210px');
                    echo '</td>';
                    echo '<td>';
                    $this->generar_html_ef('documento', '150px');
                    echo '</td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td colspan="2">';
                    $this->generar_html_ef('observaciones', '210px');
                    echo '</td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td colspan="2">';
                    $this->generar_html_ef('tipo_equivalencia', '210px');
                    echo '</td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td colspan="2">';
                    $this->generar_html_ef('institucion', '210px');
                    echo '</td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td colspan="2">';
                    $this->generar_html_ef('convenio', '210px');
                    echo '</td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td colspan="2">';
                    $this->generar_html_ef('responsable_academica', '210px');
                    echo '</td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td colspan="2">';
                    $this->generar_html_ef('propuesta', '210px');
                    echo '</td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td colspan="2">';
                    $this->generar_html_ef('propuesta_cambio', '210px');
                    echo '</td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td colspan="2">';
                    $this->generar_html_ef('plan', '210px');
                    echo '</td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td colspan="2">';
                    $this->generar_html_ef('plan_cambio', '210px');
                    echo '</td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td colspan="2">';
                    $this->generar_html_ef('plan_version', '210px');
                    echo '</td>';
                echo '</tr>';
            echo '</tbody>';
        echo '</table>';
        echo '</fieldset>';
        echo '<p> </p>';
    }
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$origen_int_libre = equivalencia::origen_int_libre;
		$origen_int_prop = equivalencia::origen_int_prop;
		$origen_int_plan = equivalencia::origen_int_plan;
		$origen_int_version = equivalencia::origen_int_version;
		$origen_ext_libre = equivalencia::origen_ext_libre;
		$origen_ext_pase = equivalencia::origen_ext_pase;
		$origen_ext_convenio = equivalencia::origen_ext_convenio;

		$entre_prop = equivalencia::matriz_alcance_entre_propuestas;
		$entre_planes = equivalencia::matriz_alcance_entre_planes_misma_prop;
		$entre_ver = equivalencia::matriz_alcance_entre_versiones;

		$msj_rep_prop = guarani_mensajes::get('800EQU_validar_origen_destino', array('La propuesta'));
		$msj_rep_plan = guarani_mensajes::get('800EQU_validar_origen_destino', array('El plan'));
		$msj_rep_ver = guarani_mensajes::get('800EQU_validar_origen_destino', array('La versión'));
		$msj_obligatorio = guarani_mensajes::get('dato_faltante');

		$date = guarani_fecha::get_hoy_js();
		$msj_fecha = guarani_mensajes::get('800_control_fechas', array('de Otorgamiento', 'mayor', 'actual'));

		echo "
		var cod_propuesta = 0;
		var cod_plan = 0;
		var cod_plan_version = 0;
		var hoy = new $date;
				
		//---- Procesamiento de EFs --------------------------------
		
		/**
		 * Método que se invoca al cambiar el valor del ef en el cliente
		 * Se dispara inicialmente al graficar la pantalla, enviando en true el primer parámetro
		 */
		{$this->objeto_js}.evt__alumno_nombre__procesar = function(es_inicial)
		{		
			this.ef('alumno_id').desactivar();
			this.ef('alumno_legajo').desactivar();
			this.ef('alumno_propuesta').desactivar();
			this.ef('alumno_plan').desactivar();
			this.ef('alumno_version').desactivar();
			if ((es_inicial) && (!this.ef('tipo_equivalencia').tiene_estado()))	{
				// Se desactivan los campos correspondientes
				this.ef('tipo_equivalencia').desactivar();
				this.ef('fecha').desactivar();
				this.ef('observaciones').desactivar();
				this.ef('documento').desactivar();
			} else {
				// Se eligió al alumno, entonces se activa la sección de Tipo de Equivalencias para edición
				this.ef('tipo_equivalencia').activar();
				this.ef('fecha').activar();
				this.ef('observaciones').activar();
				this.ef('documento').activar();
				// Se completan los valores de los campos correspondientes
				var clave = this.ef('alumno_nombre').valor();
				var array = clave.split('||');
				var alu = 0;
				if (array[1] != undefined) {
					// Valido si cambio el estado y es distinto al original y confirmo si desea perder el resto de los datos
					if (!es_inicial) {
						alu = array[0];
						this.controlador.ajax('validar_cambio_alumno', [alu], this, this.respuesta_cambio_alumno);
					}
					cod_propuesta = array[1];
					cod_plan = array[2];
					cod_plan_version = array[3];
					this.controlador.ajax('datos_alumno', [array[0]], this, this.respuesta_datos_alumno);
				}
			}		
		}
				
		/**
		 * Método que se invoca al cambiar el valor del ef en el cliente
		 * Se dispara inicialmente al graficar la pantalla, enviando en true el primer parámetro
		 * Se determina si se muestran y son obligatorios los campos Convenio, Plan, Version, Propuesta e Inst. Externas
		 */
		{$this->objeto_js}.evt__tipo_equivalencia__procesar = function(es_inicial)
		{
			// Se ocultan los campos del form (luego, de acuerdo al valor elegido se habilitarán los que correspondan
			this.ef('propuesta').ocultar();
			this.ef('propuesta_cambio').ocultar();
			this.ef('plan').ocultar();
			this.ef('plan_cambio').ocultar();
			this.ef('plan_version').ocultar();
			this.ef('institucion').ocultar();
			this.ef('responsable_academica').ocultar();
			this.ef('convenio').ocultar();
			if (!this.ef('tipo_equivalencia').tiene_estado()) return;
			var estado = this.ef('tipo_equivalencia').get_estado();
									
			// Valido si cambio el estado y es distinto al original y confirmo si desea perder el resto de los datos
			if (!es_inicial) {
				this.controlador.ajax('validar_cambio_tipo', [estado], this, this.respuesta_cambio_tipo);				
			}
		
			if (estado == '$origen_ext_convenio' || estado == '$origen_ext_pase' || estado == '$origen_ext_libre') { // Equivalencias EXTERNAS.
				this.ef('institucion').mostrar();
				this.ef('responsable_academica').mostrar();
				if (estado == '$origen_ext_convenio') {
					this.ef('convenio').mostrar();
				}
				this.ef('propuesta').ocultar();
				this.ef('propuesta').resetear_estado();
				this.ef('propuesta_cambio').ocultar(true);
				this.ef('plan').ocultar();
				this.ef('plan').resetear_estado();
				this.ef('plan_cambio').ocultar(true);
				this.ef('plan_version').ocultar();
				this.ef('plan_version').resetear_estado();
			} else { // Equivalencias INTERNAS.
				this.ef('institucion').ocultar();
				this.ef('institucion').resetear_estado();
				this.ef('responsable_academica').ocultar();
				this.ef('responsable_academica').resetear_estado();
				this.ef('convenio').ocultar();
				this.ef('convenio').resetear_estado();
				if (estado == '$origen_int_libre') {
					this.ef('propuesta').ocultar();
					this.ef('propuesta').resetear_estado();
					this.ef('propuesta_cambio').ocultar(true);
					this.ef('plan').ocultar();
					this.ef('plan').resetear_estado();
					this.ef('plan_cambio').ocultar(true);
					this.ef('plan_version').ocultar();
					this.ef('plan_version').resetear_estado();
				} else { // Cursada simultánea, cambio de versión, plan o propuesta.
					if (estado == '$origen_int_prop') {
						this.ef('propuesta_cambio').mostrar();
						this.ef('plan_cambio').mostrar();
						this.ef('propuesta').ocultar(true);
					} else {
						this.ef('propuesta').mostrar();
						this.ef('propuesta_cambio').ocultar(true);
					}
					if (estado == '$origen_int_plan') {
						this.ef('plan').mostrar();
					}
					if (estado == '$origen_int_version') {
						this.ef('plan').mostrar();
						this.ef('plan_version').mostrar();
					}
				}
			}
			
		}
		
        {$this->objeto_js}.respuesta_cambio_alumno = function(respuesta)
        {
            if (typeof respuesta == 'object') {
                if (confirm(respuesta['mensaje'])) {
                	this.set_evento(new evento_ei('cambio'), true);
              	} else {
					this.ef('alumno_id').set_estado(respuesta['alumno_id']);
					this.ef('alumno_propuesta').set_estado(respuesta['alumno_propuesta']);
					this.ef('alumno_plan').set_estado(respuesta['alumno_plan']);
					this.ef('alumno_version').set_estado(respuesta['alumno_version']);
					this.ef('alumno_nombre').set_estado(respuesta['alumno_nombre_clave'], respuesta['alumno_nombre_descr']);
                    return false;
                }
            }
        }
        
        {$this->objeto_js}.respuesta_datos_alumno = function(respuesta)
        {
            if (typeof respuesta == 'object') {
				if (respuesta['alumno_legajo'] != undefined) {
					this.ef('alumno_legajo').set_estado(respuesta['alumno_legajo']);
				} else {
					this.ef('alumno_legajo').resetear_estado();
				}
				this.ef('alumno_id').set_estado(respuesta['alumno_id']);
				this.ef('alumno_propuesta').set_estado(respuesta['alumno_propuesta']);
				this.ef('alumno_plan').set_estado(respuesta['alumno_plan']);
				this.ef('alumno_version').set_estado(respuesta['alumno_version']);
            }
        }

        {$this->objeto_js}.respuesta_cambio_tipo = function(respuesta)
        {
            if (typeof respuesta == 'object') {
                if (confirm(respuesta['mensaje'])) {
                    this.set_evento(new evento_ei('cambio'), true);
                } else {
					this.ef('tipo_equivalencia').set_estado(respuesta['estado']);
                    return false;
                }
            }
        }
		
		/**
		 * Método que se invoca al cambiar el valor del ef en el cliente
		 * Se dispara inicialmente al graficar la pantalla, enviando en true el primer parámetro
		 */
		{$this->objeto_js}.evt__propuesta__procesar = function(es_inicial)
		{
			var estado = this.ef('tipo_equivalencia').get_estado();
			if ((!es_inicial) && (estado == '$origen_int_prop')) {
				var datos = Array();
				datos[0] = '$entre_prop';
				datos[1] = cod_plan_version;
				datos[2] = this.ef('propuesta').get_estado();
				this.ef('plan_version').desactivar();
				this.ef('plan').desactivar();
			}
		}
		
		/**
		 * Método que se invoca al cambiar el valor del ef en el cliente
		 * Se dispara inicialmente al graficar la pantalla, enviando en true el primer parámetro
		 */
		{$this->objeto_js}.evt__plan__procesar = function(es_inicial)
		{
			var estado = this.ef('tipo_equivalencia').get_estado();
			if ((!es_inicial) && (estado == '$origen_int_plan')) {
				var datos = Array();
				datos[0] = '$entre_planes';
				datos[1] = cod_plan_version;
				datos[2] = this.ef('propuesta').get_estado();
				datos[3] = this.ef('plan').get_estado();
				this.ef('plan_version').desactivar();
			}
		}
		
		/**
		 * Método que se invoca al cambiar el valor del ef en el cliente
		 * Se dispara inicialmente al graficar la pantalla, enviando en true el primer parámetro
		 */
		{$this->objeto_js}.evt__plan_version__procesar = function(es_inicial)
		{
			var estado = this.ef('tipo_equivalencia').get_estado();
			if ((!es_inicial) && (estado == '$origen_int_version')) {
				var datos = Array();
				datos[0] = '$entre_ver';
				datos[1] = cod_plan_version;
				datos[2] = this.ef('propuesta').get_estado();
				datos[3] = this.ef('plan').get_estado();
				datos[4] = this.ef('plan_version').get_estado();
			}
		}
		
		//---- Validacion de EFs -----------------------------------
		
		{$this->objeto_js}.evt__fecha__validar = function()
		{
			var ok = true;
			
			if(this.ef('fecha').tiene_estado()) {
				var fecha_otorgamiento = this.ef('fecha').fecha();
				if (fecha_otorgamiento > hoy) {
					this.ef('fecha').set_error('$msj_fecha');
					ok = false;
				}
			}
			
			return ok;
		}
		
		{$this->objeto_js}.evt__plan__validar = function()
		{
			var ok = true;
			
			if(!this.ef('plan').es_oculto() && !this.ef('plan').tiene_estado()) {
				this.ef('plan').set_error('$msj_obligatorio');
				ok = false;
			}
			
			return ok;
		}
		
		{$this->objeto_js}.evt__plan_version__validar = function()
		{
			var ok = true;
			
			if(!this.ef('plan_version').es_oculto() && !this.ef('plan_version').tiene_estado()) {
				this.ef('plan_version').set_error('$msj_obligatorio');
				ok = false;
			}
			
			return ok;
		}
		
		{$this->objeto_js}.evt__cascadas_fin = function() {
			{$this->objeto_js}.ef('alumno_legajo').desactivar();
			{$this->objeto_js}.ef('alumno_id').desactivar();
			{$this->objeto_js}.ef('alumno_propuesta').desactivar();
			{$this->objeto_js}.ef('alumno_plan').desactivar();
			{$this->objeto_js}.ef('alumno_version').desactivar();
		}
		";
	}

}
?>