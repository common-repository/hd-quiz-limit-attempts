const _hdq_a_limit_results = {
	v: 0.1,
	data: {
		fields: {},
	},
	init: function () {
		console.log("HDFields init v" + _hdq_a_limit_results.v);

		_hdq_a_limit_results.tabs.init(); // set tab navigation
		_hdq_a_limit_results.radio.init(); // set radio listeners
		_hdq_a_limit_results.images.init(); // if any image or gallery inputs
		_hdq_a_limit_results.colour.init(); // colour inputs
		_hdq_a_limit_results.editor.init(); // tinyMCE editors

		let save_el = document.getElementById("hd_save");
		if (save_el) {
			save_el.addEventListener("click", _hdq_a_limit_results.save);
		}
	},
	tabs: {
		// currently only compatible with one tabbed container per page
		init: function () {
			const tab_nav_items = document.getElementsByClassName("hd_tab_nav_item");
			for (let i = 0; i < tab_nav_items.length; i++) {
				tab_nav_items[i].addEventListener("click", _hdq_a_limit_results.tabs.switch);
			}
		},
		switch: async function () {
			if (this.classList.contains("hd_tab_nav_item_active")) {
				return;
			}
			let id = this.getAttribute("data-id");
			await _hdq_a_limit_results.tabs.set(this, "hd_tab_nav_item_active");
			let el = document.getElementById("hd_tab_content_" + id);
			await _hdq_a_limit_results.tabs.set(el, "hd_tab_content_section_active");

			document.getElementsByClassName("hd_tabs_anchor")[0].scrollIntoView({ behavior: "smooth", block: "start", inline: "nearest" });
		},
		set: async function (el, className = "") {
			let active = document.getElementsByClassName(className);
			while (active.length > 0) {
				active[0].classList.remove(className);
			}
			el.classList.add(className);
		},
	},
	radio: {
		init: function () {
			let radio = document.getElementsByClassName("hd_input_radio");
			for (let i = 0; i < radio.length; i++) {
				let input = radio[i].getElementsByClassName("hd_radio_input");
				for (let x = 0; x < input.length; x++) {
					input[x].addEventListener("change", function () {
						_hdq_a_limit_results.radio.change(input[x], radio[i]);
					});
				}
			}
		},
		change: function (input, radio) {
			// once a radio option has been selected, radio value canot be unset
			let inputs = radio.getElementsByClassName("hd_radio_input");
			let hasSelection = false;
			for (let i = 0; i < inputs.length; i++) {
				if (inputs[i].checked == true) {
					hasSelection = true;
				}
			}
			if (!hasSelection) {
				if (inputs.length > 1) {
					input.checked = true;
					return;
				}
			}

			// now we ensure that only the current is checked
			if (input.checked != true) {
				return;
			}
			for (let i = 0; i < inputs.length; i++) {
				if (inputs[i] !== input) {
					inputs[i].checked = false;
				}
			}
		},
	},
	colour: {
		init: function () {
			const colours = document.getElementsByClassName("hd_colour");
			for (let i = 0; i < colours.length; i++) {
				colours[i].addEventListener("change", _hdq_a_limit_results.colour.change);
			}
		},
		change: function () {
			let value = this.value;
			value = value.toUpperCase();
			if (value.length >= 4 && value[0] === "#") {
				if (value.length !== 4 && value.length !== 7) {
					return;
				}
				this.nextSibling.style.backgroundColor = value;
				this.value = value;
			}
		},
	},
	images: {
		init: function () {
			const images = document.getElementsByClassName("hd_image");
			for (let i = 0; i < images.length; i++) {
				images[i].addEventListener("click", function () {
					let options = {
						title: this.getAttribute("data-title"),
						button: this.getAttribute("data-button"),
						multiple: this.getAttribute("data-multiple"),
					};
					_hdq_a_limit_results.images.load(this, options);
				});
			}
		},
		load: function (el, options) {
			let type = el.getAttribute("data-type");
			let frame = (wp.media.frames.file_frame = wp.media({
				title: options.title,
				button: {
					text: options.button,
				},
				multiple: options.multiple,
			}));
			// When an image is selected, run a callback.
			frame.on("select", function () {
				let attachment = frame.state().get("selection");
				if (type === "image") {
					setImage(el, attachment);
				} else if (type === "gallery") {
					setGallery(el, attachment);
				} else {
					console.log(attachment);
				}
			});
			frame.open();

			function setImage(el, attachment) {
				let id = el.getAttribute("id");
				attachment = attachment.first().toJSON();
				el.setAttribute("data-value", attachment.id);
				let image = attachment.sizes.full.url;
				if (attachment.sizes.medium) {
					image = attachment.sizes.medium.url;
				}
				el.innerHTML = `<img src = "${image}" alt = ""/>`;
				let remove = el.nextElementSibling.classList.add("active");
			}

			function setGallery(el, attachment) {
				attachment = attachment.toJSON();
				let id = el.getAttribute("data-id");

				for (let i = 0; i < attachment.length; i++) {
					let iid = attachment[i].id;
					let image = attachment[i].sizes.thumbnail.url;
					let html = `<div class = "hd_gallery_image" data-id = "${id}" data-type = "gallery" onClick = "_hd.images.remove(this)" data-value = "${iid}" role = "button" title = "click to delete, drag and drop to reorder"><img src = "${image}" alt = ""/></div>`;
					document.getElementById(id).insertAdjacentHTML("beforeend", html);
				}
				_hdq_a_limit_results.images.set_gallery_values(id);
			}
		},
		set_gallery_values: function (id) {
			let el = document.getElementById(id);
			let data = [];
			let images = el.getElementsByClassName("hdc_gallery_image");
			for (let i = 0; i < images.length; i++) {
				data.push(parseInt(images[i].getAttribute("data-value")));
			}
			data = data.join(",");
			el.setAttribute("data-value", data);
		},
		remove: function (target) {
			let id = target.getAttribute("data-id");
			let type = target.getAttribute("data-type");
			if (type === "image") {
				removeImage(target, id);
			} else if (type === "gallery") {
				removeGallery(target, id);
			}

			function removeImage(target, id) {
				target.classList.remove("active");
				let el = document.getElementById(id);
				el.innerHTML = "upload image";
				el.setAttribute("data-value", 0);
			}

			function removeGallery(target, id) {
				let el = document.getElementById(id);
				target.remove();
				_hdq_a_limit_results.images.set_gallery_values(id);
			}
		},
	},
	editor: {
		init: function () {
			const el = document.getElementsByClassName("hd_editor_input");
			for (let i = 0; i < el.length; i++) {
				el[i].setAttribute("data-type", "editor");
				if (el[i].classList.contains("hd_editor_required")) {
					el[i].setAttribute("data-required", "required");
				}
			}
		},
	},
	save: async function () {
		if (this.classList.contains("disabled")) {
			return;
		}
		this.classList.add("disabled");
		let label = this.innerHTML;
		this.innerHTML = "...";
		let action = this.getAttribute("data-action");

		let valid = await _hdq_a_limit_results.validate.init();
		if (!valid) {
			this.innerHTML = label;
			this.classList.remove("disabled");
		} else {
			await _hdq_a_limit_results.ajax(action, _hdq_a_limit_results.data.fields, this);
		}
	},
	validate: {
		init: async function () {
			// get all fields
			_hdq_a_limit_results.data.fields = {};
			let valid = true;
			let fields = document.getElementsByClassName("hderp");
			for (let i = 0; i < fields.length; i++) {
				let type = fields[i].getAttribute("data-type");
				let v = await _hdq_a_limit_results.validate.field[type](fields[i]);
				v = await _hdq_a_limit_results.validate.required(fields[i], v);
				if (!v.status) {
					valid = false;
					fields[i].classList.add("hd_error");
				} else {
					fields[i].classList.remove("hd_error");
				}

				v.id = fields[i].getAttribute("id");

				v.type = type;
				_hdq_a_limit_results.data.fields[v.id] = {
					id: v.id,
					type: v.type,
					value: v.value,
				};
			}
			return valid;
		},
		required: async function (el, v) {
			let required = el.getAttribute("data-required");
			if (required === "required") {
				if (v.value == "") {
					v.status = false;
				}
			}
			return v;
		},
		field: {
			text: async function (field) {
				let v = {
					value: field.value,
					status: true,
				};
				return v;
			},
			textarea: async function (field) {
				let v = {
					value: field.value,
					status: true,
				};
				return v;
			},
			email: async function (field) {
				let v = {
					value: field.value,
					status: await isEmail(field.value),
				};
				return v;

				async function isEmail(email) {
					let re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
					return re.test(String(email).toLowerCase());
				}
			},
			website: async function (field) {
				let v = {
					value: field.value,
					status: await isEmail(field.value),
				};
				return v;

				async function isWebsite(website = "") {
					if (website === "localhost") {
						return true;
					}
					try {
						website = new URL(website);
						return true;
					} catch (error) {
						return false;
					}
				}
			},
			integer: async function (field) {
				let v = {
					value: parseInt(field.value),
					status: true,
				};
				return v;
			},
			float: async function (field) {
				let v = {
					value: parseFloat(field.value),
					status: true,
				};
				return v;
			},
			currency: async function (field) {
				let v = {
					value: parseFloat(field.value).toFixed(2),
					status: true,
				};
				return v;
			},
			colour: async function (field) {
				let v = {
					value: field.value,
					status: true,
				};
				return v;
			},
			image: async function (field) {
				let v = {
					value: parseInt(field.value),
					status: true,
				};
				return v;
			},
			gallery: async function (field) {
				let v = {
					value: field.value,
					status: true,
				};
				return v;
			},
			select: async function (field) {
				let v = {
					value: field.value,
					status: true,
				};
				return v;
			},
			radio: async function (field) {
				let v = {
					value: "",
					status: false,
				};

				let data = "";
				let radios = field.getElementsByClassName("hd_radio_input");
				for (let i = 0; i < radios.length; i++) {
					if (radios[i].checked) {
						data = radios[i].value;
						break;
					}
				}

				v = {
					value: data,
					status: true,
				};
				return v;
			},
			checkbox: async function (field) {
				let v = {
					value: "",
					status: false,
				};
				let data = [];
				let checkboxes = field.getElementsByClassName("hd_check_input");
				for (let i = 0; i < checkboxes.length; i++) {
					if (checkboxes[i].checked) {
						data.push(checkboxes[i].value);
					}
				}

				// if user selects nothing we don't want field `default` to override that
				if (data.length === 0) {
					data = ["hd_null"];
				}

				v = {
					value: data,
					status: true,
				};
				return v;
			},
			date: async function (field) {
				let v = {
					value: field.value,
					status: true,
				};
				return v;
			},
			editor: async function (field) {
				await tinyMCE.triggerSave();
				let v = {
					value: field.value,
					status: true,
				};
				return v;
			},
			action: async function (field) {
				console.log(field);
				let action = field.getAttribute("data-action");
				// TODO: Check if action is included here, or if custom
				let v = {
					value: "",
					status: true,
				};
				return v;
			},
		},
	},
	ajax: async function (action = "", data, el = null, callback = null) {
		if (action == "") {
			console.warn("HDC.ajax: No action was provided");
			return;
		}
		let label = "";
		if (el !== null) {
			label = el.innerText;
			if (el.getAttribute("data-label") != "") {
				label = el.getAttribute("data-label");
			}
			el.classList.add("disabled");
			el.innerText = "...";
		}

		jQuery.ajax({
			type: "POST",
			data: {
				action: action,
				payload: data,
			},
			url: ajaxurl,
			success: function (data) {
				try {
					let json = JSON.parse(data);
					console.log(json);
					if ((json.status = "success")) {
						if (callback !== null) {
							callback(data);
						}
					}
				} catch (e) {
					console.log(data);
					console.warn(e);
				}
			},
			complete: function () {
				if (el !== null) {
					el.classList.remove("disabled");
					el.innerText = label;
				}
			},
		});
	},
};
_hdq_a_limit_results.init();
