import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

window.transferJobsWidget = ({ initialJobs = [], endpoint = '', refreshMs = 2500 } = {}) => ({
	jobs: initialJobs,
	endpoint,
	refreshMs,
	isLoading: false,
	timerId: null,
	init() {
		this.fetchJobs();
		this.timerId = window.setInterval(() => this.fetchJobs(), this.refreshMs);
	},
	destroy() {
		if (this.timerId !== null) {
			window.clearInterval(this.timerId);
			this.timerId = null;
		}
	},
	async fetchJobs() {
		if (!this.endpoint || this.isLoading) {
			return;
		}

		this.isLoading = true;
		try {
			const response = await fetch(this.endpoint, {
				headers: {
					'Accept': 'application/json',
					'X-Requested-With': 'XMLHttpRequest',
				},
			});

			if (!response.ok) {
				return;
			}

			const payload = await response.json();
			if (Array.isArray(payload.jobs)) {
				this.jobs = payload.jobs;
			}
		} catch (error) {
			console.error('Failed to refresh transfer jobs.', error);
		} finally {
			this.isLoading = false;
		}
	},
});

Alpine.start();

const navbar = document.getElementById('site-navbar');

if (navbar) {
	let lastScrollY = window.scrollY;
	let ticking = false;
	const hideAfterPx = 72;

	const updateNavbarVisibility = () => {
		const currentY = window.scrollY;
		const scrollingDown = currentY > lastScrollY;
		const scrollingUp = currentY < lastScrollY;

		if (currentY <= hideAfterPx || scrollingUp) {
			navbar.classList.remove('nav-hidden');
		} else if (scrollingDown) {
			navbar.classList.add('nav-hidden');
		}

		lastScrollY = currentY;
		ticking = false;
	};

	window.addEventListener('scroll', () => {
		if (!ticking) {
			window.requestAnimationFrame(updateNavbarVisibility);
			ticking = true;
		}
	}, { passive: true });
}
