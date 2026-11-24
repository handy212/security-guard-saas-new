export function operationalAlarms(open, tenantId, userId) {
    let connection;
    let subscription;
    let inboxSubscription;
    let observer;
    let audio;
    const played = new Set();
    const seenNotices = new Set();
    let pollTimer;
    let noticeTimer;
    let playback = Promise.resolve();
    const settingsKey = 'guardcore-notification-preferences:' + userId;
    let preferences = {};
    try {
        preferences = JSON.parse(localStorage.getItem(settingsKey) || '{}') ?? {};
    } catch {
        // The browser may restrict local storage.
    }

    async function play(volume = 0.25) {
        if (!audio || audio.state !== 'running') return false;

        const tone = audio.createOscillator();
        const gain = audio.createGain();
        const start = audio.currentTime;
        tone.type = 'triangle';
        tone.frequency.setValueAtTime(680, start);
        tone.frequency.setValueAtTime(880, start + 0.28);
        gain.gain.setValueAtTime(0, start);
        gain.gain.linearRampToValueAtTime(volume, start + 0.04);
        gain.gain.setValueAtTime(0, start + 0.22);
        gain.gain.linearRampToValueAtTime(volume, start + 0.31);
        gain.gain.linearRampToValueAtTime(0, start + 0.55);
        tone.connect(gain).connect(audio.destination);
        tone.start(start);
        tone.stop(start + 0.56);
        await new Promise(resolve => tone.addEventListener('ended', resolve, { once: true }));
        return true;
    }

    return {
        open,
        minimized: false,
        audioState: 'Audio permission required',
        volume: preferences.volume ?? 25,
        routineToasts: preferences.routineToasts ?? true,
        notice: null,
        init() {
            let refreshedAt = 0;
            const refresh = () => {
                refreshedAt = Date.now();
                this.$wire.$refresh();
                window.dispatchEvent(new CustomEvent('notification-stream-changed'));
            };
            const savePreferences = () => {
                try {
                    localStorage.setItem(settingsKey, JSON.stringify({ volume: this.volume, routineToasts: this.routineToasts }));
                } catch {
                    // Preferences stay available for the current page.
                }
            };
            this.$watch('volume', savePreferences);
            this.$watch('routineToasts', savePreferences);
            this.updateNotice(false);
            this.connect = () => {
                if (window.Echo && !inboxSubscription) {
                    connection = window.Echo;
                    inboxSubscription = connection.private('user.' + userId)
                        .listen('.notification-inbox.changed', refresh).subscribed(refresh);
                }
                if (window.Echo && tenantId && !subscription) {
                    subscription = connection.private('tenant.' + tenantId + '.dispatch')
                        .listen('.operational-alerts.changed', refresh).subscribed(refresh);
                }
            };
            window.addEventListener('guardcore-echo-connected', this.connect);
            window.addEventListener('online', refresh);
            this.removeOnlineListener = () => window.removeEventListener('online', refresh);
            this.connect();
            let previousAlarmCount = Number(this.$el.dataset.criticalCount);
            observer = new MutationObserver(() => {
                const alarmCount = Number(this.$el.dataset.criticalCount);
                if (alarmCount > previousAlarmCount) this.minimized = false;
                previousAlarmCount = alarmCount;
                this.playPendingAlarms();
                this.updateNotice();
            });
            observer.observe(this.$el, { attributes: true, attributeFilter: ['data-alarm-ids', 'data-critical-count', 'data-routine-notice'] });
            pollTimer = setInterval(() => {
                if (document.hidden) return;
                const connected = connection?.connector.pusher.connection.state === 'connected'
                    && inboxSubscription?.subscription?.subscribed
                    && (!tenantId || subscription?.subscription?.subscribed);
                if (Date.now() - refreshedAt >= (connected ? 60000 : 15000)) refresh();
            }, 15000);
        },
        destroy() {
            observer?.disconnect();
            clearInterval(pollTimer);
            clearTimeout(noticeTimer);
            window.removeEventListener('guardcore-echo-connected', this.connect);
            this.removeOnlineListener();
            inboxSubscription?.stopListening('.notification-inbox.changed');
            subscription?.stopListening('.operational-alerts.changed');
            audio?.close();
        },
        updateNotice(show = true) {
            const notice = JSON.parse(this.$el.dataset.routineNotice || 'null');
            if (!notice || seenNotices.has(notice.id)) return;
            seenNotices.add(notice.id);
            if (!show || !this.routineToasts) return;
            this.notice = notice;
            clearTimeout(noticeTimer);
            noticeTimer = setTimeout(() => { this.notice = null; }, 8000);
        },
        minimizePanel() {
            this.minimized = true;
            this.$nextTick(() => this.$refs.criticalIndicator?.focus());
        },
        restorePanel() {
            this.minimized = false;
            this.$nextTick(() => this.$refs.heading?.focus());
        },
        async enableAudio() {
            try {
                audio ??= new AudioContext();
                await audio.resume();
                this.audioState = audio.state === 'running' ? 'Alarm audio ready' : 'Audio permission required';
                await this.playPendingAlarms();
            } catch {
                this.audioState = 'Audio unavailable';
            }
        },
        async previewAudio() {
            if (this.previewing) return;
            this.previewing = true;
            try {
                playback = playback.then(() => play(this.volume / 100));
                await playback;
            } finally {
                this.previewing = false;
            }
        },
        async playPendingAlarms() {
            if (this.audioState !== 'Alarm audio ready') return;

            const playOnce = async () => {
                const ids = this.$el.dataset.alarmIds.split(',').filter(Boolean);
                const fresh = ids.filter(id => {
                    if (played.has(id)) return false;
                    try {
                        return !localStorage.getItem('guardcore-alarm:' + userId + ':' + id);
                    } catch {
                        return true;
                    }
                });
                if (fresh.length && await play(this.volume / 100)) {
                    for (const id of fresh) {
                        played.add(id);
                        try {
                            localStorage.setItem('guardcore-alarm:' + userId + ':' + id, '1');
                        } catch {
                            // Some browser profiles disallow persistent storage.
                        }
                    }
                } else if (audio?.state !== 'running') {
                    this.audioState = 'Audio permission required';
                }
            };
            const playAcrossTabs = () => navigator.locks
                ? navigator.locks.request('guardcore-alarm-audio:' + userId, playOnce)
                : playOnce();
            playback = playback.then(playAcrossTabs).catch(() => { this.audioState = 'Audio unavailable'; });
            await playback;
        },
    };
}
