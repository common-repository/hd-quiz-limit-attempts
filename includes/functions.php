<?php
// general HDQ Addon Limit Attempts functions
use hdq_a_limit_results\_hd_sanitize;

function hdq_limit_attempts_support_callback($field)
{
	ob_start();
?>
	<p>Please feel free to ask for support on the <a href="https://wordpress.org/plugins/hd-quiz-save-results-light/" target="_blank">official WordPress support page</a> for this addon.</p>
	<h2>Limit Modes</h2>

	<p>By default, this addon uses <strong>Hybrid</strong> mode. This mode will use Logged In mode if the quiz taker is logged into your site, or revert to All Users mode if they are not. The two modes work in very different ways, and their differences are important.</p>

	<p><strong>Logged</strong> In mode will force users to be logged into the site in order to see quizzes. If a user is not logged in, the quiz will be hidden. Furthermore, whenever a quiz is completed, the number of attempts is saved directly to the user's profile. This makes Logged In mode the most secure mode, as it tracks users across multiple devices and cannot be easily circumvented.</p>

	<p>You can view a user's account to see which quizzes they have completed, as well as how many attempts they have made. You can also update their total attempts per quiz (note: only site editors and above will be able to see this).</p>

	<p><strong>All Users</strong> mode will work regardless if the user is logged in or not. The downside is that we have to save the total number of attempts to the user's browser. This means that this method can be circumvented by a user if they clear their browser data, or use incognito mode etc.</p>

	<h2>Page Caching</h2>
	<p>This section is only relevant if you are using a page caching solution.</p>

	<p>This addon starts by hiding the quiz, and then either shows the quiz or the "No More Attempts Text" after it has figured out which to do. If you are using Logged Mode or Hybrid mode, we use ajax to contact the backend of your site to determine if the current user is logged in or not. This is done to significantly increase compatibility with page cache solutions.</p>

	<p>HOWEVER, please remember to clear your site cache after changing these settings to ensure that the old settings are not still cached.</p>
<?php
	$html = ob_get_clean();
	return $html;
}

function hdq_a_limit_attempts_save_settings()
{
	if (!current_user_can('edit_others_pages')) {
		return;
	}

	$payload = $_POST["payload"];
	$sanitize = new _hd_sanitize($payload);
	$sanitized = $sanitize->flatten();
	update_option("hdq_a_limit_results", $sanitized);
	echo '{"status":"success"}';
	die();
}
add_action('wp_ajax_hdq_a_limit_attempts_save_settings', 'hdq_a_limit_attempts_save_settings');

// add content after quizzes
function hdq_limit_attempts_after_quiz($quizID)
{
	$quizID = intval($quizID);
	$data = get_option("hdq_a_limit_results", false);
	$data = new _hd_sanitize($data);
	$data = $data->all();
	$data["no_more_attempts"] = apply_filters("hdq_limit_attempts_description", $data["no_more_attempts"], $quizID);
	
	$winData = '
	const HDQ_A_LIMIT_RESULTS_MODE = "' . esc_js($data["limit_mode"]) . '"; 
	const HDQ_A_LIMIT_RESULTS_DESCRIPTION = `' . wp_kses_post($data["no_more_attempts"]) . '`;
	const HDQ_A_LIMIT_RESULTS_MAX_ATTEMPTS = ' . esc_js($data["max_attempts"]) . ';
	const HDQ_A_IS_LOGGED_IN = "' . esc_js(is_user_logged_in()) . '";
	';
	wp_add_inline_script('hdq_admin_script', $winData);

	wp_enqueue_script(
		'hdq_a_limit_attempts_script',
		plugins_url('/../scripts/hdq_a_limit_results.js', __FILE__),
		array(),
		HDQ_A_LIMIT_ATTEMPTS,
		true
	);

	echo '<style>.hdq_quiz {display: none}</style>';
}
add_action("hdq_after", "hdq_limit_attempts_after_quiz");

// Tell HD Quiz to run a custom function on quiz submit
function hdq_a_limit_results_submit($quizOptions)
{
	array_push($quizOptions->hdq_submit, "hdq_a_limit_results_submit");
	return $quizOptions;
}
add_action('hdq_submit', 'hdq_a_limit_results_submit');

function hdq_a_limit_results_get_user()
{
	$quizID = intval($_POST["data"]["quizID"]);
	$user_id = get_current_user_id();
	if ($user_id == 0) {
		echo '{"loggedin": "no", "attempts": 0}';
		die();
	}
	$taken = get_user_meta($user_id, "hdq_a_limit_attempts", true);
	if ($taken == "") {
		$taken = array();
	}
	$taken = array_map("intval", $taken);

	if (!isset($taken[$quizID])) {
		$taken[$quizID] = 0;
	}
	echo '{"loggedin": "yes", "attempts": ' . esc_attr($taken[$quizID]) . '}';
	die();
}
add_action('wp_ajax_hdq_a_limit_results_get_user', 'hdq_a_limit_results_get_user');
add_action('wp_ajax_nopriv_hdq_a_limit_results_get_user', 'hdq_a_limit_results_get_user');

function hdq_a_limit_results_update_user()
{
	$quizID = intval($_POST["data"]["quizID"]);
	$user_id = get_current_user_id();
	$taken = get_user_meta($user_id, "hdq_a_limit_attempts", true);
	if ($taken == "") {
		$taken = array();
	}
	$taken = array_map("intval", $taken);

	if (!isset($taken[$quizID])) {
		$taken[$quizID] = 0;
	}

	$taken[$quizID] = $taken[$quizID] + 1;
	update_user_meta($user_id, "hdq_a_limit_attempts", $taken);
	die();
}
add_action('wp_ajax_hdq_a_limit_results_update_user', 'hdq_a_limit_results_update_user');

function hdq_a_limit_results_user_profile($user)
{

	if (!current_user_can('edit_others_pages')) {
		return;
	}

	$user_id = get_current_user_id();
	$taken = get_user_meta($user_id, "hdq_a_limit_attempts", true);
	if ($taken == "") {
		$taken = array();
	}
	$taken = array_map("intval", $taken);
	$taken_JSON = urlencode(json_encode($taken));
?>
	<style>
		#hdq_a_limit_results_user_profile {
			padding: 2em;
			border: 1px solid #222;
			margin-top: 2em;
		}

		.hdq_a_limit_results_user_quiz_item {
			display: grid;
			grid-template-columns: 80px 1fr;
			grid-gap: 1em;
			align-items: center;
			margin-bottom: 1em;
		}

		#hdq_a_limit_results_user_profile .hdq_a_limit_results_user_quiz_item:last-child {
			margin-bottom: 0;
		}
	</style>
	<div id="hdq_a_limit_results_user_profile">
		<h3 style="margin-top: 0">HD Quiz: Limit Quiz Attempts</h3>
		<p>Don't worry. Only users with editor access and above will be able to see this section. This is hidden from normal users.</p>

		<input type="text" style="display: none" id="hdq_a_limit_attempts" name="hdq_a_limit_attempts" value="<?php echo esc_attr($taken_JSON); ?>" readonly />

		<?php
		foreach ($taken as $quizID => $res) {
			$term = get_term($quizID);
			if ($term) {
				echo '<div class = "hdq_a_limit_results_user_quiz_item">
				<input type = "number" id = "hdq_a_limit_results_quiz_id_' . esc_attr($quizID) . '" data-id = "' . esc_attr($quizID) . '" class = "hdq_a_limit_results_quiz_id_input" value = "' . esc_attr($res) . '" title = "Times quiz taken">
				<label for = "hdq_a_limit_results_quiz_id_' . esc_attr($quizID) . '" style = "font-weight: 600;">' . esc_html($term->name) . '</label>
				</div>';
			}
		}
		?>

	</div>

	<script>
		const hdq_a_limit_results_quiz_id_input = document.getElementsByClassName("hdq_a_limit_results_quiz_id_input");
		for (let i = 0; i < hdq_a_limit_results_quiz_id_input.length; i++) {
			hdq_a_limit_results_quiz_id_input[i].addEventListener("change", function() {
				let v = parseInt(this.value);
				let id = parseInt(this.getAttribute("data-id"));

				const el = document.getElementById("hdq_a_limit_attempts");
				let value = el.value;
				value = decodeURIComponent(value)
				value = JSON.parse(value)
				value[id] = v;
				value = JSON.stringify(value);
				value = encodeURIComponent(value)
				el.value = value;
			})
		}
	</script>


<?php
}
add_action('edit_user_profile', 'hdq_a_limit_results_user_profile');
add_action('show_user_profile', 'hdq_a_limit_results_user_profile');

function hdq_a_limit_results_save_user_profile($user_id)
{
	$taken = $_POST['hdq_a_limit_attempts'];
	$taken = wp_kses_post($taken);
	$taken = urldecode($taken);
	$taken = json_decode($taken, true);
	$taken = array_map("intval", $taken);
	update_user_meta($user_id, 'hdq_a_limit_attempts', $taken);
}
add_action('edit_user_profile_update', 'hdq_a_limit_results_save_user_profile');
add_action('personal_options_update', 'hdq_a_limit_results_save_user_profile');
