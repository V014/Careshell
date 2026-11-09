<section class="panel table-panel">
    <div class="table-toolbar"><div><h2>Visit schedule <span class="count-badge"><?= number_format(count($appointments)) ?></span></h2><p>Appointments across your clinic</p></div><div class="toolbar-note"><span class="status-dot"></span> <?= e(date('l, F j')) ?></div></div>
    <?php if ($appointments): ?>
    <div class="table-scroll"><table class="data-table"><thead><tr><th>PATIENT</th><th>DATE &amp; TIME</th><th>CARE PROVIDER</th><th>LOCATION</th><th>STATUS</th><?php if (can('appointments.edit')): ?><th></th><?php endif; ?></tr></thead><tbody>
    <?php foreach ($appointments as $appointment): ?>
        <tr>
            <td><div class="table-person"><span class="table-avatar avatar-lilac"><?= e(mb_strtoupper(mb_substr($appointment['linked_patient'] ?: $appointment['interviewee'], 0, 1))) ?></span><div><strong><?= e($appointment['linked_patient'] ?: $appointment['interviewee']) ?></strong><span><?= e($appointment['interviewee_position'] ?: 'Patient visit') ?></span></div></div></td>
            <td><strong class="date-primary"><?= e(format_date($appointment['appointment_date'])) ?></strong><span class="cell-sub"><?= e($appointment['appointment_time'] ? date('g:i A', strtotime($appointment['appointment_time'])) : 'Time not set') ?></span></td>
            <td><?= e($appointment['interviewer']) ?></td><td><?= e($appointment['facility'] ?: '—') ?></td>
            <td><span class="status-pill status-<?= e($appointment['status']) ?>"><?= e(str_replace('_', ' ', $appointment['status'])) ?></span></td>
            <?php if (can('appointments.edit')): ?><td class="row-actions"><button class="icon-button" type="button" title="Edit appointment" data-edit-appointment='<?= e(json_encode($appointment, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)) ?>'>✎</button><form method="post" class="inline-form" data-confirm="Delete this appointment?"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete_appointment"><input type="hidden" name="id" value="<?= (int) $appointment['id'] ?>"><button class="icon-button icon-danger" type="submit" title="Delete appointment">×</button></form></td><?php endif; ?>
        </tr>
    <?php endforeach; ?>
    </tbody></table></div>
    <?php else: ?><div class="empty-state"><span class="empty-icon">▦</span><strong>No appointments yet.</strong><p>When you schedule visits, they’ll show up here.</p><?php if (can('appointments.edit')): ?><button class="button button-primary" type="button" data-open-dialog="appointmentDialog">＋ Schedule appointment</button><?php endif; ?></div><?php endif; ?>
    <div class="table-footer">Showing up to 200 appointments <span>Visit details are shared with your authorized care team.</span></div>
</section>
<?php if (can('appointments.edit')): ?>
<dialog class="modal-dialog" id="appointmentDialog"><form method="post" class="modal-card">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="save_appointment"><input type="hidden" name="id" value="">
    <div class="modal-heading"><div><p class="eyebrow">CARE COORDINATION</p><h2 class="dialog-title">New appointment</h2></div><button type="button" class="modal-close" data-close-dialog aria-label="Close">×</button></div>
    <div class="form-grid">
        <label>Patient record<select name="patient_id"><option value="0">Not linked</option><?php foreach ($patientOptions as $option): ?><option value="<?= (int) $option['id'] ?>"><?= e($option['name']) ?></option><?php endforeach; ?></select></label>
        <label>Patient name <span class="required">*</span><input name="interviewee" required maxlength="160" placeholder="Patient full name"></label>
        <label>Care provider <span class="required">*</span><input name="interviewer" required maxlength="120" value="<?= e($user['name']) ?>"></label>
        <label>Visit type<input name="interviewee_position" maxlength="100" placeholder="e.g. Follow-up"></label>
        <label>Date <span class="required">*</span><input name="appointment_date" type="date" required></label>
        <label>Time<input name="appointment_time" type="time"></label>
        <label>Location<input name="facility" maxlength="120" placeholder="Clinic or room"></label>
        <label>Status<select name="status"><option value="scheduled">Scheduled</option><option value="confirmed">Confirmed</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option><option value="no_show">No show</option></select></label>
        <label class="field-span">Visit notes<textarea name="description" rows="3" maxlength="2000" placeholder="Optional details for the care team"></textarea></label>
    </div>
    <div class="modal-actions"><button type="button" class="button button-quiet" data-close-dialog>Cancel</button><button type="submit" class="button button-primary">Save appointment <span>→</span></button></div>
</form></dialog>
<?php endif; ?>
