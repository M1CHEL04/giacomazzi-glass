document.querySelectorAll('[data-toggle="password"]').forEach((button) => {
            button.addEventListener('click', () => {
                const targetId = button.getAttribute('data-target');
                const input = document.getElementById(targetId);
                if (!input) {
                    return;
                }

                const isHidden = input.type === 'password';
                input.type = isHidden ? 'text' : 'password';

                const openIcon = button.querySelector('[data-eye="open"]');
                const closedIcon = button.querySelector('[data-eye="closed"]');

                if (openIcon && closedIcon) {
                    openIcon.classList.toggle('d-none', isHidden);
                    closedIcon.classList.toggle('d-none', !isHidden);
                }
            });
        });