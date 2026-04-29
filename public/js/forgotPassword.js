const setLoading = (button, isLoading) => {
	if (!button) {
		return;
	}
	const spinner = button.querySelector('[data-spinner]');
	button.disabled = isLoading;
	if (spinner) {
		spinner.classList.toggle('d-none', !isLoading);
	}
};

const setStepActive = (step) => {
	document.querySelectorAll('[data-step-indicator]').forEach((indicator) => {
		const isActive = indicator.getAttribute('data-step-indicator') === String(step);
		indicator.classList.toggle('is-active', isActive);
	});
};

const showSection = (sectionId) => {
	document.querySelectorAll('.auth-section').forEach((section) => {
		section.classList.toggle('is-hidden', section.id !== sectionId);
	});
};

const handleJsonError = async (response) => {
	let message = 'Ocurrio un error. Intenta de nuevo.';
	try {
		const data = await response.json();
		if (data && data.message) {
			message = data.message;
		} else if (data && data.errors) {
			const firstError = Object.values(data.errors)[0];
			if (Array.isArray(firstError) && firstError.length) {
				message = firstError[0];
			}
		}
	} catch (error) {
		message = 'Ocurrio un error. Intenta de nuevo.';
	}

	if (window.showToast) {
		window.showToast(message, 'error');
	}
};

const handleSubmit = async ({ formId, nextStep, nextSectionId }) => {
	const form = document.getElementById(formId);
	if (!form) {
		return;
	}

	form.addEventListener('submit', async (event) => {
		event.preventDefault();
		const button = form.querySelector('[data-submit]');
		setLoading(button, true);

		const url = form.getAttribute('data-url');
		const formData = new FormData(form);
		const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

		try {
			const response = await fetch(url, {
				method: 'POST',
				headers: {
					'Accept': 'application/json',
					'X-CSRF-TOKEN': csrfToken || ''
				},
				body: formData
			});

			if (!response.ok) {
				await handleJsonError(response);
				return;
			}

			const data = await response.json();
			if (data?.redirect_url) {
				window.location.href = data.redirect_url;
				return;
			}

			if (window.showToast && data?.message) {
				window.showToast(data.message, 'success');
			}

			if (nextStep) {
				setStepActive(nextStep);
			}
			if (nextSectionId) {
				showSection(nextSectionId);
			}
		} catch (error) {
			if (window.showToast) {
				window.showToast('Ocurrio un error. Intenta de nuevo.', 'error');
			}
		} finally {
			setLoading(button, false);
		}
	});
};

handleSubmit({
	formId: 'request-code-form',
	nextStep: 2,
	nextSectionId: 'verify-code-form'
});

handleSubmit({
	formId: 'verify-code-form',
	nextStep: 3,
	nextSectionId: 'reset-password-form'
});

handleSubmit({
	formId: 'reset-password-form'
});
