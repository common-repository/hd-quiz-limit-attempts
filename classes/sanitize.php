<?php

namespace hdq_a_limit_results;

class _hd_sanitize
{
	private $data = array();
	private $tabs = false;
	private $components = array();
	private $fields = array();

	public function __construct($data = array(), $fields = null)
	{
		$this->fields = $fields;
		if ($fields === null) {
			$fields = new _hd_fields();
			$this->fields = $fields->fields_list;
		}
		$this->data = $data;
		$this->getComponents();
	}


	private function getComponents()
	{
		// only important when reading
		$JSON = '{
	"id": "text_field",
	"type": "text_field",
	"label": "wp_kses",
	"tooltip": "wp_kses",
	"description": "wp_kses_post",
	"placeholder": "text_field",
	"required": "boolean",
	"prefix": "text_field",
	"postfix": "text_field",
	"attributes": "text_field",
	"options": "text_field",
	"media": "boolean",
	"column_type": "text_field",
	"action": "text_field"
}';
		$components = json_decode($JSON, true);
		// allow field components to be filterable
		$components = apply_filters("hd_add_new_field_components", $components);
		$this->components = $components;

		// NOTE: Attributes needs to be an array of objects
		// [{"name": "attribute name", "value":"attribute value"}]
	}

	// flatten array
	// primarily only used when saving data
	// returns array with data type and value
	public function flatten()
	{
		if (empty($this->data)) {
			return;
		}

		foreach ($this->data as $k => $value) {
			if (isset($value["value"])) {
				if ($value["value"] !== "") {
					$type = $value["type"];
					if (isset($this->fields[$type])) {
						$method = $this->fields[$type]["value"];
						if (!is_array($value["value"])) {
							if (method_exists($this, $method)) {
								$value["value"] = $this->$method($value["value"]);
							} else {
								// find custom or default to text
							}
						} else {
							foreach ($value["value"] as $kk => $v) {
								if (method_exists($this, $method)) {
									$value["value"][$kk] = $this->$method($v);
								} else {
									// find custom or default to text
								}
							}
						}
					} else {
						$value["value"] = $this->text_field($value["value"]);
					}
				}
				$this->data[$k] = array(
					"value" => $value["value"],
					"type" => $value["type"]
				);
			}
		}
		return $this->data;
	}

	// for reding the field data
	public function fields($field)
	{
		// take a field, and sanitize each component
		$data = array();
		foreach ($field as $k => $f) {
			if ($k !== "children" && $k !== "default") {
				if (isset($this->components[$k])) {
					$method = $this->components[$k];
					if (method_exists($this, $method)) {
						if (!is_array($f)) {
							$data[$k] = $this->$method($f);
						} else {
							$data[$k] = array();
							foreach ($f as $ff => $fff) {
								foreach ($fff as $s => $ss) {
									if (isset($this->components[$s])) {
										$method = $this->components[$s];
										$data[$k][$ff][$s] = $this->$method($ss);
									} else {
										$data[$k][$ff][$s] = $this->text_field($ss);
									}
								}
							}
						}
					} else {
						// find custom or default to text
						$data[$k] = "";
					}
				}
			} else {
				// pass along children
				$data[$k] = $f;
			}
		}
		return $data;
	}

	// sanitize individual value
	public function field($value = "", $type)
	{
		if (isset($this->fields[$type])) {
			$method = $this->fields[$type]["value"];
			if (method_exists($this, $method)) {
				if (!is_array($value)) {
					$value = $this->$method($value);
				} else {
					foreach ($value as $k => $v) {
						$value[$k] = $this->$method($v);
					}
				}
			}
		} else {
			// custom sanitize function or text_field
		}
		return $value;
	}

	// sanitize a flattened array
	public function all()
	{
		$data = array();
		foreach ($this->data as $k => $d) {
			$data[$k] = $this->field($d["value"], $d["type"]);
		}
		return $data;
	}

	/* Start sanitization functions */
	function text_field($value)
	{
		return sanitize_text_field($value);
	}

	function textarea_field($value)
	{
		return sanitize_textarea_field($value);
	}

	function email_field($value)
	{
		return sanitize_email($value);
	}

	function url($value)
	{
		return sanitize_url($value);
	}

	function intval($value)
	{
		return intval($value);
	}

	function floatval($value)
	{
		return floatval($value);
	}

	function currency($value)
	{
		return number_format(floatval($value), 2);
	}

	function wp_kses_post($value)
	{
		$value = wp_kses_post($value);
		$value = apply_filters('the_content', $value);
		$value = wpautop($value);
		return $value;
	}

	function wp_kses($value)
	{
		$allowed_html = array(
			'a' => array(),
			'p' => array(),
			'span' => array(),
			'strong' => array(),
			'em' => array(),
			'code' => array(),
			'sup' => array(),
			'sub' => array(),
		);
		return wp_kses($value, $allowed_html);
	}

	function boolean($value)
	{
		return filter_var($value, FILTER_VALIDATE_BOOLEAN);
	}
}
