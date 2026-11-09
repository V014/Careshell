(() => {
    const sidebar = document.getElementById('sidebar');
    const scrim = document.getElementById('sidebarScrim');
    const menuToggle = document.getElementById('menuToggle');

    const setMenu = (open) => {
        sidebar?.classList.toggle('is-open', open);
        scrim?.classList.toggle('is-visible', open);
        document.body.classList.toggle('menu-open', open);
    };

    menuToggle?.addEventListener('click', () => setMenu(!sidebar?.classList.contains('is-open')));
    scrim?.addEventListener('click', () => setMenu(false));

    document.querySelectorAll('[data-open-dialog]').forEach((button) => {
        button.addEventListener('click', () => {
            const dialog = document.getElementById(button.dataset.openDialog);
            if (!dialog) return;
            dialog.querySelector('form')?.reset();
            const id = dialog.querySelector('input[name="id"]');
            if (id) id.value = '';
            const title = dialog.querySelector('.dialog-title');
            if (title) {
                title.textContent = dialog.id === 'patientDialog' ? 'Add a patient'
                    : dialog.id === 'appointmentDialog' ? 'New appointment'
                    : dialog.id === 'reminderDialog' ? 'Add a reminder'
                    : 'Add team member';
            }
            if (dialog.id === 'appointmentDialog') {
                const date = dialog.querySelector('[name="appointment_date"]');
                if (date) date.value = new Date().toLocaleDateString('en-CA');
                const provider = dialog.querySelector('[name="interviewer"]');
                if (provider) provider.value = provider.defaultValue;
            }
            dialog.showModal();
        });
    });

    document.querySelectorAll('[data-close-dialog]').forEach((button) => {
        button.addEventListener('click', () => button.closest('dialog')?.close());
    });

    document.querySelectorAll('[data-edit-patient]').forEach((button) => {
        button.addEventListener('click', () => {
            const data = JSON.parse(button.dataset.editPatient);
            const dialog = document.getElementById('patientDialog');
            if (!dialog) return;
            Object.entries({
                id: data.id,
                name: data.name,
                contact: data.contact,
                email: data.email,
                birthday: data.birthday,
                blood_type: data.blood_type,
                gender: data.gender,
                address: data.address,
                notes: data.notes,
            }).forEach(([name, value]) => {
                const input = dialog.querySelector(`[name="${name}"]`);
                if (input) input.value = value ?? '';
            });
            dialog.querySelector('.dialog-title').textContent = 'Edit patient';
            dialog.showModal();
        });
    });

    document.querySelectorAll('[data-edit-appointment]').forEach((button) => {
        button.addEventListener('click', () => {
            const data = JSON.parse(button.dataset.editAppointment);
            const dialog = document.getElementById('appointmentDialog');
            if (!dialog) return;
            Object.entries({
                id: data.id,
                patient_id: data.patient_id,
                interviewer: data.interviewer,
                interviewee: data.interviewee,
                interviewee_position: data.interviewee_position,
                facility: data.facility,
                description: data.description,
                appointment_date: data.appointment_date,
                appointment_time: data.appointment_time?.slice(0, 5),
                status: data.status,
            }).forEach(([name, value]) => {
                const input = dialog.querySelector(`[name="${name}"]`);
                if (input) input.value = value ?? '';
            });
            dialog.querySelector('.dialog-title').textContent = 'Edit appointment';
            dialog.showModal();
        });
    });

    document.querySelectorAll('[data-edit-reminder]').forEach((button) => {
        button.addEventListener('click', () => {
            const data = JSON.parse(button.dataset.editReminder);
            const dialog = document.getElementById('reminderDialog');
            if (!dialog) return;
            Object.entries({
                id: data.id,
                title: data.title,
                description: data.description,
                reminder_date: data.reminder_date,
                reminder_time: data.reminder_time?.slice(0, 5),
                repeat_rule: data.repeat_rule,
            }).forEach(([name, value]) => {
                const input = dialog.querySelector(`[name="${name}"]`);
                if (input) input.value = value ?? '';
            });
            dialog.querySelector('.dialog-title').textContent = 'Edit reminder';
            dialog.showModal();
        });
    });

    document.querySelectorAll('dialog').forEach((dialog) => {
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) dialog.close();
        });
    });

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) event.preventDefault();
        });
    });

    document.querySelectorAll('.notice-close').forEach((button) => {
        button.addEventListener('click', () => button.closest('.notice')?.remove());
    });

    const patientSelect = document.querySelector('#appointmentDialog [name="patient_id"]');
    const patientName = document.querySelector('#appointmentDialog [name="interviewee"]');
    patientSelect?.addEventListener('change', () => {
        if (patientSelect.value === '0') return;
        const selected = patientSelect.options[patientSelect.selectedIndex];
        if (patientName) patientName.value = selected.textContent.trim();
    });

    const historyPanel = document.querySelector('[data-message-history]');
    if (historyPanel) historyPanel.scrollTop = historyPanel.scrollHeight;
})();
