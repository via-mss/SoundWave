// js/app.js — SoundWave dynamic interactions

document.addEventListener('DOMContentLoaded', () => {

    function isInternalNavigation(target) {
        if (!target || !target.href) return false;
        if (target.target && target.target !== '_self') return false;
        if (target.hasAttribute('download')) return false;
        if (target.href.startsWith('mailto:') || target.href.startsWith('tel:')) return false;
        const url = new URL(target.href, window.location.href);
        if (url.origin !== window.location.origin) return false;
        if (url.pathname.startsWith('/soundwave/php/') || url.pathname.startsWith('/php/')) return false;
        return true;
    }

    function parseHTML(html) {
        return new DOMParser().parseFromString(html, 'text/html');
    }

    function executeInlineScripts(container) {
        container.querySelectorAll('script:not([src])').forEach(oldScript => {
            const script = document.createElement('script');
            script.textContent = oldScript.textContent;
            document.body.appendChild(script).parentNode.removeChild(script);
        });
    }

    async function loadPage(url, replaceState = false) {
        try {
            const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) {
                window.location.href = url;
                return;
            }
            const html = await response.text();
            const doc = parseHTML(html);
            const newMain = doc.querySelector('main.main-content');
            const newTitle = doc.querySelector('title');
            if (!newMain || !newTitle) {
                window.location.href = url;
                return;
            }
            const currentMain = document.querySelector('main.main-content');
            if (!currentMain) {
                window.location.href = url;
                return;
            }
            currentMain.replaceWith(newMain);
            document.title = newTitle.textContent;
            if (replaceState) {
                window.history.replaceState({ url }, '', url);
            } else {
                window.history.pushState({ url }, '', url);
            }
            executeInlineScripts(newMain);
            executeInlineScripts(doc.body);
            initPageContent();
            window.scrollTo(0, 0);
        } catch (err) {
            console.error('Page load failed:', err);
            window.location.href = url;
        }
    }

    function handleNavigationClick(e) {
        const anchor = e.target.closest('a');
        if (!anchor) return;
        if (!isInternalNavigation(anchor)) return;
        const currentBase = window.location.href.split('#')[0];
        const anchorBase = anchor.href.split('#')[0];
        if (anchorBase === currentBase) {
            if (!anchor.hash) {
                e.preventDefault();
            }
            return;
        }
        e.preventDefault();
        loadPage(anchor.href);
    }

    function bindNavigation() {
        document.body.addEventListener('click', handleNavigationClick);
        window.addEventListener('popstate', () => {
            loadPage(window.location.href, true);
        });
        window.history.replaceState({ url: window.location.href }, '', window.location.href);
    }

    function initPageContent() {
        document.querySelectorAll('.song-row[data-song-url]').forEach((row, idx, rows) => {
            row.addEventListener('click', (e) => {
                if (e.target.closest('.song-row-actions') || e.target.closest('.song-row-title a') || e.target.closest('.song-row-artist a')) return;
                const songs = Array.from(rows).map(r => ({
                    id:       r.dataset.songId,
                    url:      r.dataset.songUrl,
                    title:    r.dataset.songTitle,
                    artist:   r.dataset.songArtist,
                    cover:    r.dataset.songCover,
                    album:    r.dataset.songAlbum || '',
                    artistId: r.dataset.songArtistId || ''
                }));
                Player.setQueue(songs, idx);
            });
        });

        document.querySelectorAll('.song-card[data-song-url]').forEach(card => {
            card.addEventListener('click', (e) => {
                if (e.target.closest('.song-card-title a') || e.target.closest('.song-card-artist a')) return;
                Player.playSong({
                    id:       card.dataset.songId,
                    url:      card.dataset.songUrl,
                    title:    card.dataset.songTitle,
                    artist:   card.dataset.songArtist,
                    cover:    card.dataset.songCover,
                    album:    card.dataset.songAlbum || '',
                    artistId: card.dataset.songArtistId || ''
                });
            });
        });

        document.querySelectorAll('.playlist-play-all-btn').forEach(btn => {
            btn.removeEventListener('click', btn._playAllHandler);
            const handler = () => {
                let songs = [];
                if (btn.dataset.songs) {
                    try {
                        songs = JSON.parse(btn.dataset.songs);
                    } catch (err) {
                        console.error('Failed to parse Play All playlist data:', err);
                    }
                }
                if (songs.length === 0) {
                    songs = Array.from(document.querySelectorAll('.song-row[data-song-url]')).map(r => ({
                        id:     r.dataset.songId,
                        url:    r.dataset.songUrl,
                        title:  r.dataset.songTitle,
                        artist: r.dataset.songArtist,
                        cover:  r.dataset.songCover
                    }));
                }
                if (songs.length === 0) return;
                Player.setQueue(songs, 0);
            };
            btn.addEventListener('click', handler);
            btn._playAllHandler = handler;
        });

        document.querySelectorAll('.like-btn').forEach(btn => {
            btn.removeEventListener('click', btn._likeHandler);
            const handler = async (e) => {
                e.stopPropagation();
                const songId = btn.dataset.songId;
                if (!songId) return;
                try {
                    const res  = await fetch(`/soundwave/php/toggle_like.php?song_id=${songId}`, { method: 'POST' });
                    const data = await res.json();
                    if (data.liked !== undefined) {
                        btn.classList.toggle('liked', data.liked);
                        btn.innerHTML = `<i class="fa${data.liked ? 's' : 'r'} fa-heart"></i>`;
                    } else if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                } catch (err) { console.error(err); }
            };
            btn.addEventListener('click', handler);
            btn._likeHandler = handler;
        });

        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('song-file');
        if (dropZone && fileInput) {
            const uploadForm = document.querySelector('form[action="upload.php"]');
            const uploadSubmit = uploadForm?.querySelector('button[type="submit"]');
            let durationReady = false;
            let durationError = false;

            dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('drag-over'); });
            dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
            dropZone.addEventListener('drop', (e) => {
                e.preventDefault(); dropZone.classList.remove('drag-over');
                if (e.dataTransfer.files[0]) {
                    const dt   = new DataTransfer();
                    dt.items.add(e.dataTransfer.files[0]);
                    fileInput.files = dt.files;
                    dropZone.querySelector('p').textContent = e.dataTransfer.files[0].name;
                }
            });
            dropZone.addEventListener('click', () => fileInput.click());
            fileInput.addEventListener('change', () => {
                const songFile = fileInput.files[0];
                if (!songFile) return;
                dropZone.querySelector('p').textContent = songFile.name;

                const durationInput = document.getElementById('duration');
                const durationDisplay = document.getElementById('duration-display');
                if (!durationInput) return;

                // start detection: disable submit and reset flags
                durationReady = false;
                durationError = false;
                if (uploadSubmit) uploadSubmit.disabled = true;
                if (durationDisplay) durationDisplay.textContent = 'Detecting duration...';

                const audio = document.createElement('audio');
                audio.preload = 'metadata';
                audio.src = URL.createObjectURL(songFile);
                audio.addEventListener('loadedmetadata', () => {
                    const seconds = Math.round(audio.duration || 0);
                    durationInput.value = seconds;
                    if (durationDisplay) {
                        durationDisplay.textContent = seconds > 0 ? `${Math.floor(seconds / 60)}:${(seconds % 60).toString().padStart(2, '0')} (${seconds} sec)` : 'Duration unavailable';
                    }
                    durationReady = seconds > 0;
                    if (uploadSubmit) uploadSubmit.disabled = !durationReady;
                    URL.revokeObjectURL(audio.src);
                });
                audio.addEventListener('error', () => {
                    if (durationDisplay) durationDisplay.textContent = 'Unable to detect duration automatically.';
                    durationReady = false;
                    durationError = true;
                    if (uploadSubmit) uploadSubmit.disabled = false;
                    durationInput.value = 0;
                    URL.revokeObjectURL(audio.src);
                });
            });

            if (uploadForm) {
                uploadForm.addEventListener('submit', (e) => {
                    const durationInput = document.getElementById('duration');
                    if (!durationInput) {
                        e.preventDefault();
                        showToast('Unable to validate upload duration. Please try again.', 'error');
                        return;
                    }

                    if (Number(durationInput.value) <= 0 && !durationReady && !durationError) {
                        e.preventDefault();
                        showToast('Please wait while the audio duration is detected, then submit again.', 'error');
                    }
                });
            }
        }
        // initialize custom selects to replace native dropdowns where needed
        initCustomSelects();
    }

    function initCustomSelects() {
        document.querySelectorAll('select.sw-input').forEach(select => {
            if (select.dataset.csInit) return; // already initialized
            select.dataset.csInit = '1';

            // build wrapper
            const wrapper = document.createElement('div');
            wrapper.className = 'custom-select-wrapper';

            const display = document.createElement('div');
            display.className = 'custom-select-display';

            const label = document.createElement('div');
            label.className = 'cs-label';
            label.textContent = select.options[select.selectedIndex]?.text || '';

            const arrow = document.createElement('div');
            arrow.className = 'cs-arrow';
            arrow.innerHTML = '<svg viewBox="0 0 20 20" width="14" height="14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 8l4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';

            display.appendChild(label);
            display.appendChild(arrow);

            const optionsList = document.createElement('ul');
            optionsList.className = 'custom-select-options';
            optionsList.style.display = 'none';

            Array.from(select.options).forEach((opt, idx) => {
                const li = document.createElement('li');
                li.textContent = opt.text;
                li.dataset.value = opt.value;
                if (idx === select.selectedIndex) li.classList.add('selected');
                li.addEventListener('click', () => {
                    // update original select
                    select.value = li.dataset.value;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    // update UI
                    optionsList.querySelectorAll('li').forEach(x => x.classList.remove('selected'));
                    li.classList.add('selected');
                    label.textContent = li.textContent;
                    optionsList.style.display = 'none';
                });
                optionsList.appendChild(li);
            });

            display.addEventListener('click', (e) => {
                e.stopPropagation();
                const open = optionsList.style.display === 'block';
                document.querySelectorAll('.custom-select-options').forEach(o => o.style.display = 'none');
                optionsList.style.display = open ? 'none' : 'block';
            });

            // insert wrapper before select and move select into wrapper (hidden)
            select.style.display = 'none';
            select.parentNode.insertBefore(wrapper, select);
            wrapper.appendChild(display);
            wrapper.appendChild(optionsList);
            wrapper.appendChild(select);
        });

        // close on outside click
        document.addEventListener('click', () => document.querySelectorAll('.custom-select-options').forEach(o => o.style.display = 'none'));
    }

    bindNavigation();
    initPageContent();

    document.addEventListener('keydown', (e) => {
        if (e.code !== 'Space') return;
        const target = e.target;
        const tagName = target.tagName;
        const isEditable = target.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT', 'BUTTON'].includes(tagName);
        if (isEditable) return;
        e.preventDefault();
        Player.togglePlay();
    });

    // ── Add to Playlist (Bootstrap modal) ────────
    let _pendingSongId = null;

    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.add-to-playlist-btn');
        if (!btn) return;
        e.stopPropagation();
        _pendingSongId = btn.dataset.songId;
        const body = document.getElementById('playlist-modal-body');
        if (!body) return;
        body.innerHTML = '<p class="text-muted text-center py-3" style="font-size:0.85rem;">Loading…</p>';
        const modal = new bootstrap.Modal(document.getElementById('playlistModal'));
        modal.show();
        try {
            const res  = await fetch('/soundwave/php/get_playlists.php');
            const data = await res.json();
            if (data.playlists && data.playlists.length > 0) {
                body.innerHTML = data.playlists.map(p =>
                    `<button class="playlist-pick-btn w-100 text-start px-3 py-2 mb-1"
                        data-pid="${p.id}" style="background:var(--surface-2,#2a2a3a);border:none;border-radius:6px;color:var(--text-primary,#fff);font-size:0.875rem;cursor:pointer;">
                        <i class="fas fa-music me-2" style="color:var(--accent);"></i>${p.name}
                    </button>`
                ).join('');
                body.querySelectorAll('.playlist-pick-btn').forEach(pb => {
                    pb.addEventListener('click', async () => {
                        const pid = pb.dataset.pid;
                        modal.hide();
                        const r2 = await fetch('/soundwave/php/add_to_playlist.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `playlist_id=${pid}&song_id=${_pendingSongId}`
                        });
                        const d2 = await r2.json();
                        showToast(d2.message || 'Added to playlist!');
                    });
                });
            } else {
                body.innerHTML = '<p class="text-muted text-center py-3" style="font-size:0.85rem;">No playlists yet.<br><a href="/soundwave/playlists.php" style="color:var(--accent)">Create one first</a></p>';
            }
        } catch(err) {
            body.innerHTML = '<p class="text-muted text-center py-3">Failed to load playlists.</p>';
        }
    });

    // ── Toast Notification ─────────────────────────
    window.showToast = function(msg, type = 'success') {
        const t = document.createElement('div');
        t.className = `sw-toast sw-toast-${type}`;
        t.textContent = msg;
        Object.assign(t.style, {
            position: 'fixed', bottom: '90px', right: '20px', zIndex: 9999,
            background: type === 'success' ? 'rgba(34,197,94,0.9)' : 'rgba(239,68,68,0.9)',
            color: '#fff', padding: '10px 18px', borderRadius: '8px',
            fontSize: '0.875rem', fontWeight: '600',
            animation: 'fadeInUp 0.3s ease'
        });
        document.body.appendChild(t);
        setTimeout(() => t.remove(), 3000);
    };

    // ── Password Strength Meter ────────────────────
    const pwInput    = document.getElementById('password');
    const pwStrength = document.getElementById('pw-strength-fill');
    if (pwInput && pwStrength) {
        pwInput.addEventListener('input', () => {
            const val = pwInput.value;
            let score = 0;
            if (val.length >= 8)              score++;
            if (/[A-Z]/.test(val))            score++;
            if (/[0-9]/.test(val))            score++;
            if (/[^A-Za-z0-9]/.test(val))     score++;
            const colors = ['#ef4444','#f97316','#eab308','#22c55e'];
            pwStrength.style.width      = (score * 25) + '%';
            pwStrength.style.background = colors[score - 1] || '#ef4444';
        });
    }

    // ── Register Form Validation ───────────────────
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', (e) => {
            const pw  = document.getElementById('password').value;
            const pw2 = document.getElementById('password_confirm').value;
            const err = document.getElementById('form-error');

            if (pw.length < 8) {
                e.preventDefault();
                err.textContent = 'Password must be at least 8 characters.';
                err.style.display = 'block';
                return;
            }
            if (pw !== pw2) {
                e.preventDefault();
                err.textContent = 'Passwords do not match.';
                err.style.display = 'block';
                return;
            }
            const emailVal = document.getElementById('email').value;
            const emailRe  = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRe.test(emailVal)) {
                e.preventDefault();
                err.textContent = 'Please enter a valid email address.';
                err.style.display = 'block';
            }
        });
    }

    // ── Contact Form Validation ────────────────────
    const contactForm = document.getElementById('contact-form');
    if (contactForm) {
        contactForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const name    = document.getElementById('contact-name').value.trim();
            const email   = document.getElementById('contact-email').value.trim();
            const message = document.getElementById('contact-message').value.trim();
            const errEl   = document.getElementById('contact-error');
            const okEl    = document.getElementById('contact-ok');

            if (!name || !email || !message) {
                errEl.textContent = 'All fields are required.'; errEl.style.display = 'block'; return;
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                errEl.textContent = 'Invalid email address.'; errEl.style.display = 'block'; return;
            }
            errEl.style.display = 'none';
            okEl.style.display  = 'block';
            contactForm.reset();
            setTimeout(() => okEl.style.display = 'none', 4000);
        });
    }

    function formatTime(seconds) {
        if (!Number.isFinite(seconds) || seconds <= 0) return '0:00';
        const m = Math.floor(seconds / 60);
        const s = Math.floor(seconds % 60);
        return `${m}:${s.toString().padStart(2, '0')}`;
    }

    function updateMissingSongDurations() {
        const rows = Array.from(document.querySelectorAll('.song-row[data-song-url]')).filter(row => {
            const durationEl = row.querySelector('.song-row-duration');
            return durationEl && durationEl.textContent.trim() === '0:00';
        });
        if (rows.length === 0) return;

        const audio = document.createElement('audio');
        audio.preload = 'metadata';
        let index = 0;

        audio.addEventListener('loadedmetadata', () => {
            const durationEl = rows[index].querySelector('.song-row-duration');
            if (durationEl) {
                durationEl.textContent = formatTime(Math.round(audio.duration || 0));
            }
            index += 1;
            if (index < rows.length) {
                audio.src = rows[index].dataset.songUrl;
                audio.load();
            }
        });

        audio.addEventListener('error', () => {
            index += 1;
            if (index < rows.length) {
                audio.src = rows[index].dataset.songUrl;
                audio.load();
            }
        });

        audio.src = rows[0].dataset.songUrl;
        audio.load();
    }

    updateMissingSongDurations();

    // ── Live Search Debounce ───────────────────────
    const searchInput = document.querySelector('.sw-search');
    if (searchInput) {
        let debounceTimer;
        searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                // Auto-submit search after 600ms pause
                if (searchInput.value.length > 2) {
                    searchInput.closest('form').submit();
                }
            }, 600);
        });
    }
});
