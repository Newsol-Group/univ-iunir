<?php
/**
 * 
 * @package Comprobantes
 */
class comprobantes_nucleo
{
	
	// Tipos de Comprobantes 
	const tipo_comprobante_insc_act 		= 'IC';
	const tipo_comprobante_baja_insc_act 	= 'BIC';
	const tipo_comprobante_rec_insc_act		= 'RIC';
	const tipo_comprobante_insc_exa			= 'IE';
	const tipo_comprobante_baja_insc_exa 	= 'BIE';
	const tipo_comprobante_rec_insc_exa		= 'RIE';
	const tipo_comprobante_insc_prop 		= 'IP';
	const tipo_comprobante_baja_insc_prop	= 'BIP';
	const tipo_comprobante_reinsc_prop 		= 'RP';
	const tipo_comprobante_sol_constancia	= 'SCC';
	
	const tipo_comprobante_insc_act_descr		= 'Inscripción a actividad';
	const tipo_comprobante_baja_insc_act_descr	= 'Baja inscripción a actividad';
	const tipo_comprobante_rec_insc_act_descr	= 'Rechazo de inscripción a actividad';
	const tipo_comprobante_insc_exa_descr		= 'Inscripción a examen';
	const tipo_comprobante_baja_insc_exa_descr 	= 'Baja inscripción a examen';
	const tipo_comprobante_rec_insc_exa_descr	= 'Rechazo de inscripción a examen';
	const tipo_comprobante_insc_prop_descr 		= 'Inscripción a propuesta';
	const tipo_comprobante_baja_insc_prop_descr	= 'Baja de inscripción a propuesta';
	const tipo_comprobante_reinsc_prop_descr 	= 'Reinscripción a propuesta';
	const tipo_comprobante_sol_constancia_descr	= 'Solicitud de constancias';
	
	static protected $instancia;

	static function instancia()
	{
		if (!isset(self::$instancia)) {
			self::$instancia = new comprobantes();	
		}
		return self::$instancia;	
	}

	
	private function __construct()
	{
	
	}
	
	/**
	 * Retorna las opciones Inscripción a Propuesta / Baja inscripción a Propuesta / Reinscripción a Propuesta
	 * Inscripción a Actividad / Baja inscripción a Actividad / Inscripción a Examen / Baja inscripción a Examen
     */
    static function get_tipos_comprobantes()
    {
		return array(	array('valor' => self::tipo_comprobante_baja_insc_act,	'descr' => 'Baja inscripción a Actividad'),
						array('valor' => self::tipo_comprobante_baja_insc_exa,	'descr' => 'Baja inscripción a Examen'),
						array('valor' => self::tipo_comprobante_insc_act,		'descr' => 'Inscripción a Actividad'),
						array('valor' => self::tipo_comprobante_insc_exa,		'descr' => 'Inscripción a Examen'),
						array('valor' => self::tipo_comprobante_insc_prop,		'descr' => 'Inscripción a Propuesta'),
						array('valor' => self::tipo_comprobante_reinsc_prop,	'descr' => 'Reinscripción a Propuesta'));
    }
    	
}
?>