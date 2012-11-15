<?php

class dbmaker {
	/**
	* How to modify table name:
	* - Table rename: OK (But make sure to check another key)
	* - Table order change: PROHIBITED
	* - Table add: OK
	* - Field rename: OK
	* - Field reorder: PROHIBITED
	* - Field add: OK
	* - Field behaviour change: PROHIBITED
	*/
	var $tables = array(
		// I don't have any idea, but please do not use the zero
		// Index of table
		"dummy" => "NOT AVAILABLE",
		// Fill the table name
		"user" =>
			// And the table fields
			array("username", "userpassword", "email", "description"),
		"posts" =>
			array("uid", "post-title", "post-")
	);

	// The Things table, please check your configuration correspondently
	var $things_table = "things";
	// The Metas table, please check your configuration correspondently
	var $metas_table  = "things_meta";

	var $CI;
	function __get($key) {
		return $this->CI->$key;
	}
	function __construct() {
		$this->CI =& get_instance();
	}

	// Todo: set key without value (empty definition of dictionary term)
	function set($table, $key, $values) {
		// TODO:
		// Yeah, transaction is NOT here comrade. This
		// gonna be inside to do list
		
		// Sanitize keys
		$fields = $this->tables[$table];
		$fields_number_key = array_combine(array_values($fields), array_keys($fields));
		if (is_array($values) != TRUE) $values = array($fields[1] => $values);
		
		if (!$this->db->where("kunci", $key)
					  ->where("jenis", $this->_table_index($table))
					  ->get($this->things_table)->row()) {
			// do insert, then get the id
			$isi = isset($values[$fields[1]]) ? $values[$fields[1]] : "";
			$this->db->insert($this->things_table, array(
												"jenis" => $this->_table_index($table), 
												"kunci" => $key, 
												"isi" => $isi));
			$id = $this->db->where("kunci", $key)->where("jenis", $this->_table_index($table))->get($this->things_table)->row()->id;
		} else {
			if (isset($values[$fields[1]])) {
				$this->db->where("kunci", $key)->where("jenis", $this->_table_index($table))->update($this->things_table, array("isi" => $values[$fields[1]]));
			}
			$id = $this->db->where("kunci", $key)->where("jenis", $this->_table_index($table))->get($this->things_table)->row()->id;
		}
		
		// check the ids one by one on the meta table
		foreach ($values as $field => $value) {
			// but only if id > 1
			$field_num = $fields_number_key[$field];
			if ($field_num > 1) {
				if ($this->db->where("parent", $id)->where("type", $field_num)->get($this->metas_table)->row()) {
					$this->db->where("parent", $id)->where("type", $field_num)->update($this->metas_table, array("value" => $value));
				} else {
					$this->db->insert($this->metas_table, array("parent" => $id, "type" => $field_num, "value" => $value));
				}
			}
		}
		
		// Our work is done here.
		return $id;
	}
	function del($table, $key) {
		// delete only if it exist, right?
		if ($hasil = $this->db->where("kunci", $key)->where("jenis", $this->_table_index($table))->get($this->things_table)->row()) {
			$id = $hasil->id;
			$this->db->where("parent", $id)->delete($this->metas_table);
			$this->db->where("id", $id)->delete($this->things_table);
			return TRUE;
		} else {
			return FALSE;
		}
	}

	function delall($table) {
		$this->db->where("jenis", $this->_table_index($table))->delete($this->things_table);
		return TRUE;
	}

	function delmeta($table, $key, $metaname) {
		if ($row = $this->db->where("kunci", $key)->get($this->things_table)->row()) {
			$this->db->where("parent", $id)->where("type", $this->_field_index($table, $metaname))->delete($this->metas_table);
		}
	}
	private function _table_index($name) {
		$keys = array_keys($this->tables);
		$arr = array_combine($keys, array_keys($keys));
		return $arr[$name];
	}
	private function _field_index($table, $key) {
		$vals = array_values($this->tables[$table]);
		$arr = array_combine($vals, array_keys($this->tables[$table]));
		return $arr[$key];
	}
	function get($table, $key) {
		$fields = $this->tables[$table];
		
		// Select tables, return all of their metas
		$row = $this->db->where(array("kunci" => $key, "jenis" => $this->_table_index($table)))->get($this->things_table)->row();
		
		// if not exist, just return with FALSE
		if (!$row) return FALSE;
		
		// We will get the id. The first one, return the id
		$metas = $this->db->where("parent", $row->id)->get($this->metas_table)->result();
		
		// Map the metas
		$metas_map = array();
		foreach ($metas as $meta) {
			// TODO: Automatic remap for integer query
			$metas_map[$meta->type] = $meta->value; 
		}
		
		// Remap the result
		$result = new stdClass;
		for ($i=0; $i < count($fields); $i++) {
			$current_field = $fields[$i];
			if ($i == 0) {
				$result->$current_field = $row->kunci;
			} else if ($i == 1) {
				$result->$current_field = $row->isi;
			} else {
				// Note the suppress error sign!
				$result->$current_field = @$metas_map[$i];
			}
		}
		$result->id = $row->id;
		return $result;
	}
	function gets($table) {
		// Get all available keys
		$results = $this->db->where("jenis", $this->_table_index($table))->get($this->things_table)->result();

		// Now, get the results!
		$hasil = array();

		foreach ($results as $row) {
			$hasil[] = $this->get($table, $row->kunci);
		}

		if (count($hasil) == 0) return FALSE;
		else return $hasil;
	}

	// Search with custom key
	function get_where($table, $where) {
		// Recursive use where
		$where_line = "";
		$escaped = array();
		foreach ($where as $key2 => $value) {
			if (strlen($where_line) > 0) {
				$where_line .= " OR ";
			}
			$fieldid = $this->_field_index($table, $key2);
			$where_line .= "m.type = ? AND s.jenis = ? AND m.value = ?";
			$escaped[] = $this->_field_index($table, $key2);
			$escaped[] = $this->_table_index($table);
			$escaped[] = $value;
		}
		$q = "
			SELECT * FROM $this->things_table s

			LEFT OUTER JOIN $this->metas_table m
			ON  m.parent = s.id
			WHERE s.jenis = " . $this->_table_index($table) . "
			AND (
				$where_line
				)
			LIMIT 0, 100;
		";
		$res = $this->db->query($q, $escaped)->result();

		$hasil = array();

		foreach ($res as $row) {
			$hasil[] = $this->get($table, $row->kunci);
		}

		if (count($hasil) == 0) return FALSE;
		else return $hasil;
	}
}
