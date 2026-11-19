<?php 
class cn_ent_planes_reporte extends cn_entidad
{
	protected $dt_pv = 'plan_version';
	protected $dt_pl = 'plan';
	protected $dt_pr = 'propuesta';
	protected $dr_datos = 'datos';
	
	function cargar($id_plan_version)
	{
		$this->dep($this->dr_datos)->cargar(array("plan_version"=>$id_plan_version));
		$this->tabla($this->dt_pv)->set_cursor(0);
		$this->tabla($this->dt_pl)->set_cursor(0);
	}

	function get_plan_version()
	{
		$pv = $this->dep($this->dr_datos)->tabla($this->dt_pv)->get();
		$p = $this->dep($this->dr_datos)->tabla($this->dt_pl)->get();
		$pv['version'] = $pv['nombre'];
		$pv['nombre'] = $p['nombre'];
		foreach($pv as $k=>$v) {
			if((is_null($v) || $v=='') && isset($p[$k])) {
				$pv[$k]="val ant: $v ahora: ".$p[$k];
			}
		}
		return $pv;
	}
}

?>