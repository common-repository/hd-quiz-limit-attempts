<?php

namespace hdq_a_limit_results;

class _hd_fields
{
    private $tabs = false; // if data is contained in tabs
    public $fields_list = array(); // stores field type, value sanitization type, and field components
    private $fields = array(); // stores field data to be sanitized
    private $values = array(); // stores field value data to be sanitized
    private $flat_values = array(); // stores flat array of cleaned values
    private $html = ""; // rendered HTML to return
    private $media = false; // if we need to enqueue media libraries

    public function __construct($fields = array(), $values = array(), $tabs = false)
    {
        $this->tabs = filter_var($tabs, FILTER_VALIDATE_BOOLEAN);
        $this->fields = $fields;
        $this->values = $values;
        $this->getFields();
    }

    private function getFields()
    {
        $JSON = '{"heading": { "value": "wp_kses", "components": ["heading_type"] },
	"text": { "value": "text_field", "components": ["label", "required", "tooltip", "description", "placeholder", "prefix", "postfix", "attributes"] },
	"textarea": { "value": "textarea_field", "components": ["label", "required", "tooltip", "description", "placeholder", "prefix", "postfix", "attributes"] },
	"email": { "value": "email_field", "components": ["label", "required", "tooltip", "description", "placeholder", "prefix", "postfix", "attributes"] },
    "website": { "value": "url", "components": ["label", "required", "tooltip", "description", "placeholder", "prefix", "postfix", "attributes"] },
	"integer": { "value": "intval", "components": ["label", "required", "tooltip", "description", "placeholder", "prefix", "postfix", "attributes"] },
	"float": { "value": "floatval", "components": ["label", "required", "tooltip", "description", "placeholder", "prefix", "postfix", "attributes"] },
	"currency": { "value": "currency", "components": ["label", "required", "tooltip", "description", "placeholder", "prefix", "postfix", "attributes"] },
	"content": { "value": "wp_kses_post", "components": [] },
	"divider": { "value": "", "components": [] },
	"colour": { "value": "text_field", "components": ["label", "required", "tooltip", "description", "placeholder", "prefix", "postfix", "attributes"] },
	"image": { "value": "intval", "components": ["label", "required", "tooltip", "description", "attributes"] },
	"gallery": { "value": "intval", "components": ["label", "required", "tooltip", "description", "attributes"] },
	"select": { "value": "text_field", "components": ["label", "required", "tooltip", "description", "placeholder", "prefix", "postfix", "attributes", "options"] },
	"radio": { "value": "text_field", "components": ["label", "required", "tooltip", "description", "placeholder", "attributes", "options"] },
	"checkbox": { "value": "text_field", "components": ["label", "required", "tooltip", "description", "placeholder", "attributes", "options"] },
	"date": { "value": "text_field", "components": ["label", "required", "tooltip", "description", "placeholder", "attributes"] },
	"editor": { "value": "wp_kses_post", "components": ["media", "label", "required", "tooltip", "description", "attributes"] },
	"column": { "value": "children", "components": ["column_type"] },
	"action": { "value": "action", "components": ["action"] }}';
        $fields = json_decode($JSON, true);

        // allow field types to be filterable
        $fields = apply_filters("hd_add_new_field_types", $fields);
        $this->fields_list = $fields;
    }


    public function display()
    {
        if (!empty($this->values)) {
            $sanitize = new _hd_sanitize($this->values);
            $this->values = $sanitize->all();
        }

        $this->html = $this->render();
        if ($this->media) {
            wp_enqueue_media();
        }
        echo $this->html;
    }


    private function render($fields = array())
    {

        if (empty($fields)) {
            $fields = $this->fields;
        }

        $html = "";
        if (!$this->tabs) {
            $html = $this->render_fields($fields);
        } else {
            $html .= $this->render_tabs($fields);
        }
        return $html;
    }

    private function render_tabs($fields)
    {
        $this->tabs = false; // reset for tab children

        $html = '<div class = "hd_tabs_anchor"></div>';
        $html .= '<div class = "hd_content_tabs">';

        // tab nav
        $logo = plugins_url('/../includes/web-logo.png', __FILE__);
        $html .= '<div claass="hd_tab_nav_wrapper">
                <div class = "hd_logo">
                    <span class="hd_logo_tooltip">
                        <img src="'.$logo.'" alt="Harmonic Design Logo">
                    </span>
                </div>
                <div class = "hd_tab_nav">';
        $i = 0;
        foreach ($fields as $tab) {
            $active = "";
            if ($i === 0) {
                $active = "hd_tab_nav_item_active";
            }
            $html .= '<div role="button" class="hd_tab_nav_item ' . $active . '" data-id="' . $tab["id"] . '">' . $tab["label"] . '</div>';
            $i++;
        }

        $html .= '</div></div>';

        // tab content
        $html .= '<div class = "hd_tab_content">';
        $i = 0;
        foreach ($fields as $tab) {
            $active = "";
            if ($i === 0) {
                $active = "hd_tab_content_section_active";
            }
            $html .= '<div id = "hd_tab_content_' . $tab["id"] . '" class = "hd_tab_content_section ' . $active . '">';
            $html .= '<h2 class="hd_tab_heading">' . $tab["label"] . '</h2>';
            $html .= $this->render_fields($tab["children"]);
            $html .= '</div>';
            $i++;
        }
        $html .= '</div>';


        $html .= '</div>';
        return $html;
    }

    private function render_fields($fields)
    {
        $html = "";
        // array of field types that don't need an ID
        $no_id = array(
            "column",
            "divider",
            "heading",
            "content"
        );
        foreach ($fields as $field) {
            // sanitize field data
            $sanitize = new _hd_sanitize($field, $this->fields_list);
            $field = $sanitize->fields($field);

            $method = "render_" . $field["type"];
            if (isset($field["id"]) || in_array($field["type"], $no_id)) {
                $html .= '<div class = "hd_input_item">';
                if (method_exists($this, $method)) {
                    $field = $this->createComponents($field);
                    $html .= $this->$method($field);
                } else {
                    $html .= $this->render_field_not_found($field);
                }
                $html .= '</div>';
            } else {
                // noob. add an ID to the field
            }
        }
        return $html;
    }

    private function createComponents($field)
    {
        $type = $field["type"];
        $components = $this->fields_list[$type]["components"];

        foreach ($components as $component) {
            if (!isset($field[$component])) {
                $field[$component] = ""; // set component value
            }
        }

        // list of fields whose value is an array
        $field_arrays = array(
            "checkbox",
            "radio",
            "gallery",
            "imageSelect"
        );

        if (!isset($field["value"])) {
            if (in_array($field["type"], $field_arrays)) {
                $field["value"] = array();
            } else {
                $field["value"] = "";
            }
        }
        if (!isset($field["default"])) {
            if (in_array($field["type"], $field_arrays)) {
                $field["default"] = array();
            } else {
                $field["default"] = "";
            }
        }
        return $field;
    }


    /*
        * Render Components
    */

    private function get_value($field)
    {
        // sanitize value as type
        $value = $field["value"];

        if (isset($this->values[$field["id"]])) {
            $value = $this->values[$field["id"]];
        }

        if ($value == "") {
            if (isset($field["default"])) {
                $value = $field["default"];
            }
        } elseif (is_array($value) && empty($value)) {
            if (isset($field["default"])) {
                $value = $field["default"];
            }
        }

        $type = $field["type"];
        $sanitize = new _hd_sanitize($value, $this->fields_list);
        $value = $sanitize->field($value, $type);
        return $value;
    }

    private function get_required_label($field)
    {
        $html = "";
        if ($field["required"] == true) {
            $html = '<span class="hd_required_icon">*</span>';
        }
        return $html;
    }

    private function get_tooltip($field)
    {
        if (!isset($field["tooltip"]) || $field["tooltip"] == "") {
            return "";
        }
        $html = '<span class = "hd_tooltip_item">?<span class = "hd_tooltip"><div class = "hd_tooltip_content">' . $field["tooltip"] . '</div></span></span>';
        return $html;
    }

    private function get_label($field)
    {
        if ($field["label"] === "") {
            return "";
        }
        $required = $this->get_required_label($field);
        $tooltip = $this->get_tooltip($field);
        $html = '<label class="hd_input_label" for="' . $field["id"] . '">' . $required . $field["label"] . $tooltip . '</label>';
        if (!isset($field["hasParent"]) || $field["hasParent"] !== true) {
            $html .= $this->get_description($field);
        }
        return $html;
    }

    private function get_description($field, $after = false)
    {
        if (!isset($field["description"]) || $field["description"] == "") {
            return "";
        }
        if ($after && !isset($field["hasParent"]) || isset($field["hasParent"]) && $field["hasParent"] !== true) {
            return;
        }
        return '<div class = "hd_input_description">' . $field["description"] . '</div>';
    }

    private function get_attributes($field)
    {
        if (empty($field["attributes"])) {
            return "";
        }
        $html = "";
        foreach ($field["attributes"] as $a) {
            if ($a["value"] === "") {
                $html .= $a["name"] . " ";
            } else {
                $html .= $a["name"] . ' = "' . $a["value"] . '" ';
            }
        }
        return $html;
    }

    private function get_fix($field, $html)
    {
        if ($field["prefix"] !== "") {
            $html = '<div class = "hd_prefix"><div class = "hd_fix">' . $field["prefix"] . '</div>' . $html . '</div>';
        } elseif ($field["postfix"] !== "") {
            $html = '<div class = "hd_postfix">' . $html . '<div class = "hd_fix">' . $field["postfix"] . '</div></div>';
        }
        return $html;
    }

    /*
        * Render fields
    */

    private function render_field_not_found($field)
    {
        return 'render function for field type ' . $field["type"] . ' not found';
    }

    private function render_column($field)
    {
        $html = "";
        if (!empty($field["children"])) {
            $html .= '<div class = "hd_cols hd_cols_' . $field["column_type"] . '">';
            foreach ($field["children"] as $child) {
                $child["hasParent"] = true;
                $html .= $this->render(array($child));
            }
            $html .= '</div>';
        }
        return $html;
    }

    private function render_heading($field)
    {
        $allowed = array("h1", "h2", "h3", "h4", "h5", "h6");
        $field["heading_type"] = strtolower($field["heading_type"]);
        if (!in_array($field["heading_type"], $allowed)) {
            $field["heading_type"] = "H2";
        }
        if (!isset($field["id"])) {
            $field["id"] = "";
        }
        return '<' . $field["heading_type"] . ' id = "' . $field["id"] . '">' . $field["value"] . '</' . $field["heading_type"] . '>';
    }

    private function render_content($field)
    {
        return $field["value"];
    }

    private function render_divider($field)
    {
        return '<hr/>';
    }

    private function render_text($field)
    {
        $value = $this->get_value($field);
        $html = "";
        $html .= $this->get_label($field);
        $required = "";
        if ($field["required"]) {
            $required = "required";
        }
        $attributes = $this->get_attributes($field);

        $input = '<input type="text" data-type="text" data-required="' . $required . '" ' . $attributes . ' class="hderp hd_input" id="' . $field["id"] . '" value="' . $value . '" placeholder="' . $field["placeholder"] . '">';

        $html .= $this->get_fix($field, $input);
        $html .= $this->get_description($field, true);
        return $html;
    }

    private function render_email($field)
    {
        $value = $this->get_value($field);
        $html = "";
        $html .= $this->get_label($field);
        $required = "";
        if ($field["required"]) {
            $required = "required";
        }
        $attributes = $this->get_attributes($field);

        $input = '<input type="email" data-type="email" data-required="' . $required . '" ' . $attributes . ' class="hderp hd_input" id="' . $field["id"] . '" value="' . $value . '" placeholder="' . $field["placeholder"] . '">';

        $html .= $this->get_fix($field, $input);
        $html .= $this->get_description($field, true);
        return $html;
    }

    private function render_website($field)
    {
        $value = $this->get_value($field);
        $html = "";
        $html .= $this->get_label($field);
        $required = "";
        if ($field["required"]) {
            $required = "required";
        }
        $attributes = $this->get_attributes($field);
        if ($field["placeholder"] == "") {
            $field["placeholder"] = "https://";
        }

        $input = '<input type="text" data-type="website" data-required="' . $required . '" ' . $attributes . ' class="hderp hd_input" id="' . $field["id"] . '" value="' . $value . '" placeholder="' . $field["placeholder"] . '">';

        $html .= $this->get_fix($field, $input);
        $html .= $this->get_description($field, true);
        return $html;
    }

    private function render_integer($field)
    {
        $value = $this->get_value($field);
        $html = "";
        $html .= $this->get_label($field);
        $required = "";
        if ($field["required"]) {
            $required = "required";
        }
        $attributes = $this->get_attributes($field);

        $input = '<input type="number" data-type="integer" data-required="' . $required . '" ' . $attributes . ' class="hderp hd_input" id="' . $field["id"] . '" value="' . $value . '" placeholder="' . $field["placeholder"] . '">';

        $html .= $this->get_fix($field, $input);
        $html .= $this->get_description($field, true);

        return $html;
    }

    private function render_float($field)
    {
        $value = $this->get_value($field);
        $html = "";
        $html .= $this->get_label($field);
        $required = "";
        if ($field["required"]) {
            $required = "required";
        }
        $attributes = $this->get_attributes($field);

        $input = '<input type="number" data-type="float" data-required="' . $required . '" ' . $attributes . ' class="hderp hd_input" id="' . $field["id"] . '" value="' . $value . '" placeholder="' . $field["placeholder"] . '">';

        $html .= $this->get_fix($field, $input);
        $html .= $this->get_description($field, true);
        return $html;
    }

    private function render_currency($field)
    {
        // Should have the pre/postfix sent as part of the field data
        $value = $this->get_value($field);
        $html = "";
        $html .= $this->get_label($field);
        $required = "";
        if ($field["required"]) {
            $required = "required";
        }
        $attributes = $this->get_attributes($field);

        $html .= '<input type="number" data-type="currency" data-required="' . $required . '" ' . $attributes . ' class="hderp hd_input" id="' . $field["id"] . '" value="' . $value . '" placeholder="' . $field["placeholder"] . '">';
        $html .= $this->get_description($field, true);
        return $html;
    }

    private function render_textarea($field)
    {
        $value = $this->get_value($field);
        $html = "";
        $html .= $this->get_label($field);
        $required = "";
        if ($field["required"]) {
            $required = "required";
        }
        $attributes = $this->get_attributes($field);

        $html .= '<textarea type="text" data-type="textarea" data-required="' . $required . '" ' . $attributes . ' rows = "8" class="hderp hd_input" id="' . $field["id"] . '" placeholder="' . $field["placeholder"] . '">' . $value . '</textarea>';
        $html .= $this->get_description($field, true);
        return $html;
    }

    private function render_select($field)
    {
        $value = $this->get_value($field);
        $html = "";
        $html .= $this->get_label($field);
        $required = "";
        if ($field["required"]) {
            $required = "required";
        }
        $attributes = $this->get_attributes($field);

        $input = '<select data-type="select" data-required="' . $required . '" class="hderp hd_input" id="' . $field["id"] . '" value="' . $value . '" ' . $attributes . '>';
        if ($field["placeholder"] !== "") {
            $input .= '<option value = "">' . $field["placeholder"] . '</option>';
        } else {
            if ($required === "required") {
                $input .= '<option value = "">- - -</option>';
            }
        }
        foreach ($field["options"] as $option) {
            $selected = "";
            if ($option["value"] == $value) {
                $selected = "selected";
            }
            $input .= '<option value = "' . $option["value"] . '" ' . $selected . '>' . $option["label"] . '</option>';
        }
        $input .= '</select>';

        $html .= $this->get_fix($field, $input);
        $html .= $this->get_description($field, true);
        return $html;
    }

    private function render_radio($field)
    {
        $value = $this->get_value($field);
        $html = "";
        $html .= $this->get_label($field);
        $required = "";
        if ($field["required"]) {
            $required = "required";
        }
        $attributes = $this->get_attributes($field);


        $html .= '<div data-type="radio" data-required="' . $required . '" class="hderp hd_input_radio" id="' . $field["id"] . '">';
        $i = 0;
        foreach ($field["options"] as $option) {
            $checked = "";
            if ($option["value"] == $value) {
                $checked = 'checked';
            }
            $label = $option["label"];

            if (isset($option["tooltip"]) && $option["tooltip"] !== "") {
                $label .=  $this->get_tooltip($option);
            }


            $html .= '<div class="hd_input_row">
		<label class="hd_label_input" data-type="radio" data-id="' . $field["id"] . '" for="' . $field["id"] . $i . '">
			<div class="hd_options_check">
				<input type="checkbox" title="' . $option["label"] . '" data-id="' . $field["id"] . '" class="hd_option hd_radio_input" data-type="radio" value="' . $option["value"] . '" name="' . $field["id"] . $i . '" autocomplete="off" id="' . $field["id"] . $i . '" ' . $checked . '>
				<span class="hd_toggle"><span class="hd_aria_label">' . $option["label"] . '</span></span>			
			</div>
			' . $label . '
		</label>';

            if (isset($option["description"]) && $option["description"] !== "") {
                $html .= '<span class = "hd_option_description">' . $option["description"] . '</span>';
            }

            $html .= '</div>';
            $i++;
        }
        $html .= '</div>';
        $html .= $this->get_description($field, true);
        return $html;
    }

    private function render_checkbox($field)
    {
        $value = $this->get_value($field);
        $html = "";
        $html .= $this->get_label($field);
        $required = "";
        if ($field["required"]) {
            $required = "required";
        }
        $attributes = $this->get_attributes($field);


        $html .= '<div data-type="checkbox" data-required="' . $required . '" class="hderp hd_input_checkbox" id="' . $field["id"] . '">';
        $i = 0;
        foreach ($field["options"] as $option) {
            $checked = "";
            if (in_array($option["value"], $value)) {
                $checked = 'checked';
            }
            $label = $option["label"];

            $html .= '<div class="hd_input_row">
		<label class="hd_label_input" data-type="radio" data-id="' . $field["id"] . '" for="' . $field["id"] . $i . '">
			<div class="hd_options_check">
				<input type="checkbox" title="' . $option["label"] . '" data-id="' . $field["id"] . '" class="hd_option hd_check_input" data-type="radio" value="' . $option["value"] . '" name="' . $field["id"] . $i . '" autocomplete="off" id="' . $field["id"] . $i . '" ' . $checked . '>
				<span class="hd_toggle"><span class="hd_aria_label">' . $option["label"] . '</span></span>			
			</div>
			' . $label . '
		</label>';

            if (isset($option["description"]) && $option["description"] !== "") {
                $html .= '<span class = "hd_option_description">' . $option["description"] . '</span>';
            }

            $html .= '</div>';

            $i++;
        }
        $html .= '</div>';
        $html .= $this->get_description($field, true);
        return $html;
    }

    private function render_colour($field)
    {
        $value = $this->get_value($field);
        $html = "";
        $html .= $this->get_label($field);
        $required = "";
        if ($field["required"]) {
            $required = "required";
        }
        $attributes = $this->get_attributes($field);
        if ($field["placeholder"] == "") {
            $field["placeholder"] = "#000000";
        }

        $style = "";
        if ($field["default"] !== "") {
            $style = "background-color: " . $field["default"];
        }
        if ($value !== "") {
            $style = "background-color: " . $value;
        }

        $html .= '<div class="hd_postfix"><input type="text" maxlength = "7" data-type="colour" data-required="" class="hderp hd_input hd_colour" id="' . $field["id"] . '" value="' . $value . '" placeholder="' . $field["placeholder"] . '"><div class="hd_fix hd_colour_select" title = "Select Colour" data-id = "' . $field["id"] . '" style = "' . $style . '">&nbsp;&nbsp;&#x270E;&nbsp;&nbsp;</div></div>';
        $html .= $this->get_description($field, true);
        return $html;
    }

    private function render_image($field)
    {
        $this->media = true;
        $value = $this->get_value($field);
        $html = "";
        $html .= $this->get_label($field);
        $required = "";
        if ($field["required"]) {
            $required = "required";
        }
        $attributes = $this->get_attributes($field);

        $title = "Set image";
        $button = "Set image";
        $active = "";
        if ($value > 0) {
            $active = "active";
        }

        $html .= '<div data-title="' . $title . '" data-button="' . $button . '" data-multiple="no" data-type="image" data-required="' . $required . '" class="hderp hd_image" data-value="' . $value . '" id="' . $field["id"] . '" role="button" title="upload image">upload image</div>';
        $html .= '<span class="hd_image_remove ' . $active . '" data-type="image" data-id="' . $field["id"] . '" onclick="_hd.images.remove(this)" role="button">remove image</span>';

        return $html;
    }

    private function render_editor($field)
    {
        $this->media = true;
        $value = $this->get_value($field);
        $requiredClass = "";
        $required = "";
        if ($field["required"]) {
            $required = "required";
            $requiredClass = "hd_editor_required";
        }
        $attributes = $this->get_attributes($field);

        $html = "";
        $html .= $this->get_label($field);
        $media = false;
        if ($field["media"]) {
            $media = true;
        }
        ob_start();
        wp_editor(stripslashes(urldecode($value)), $field["id"], array('textarea_name' => $field["id"], 'editor_class' => "hderp hd_input hd_editor_input '.$requiredClass.'", 'media_buttons' => $media, 'textarea_rows' => 20, 'quicktags' => true, 'editor_height' => 240));
        $html .= ob_get_clean();

        return $html;
    }

    private function render_action($field)
    {
        $f = $field["action"];
        if (!function_exists($f)) {
            return "No function with name " . $f;
        }
        return $f($field);
    }
}
