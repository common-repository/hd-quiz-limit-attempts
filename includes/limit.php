<?php
// Settings page for the addon
wp_enqueue_style(
	"hdq_a_limit_attempts",
	plugins_url('/../css/hdfields_style.css', __FILE__),
	array(),
	HDQ_A_LIMIT_ATTEMPTS
);

wp_enqueue_script(
	'hdq_a_limit_attempts',
	plugins_url('/../scripts/hdfields_script.js', __FILE__),
	array(),
	HDQ_PLUGIN_VERSION,
	true
);

// settings field markup
$data = '[
	{
		"id": "main",
		"label": "Main",
		"children": [
			{
				"type": "column",
				"column_type": "1-1",
				"children": [
					{
						"id": "max_attempts",
						"label": "Maximum Allowed Attempts",
						"type": "integer",
						"default": 3,
						"tooltip": "feel free to set this to <code>0</code> if you want to temporarily disable all quizzes for the relevant mode.",
						"description": "Enter the total number of attempts a user is allowed to make before they will be denied access to the quiz. Leave blank to allow unlimited attempts."
					},
{
				"id": "limit_mode",
				"label": "Limit Mode",
				"type": "radio",
				"description": "The difference between modes is important.<br/>Please learn more about each mode by visiting the support tab located on the left.",
				"options": [
					{
						"label": "Logged In Only",
						"value": "users",
						"tooltip": "Will only allow the quiz to be seen and completed by logged-in users"
					},
					{
						"label": "All Users",
						"value": "all",
						"tooltip": "Will limit attempts for all users"
					},
					{
						"label": "Hybrid",
						"value": "hybrid",
						"tooltip": "Will use Logged In mode for logged in users, and All Users mode for the rest"
					}
				],
				"default": "hybrid"
			}					
					
				]
			},			
			{
				"id": "divider_attempts",
				"type": "divider"
			},
			{
				"id": "no_more_attempts",
				"label": "No More Attempts Text",
				"type": "editor",
				"media": true,
				"description": "Set custom text that appears if the user tries to view a quiz that they no longer have access to.",
				"default": "Sorry, but you have used up all of your attempts. This quiz is no longer available to you."
			}
		]
	},
	{
		"id": "support",
		"label": "Support",
		"children": [
			{
				"id": "support_content",
				"type": "action",
				"action": "hdq_limit_attempts_support_callback"
			}
		]
	}
]';

$values = get_option("hdq_a_limit_results", false); // sanitized in _hd_fields
$fields = json_decode($data, true);
?>
<div class="hd_main">
	<h2>HD Quiz - Limit Attempts Addon</h2>
	<p>This FREE addon for HD Quiz will allow you to limit how many times a user can take a quiz. Pairs really nicely with the <a href="https://harmonicdesign.ca/product/hd-quiz-save-results-pro/?utm_source=HDQuiz&utm_medium=limitResults" target="_blank">Save Results Pro</a> addon.</p>
	<p>Please feel free to ask for support on the <a href="https://wordpress.org/plugins/hd-quiz-save-results-light/" target="_blank">official WordPress support page</a> for this addon, and please take a look at the Support tab to see instructions, explanations, and a video tutorial. <strong>NOTE: </strong> If this addon is not working correctly for you, please take a look at the Page Caching section on the Support tab.</p>

	<div class="hd_header">
		<h1 class="hd_heading_title">
			Settings
		</h1>
		<div class="hd_header_actions">
			<div id="hd_save" data-id="0" data-action="hdq_a_limit_attempts_save_settings" data-label="SAVE" class="button button_primary" title="save settings" role="button">SAVE</div>
		</div>
	</div>
	<div class="hd_content">
		<?php

		use hdq_a_limit_results\_hd_fields;

		$fields = new _hd_fields($fields, $values, true);
		$fields->display();
		?>
	</div>
</div>