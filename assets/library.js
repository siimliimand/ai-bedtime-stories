(function () {
	'use strict';

	var player = document.getElementById('absLibPlayer');

	if (!player || !player.classList.contains('abs-lib-player')) {
		return;
	}

	var audio = new Audio();
	var toggle = player.querySelector('.abs-lib-toggle');
	var closeBtn = player.querySelector('.abs-lib-close');
	var seek = player.querySelector('.abs-lib-seek');
	var cur = player.querySelector('.abs-lib-cur');
	var dur = player.querySelector('.abs-lib-dur');
	var titleEl = player.querySelector('.abs-lib-player-title');
	var coverEl = player.querySelector('.abs-lib-player-cover');
	var autoBtn = player.querySelector('.abs-lib-auto');
	var autoOn = false;
	var cards = Array.prototype.slice.call(document.querySelectorAll('.abs-lib-card'));
	var activeCard = null;
	var seeking = false;
	var lastTrack = 0;
	var listened = 0;
	var lastPos = null;

	var listenCfg = window.absListen || {};

	function trackListen(event, percent, keepalive) {
		if (!listenCfg.ajax || !activeCard) {
			return;
		}

		var body = new window.FormData();

		body.append('action', 'abs_track_listen');
		body.append('nonce', listenCfg.nonce || '');
		body.append('post_id', String(activeCard.getAttribute('data-post') || 0));
		body.append('event', event);
		body.append('percent', String(percent));

		window.fetch(listenCfg.ajax, { method: 'POST', body: body, credentials: 'same-origin', keepalive: !!keepalive }).catch(function () {});
	}

	function flush() {
		if (!activeCard || listened <= 0) {
			return;
		}

		trackListen('progress', listenedPercent(), true);
		listened = 0;
		lastPos = null;
	}

	function listenedPercent() {
		if (!isFinite(audio.duration) || audio.duration <= 0) {
			return 0;
		}

		return Math.max(0, Math.min(100, Math.round((listened / audio.duration) * 100)));
	}

	try {
		autoOn = window.localStorage.getItem('absLibAuto') === '1';
	} catch (e) {}

	function applyAutoState() {
		autoBtn.classList.toggle('abs-lib-on', autoOn);
		autoBtn.setAttribute('aria-pressed', autoOn ? 'true' : 'false');
	}

	applyAutoState();

	autoBtn.addEventListener('click', function () {
		autoOn = !autoOn;
		applyAutoState();

		try {
			window.localStorage.setItem('absLibAuto', autoOn ? '1' : '0');
		} catch (e) {}
	});

	function fmt(s) {
		s = Math.max(0, Math.floor(s || 0));
		var m = Math.floor(s / 60);
		var r = s % 60;

		return m + ':' + (r < 10 ? '0' : '') + r;
	}

	function clearButtons() {
		cards.forEach(function (card) {
			var btn = card.querySelector('.abs-lib-play');

			if (btn) {
				btn.textContent = '▶';
				btn.setAttribute('aria-label', btn.getAttribute('aria-label').replace(/^Peata/, 'Esita'));
			}
		});
	}

	function setActive(card, playing) {
		clearButtons();

		if (activeCard) {
			activeCard.classList.remove('abs-lib-active');
		}

		activeCard = card;

		if (!card) {
			return;
		}

		card.classList.add('abs-lib-active');

		var btn = card.querySelector('.abs-lib-play');

		if (btn) {
			btn.textContent = playing ? '❚❚' : '▶';
			btn.setAttribute('aria-label', btn.getAttribute('aria-label').replace(/^(Esita|Peata)/, playing ? 'Peata' : 'Esita'));
		}
	}

	function openPlayer(card) {
		titleEl.textContent = card.getAttribute('data-title') || '';
		coverEl.style.backgroundImage = card.getAttribute('data-image') ? 'url("' + card.getAttribute('data-image') + '")' : '';
		player.hidden = false;
	}

	function playCard(card) {
		var src = card.getAttribute('data-src') || '';

		if ('' === src) {
			return;
		}

		if (activeCard === card && !audio.paused) {
			audio.pause();
			setActive(card, false);

			return;
		}

		if (activeCard !== card && audio.src !== src) {
			flush();
		}

		var fresh = audio.src !== src || audio.ended;

		if (fresh) {
			listened = 0;
			lastPos = null;
		}

		if (audio.src !== src) {
			audio.src = src;
			audio.currentTime = 0;
			dur.textContent = '0:00';
		}

		openPlayer(card);
		setActive(card, true);

		audio.play().then(function () {
			if (fresh) {
				trackListen('start', 0);
			}
		}).catch(function () {
			setActive(card, false);
		});
	}

	cards.forEach(function (card) {
		card.addEventListener('click', function (event) {
			if (event.target.closest('.abs-lib-link')) {
				return;
			}

			playCard(card);
		});
	});

	toggle.addEventListener('click', function () {
		if (!activeCard) {
			return;
		}

		if (audio.paused) {
			audio.play().catch(function () {});
			setActive(activeCard, true);
		} else {
			audio.pause();
			setActive(activeCard, false);
		}
	});

	closeBtn.addEventListener('click', function () {
		flush();
		audio.pause();
		player.hidden = true;
		setActive(null, false);
	});

	audio.addEventListener('pause', function () {
		if (!activeCard) {
			return;
		}

		trackListen('progress', listenedPercent(), true);
	});

	audio.addEventListener('pagehide', flush);

	audio.addEventListener('seeked', function () {
		lastPos = audio.currentTime;
	});

	audio.addEventListener('timeupdate', function () {
		if (!audio.paused) {
			if (null !== lastPos && audio.currentTime > lastPos && audio.currentTime - lastPos < 2) {
				listened += audio.currentTime - lastPos;
			}

			lastPos = audio.currentTime;
		}

		if (seeking || !isFinite(audio.duration) || audio.duration <= 0) {
			return;
		}

		seek.value = String(Math.round((audio.currentTime / audio.duration) * 1000));
		cur.textContent = fmt(audio.currentTime);

		var now = Date.now();

		if (now - lastTrack >= 5000) {
			lastTrack = now;
			trackListen('progress', listenedPercent());
		}
	});

	audio.addEventListener('loadedmetadata', function () {
		dur.textContent = fmt(audio.duration);
	});

	seek.addEventListener('input', function () {
		seeking = true;

		if (isFinite(audio.duration) && audio.duration > 0) {
			cur.textContent = fmt((parseInt(seek.value, 10) / 1000) * audio.duration);
		}
	});

	seek.addEventListener('change', function () {
		if (isFinite(audio.duration) && audio.duration > 0) {
			audio.currentTime = (parseInt(seek.value, 10) / 1000) * audio.duration;
		}

		seeking = false;
	});

	audio.addEventListener('ended', function () {
		lastTrack = 0;
		listened = 0;
		lastPos = null;
		trackListen('end', 100);

		if (!autoOn) {
			setActive(null, false);

			return;
		}

		var index = activeCard ? cards.indexOf(activeCard) : -1;
		var next = index >= 0 && index + 1 < cards.length ? cards[index + 1] : null;

		if (next) {
			playCard(next);
		} else {
			setActive(null, false);
		}
	});
})();
