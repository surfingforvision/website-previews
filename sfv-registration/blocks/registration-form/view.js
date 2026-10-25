/**
 * Registration form, in the browser. Plain script (no build step), loaded as
 * the block's viewScript so WordPress's content filters never touch it.
 *
 * - On load: moves focus to the error summary (after a server-side check found
 *   problems) or to the thank-you heading, so screen reader users hear it.
 * - On submit: runs the same checks as the server, shows each field's message
 *   and builds the "There is a problem" summary, then moves focus to it.
 * Messages come from PHP (sfv_register_messages) via data-sfv-validation.
 */
(function () {
	var target = document.getElementById('sfv-error-summary') || document.getElementById('sfv-success-title');

	if (target) {
		target.focus();
	}
})();

(function () {
	var form = document.getElementById('register-form');

	if (!form) {
		return;
	}

	var data = JSON.parse(form.getAttribute('data-sfv-validation'));
	var messages = data.messages;
	var targets = data.targets;
	var summaryTitle = data.summaryTitle;
	var errorPrefix = data.errorPrefix;
	var baseTitle = document.title.replace(/^Error: /, '');
	var validationActive = form.dataset.validationActive === '1';

	function isValidName(value) {
		var name = value.trim().replace(/\s+/g, ' ');

		return name.length >= 1 && name.length <= 60 && /^[\p{L}\p{M}\u0027\u2019. -]+$/u.test(name) && /\p{L}/u.test(name);
	}

	function isValidEmail(value) {
		return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.trim());
	}

	function isValidPhone(value) {
		var digits = value.replace(/\D+/g, '');

		if (digits.length === 11 && digits.charAt(0) === '1') {
			digits = digits.slice(1);
		}

		return digits.length === 10 && !/[01]/.test(digits.charAt(0)) && !/[01]/.test(digits.charAt(3));
	}

	function isValidAge(value) {
		var age = Number(value.trim());

		return /^\d+$/.test(value.trim()) && age >= 1 && age <= 120;
	}

	function value(name) {
		var input = form.querySelector('[name="' + name + '"]');

		return input ? input.value : '';
	}

	function checked(name) {
		return !!form.querySelector('[name="' + name + '"]:checked');
	}

	var checks = {
		first_name: function () { return isValidName(value('first_name')); },
		last_name: function () { return isValidName(value('last_name')); },
		registration_type: function () { return checked('registration_type'); },
		email: function () { return isValidEmail(value('email')); },
		phone: function () { return isValidPhone(value('phone')); },
		age: function () { return isValidAge(value('age')); },
		release_ack: function () { return checked('release_ack'); }
	};

	function errorId(name) {
		return 'sfv-' + name.replace(/_/g, '-') + '-error';
	}

	function field(name) {
		var input = form.querySelector('[name="' + name + '"]');

		return input ? input.closest('.sfv-field') : null;
	}

	// Inputs keep their hint in aria-describedby; the error id is added or removed.
	function setDescribedBy(el, id, on) {
		var ids = (el.getAttribute('aria-describedby') || '').split(' ').filter(function (x) { return x && x !== id; });

		if (on) {
			ids.push(id);
		}

		if (ids.length) {
			el.setAttribute('aria-describedby', ids.join(' '));
		} else {
			el.removeAttribute('aria-describedby');
		}
	}

	function showInline(name, valid) {
		var wrap = field(name);
		var id = errorId(name);
		var error = document.getElementById(id);
		var isGroup = name === 'registration_type';

		if (!wrap) {
			return;
		}

		wrap.classList.toggle('has-error', !valid);

		if (!valid && !error) {
			error = document.createElement('p');
			error.className = 'sfv-form-error';
			error.id = id;
			error.innerHTML = '<span class="sfv-sr-only"></span>';
			error.firstChild.textContent = errorPrefix + ' ';
			error.appendChild(document.createTextNode(messages[name]));
			wrap.appendChild(error);
		} else if (valid && error) {
			error.remove();
		}

		form.querySelectorAll('[name="' + name + '"]').forEach(function (control) {
			if (valid) {
				control.removeAttribute('aria-invalid');
			} else {
				control.setAttribute('aria-invalid', 'true');
			}
			if (!isGroup) {
				setDescribedBy(control, id, !valid);
			}
		});

		if (isGroup) {
			setDescribedBy(wrap, id, !valid);
		}
	}

	function renderSummary(failed) {
		var summary = document.getElementById('sfv-error-summary');

		if (!failed.length) {
			if (summary) {
				summary.remove();
			}
			document.title = baseTitle;
			return;
		}

		if (!summary) {
			summary = document.createElement('div');
			summary.className = 'sfv-error-summary';
			summary.id = 'sfv-error-summary';
			summary.tabIndex = -1;
			summary.setAttribute('role', 'region');
		summary.setAttribute('aria-labelledby', 'sfv-error-summary-title');
			form.insertBefore(summary, form.firstChild);
		}

		summary.innerHTML = '<h3 id="sfv-error-summary-title"></h3><ul></ul>';
		summary.firstChild.textContent = summaryTitle;
		failed.forEach(function (name) {
			var li = document.createElement('li');
			var a = document.createElement('a');
			a.href = '#' + targets[name];
			a.textContent = messages[name];
			li.appendChild(a);
			summary.lastChild.appendChild(li);
		});

		document.title = errorPrefix + ' ' + baseTitle;
		summary.focus();
	}

	form.addEventListener('submit', function (event) {
		var failed = Object.keys(checks).filter(function (name) {
			var valid = checks[name]();
			showInline(name, valid);
			return !valid;
		});

		validationActive = true;

		if (failed.length) {
			event.preventDefault();
			renderSummary(failed);
		}
	});

	// After the first attempt, fix-as-you-go: update each field's own
	// message quietly (no announcements while typing).
	function recheck(event) {
		if (validationActive && event.target.name && checks[event.target.name]) {
			showInline(event.target.name, checks[event.target.name]());
		}
	}

	form.addEventListener('input', recheck);
	form.addEventListener('change', recheck);

	// Summary links move focus to the field and show its label.
	form.addEventListener('click', function (event) {
		var link = event.target.closest('.sfv-error-summary a');

		if (!link) {
			return;
		}

		var target = document.getElementById(link.getAttribute('href').slice(1));

		if (target) {
			event.preventDefault();
			target.focus({ preventScroll: true });
			(target.closest('fieldset') || target.closest('.sfv-field') || target).scrollIntoView({ block: 'start' });
		}
	});
})();
