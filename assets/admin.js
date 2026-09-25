(function () {
	'use strict';

	if (typeof absAdmin === 'undefined') {
		return;
	}

	function post(action, data, nonce) {
		var form = new FormData();
		form.append('action', action);
		form.append('nonce', nonce || absAdmin.nonce);

		if (data) {
			Object.keys(data).forEach(function (key) {
				form.append(key, data[key]);
			});
		}

		return fetch(absAdmin.ajaxUrl, {
			method: 'POST',
			body: form,
			credentials: 'same-origin'
		}).then(function (response) {
			return response.text().then(function (text) {
				var parsed = null;

				try {
					parsed = JSON.parse(text);
				} catch (err) {
					parsed = null;
				}

				if (parsed === null) {
					throw new Error(absAdmin.serverErrorLabel || 'Server error — check PHP error log.');
				}

				if (!parsed.success) {
					var message = (parsed.data && parsed.data.message) ? parsed.data.message : '';
					throw new Error(message || (absAdmin.failureLabel || 'Request failed'));
				}

				return parsed.data || {};
			});
		});
	}

	function showMessage(container, message) {
		if (!container) {
			return;
		}

		container.textContent = message;
		container.classList.remove('notice-success');
		container.classList.add('notice-error');
		container.hidden = false;
	}

	function clearMessage(container) {
		if (!container) {
			return;
		}

		container.textContent = '';
		container.hidden = true;
	}

	function selectedCharacter() {
		var select = document.getElementById('abs-character-select');

		if (!select) {
			return -1;
		}

		var value = parseInt(select.value, 10);

		return isNaN(value) ? -1 : value;
	}

	var ideasButton = document.getElementById('abs-generate-ideas');
	var ideasSpinner = document.getElementById('abs-ideas-spinner');
	var ideasFeedback = document.getElementById('abs-ideas-feedback');
	var ideasGrid = document.getElementById('abs-ideas-grid');
	var ideasRunning = false;

	function setIdeasRunning(running) {
		ideasRunning = running;

		if (ideasButton) {
			ideasButton.disabled = running;
			ideasButton.textContent = running
				? (absAdmin.generatingLabel || 'Generating…')
				: (absAdmin.generateLabel || 'Generate ideas');
		}

		if (ideasSpinner) {
			ideasSpinner.hidden = !running;
		}
	}

	function createIdeaCard(idea) {
		var card = document.createElement('article');
		card.className = 'abs-idea-card';

		if (idea.id) {
			card.dataset.id = String(idea.id);
		}

		var heading = document.createElement('h3');
		heading.className = 'abs-idea-title';
		heading.textContent = idea.title;
		card.appendChild(heading);

		if (typeof idea.neuro === 'string' && idea.neuro) {
			var neuro = document.createElement('span');
			neuro.className = 'abs-idea-neuro';
			neuro.textContent = idea.neuro;
			card.appendChild(neuro);
		}

		if (typeof idea.character === 'string' && idea.character) {
			var character = document.createElement('span');
			character.className = 'abs-idea-character';
			character.textContent = idea.character;
			card.appendChild(character);
		}

		var paragraph = document.createElement('p');
		paragraph.className = 'abs-idea-desc';
		paragraph.textContent = idea.description;
		card.appendChild(paragraph);

		var footer = document.createElement('footer');

		var button = document.createElement('button');
		button.type = 'button';
		button.className = 'button button-primary abs-generate-story';
		if (idea.id) {
			button.dataset.id = String(idea.id);
		}
		button.setAttribute('data-title', idea.title);
		button.setAttribute('data-description', idea.description);
		button.textContent = 'Generate full story';
		footer.appendChild(button);

		var discard = document.createElement('button');
		discard.type = 'button';
		discard.className = 'button-link abs-discard-idea';
		if (idea.id) {
			discard.dataset.id = String(idea.id);
		}
		discard.textContent = 'Discard';
		footer.appendChild(discard);

		var edit = document.createElement('button');
		edit.type = 'button';
		edit.className = 'button-link abs-edit-idea';
		if (idea.id) {
			edit.dataset.id = String(idea.id);
		}
		edit.textContent = absAdmin.ideaEditLabel || 'Muuda';
		footer.appendChild(edit);

		var error = document.createElement('span');
		error.className = 'abs-card-error';
		footer.appendChild(error);

		card.appendChild(footer);

		return card;
	}

	function ensureGridNotEmpty() {
		if (!ideasGrid) {
			return;
		}

		if (!ideasGrid.querySelector('.abs-idea-card') && !ideasGrid.querySelector('.abs-no-ideas')) {
			var empty = document.createElement('p');
			empty.className = 'abs-no-ideas';
			empty.textContent = 'No pending ideas. Click "Generate ideas" to create some.';
			ideasGrid.appendChild(empty);
		}
	}

	function renderIdeas(ideas) {
		if (!ideasGrid) {
			return;
		}

		var empty = ideasGrid.querySelector('.abs-no-ideas');

		if (empty) {
			empty.remove();
		}

		var fragment = document.createDocumentFragment();

		ideas.forEach(function (idea) {
			fragment.appendChild(createIdeaCard(idea));
		});

		ideasGrid.insertBefore(fragment, ideasGrid.firstChild);
	}

	function generateIdeas() {
		if (ideasRunning) {
			return;
		}

		setIdeasRunning(true);
		clearMessage(ideasFeedback);

		post('abs_generate_ideas', { char: selectedCharacter() }).then(function (data) {
			renderIdeas(Array.isArray(data.ideas) ? data.ideas : []);
			setIdeasRunning(false);
		}).catch(function (error) {
			showMessage(ideasFeedback, error && error.message ? error.message : (absAdmin.failureLabel || 'Request failed'));
			setIdeasRunning(false);
		});
	}

	if (ideasButton) {
		ideasButton.addEventListener('click', generateIdeas);
	}

	function handleEditIdea(button) {
		var card = button.closest('.abs-idea-card');

		if (!card || card.classList.contains('abs-card-editing') || card.classList.contains('abs-card-loading') || card.classList.contains('abs-card-success')) {
			return;
		}

		var titleEl = card.querySelector('.abs-idea-title');
		var descEl = card.querySelector('.abs-idea-desc');
		var footer = card.querySelector('footer');

		if (!titleEl || !descEl || !footer || !card.getAttribute('data-id')) {
			return;
		}

		var originalTitle = titleEl.textContent;
		var originalDesc = descEl.textContent;

		card.classList.add('abs-card-editing');
		titleEl.hidden = true;
		descEl.hidden = true;
		Array.prototype.forEach.call(footer.children, function (el) {
			el.hidden = true;
		});

		var form = document.createElement('div');
		form.className = 'abs-idea-edit';

		var titleField = document.createElement('p');
		titleField.className = 'abs-idea-edit-field';
		var titleLabel = document.createElement('label');
		titleLabel.textContent = absAdmin.ideaTitleLabel || 'Pealkiri';
		var titleInput = document.createElement('input');
		titleInput.type = 'text';
		titleInput.className = 'regular-text abs-idea-title-input';
		titleInput.value = originalTitle;
		titleField.appendChild(titleLabel);
		titleField.appendChild(titleInput);
		form.appendChild(titleField);

		var descField = document.createElement('p');
		descField.className = 'abs-idea-edit-field';
		var descLabel = document.createElement('label');
		descLabel.textContent = absAdmin.ideaDescLabel || 'Kirjeldus';
		var descInput = document.createElement('textarea');
		descInput.className = 'abs-idea-desc-input';
		descInput.rows = 4;
		descInput.value = originalDesc;
		descField.appendChild(descLabel);
		descField.appendChild(descInput);
		form.appendChild(descField);

		var actions = document.createElement('p');
		actions.className = 'abs-idea-edit-actions';

		var save = document.createElement('button');
		save.type = 'button';
		save.className = 'button button-primary abs-idea-save';
		save.textContent = absAdmin.ideaSaveLabel || 'Salvesta';
		actions.appendChild(save);

		var cancel = document.createElement('button');
		cancel.type = 'button';
		cancel.className = 'button abs-idea-cancel';
		cancel.textContent = absAdmin.ideaCancelLabel || 'Katkesta';
		actions.appendChild(cancel);

		var error = document.createElement('span');
		error.className = 'abs-card-error';
		actions.appendChild(error);

		form.appendChild(actions);
		card.insertBefore(form, footer);

		function exitEdit() {
			form.remove();
			titleEl.hidden = false;
			descEl.hidden = false;
			Array.prototype.forEach.call(footer.children, function (el) {
				el.hidden = false;
			});
			card.classList.remove('abs-card-editing');
		}

		cancel.addEventListener('click', exitEdit);

		save.addEventListener('click', function () {
			var newTitle = titleInput.value.trim();
			var newDesc = descInput.value.trim();

			if ('' === newTitle || '' === newDesc) {
				error.textContent = absAdmin.ideaRequiredLabel || 'Pealkiri ja kirjeldus on kohustuslikud.';
				return;
			}

			save.disabled = true;
			save.textContent = absAdmin.ideaSavingLabel || 'Salvestan…';
			error.textContent = '';

			post('abs_update_idea', { idea_id: card.getAttribute('data-id'), title: newTitle, description: newDesc }, absAdmin.nonce).then(function () {
				titleEl.textContent = newTitle;
				descEl.textContent = newDesc;

				var gen = card.querySelector('.abs-generate-story');

				if (gen) {
					gen.setAttribute('data-title', newTitle);
					gen.setAttribute('data-description', newDesc);
				}

				exitEdit();
			}).catch(function (err) {
				save.disabled = false;
				save.textContent = absAdmin.ideaSaveLabel || 'Salvesta';
				error.textContent = err && err.message ? err.message : (absAdmin.failureLabel || 'Request failed');
			});
		});
	}

	function renderSuccess(card, data) {
		card.textContent = '';
		card.classList.remove('abs-card-loading');
		card.classList.add('abs-card-success');

		var heading = document.createElement('h3');
		heading.textContent = absAdmin.storyCreated || 'Story created';
		card.appendChild(heading);

		var title = document.createElement('p');
		title.className = 'abs-story-title';
		title.textContent = data.title || '';
		card.appendChild(title);

		var words = document.createElement('p');
		words.className = 'abs-story-words';
		words.textContent = String(data.words || 0) + ' ' + (absAdmin.wordsLabel || 'words');
		card.appendChild(words);

		if (data.edit_link) {
			var link = document.createElement('a');
			link.className = 'button button-primary';
			link.href = data.edit_link;
			link.textContent = absAdmin.editStoryLabel || 'Edit story';
			card.appendChild(link);
		}
	}

	if (ideasGrid) {
		ideasGrid.addEventListener('click', function (event) {
			var editButton = event.target.closest('.abs-edit-idea');

			if (editButton && ideasGrid.contains(editButton)) {
				handleEditIdea(editButton);

				return;
			}

			var button = event.target.closest('.abs-generate-story');

			if (!button || !ideasGrid.contains(button)) {
				return;
			}

			var card = button.closest('.abs-idea-card');

			if (!card || card.classList.contains('abs-card-loading') || card.classList.contains('abs-card-success')) {
				return;
			}

			var title = button.getAttribute('data-title') || '';
			var description = button.getAttribute('data-description') || '';
			var ideaId = button.getAttribute('data-id') || card.getAttribute('data-id') || '';
			var payload = { title: title, description: description, char: selectedCharacter() };

			if (ideaId) {
				payload.idea_id = ideaId;
			}

			button.disabled = true;
			button.textContent = absAdmin.writingLabel || 'Writing story…';
			card.classList.add('abs-card-loading');

			var errorSpan = card.querySelector('.abs-card-error');

			if (errorSpan) {
				errorSpan.textContent = '';
			}

			post('abs_generate_story', payload).then(function (data) {
				renderSuccess(card, data);
			}).catch(function (error) {
				card.classList.remove('abs-card-loading');
				button.disabled = false;
				button.textContent = 'Generate full story';

				if (!errorSpan) {
					var footer = card.querySelector('footer');

					if (footer) {
						errorSpan = document.createElement('span');
						errorSpan.className = 'abs-card-error';
						footer.appendChild(errorSpan);
					}
				}

				if (errorSpan) {
					errorSpan.textContent = error && error.message ? error.message : (absAdmin.failureLabel || 'Request failed');
				}
			});
		});

		ideasGrid.addEventListener('click', function (event) {
			var button = event.target.closest('.abs-discard-idea');

			if (!button || !ideasGrid.contains(button)) {
				return;
			}

			var card = button.closest('.abs-idea-card');
			var ideaId = button.getAttribute('data-id') || (card ? card.getAttribute('data-id') : '');

			if (!ideaId) {
				return;
			}

			button.disabled = true;

			post('abs_discard_idea', { idea_id: ideaId }).then(function () {
				if (card) {
					card.remove();
				}

				ensureGridNotEmpty();
			}).catch(function (error) {
				button.disabled = false;

				var errorSpan = card ? card.querySelector('.abs-card-error') : null;

				if (!errorSpan && card) {
					var footer = card.querySelector('footer');

					if (footer) {
						errorSpan = document.createElement('span');
						errorSpan.className = 'abs-card-error';
						footer.appendChild(errorSpan);
					}
				}

				if (errorSpan) {
					errorSpan.textContent = error && error.message ? error.message : (absAdmin.failureLabel || 'Request failed');
				}
			});
		});
	}

	var storiesTable = document.querySelector('.wp-list-table');

	function buildAudioButton(storyId, label) {
		var wrap = document.createElement('p');
		var button = document.createElement('button');

		button.type = 'button';
		button.className = 'button button-small';
		button.setAttribute('data-abs-audio', '1');
		button.setAttribute('data-story-id', storyId);
		button.textContent = label;
		wrap.appendChild(button);

		return wrap;
	}

	function prettyAudioJson(lines) {
		return JSON.stringify({ lines: lines }, null, 4).replace(/\\u([0-9a-fA-F]{4})/g, function (match, code) {
			return String.fromCharCode(parseInt(code, 16));
		});
	}

	function buildAudioEditor(lines) {
		var editor = document.createElement('textarea');

		editor.className = 'abs-audio-editor';
		editor.setAttribute('rows', '10');
		editor.hidden = true;
		editor.value = prettyAudioJson(lines);

		return editor;
	}

	function buildAudioEditOpen(storyId) {
		var button = document.createElement('button');

		button.type = 'button';
		button.className = 'button button-small abs-audio-edit-open';
		button.setAttribute('data-story-id', storyId);
		button.textContent = absAdmin.audioEditLabel || 'Muuda';

		return button;
	}

	function renderAudioCell(cell, storyId, lines, fullUrl) {
		if (!cell) {
			return;
		}

		cell.textContent = '';

		var status = document.createElement('span');
		status.className = 'abs-audio-status';
		status.textContent = '✓ ' + (absAdmin.audioDoneLabel || 'Valmis') + ' (' + String(lines.length) + ' ' + (absAdmin.audioRowsLabel || 'rida') + ')';
		cell.appendChild(status);

		if (fullUrl) {
			cell.appendChild(buildAudioFullPlay(fullUrl));
		}

		cell.appendChild(buildAudioEditOpen(storyId));
		cell.appendChild(buildAudioEditor(lines));
		cell.appendChild(buildAudioButton(storyId, absAdmin.audioRetryLabel || 'Genereeri uuesti'));
	}

	function audioVoiceOptions() {
		var voices = Array.isArray(absAdmin.audioVoices) ? absAdmin.audioVoices : [];
		var options = [];

		voices.forEach(function (voice) {
			var name = String(voice && voice.name ? voice.name : '').trim();

			if ('' === name) {
				return;
			}

			options.push({
				name: name,
				description: String(voice && voice.description ? voice.description : '').trim()
			});
		});

		return options;
	}

	function clampAudioPause(value) {
		return Math.min(5000, Math.max(0, Math.round(Number(value) || 0)));
	}

	function buildAudioLineField(labelText, field) {
		var wrap = document.createElement('p');
		wrap.className = 'abs-audio-line-field';

		var label = document.createElement('label');
		label.textContent = labelText;
		wrap.appendChild(label);
		wrap.appendChild(field);

		return wrap;
	}

	function buildAudioLineRow(line, index, audio) {
		var row = document.createElement('div');
		row.className = 'abs-audio-line-row';

		var voices = audioVoiceOptions();
		var speaker = String(line && line.speaker ? line.speaker : '').trim();

		var select = document.createElement('select');
		select.className = 'abs-audio-line-speaker';

		var matched = false;

		voices.forEach(function (voice) {
			var option = document.createElement('option');
			option.value = voice.name;
			option.textContent = '' !== voice.description ? (voice.name + ' — ' + voice.description) : voice.name;

			if (speaker.toLowerCase() === voice.name.toLowerCase()) {
				matched = true;
				option.selected = true;
			}

			select.appendChild(option);
		});

		if (!matched && '' !== speaker) {
			var extra = document.createElement('option');
			extra.value = speaker;
			extra.textContent = speaker;
			extra.selected = true;
			select.insertBefore(extra, select.firstChild);
		}

		row.appendChild(buildAudioLineField(absAdmin.audioSpeakerLabel || 'Kõneleja', select));

		var tone = document.createElement('input');
		tone.type = 'text';
		tone.className = 'abs-audio-line-tone';
		tone.value = String(line && line.tone ? line.tone : '');
		row.appendChild(buildAudioLineField(absAdmin.audioToneLabel || 'Toon', tone));

		var pause = document.createElement('input');
		pause.type = 'number';
		pause.className = 'abs-audio-line-pause';
		pause.min = '0';
		pause.max = '5000';
		pause.step = '100';
		pause.value = String(line && undefined !== line.pause_after_ms ? clampAudioPause(line.pause_after_ms) : 400);
		row.appendChild(buildAudioLineField(absAdmin.audioPauseLabel || 'Paus (ms)', pause));

		var text = document.createElement('textarea');
		text.className = 'abs-audio-line-text';
		text.rows = 2;
		text.value = String(line && line.text ? line.text : '');
		row.appendChild(buildAudioLineField(absAdmin.audioTextLabel || 'Tekst', text));

		row.appendChild(buildAudioLineAudio(index, audio));

		var remove = document.createElement('button');
		remove.type = 'button';
		remove.className = 'button-link button-link-delete abs-audio-line-remove';
		remove.textContent = absAdmin.audioRemoveLineLabel || 'Eemalda';
		row.appendChild(remove);

		return row;
	}

	function buildAudioLineAudio(index, audio) {
		var wrap = document.createElement('div');
		wrap.className = 'abs-audio-line-audio';

		var gen = document.createElement('button');

		gen.type = 'button';
		gen.className = 'button button-small abs-audio-line-gen';
		gen.setAttribute('data-line', String(index));

		if (absAdmin.audioTtsEnabled) {
			gen.textContent = audio && audio.url ? (absAdmin.audioLineRegenLabel || 'Genereeri uuesti') : (absAdmin.audioLineGenLabel || 'Genereeri');
		} else {
			gen.disabled = true;
			gen.setAttribute('title', absAdmin.audioLineKeyMissingLabel || 'Jutusta API võti on seadistamata.');
			gen.textContent = absAdmin.audioLineGenLabel || 'Genereeri';
		}

		wrap.appendChild(gen);

		if (audio && audio.url) {
			wrap.appendChild(buildAudioLinePlay(String(audio.url)));
		}

		return wrap;
	}

	function buildAudioFullPlay(url) {
		var play = document.createElement('button');

		play.type = 'button';
		play.className = 'button button-small abs-audio-full-play';
		play.setAttribute('aria-label', absAdmin.audioFullPlayLabel || 'Kuula');
		play.setAttribute('data-src', url);
		play.textContent = '▶ ' + (absAdmin.audioFullPlayLabel || 'Kuula');

		return play;
	}

	function buildAudioLinePlay(url) {
		var play = document.createElement('button');

		play.type = 'button';
		play.className = 'button button-small abs-audio-line-play';
		play.setAttribute('aria-label', absAdmin.audioLinePlayLabel || 'Esita');
		play.setAttribute('data-src', url);
		play.textContent = '▶';

		return play;
	}

	function defaultAudioLine() {
		var voices = audioVoiceOptions();

		return {
			speaker: voices.length > 0 ? voices[0].name : 'Jutustaja',
			tone: '',
			pause_after_ms: 400,
			text: ''
		};
	}

	function collectAudioLines() {
		var lines = [];

		audioModalLines.querySelectorAll('.abs-audio-line-row').forEach(function (row) {
			var speaker = row.querySelector('.abs-audio-line-speaker');
			var tone = row.querySelector('.abs-audio-line-tone');
			var pause = row.querySelector('.abs-audio-line-pause');
			var text = row.querySelector('.abs-audio-line-text');
			var textValue = text ? String(text.value) : '';

			if ('' === textValue.trim()) {
				return;
			}

			lines.push({
				speaker: speaker ? String(speaker.value || '').trim() : '',
				tone: tone ? String(tone.value || '').trim() : '',
				pause_after_ms: clampAudioPause(pause ? pause.value : 0),
				text: textValue
			});
		});

		return lines;
	}

	var audioModalBackdrop = null;
	var audioModalLines = null;
	var audioModalError = null;
	var audioModalSave = null;
	var audioModalMerge = null;
	var audioModalGenAll = null;
	var audioModalCell = null;
	var audioModalStoryId = '';
	var audioModalFiles = {};
	var audioModalFull = '';
	var audioModalGenAllAbort = false;

	function sleepMs(ms) {
		return new Promise(function (resolve) {
			setTimeout(resolve, ms);
		});
	}

	function setLineAudio(row, url) {
		var play = row.querySelector('.abs-audio-line-play');

		if (play) {
			if (lineAudioButton === play) {
				stopLineAudio();
			}

			play.setAttribute('data-src', url);
		} else {
			var wrap = row.querySelector('.abs-audio-line-audio');

			if (wrap) {
				wrap.appendChild(buildAudioLinePlay(url));
			}
		}

		var gen = row.querySelector('.abs-audio-line-gen');

		if (gen) {
			gen.textContent = absAdmin.audioLineRegenLabel || 'Genereeri uuesti';
		}
	}

	function setGenAllBusy(busy) {
		if (!audioModalGenAll) {
			return;
		}

		if (busy) {
			audioModalGenAllAbort = false;
			audioModalGenAll.setAttribute('data-busy', '1');
			audioModalGenAll.disabled = true;
			audioModalGenAll.textContent = absAdmin.genAllLabel || 'Genereeri kõik';

			if (audioModalSave) {
				audioModalSave.disabled = true;
			}

			if (audioModalMerge) {
				audioModalMerge.disabled = true;
			}

			var add = audioModalLines.querySelector('.abs-audio-modal-add');

			if (add) {
				add.disabled = true;
			}

			audioModalLines.querySelectorAll('.abs-audio-line-gen, .abs-audio-line-remove').forEach(function (el) {
				el.disabled = true;
			});
		} else {
			audioModalGenAll.removeAttribute('data-busy');
			audioModalGenAll.disabled = false;
			audioModalGenAll.textContent = absAdmin.genAllLabel || 'Genereeri kõik';

			if (audioModalSave) {
				audioModalSave.disabled = false;
			}

			if (audioModalMerge) {
				audioModalMerge.removeAttribute('data-busy');
				updateMergeState();
			}

			var add = audioModalLines.querySelector('.abs-audio-modal-add');

			if (add) {
				add.disabled = false;
			}

			audioModalLines.querySelectorAll('.abs-audio-line-gen, .abs-audio-line-remove').forEach(function (el) {
				el.disabled = false;
			});
		}
	}

	function finishGenAll(message) {
		setGenAllBusy(false);
		showMessage(audioModalError, message);
	}

	function runGenerateAll(storyId, rowList, total) {
		var index = 0;
		var fails = 0;

		function next() {
			if (audioModalGenAllAbort) {
				finishGenAll(absAdmin.genAllAbortLabel || 'Katkestatud.');

				return;
			}

			if (index >= total) {
				finishGenAll((absAdmin.genAllDoneLabel || 'Kõik read genereeritud.') + ' (' + total + ')');

				return;
			}

			if (audioModalGenAll) {
				audioModalGenAll.textContent = (absAdmin.genAllBusyLabel || 'Genereerin…') + ' ' + (index + 1) + '/' + total;
			}

			post('abs_generate_line_audio', { story_id: storyId, line: index }, absAdmin.audioNonce).then(function (data) {
				fails = 0;

				var url = data && data.url ? String(data.url) : '';

				audioModalFiles[index] = { url: url, voice: data && data.voice ? String(data.voice) : '', updated: Math.floor(Date.now() / 1000) };

				if (rowList[index]) {
					setLineAudio(rowList[index], url);
				}

				index++;
				next();
			}).catch(function () {
				fails++;

				if (fails >= 5) {
					finishGenAll((absAdmin.genAllStopLabel || 'Peatatud — 5 järjestikust ebaõnnestumist.') + ' (' + index + '/' + total + ')');

					return;
				}

				if (audioModalGenAll) {
					audioModalGenAll.textContent = absAdmin.genAllWaitLabel || 'Ebaõnnestus, proovin 5 sekundi pärast uuesti…';
				}

				sleepMs(5000).then(next);
			});
		}

		next();
	}

	function handleGenerateAll(button) {
		if (button.disabled || '1' === button.getAttribute('data-busy')) {
			return;
		}

		if (!absAdmin.audioTtsEnabled) {
			showMessage(audioModalError, absAdmin.audioLineKeyMissingLabel || 'Jutusta API võti on seadistamata.');

			return;
		}

		var storyId = audioModalStoryId;

		if (!storyId) {
			return;
		}

		var lines = collectAudioLines();

		if (0 === lines.length) {
			return;
		}

		var rowList = [];

		audioModalLines.querySelectorAll('.abs-audio-line-row').forEach(function (row) {
			var text = row.querySelector('.abs-audio-line-text');

			if (text && String(text.value || '').trim()) {
				rowList.push(row);
			}
		});

		if (rowList.length !== lines.length) {
			showMessage(audioModalError, absAdmin.failureLabel || 'Request failed');

			return;
		}

		setGenAllBusy(true);
		clearMessage(audioModalError);

		post('abs_save_audio', { story_id: storyId, script: prettyAudioJson(lines) }, absAdmin.audioNonce).then(function () {
			runGenerateAll(storyId, rowList, lines.length);
		}).catch(function (error) {
			setGenAllBusy(false);
			showMessage(audioModalError, error && error.message ? error.message : (absAdmin.failureLabel || 'Request failed'));
		});
	}
	var lineAudioPlayer = null;
	var lineAudioButton = null;

	function audioLineIndex(row) {
		var index = 0;

		if (!audioModalLines || !row) {
			return 0;
		}

		var rows = audioModalLines.querySelectorAll('.abs-audio-line-row');

		for (var i = 0; i < rows.length; i++) {
			if (rows[i] === row) {
				break;
			}

			var text = rows[i].querySelector('.abs-audio-line-text');
			var value = text ? String(text.value || '') : '';

			if ('' !== value.trim()) {
				index++;
			}
		}

		return index;
	}

	function stopLineAudio() {
		if (lineAudioPlayer) {
			lineAudioPlayer.pause();
			lineAudioPlayer = null;
		}

		if (lineAudioButton) {
			lineAudioButton.textContent = '▶';
			lineAudioButton.setAttribute('aria-label', absAdmin.audioLinePlayLabel || 'Esita');
			lineAudioButton = null;
		}
	}

	function voiceVolumeFactor(button) {
		var row = button.closest('.abs-audio-line-row');

		if (!row) {
			return 1;
		}

		var select = row.querySelector('.abs-audio-line-speaker');

		if (!select) {
			return 1;
		}

		var name = String(select.value || '').trim().toLowerCase();
		var voices = absAdmin.audioVoices || [];

		for (var i = 0; i < voices.length; i++) {
			if (String(voices[i].name || '').trim().toLowerCase() === name) {
				var vol = parseFloat(voices[i].volume);

				return isNaN(vol) ? 1 : Math.max(0, Math.min(1, vol / 100));
			}
		}

		return 1;
	}

	function toggleLineAudio(button) {
		var src = button.getAttribute('data-src') || '';

		if ('' === src) {
			return;
		}

		if (lineAudioButton === button) {
			stopLineAudio();

			return;
		}

		stopLineAudio();

		var player = new Audio(src);

		player.volume = voiceVolumeFactor(button);

		lineAudioPlayer = player;
		lineAudioButton = button;
		button.textContent = '❚❚';
		button.setAttribute('aria-label', absAdmin.audioLinePauseLabel || 'Peata');

		player.addEventListener('ended', function () {
			if (lineAudioPlayer === player) {
				stopLineAudio();
			}
		});

		player.play().catch(function () {
			if (lineAudioPlayer === player) {
				stopLineAudio();
			}
		});
	}

	function handleLineGen(button) {
		if (button.disabled || '1' === button.getAttribute('data-busy')) {
			return;
		}

		if (!absAdmin.audioTtsEnabled) {
			showMessage(audioModalError, absAdmin.audioLineKeyMissingLabel || 'Jutusta API võti on seadistamata.');

			return;
		}

		var row = button.closest('.abs-audio-line-row');

		if (!row || !audioModalStoryId) {
			return;
		}

		var text = row.querySelector('.abs-audio-line-text');
		var textValue = text ? String(text.value || '') : '';

		if ('' === textValue.trim()) {
			showMessage(audioModalError, absAdmin.audioLineEmptyLabel || 'Rea tekst on tühi.');

			return;
		}

		var storyId = audioModalStoryId;
		var lineIndex = audioLineIndex(row);
		var originalLabel = button.textContent;

		button.setAttribute('data-line', String(lineIndex));
		button.setAttribute('data-busy', '1');
		button.disabled = true;
		button.textContent = absAdmin.audioLineBusyLabel || 'Genereerin…';
		clearMessage(audioModalError);

		post('abs_save_audio', { story_id: storyId, script: prettyAudioJson(collectAudioLines()) }, absAdmin.audioNonce).then(function () {
			return post('abs_generate_line_audio', { story_id: storyId, line: lineIndex }, absAdmin.audioNonce);
		}).then(function (data) {
			var url = data && data.url ? String(data.url) : '';
			var voice = data && data.voice ? String(data.voice) : '';

			audioModalFiles[lineIndex] = { url: url, voice: voice, updated: Math.floor(Date.now() / 1000) };

			button.removeAttribute('data-busy');
			button.disabled = false;
			button.textContent = absAdmin.audioLineRegenLabel || 'Genereeri uuesti';

			var play = row.querySelector('.abs-audio-line-play');

			if (play) {
				if (lineAudioButton === play) {
					stopLineAudio();
				}

				play.setAttribute('data-src', url);
			} else {
				var audioWrap = row.querySelector('.abs-audio-line-audio');

				if (audioWrap) {
					audioWrap.appendChild(buildAudioLinePlay(url));
				}
			}

			updateMergeState();
		}).catch(function (error) {
			button.removeAttribute('data-busy');
			button.disabled = false;
			button.textContent = originalLabel;
			showMessage(audioModalError, error && error.message ? error.message : (absAdmin.failureLabel || 'Request failed'));
		});
	}

	function updateMergeState() {
		if (!audioModalMerge) {
			return;
		}

		var lines = collectAudioLines();
		var ready = lines.length > 0 && lines.every(function (line, index) {
			return audioModalFiles && audioModalFiles[index] && audioModalFiles[index].url;
		});

		audioModalMerge.disabled = !ready || '1' === audioModalMerge.getAttribute('data-busy');
	}

	function handleMergeAudio(button) {
		if (button.disabled || '1' === button.getAttribute('data-busy')) {
			return;
		}

		var storyId = audioModalStoryId;

		if (!storyId) {
			return;
		}

		var originalLabel = button.textContent;

		button.setAttribute('data-busy', '1');
		button.disabled = true;
		button.textContent = absAdmin.audioMergeBusyLabel || 'Koostan…';
		clearMessage(audioModalError);

		post('abs_save_audio', { story_id: storyId, script: prettyAudioJson(collectAudioLines()) }, absAdmin.audioNonce).then(function () {
			return post('abs_merge_audio', { story_id: storyId }, absAdmin.audioNonce);
		}).then(function (data) {
			button.removeAttribute('data-busy');
			audioModalFull = data && data.full_url ? String(data.full_url) : '';

			if (audioModalCell) {
				renderAudioCell(audioModalCell, storyId, collectAudioLines(), audioModalFull);
			}

			closeAudioModal();
		}).catch(function (error) {
			button.removeAttribute('data-busy');
			button.textContent = originalLabel;
			updateMergeState();
			showMessage(audioModalError, error && error.message ? error.message : (absAdmin.failureLabel || 'Request failed'));
		});
	}

	function closeAudioModal() {
		if (!audioModalBackdrop) {
			return;
		}

		stopLineAudio();
		audioModalGenAllAbort = true;
		audioModalBackdrop.hidden = true;
		audioModalLines.textContent = '';
		clearMessage(audioModalError);
		audioModalSave.disabled = false;

		if (audioModalMerge) {
			audioModalMerge.disabled = true;
			audioModalMerge.removeAttribute('data-busy');
			audioModalMerge.textContent = absAdmin.audioMergeLabel || 'Koosta audio';
		}

		if (audioModalGenAll) {
			audioModalGenAll.disabled = false;
			audioModalGenAll.removeAttribute('data-busy');
			audioModalGenAll.textContent = absAdmin.genAllLabel || 'Genereeri kõik';
		}

		audioModalFull = '';
		audioModalCell = null;
		audioModalStoryId = '';
	}

	function buildAudioModal() {
		if (audioModalBackdrop) {
			return;
		}

		var backdrop = document.createElement('div');
		backdrop.className = 'abs-audio-modal-backdrop';
		backdrop.hidden = true;

		var dialog = document.createElement('div');
		dialog.className = 'abs-audio-modal-dialog';
		dialog.setAttribute('role', 'dialog');
		dialog.setAttribute('aria-modal', 'true');
		dialog.setAttribute('aria-labelledby', 'abs-audio-modal-title');

		var header = document.createElement('div');
		header.className = 'abs-audio-modal-header';

		var title = document.createElement('h2');
		title.className = 'abs-audio-modal-title';
		title.id = 'abs-audio-modal-title';
		title.textContent = absAdmin.audioModalTitleLabel || 'Muuda audio skripti';
		header.appendChild(title);

		var close = document.createElement('button');
		close.type = 'button';
		close.className = 'abs-audio-modal-close';
		close.setAttribute('aria-label', absAdmin.audioCloseLabel || 'Sulge');
		close.textContent = '×';
		header.appendChild(close);

		dialog.appendChild(header);

		var body = document.createElement('div');
		body.className = 'abs-audio-modal-body';

		var lines = document.createElement('div');
		lines.className = 'abs-audio-modal-lines';
		body.appendChild(lines);

		var add = document.createElement('button');
		add.type = 'button';
		add.className = 'button button-secondary abs-audio-modal-add';
		add.textContent = absAdmin.audioAddLineLabel || 'Lisa rida';
		body.appendChild(add);

		dialog.appendChild(body);

		var footer = document.createElement('div');
		footer.className = 'abs-audio-modal-footer';

		var error = document.createElement('div');
		error.className = 'abs-audio-error';
		error.hidden = true;
		footer.appendChild(error);

		var genAll = document.createElement('button');
		genAll.type = 'button';
		genAll.className = 'button button-secondary abs-audio-modal-genall';
		genAll.textContent = absAdmin.genAllLabel || 'Genereeri kõik';
		footer.appendChild(genAll);

		var merge = document.createElement('button');
		merge.type = 'button';
		merge.className = 'button button-secondary abs-audio-modal-merge';
		merge.disabled = true;
		merge.textContent = absAdmin.audioMergeLabel || 'Koosta audio';
		footer.appendChild(merge);

		var cancel = document.createElement('button');
		cancel.type = 'button';
		cancel.className = 'button button-secondary abs-audio-modal-cancel';
		cancel.textContent = absAdmin.audioCancelLabel || 'Katkesta';
		footer.appendChild(cancel);

		var save = document.createElement('button');
		save.type = 'button';
		save.className = 'button button-primary abs-audio-modal-save';
		save.setAttribute('data-story-id', '');
		save.textContent = absAdmin.audioSaveLabel || 'Salvesta';
		footer.appendChild(save);

		dialog.appendChild(footer);
		backdrop.appendChild(dialog);

		merge.addEventListener('click', function () {
			handleMergeAudio(merge);
		});

		genAll.addEventListener('click', function () {
			handleGenerateAll(genAll);
		});

		backdrop.addEventListener('click', function (event) {
			if (event.target === backdrop) {
				closeAudioModal();
			}
		});

		close.addEventListener('click', closeAudioModal);
		cancel.addEventListener('click', closeAudioModal);

		document.addEventListener('keydown', function (event) {
			if ('Escape' === event.key && !backdrop.hidden) {
				closeAudioModal();
			}
		});

		add.addEventListener('click', function () {
			audioModalLines.appendChild(buildAudioLineRow(null, audioModalLines.querySelectorAll('.abs-audio-line-row').length, null));
			updateMergeState();
		});

		lines.addEventListener('click', function (event) {
			var gen = event.target.closest('.abs-audio-line-gen');

			if (gen && lines.contains(gen)) {
				handleLineGen(gen);

				return;
			}

			var play = event.target.closest('.abs-audio-line-play');

			if (play && lines.contains(play)) {
				toggleLineAudio(play);

				return;
			}

			var remove = event.target.closest('.abs-audio-line-remove');

			if (!remove || !lines.contains(remove)) {
				return;
			}

			var row = remove.closest('.abs-audio-line-row');

			if (row) {
				row.remove();
			}

			updateMergeState();
		});

		save.addEventListener('click', function () {
			var storyId = audioModalStoryId;

			if (!storyId || save.disabled) {
				return;
			}

			save.disabled = true;
			clearMessage(audioModalError);

			post('abs_save_audio', { story_id: storyId, script: prettyAudioJson(collectAudioLines()) }, absAdmin.audioNonce).then(function (data) {
				save.disabled = false;

				if (audioModalCell) {
					renderAudioCell(audioModalCell, storyId, Array.isArray(data.lines_data) ? data.lines_data : [], data && data.full_url ? String(data.full_url) : '');
				}

				closeAudioModal();
			}).catch(function (error) {
				save.disabled = false;
				showMessage(audioModalError, error && error.message ? error.message : (absAdmin.failureLabel || 'Request failed'));
			});
		});

		document.body.appendChild(backdrop);

		audioModalBackdrop = backdrop;
		audioModalLines = lines;
		audioModalError = error;
		audioModalSave = save;
		audioModalMerge = merge;
		audioModalGenAll = genAll;
	}

	function openAudioModal(cell, storyId) {
		if (!cell || !storyId) {
			return;
		}

		var rowEditor = cell.querySelector('.abs-audio-editor');

		if (!rowEditor) {
			return;
		}

		buildAudioModal();

		audioModalCell = cell;
		audioModalStoryId = storyId;
		audioModalSave.setAttribute('data-story-id', storyId);
		audioModalSave.disabled = false;
		clearMessage(audioModalError);

		var rowFiles = cell.querySelector('.abs-audio-files');
		var storedFiles = null;

		try {
			storedFiles = rowFiles ? JSON.parse(rowFiles.value) : null;
		} catch (err) {
			storedFiles = null;
		}

		audioModalFiles = storedFiles && 'object' === typeof storedFiles && !Array.isArray(storedFiles) ? storedFiles : {};

		var rowFull = cell.querySelector('.abs-audio-full');
		var storedFull = null;

		try {
			storedFull = rowFull ? JSON.parse(rowFull.value) : null;
		} catch (err) {
			storedFull = null;
		}

		audioModalFull = storedFull && storedFull.url ? String(storedFull.url) : '';

		stopLineAudio();

		var decoded = null;

		try {
			decoded = JSON.parse(rowEditor.value);
		} catch (err) {
			decoded = null;
		}

		var validJson = null !== decoded && 'object' === typeof decoded && Array.isArray(decoded.lines);
		var rows = validJson ? decoded.lines.filter(function (entry) {
			return entry && 'string' === typeof entry.text && '' !== String(entry.text).trim();
		}) : [];

		if (rows.length > 0) {
			rows.forEach(function (entry, index) {
				audioModalLines.appendChild(buildAudioLineRow(entry, index, audioModalFiles[index]));
			});
		} else {
			showMessage(audioModalError, validJson
				? (absAdmin.audioInvalidLineLabel || 'Skriptis ei ole ühtegi kehtivat rida.')
				: (absAdmin.audioInvalidJsonLabel || 'Skript ei ole kehtiv JSON.'));
			audioModalLines.appendChild(buildAudioLineRow(defaultAudioLine(), 0, null));
		}

		audioModalBackdrop.hidden = false;
		updateMergeState();

		if (audioModalGenAll) {
			audioModalGenAllAbort = false;
			audioModalGenAll.disabled = !absAdmin.audioTtsEnabled;

			if (!absAdmin.audioTtsEnabled) {
				audioModalGenAll.setAttribute('title', absAdmin.audioLineKeyMissingLabel || 'Jutusta API võti on seadistamata.');
			}
		}

		var first = audioModalLines.querySelector('input, textarea, select');

		if (first) {
			first.focus();
		}
	}

	function showAudioError(cell, message) {
		if (!cell) {
			return;
		}

		var existing = cell.querySelector('.abs-audio-error');

		if (existing && existing.parentElement !== cell) {
			existing = null;
		}

		if (!existing) {
			existing = document.createElement('p');
			existing.className = 'abs-audio-error';
			cell.appendChild(existing);
		}

		existing.textContent = message;
	}

	function handleImageGenerate(button) {
		var storyId = button.getAttribute('data-story-id') || '';

		if (!storyId || button.disabled) {
			return;
		}

		var cell = button.closest('.abs-image-cell') || button.closest('td');
		var originalLabel = button.textContent;
		var existingError = cell ? cell.querySelector('.abs-audio-error') : null;

		if (existingError && existingError.parentElement === cell) {
			existingError.remove();
		}

		button.disabled = true;
		button.textContent = absAdmin.imageBusyLabel || 'Genereerin…';

		var spinner = document.createElement('span');
		spinner.className = 'abs-spinner';
		button.parentNode.insertBefore(spinner, button.nextSibling);

		post('abs_generate_image', { story_id: storyId }).then(function (data) {
			if (spinner.parentNode) {
				spinner.parentNode.removeChild(spinner);
			}

			if (cell && data && data.html) {
				cell.innerHTML = data.html;
			}
		}).catch(function (error) {
			if (spinner.parentNode) {
				spinner.parentNode.removeChild(spinner);
			}

			button.disabled = false;
			button.textContent = originalLabel;

			if (cell) {
				showAudioError(cell, error && error.message ? error.message : (absAdmin.failureLabel || 'Request failed'));
			}
		});
	}

	if (storiesTable) {
		storiesTable.addEventListener('click', function (event) {
			var imageButton = event.target.closest('[data-abs-image]');

			if (imageButton && storiesTable.contains(imageButton)) {
				handleImageGenerate(imageButton);

				return;
			}

			var fullPlay = event.target.closest('.abs-audio-full-play');

			if (fullPlay && storiesTable.contains(fullPlay)) {
				toggleLineAudio(fullPlay);

				return;
			}

			var editOpen = event.target.closest('.abs-audio-edit-open');

			if (editOpen && storiesTable.contains(editOpen)) {
				var editCell = editOpen.closest('.abs-audio-cell') || editOpen.closest('td');

				openAudioModal(editCell, editOpen.getAttribute('data-story-id') || '');

				return;
			}

			var button = event.target.closest('[data-abs-audio]');

			if (!button || !storiesTable.contains(button)) {
				return;
			}

			var storyId = button.getAttribute('data-story-id') || '';

			if (!storyId || button.disabled) {
				return;
			}

			var cell = button.closest('.abs-audio-cell') || button.closest('td');
			var originalLabel = button.textContent;
			var existingError = cell ? cell.querySelector('.abs-audio-error') : null;

			if (existingError && existingError.parentElement === cell) {
				existingError.remove();
			}

			button.disabled = true;
			button.textContent = absAdmin.audioBusyLabel || 'Genereerin…';

			var spinner = document.createElement('span');
			spinner.className = 'abs-spinner';
			button.parentNode.insertBefore(spinner, button.nextSibling);

			post('abs_generate_audio', { story_id: storyId }, absAdmin.audioNonce).then(function (data) {
				if (spinner.parentNode) {
					spinner.parentNode.removeChild(spinner);
				}

				renderAudioCell(cell, storyId, Array.isArray(data.lines_data) ? data.lines_data : [], data && data.full_url ? String(data.full_url) : '');
			}).catch(function (error) {
				if (spinner.parentNode) {
					spinner.parentNode.removeChild(spinner);
				}

				button.disabled = false;
				button.textContent = originalLabel;
				showAudioError(cell, error && error.message ? error.message : (absAdmin.failureLabel || 'Request failed'));
			});
		});
	}

	var testButton = document.getElementById('abs-test-connection');
	var testResult = document.getElementById('abs-test-result');
	var testProvider = document.getElementById('abs-test-provider');
	var testRunning = false;

	function setTestRunning(running) {
		testRunning = running;

		if (testButton) {
			testButton.disabled = running;
			testButton.textContent = running
				? (absAdmin.testingLabel || 'Testing…')
				: (absAdmin.testButtonLabel || 'Test API connection');
		}
	}

	if (testButton) {
		testButton.addEventListener('click', function () {
			if (testRunning) {
				return;
			}

			setTestRunning(true);

			if (testResult) {
				testResult.textContent = '';
				testResult.classList.remove('abs-test-ok', 'abs-test-fail');
			}

			post('abs_test_connection', { provider: testProvider ? testProvider.value : 'zai' }).then(function (data) {
				if (testResult) {
					testResult.textContent = data.message || '';
					testResult.classList.add('abs-test-ok');
				}

				setTestRunning(false);
			}).catch(function (error) {
				if (testResult) {
					testResult.textContent = error && error.message ? error.message : (absAdmin.failureLabel || 'Request failed');
					testResult.classList.add('abs-test-fail');
				}

				setTestRunning(false);
			});
		});
	}

	function bindProviderToggle(task) {
		var providerSelect = document.getElementById('abs-' + task + '-provider');

		if (!providerSelect) {
			return;
		}

		var selects = document.querySelectorAll('.abs-model-select[data-task="' + task + '"]');
		var customInput = document.getElementById('abs-' + task + '-model-custom');

		function syncModels() {
			var provider = providerSelect.value;
			var activeSelect = null;

			selects.forEach(function (select) {
				var active = select.getAttribute('data-provider') === provider;

				select.classList.toggle('abs-hidden', !active);
				select.disabled = !active;

				if (active) {
					activeSelect = select;
				}
			});

			if (customInput && activeSelect) {
				customInput.classList.toggle('abs-hidden', 'custom' !== activeSelect.value);
			}
		}

		providerSelect.addEventListener('change', syncModels);

		selects.forEach(function (select) {
			select.addEventListener('change', syncModels);
		});

		syncModels();
	}

	bindProviderToggle('ideas');
	bindProviderToggle('story');
	bindProviderToggle('audio');

	var neuroRows = document.getElementById('abs-neuro-rows');
	var addNeuroButton = document.getElementById('abs-add-neuro');

	function buildNeuroRowFallback() {
		var item = document.createElement('div');
		item.className = 'abs-char-item';

		var toggle = document.createElement('button');
		toggle.type = 'button';
		toggle.className = 'abs-char-toggle';
		toggle.setAttribute('aria-expanded', 'false');

		var title = document.createElement('span');
		title.className = 'abs-char-title';
		title.textContent = 'Nimetu erisus';
		toggle.appendChild(title);

		var arrow = document.createElement('span');
		arrow.className = 'abs-char-arrow';
		arrow.setAttribute('aria-hidden', 'true');
		arrow.textContent = '▾';
		toggle.appendChild(arrow);

		item.appendChild(toggle);

		var panel = document.createElement('div');
		panel.className = 'abs-char-panel';
		panel.hidden = true;

		var name = document.createElement('input');
		name.type = 'text';
		name.className = 'abs-neuro-name';
		name.name = 'abs_neuro_names[]';
		name.maxLength = 120;
		panel.appendChild(buildCharField('Nimi', name));

		var description = document.createElement('textarea');
		description.className = 'abs-neuro-desc';
		description.name = 'abs_neuro_descs[]';
		description.rows = 5;
		description.maxLength = 2000;
		panel.appendChild(buildCharField('Kirjeldus', description));

		var remove = document.createElement('button');
		remove.type = 'button';
		remove.className = 'button-link abs-char-remove';
		remove.textContent = 'Eemalda';
		panel.appendChild(remove);

		item.appendChild(panel);

		return item;
	}

	function createNeuroRow() {
		var template = document.querySelector('[data-abs-neuro-template]');

		if (!template) {
			return buildNeuroRowFallback();
		}

		var item = template.cloneNode(true);

		item.classList.remove('abs-hidden');
		item.removeAttribute('data-abs-neuro-template');

		openItem(item);

		return item;
	}

	var charRows = document.getElementById('abs-char-rows');
	var addCharButton = document.getElementById('abs-add-char');
	var voiceRows = document.getElementById('abs-voice-rows');
	var addVoiceButton = document.getElementById('abs-add-voice');

	function buildCharField(labelText, field) {
		var wrap = document.createElement('p');
		wrap.className = 'abs-char-field';

		var label = document.createElement('label');
		label.textContent = labelText;
		wrap.appendChild(label);
		wrap.appendChild(field);

		return wrap;
	}

	function buildCharRowFallback() {
		var item = document.createElement('div');
		item.className = 'abs-char-item';

		var toggle = document.createElement('button');
		toggle.type = 'button';
		toggle.className = 'abs-char-toggle';
		toggle.setAttribute('aria-expanded', 'false');

		var title = document.createElement('span');
		title.className = 'abs-char-title';
		title.textContent = 'Nimetu tegelane';
		toggle.appendChild(title);

		var sub = document.createElement('span');
		sub.className = 'abs-char-sub';
		sub.hidden = true;
		toggle.appendChild(sub);

		var arrow = document.createElement('span');
		arrow.className = 'abs-char-arrow';
		arrow.setAttribute('aria-hidden', 'true');
		arrow.textContent = '▾';
		toggle.appendChild(arrow);

		item.appendChild(toggle);

		var panel = document.createElement('div');
		panel.className = 'abs-char-panel';
		panel.hidden = true;

		var name = document.createElement('input');
		name.type = 'text';
		name.className = 'abs-char-name';
		name.name = 'abs_char_names[]';
		name.maxLength = 120;
		panel.appendChild(buildCharField('Nimi', name));

		var role = document.createElement('input');
		role.type = 'text';
		role.className = 'abs-char-role';
		role.name = 'abs_char_roles[]';
		role.maxLength = 120;
		panel.appendChild(buildCharField('Tegelane', role));

		var description = document.createElement('textarea');
		description.className = 'abs-char-desc';
		description.name = 'abs_char_descs[]';
		description.rows = 5;
		description.maxLength = 2000;
		panel.appendChild(buildCharField('Kirjeldus', description));

		var neuro = document.createElement('select');
		neuro.className = 'abs-char-neuro';
		neuro.name = 'abs_char_neuros[]';

		var neuroNone = document.createElement('option');
		neuroNone.value = '';
		neuroNone.textContent = '— Puudub —';
		neuro.appendChild(neuroNone);

		panel.appendChild(buildCharField('Neuroerisus', neuro));

		var remove = document.createElement('button');
		remove.type = 'button';
		remove.className = 'button-link abs-char-remove';
		remove.textContent = 'Eemalda';
		panel.appendChild(remove);

		item.appendChild(panel);

		return item;
	}

	function openItem(item) {
		var toggle = item.querySelector('.abs-char-toggle');
		var panel = item.querySelector('.abs-char-panel');

		item.classList.add('open');

		if (toggle) {
			toggle.setAttribute('aria-expanded', 'true');
		}

		if (panel) {
			panel.hidden = false;
		}
	}

	function createCharRow() {
		var template = document.querySelector('[data-abs-char-template]');

		if (!template) {
			return buildCharRowFallback();
		}

		var item = template.cloneNode(true);

		item.classList.remove('abs-hidden');
		item.removeAttribute('data-abs-char-template');

		var neuro = item.querySelector('.abs-char-neuro');

		if (neuro) {
			neuro.value = '';
		}

		openItem(item);

		return item;
	}

	function buildVoiceRowFallback() {
		var item = document.createElement('div');
		item.className = 'abs-char-item';

		var toggle = document.createElement('button');
		toggle.type = 'button';
		toggle.className = 'abs-char-toggle';
		toggle.setAttribute('aria-expanded', 'false');

		var title = document.createElement('span');
		title.className = 'abs-char-title';
		title.textContent = 'Nimetu hääl';
		toggle.appendChild(title);

		var arrow = document.createElement('span');
		arrow.className = 'abs-char-arrow';
		arrow.setAttribute('aria-hidden', 'true');
		arrow.textContent = '▾';
		toggle.appendChild(arrow);

		item.appendChild(toggle);

		var panel = document.createElement('div');
		panel.className = 'abs-char-panel';
		panel.hidden = true;

		var name = document.createElement('input');
		name.type = 'text';
		name.className = 'abs-voice-name';
		name.name = 'abs_voice_names[]';
		name.maxLength = 120;
		panel.appendChild(buildCharField('Nimi', name));

		var description = document.createElement('input');
		description.type = 'text';
		description.className = 'abs-voice-desc';
		description.name = 'abs_voice_descs[]';
		description.maxLength = 200;
		panel.appendChild(buildCharField('Kirjeldus', description));

		var remove = document.createElement('button');
		remove.type = 'button';
		remove.className = 'button-link abs-char-remove';
		remove.textContent = 'Eemalda';
		panel.appendChild(remove);

		item.appendChild(panel);

		return item;
	}

	function createVoiceRow() {
		var template = document.querySelector('[data-abs-voice-template]');

		if (!template) {
			return buildVoiceRowFallback();
		}

		var item = template.cloneNode(true);

		item.classList.remove('abs-hidden');
		item.removeAttribute('data-abs-voice-template');

		openItem(item);

		return item;
	}

	function handleRowsClick(event) {
		var toggle = event.target.closest('.abs-char-toggle');

		if (toggle && event.currentTarget.contains(toggle)) {
			var item = toggle.closest('.abs-char-item');

			if (item) {
				var panel = item.querySelector('.abs-char-panel');
				var open = !item.classList.contains('open');

				item.classList.toggle('open', open);
				toggle.setAttribute('aria-expanded', open ? 'true' : 'false');

				if (panel) {
					panel.hidden = !open;
				}
			}

			return;
		}

		var button = event.target.closest('.abs-char-remove');

		if (!button || !event.currentTarget.contains(button)) {
			return;
		}

		var row = button.closest('.abs-char-item');

		if (row) {
			row.remove();
		}
	}

	function handleRowsInput(event) {
		var item = event.target.closest('.abs-char-item');

		if (!item) {
			return;
		}

		if (event.target.matches('.abs-char-name')) {
			var title = item.querySelector('.abs-char-title');

			if (title) {
				title.textContent = '' === event.target.value.trim() ? 'Nimetu tegelane' : event.target.value;
			}
		}

		if (event.target.matches('.abs-char-role')) {
			var sub = item.querySelector('.abs-char-sub');

			if (sub) {
				sub.textContent = event.target.value;
				sub.hidden = '' === event.target.value.trim();
			}
		}

		if (event.target.matches('.abs-neuro-name')) {
			var neuroTitle = item.querySelector('.abs-char-title');

			if (neuroTitle) {
				neuroTitle.textContent = '' === event.target.value.trim() ? 'Nimetu erisus' : event.target.value;
			}
		}

		if (event.target.matches('.abs-voice-name')) {
			var voiceTitle = item.querySelector('.abs-char-title');

			if (voiceTitle) {
				voiceTitle.textContent = '' === event.target.value.trim() ? 'Nimetu hääl' : event.target.value;
			}
		}
	}

	[neuroRows, charRows, voiceRows].forEach(function (rows) {
		if (!rows) {
			return;
		}

		rows.addEventListener('click', handleRowsClick);
		rows.addEventListener('input', handleRowsInput);
	});

	if (addNeuroButton) {
		addNeuroButton.addEventListener('click', function () {
			if (neuroRows) {
				neuroRows.appendChild(createNeuroRow());
			}
		});
	}

	if (addCharButton) {
		addCharButton.addEventListener('click', function () {
			if (charRows) {
				charRows.appendChild(createCharRow());
			}
		});
	}

	if (addVoiceButton) {
		addVoiceButton.addEventListener('click', function () {
			if (voiceRows) {
				voiceRows.appendChild(createVoiceRow());
			}
		});
	}
})();
