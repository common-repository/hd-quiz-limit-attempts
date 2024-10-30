const HDQ_A_LIMIT_RESULTS = {
	init: async function (quizID = 0) {
		console.log("Limit Results Init");
		quizID = parseInt(quizID);
		if (quizID === 0) {
			console.error("No Quiz ID was provided");
			return;
		}

		let res = await HDQ_A_LIMIT_RESULTS.runMode(quizID);
		if (!res) {
			HDQ_A_LIMIT_RESULTS.limit();
		}
		if (HDQ.VARS.timer.time == "") {
			const quiz = document.getElementsByClassName("hdq_quiz");
			if (quiz.length > 0) {
				quiz[0].style.display = "block";
			}
		}
	},
	runMode: async function (quizID) {
		if (HDQ_A_LIMIT_RESULTS_MODE === "all") {
			return await HDQ_A_LIMIT_RESULTS.all(quizID);
		} else if (HDQ_A_LIMIT_RESULTS_MODE === "users") {
			let logged = await HDQ_A_LIMIT_RESULTS.users.isLoggedIn(quizID);
			logged = JSON.parse(logged);
			return await HDQ_A_LIMIT_RESULTS.users.status(logged);
		} else {
			return await HDQ_A_LIMIT_RESULTS.hybrid(quizID);
		}
	},
	all: async function (quizID) {
		let attempts = localStorage.getItem("hdq_a_limit_results_" + quizID);
		if (attempts === null) {
			attempts = 0;
		}
		if (attempts >= HDQ_A_LIMIT_RESULTS_MAX_ATTEMPTS) {
			return false;
		}
		return true;
	},
	users: {
		status: async function (logged) {
			if (logged.loggedin == "no" || logged.attempts >= HDQ_A_LIMIT_RESULTS_MAX_ATTEMPTS) {
				return false;
			}
			return true;
		},
		isLoggedIn: async function (quizID) {
			return await jQuery.ajax({
				type: "POST",
				data: {
					action: "hdq_a_limit_results_get_user",
					data: { quizID: quizID },
				},
				url: HDQ.VARS.ajax,
				success: async function (res) {
					return res;
				},
			});
		},
	},
	hybrid: async function (quizID) {
		let logged = await HDQ_A_LIMIT_RESULTS.users.isLoggedIn(quizID);
		logged = JSON.parse(logged);
		let loggedStatus = await HDQ_A_LIMIT_RESULTS.users.status(logged);

		if (logged.loggedin == "no") {
			return HDQ_A_LIMIT_RESULTS.all(quizID);
		} else {
			return loggedStatus;
		}
	},
	limit: function () {
		document.getElementsByClassName("hdq_quiz_wrapper")[0].innerHTML = HDQ_A_LIMIT_RESULTS_DESCRIPTION;
	},
};
HDQ_A_LIMIT_RESULTS.init(HDQ.VARS.id);

function hdq_a_limit_results_submit() {
	const quizID = HDQ.VARS.id;
	if (HDQ_A_LIMIT_RESULTS_MODE === "users") {
		hdq_a_limit_results_update_attempts(quizID, true);
	} else {
		if (HDQ_A_LIMIT_RESULTS_MODE === "all") {
			hdq_a_limit_results_update_attempts(quizID);
		} else {
			let isLoggedIn = parseInt(HDQ_A_IS_LOGGED_IN);
			if (isLoggedIn === 1) {
				isLoggedIn = true;
			} else {
				isLoggedIn = false;
			}
			hdq_a_limit_results_update_attempts(quizID, isLoggedIn);
		}
	}
	return {};
}

function hdq_a_limit_results_update_attempts(quizID, user = false) {
	if (user) {
		jQuery.ajax({
			type: "POST",
			data: {
				action: "hdq_a_limit_results_update_user",
				data: { quizID: quizID },
			},
			url: HDQ.VARS.ajax,
			success: function (res) {
				console.log(res);
			},
		});
	} else {
		let attempts = localStorage.getItem("hdq_a_limit_results_" + quizID);
		if (attempts === null) {
			attempts = 0;
		}
		attempts = attempts + 1;
		localStorage.setItem("hdq_a_limit_results_" + quizID, attempts);
	}
}
