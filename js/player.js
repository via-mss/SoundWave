// js/player.js — SoundWave Audio Player

const Player = (() => {
    const audio       = document.getElementById('audio-player');
    const btnPlay     = document.getElementById('btn-play');
    const btnPrev     = document.getElementById('btn-prev');
    const btnNext     = document.getElementById('btn-next');
    const progressBar = document.getElementById('progress-bar');
    const volumeBar   = document.getElementById('volume-bar');
    const timeCurrent = document.getElementById('time-current');
    const timeTotal   = document.getElementById('time-total');
    const coverEl     = document.getElementById('player-cover');
    const titleEl     = document.getElementById('player-title');
    const artistEl    = document.getElementById('player-artist');

    let queue    = [];
    let queueIdx = 0;
    let isPlaying = false;

    function formatTime(sec) {
        if (isNaN(sec)) return '0:00';
        const m = Math.floor(sec / 60);
        const s = Math.floor(sec % 60);
        return `${m}:${s.toString().padStart(2, '0')}`;
    }

    function updatePlayBtn() {
        btnPlay.innerHTML = isPlaying
            ? '<i class="fas fa-pause"></i>'
            : '<i class="fas fa-play"></i>';
    }

    function loadSong(song) {
        if (!song) return;
        audio.src = song.url;
        titleEl.textContent  = song.title;
        artistEl.textContent = song.artist;
        if (song.cover) {
            coverEl.style.backgroundImage = `url('${song.cover}')`;
            coverEl.classList.remove('player-cover-placeholder');
            coverEl.innerHTML = '';
        } else {
            coverEl.style.backgroundImage = '';
            coverEl.classList.add('player-cover-placeholder');
            coverEl.innerHTML = '<i class="fas fa-user"></i>';
        }
        if (song.album) {
            titleEl.href = `album.php?name=${encodeURIComponent(song.album)}`;
            titleEl.classList.remove('player-link-disabled');
        } else {
            titleEl.href = '#';
            titleEl.classList.add('player-link-disabled');
        }
        if (song.artistId) {
            artistEl.href = `artist.php?id=${encodeURIComponent(song.artistId)}`;
            artistEl.classList.remove('player-link-disabled');
        } else {
            artistEl.href = '#';
            artistEl.classList.add('player-link-disabled');
        }
        audio.load();
        play();
        // Highlight active row
        document.querySelectorAll('.song-row').forEach(r => r.classList.remove('playing'));
        const active = document.querySelector(`.song-row[data-song-id="${song.id}"]`);
        if (active) active.classList.add('playing');
        // Increment play count via AJAX
        if (song.id) {
            fetch(`/soundwave/php/increment_play.php?id=${song.id}`).catch(() => {});
        }
    }

    function play() {
        audio.play().then(() => {
            isPlaying = true;
            updatePlayBtn();
        }).catch(err => console.warn('Play error:', err));
    }

    function pause() {
        audio.pause();
        isPlaying = false;
        updatePlayBtn();
    }

    function togglePlay() {
        if (audio.src && audio.src !== window.location.href) {
            isPlaying ? pause() : play();
        } else if (queue.length > 0) {
            loadSong(queue[queueIdx]);
        }
    }

    function playNext() {
        if (queue.length === 0) return;
        queueIdx = (queueIdx + 1) % queue.length;
        loadSong(queue[queueIdx]);
    }

    function playPrev() {
        if (audio.currentTime > 3) {
            audio.currentTime = 0;
            return;
        }
        if (queue.length === 0) return;
        queueIdx = (queueIdx - 1 + queue.length) % queue.length;
        loadSong(queue[queueIdx]);
    }

    // Public: play a single song or set queue
    function playSong(song) {
        const idx = queue.findIndex(s => s.id === song.id);
        if (idx !== -1) {
            queueIdx = idx;
        } else {
            queue = [song];
            queueIdx = 0;
        }
        loadSong(queue[queueIdx]);
    }

    function setQueue(songs, startIdx = 0) {
        queue    = songs;
        queueIdx = startIdx;
        loadSong(queue[queueIdx]);
    }

    // Event listeners
    btnPlay.addEventListener('click', togglePlay);
    btnNext.addEventListener('click', playNext);
    btnPrev.addEventListener('click', playPrev);

    audio.addEventListener('timeupdate', () => {
        if (!audio.duration) return;
        const pct = (audio.currentTime / audio.duration) * 100;
        progressBar.value     = pct;
        timeCurrent.textContent = formatTime(audio.currentTime);
    });

    audio.addEventListener('loadedmetadata', () => {
        timeTotal.textContent = formatTime(audio.duration);
    });

    audio.addEventListener('ended', playNext);

    progressBar.addEventListener('input', () => {
        if (!audio.duration) return;
        audio.currentTime = (progressBar.value / 100) * audio.duration;
    });

    volumeBar.addEventListener('input', () => {
        audio.volume = volumeBar.value;
    });

    audio.volume = 0.8;

    const api = { playSong, setQueue, play, pause, togglePlay };
    window.Player = api;
    return api;
})();
