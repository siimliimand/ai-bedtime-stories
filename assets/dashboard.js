(function () {
	'use strict';

	var cfg = window.absQuiz || {};
	var root = document.querySelector('.abs-quiz');

	if (!root || !cfg.ajax) {
		return;
	}

	var steps = {
		intro: root.querySelector('.abs-quiz-step-intro'),
		price: root.querySelector('.abs-quiz-step-price'),
		done: root.querySelector('.abs-quiz-step-done')
	};
	var input = root.querySelector('.abs-quiz-price-input');
	var answerEl = root.querySelector('.abs-quiz-answer');
	var state = {
		interest: root.getAttribute('data-interest') || '',
		price: root.getAttribute('data-price') || ''
	};

	function show(step) {
		Object.keys(steps).forEach(function (key) {
			if (steps[key]) {
				steps[key].hidden = key !== step;
			}
		});
	}

	function summary() {
		if ('no' === state.interest) {
			return 'Ei';
		}

		if ('yes' === state.interest) {
			return state.price ? 'Jah — ' + state.price : 'Jah';
		}

		return '';
	}

	function renderDone() {
		if (answerEl) {
			answerEl.textContent = summary();
		}

		show('done');
	}

	function save(field, value) {
		var body = new window.FormData();

		body.append('action', 'abs_quiz');
		body.append('nonce', cfg.nonce || '');
		body.append('field', field);
		body.append('value', value);

		return window.fetch(cfg.ajax, { method: 'POST', body: body, credentials: 'same-origin' })
			.then(function (res) {
				return res.json();
			})
			.then(function (json) {
				if (json && json.success && json.data) {
					state.interest = String(json.data.interest || '');
					state.price = String(json.data.price || '');
				}

				return json;
			})
			.catch(function () {
				return null;
			});
	}

	root.addEventListener('click', function (event) {
		var target = event.target && event.target.closest ? event.target.closest('.abs-quiz-btn') : null;

		if (!target) {
			return;
		}

		if (target.classList.contains('abs-quiz-yes')) {
			state.interest = 'yes';
			save('interest', 'yes');
			show('price');

			if (input) {
				input.focus();
			}

			return;
		}

		if (target.classList.contains('abs-quiz-no')) {
			save('interest', 'no').then(renderDone);
			return;
		}

		if (target.classList.contains('abs-quiz-send')) {
			var value = input ? input.value.replace(/\s+/g, ' ').trim() : '';

			if (!value) {
				if (input) {
					input.focus();
				}

				return;
			}

			target.disabled = true;
			save('price', value).then(function () {
				target.disabled = false;
				renderDone();
			});
			return;
		}

		if (target.classList.contains('abs-quiz-back')) {
			show('intro');
			return;
		}

		if (target.classList.contains('abs-quiz-change')) {
			show('intro');
		}
	});

	if (input) {
		input.addEventListener('keydown', function (event) {
			if ('Enter' !== event.key) {
				return;
			}

			event.preventDefault();

			var send = root.querySelector('.abs-quiz-send');

			if (send) {
				send.click();
			}
		});
	}
})();
