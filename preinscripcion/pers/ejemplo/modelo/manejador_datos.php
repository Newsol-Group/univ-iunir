<?php
namespace ejemplo\modelo;

use siu\modelo\manejador_datos as manejador_datos_base;

class manejador_datos extends manejador_datos_base
{
	/**
	 * SE EJEMPLIFICA EN ESTA CLASE CÓMO PROCEDER ANTE LA NECESIDAD DE RECOLECTAR
	 * DATOS EXTRA EN LOS FORMULARIOS DE PREINSCRIPCIÓN.
	 * 
	 * No se muestra aquí cómo agregar los campos en los formularios y pagelets. 
	 * Sólo cómo darles tratamiento a nivel de persistencia.
	 * 
	 * Parte del supuesto de haber creado una tabla anexa con los campos propios.
	 * Sugerimos prefijar las tablas propias con el código de institución (de no
	 * conoverlo solicitarlo al SIU; en el ejemplo: Uxyz_ )
	 * 
		CREATE TABLE Uxyz_sga_preinscripcion 
		(	
			id_preinscripcion   Integer,
			campo1				VARCHAR(20),
			campo2				VARCHAR(20)
		);
		ALTER TABLE Uxyz_sga_preinscripcion ADD CONSTRAINT pk_Uxyz_sga_preinscripcion PRIMARY KEY (id_preinscripcion);
	 * 
	 * El campo 'id_preinscripcion' será una Foreign Key al campo homónimo en 
	 * la tabla principal (sga_preinscripcion).
	 * 
		ALTER TABLE Uxyz_sga_preinscripcion
			ADD CONSTRAINT fk__Uxyz_sga_preinscripcion__sga_preinscripcion FOREIGN KEY (id_preinscripcion) 
			REFERENCES sga_preinscripcion (id_preinscripcion) on update restrict on delete restrict;
	 * 
	 * Sugerimos definir un trigger que inserte un registro en la tabla anexa
	 * al momento de dar de alta un registro en la tabla principal (sga_preinscripcion).
	 * De esa manera, nos garantizamos trabajar siempre en modo UPDATE.
	 * 
		CREATE OR REPLACE FUNCTION ftia_Uxyz_sga_preinscripcion () RETURNS trigger AS 
		$BODY$
		BEGIN
		  -- Inserta en la tabla del IPAP
		  INSERT INTO Uxyz_sga_preinscripcion(id_preinscripcion) VALUES (NEW.id_preinscripcion);
		  RETURN NEW;
		END;
		$BODY$
		LANGUAGE 'plpgsql';
	 
		CREATE TRIGGER tia_Uxyz_sga_preinscripcion
			AFTER INSERT ON sga_preinscripcion
			FOR EACH ROW EXECUTE PROCEDURE ftia_Uxyz_sga_preinscripcion();
	 * 
	 */
	
	
	function get_datos_formularios_preinsc()
	{
		$this->datos_formulario = parent::get_datos_formularios_preinsc();
		
		/**
		 * Se obtienen los datos propios almacenados en la tabla anexa y se 
		 * efectúa un merge con los datos por defecto.
		 * 
		$id_preinscripcion = kernel::db()->quote($this->get_id_preinscripcion());
		$sql = "SELECT	campo1,
		 				campo2 
				FROM	Uxyz_sga_preinscripcion
				WHERE	id_preinscripcion = $id_preinscripcion";
		$datos_propios = kernel::db()->consultar_fila($sql);
		
		$this->datos_formulario = array_merge($this->datos_formulario, $datos_propios);
		 * 
		 */
		
		return $this->datos_formulario;
	}
	
	function set_datos_preinscripcion($datos)
	{
		/**
		 * 
		// Completar este arreglo con los campos agregados en la tabla anexa 
		$campos_propios = array('campo1', 'campo2');
		
		// Se guardan en el arreglo $datos_propios los datos que persisten en la tabla anexa, 
		// ya que luego serán filtrados por el método "filtrar_datos_preinscripcion"
		// ( al invocar parent::set_datos_preinscripcion($datos); )
		$datos_propios = array();
		foreach ($campos_propios as $campo) {
			if (isset($datos[$campo])) {
				$datos_propios[$campo] = $datos[$campo];
				unset($datos[$campo]);
			}
		}
		
		if (!empty($datos_propios)) {
			// Se procede a realizar el UPDATE sobre la tabla anexa con los datos propios
			$clave = array('id_preinscripcion' => $this->get_id_preinscripcion());
			abm::modificacion('Uxyz__sga_preinsincripcion', $datos_propios, $clave);
		}
		 * 
		 */
		
		// Se setean los datos por defecto del SIU
		parent::set_datos_preinscripcion($datos);
	}
}
?>