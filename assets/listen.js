(function () {
	'use strict';

	var cfg = window.absListen || {};
	var audio = document.querySelector('.abs-story-audio audio');
	var lastSent = 0;
	var listened = 0;
	var lastPos = null;
	var started = false;

	if (!audio || !cfg.ajax) {
		return;
	}

	function send(event, percent, keepalive) {
		var body = new window.FormData();

		body.append('action', 'abs_track_listen');
		body.append('nonce', cfg.nonce || '');
		body.append('post_id', String(cfg.post || 0));
		body.append('event', event);
		body.append('percent', String(percent));

		window.fetch(cfg.ajax, { method: 'POST', body: body, credentials: 'same-origin', keepalive: !!keepalive }).catch(function () {});
	}

	function flush() {
		if (!started || listened <= 0) {
			return;
		}

		lastSent = Date.now();
		send('progress', percent(), true);
	}

	function percent() {
		if (!isFinite(audio.duration) || audio.duration <= 0) {
			return 0;
		}

		return Math.max(0, Math.min(100, Math.round((listened / audio.duration) * 100)));
	}

	audio.addEventListener('play', function () {
		if (started) {
			return;
		}

		if (audio.currentTime < 1 || audio.ended) {
			listened = 0;
			lastPos = null;
			started = true;
			send('start', 0);
		}
	});

	audio.addEventListener('pause', flush);

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

		var now = Date.now();

		if (now - lastSent < 5000) {
			return;
		}

		lastSent = now;
		send('progress', percent());
	});

	audio.addEventListener('ended', function () {
		lastSent = 0;
		started = false;
		send('end', 100);
	});
})();
