<section class="panel table-panel">
    <div class="table-toolbar">
        <div><h2>Patient directory <span class="count-badge"><?= number_format(count($patients)) ?></span></h2><p>Search and manage patient records</p></div>
        <form class="search-form" method="get" action="index.php"><input type="hidden" name="page" value="patients"><span class="search-icon">⌕</span><input type="search" name="q" placeholder="Search patients…" value="<?= e($search) ?>" aria-label="Search patients"><?php if ($search !== ''): ?><a href="index.php?page=patients" class="clear-search">Clear</a><?php endif; ?></form>
    </div>
    <?php if ($patients): ?>
    <div class="table-scroll"><table class="data-table"><thead><tr><th>PATIENT</th><th>CONTACT</th><th>DATE OF BIRTH</th><th>BLOOD TYPE</th><th>ADDED</th><?php if (can('patients.edit')): ?><th></th><?php endif; ?></tr></thead><tbody>
        <?php foreach ($patients as $patient): ?>
        <tr>
            <td><div class="table-person"><span class="table-avatar"><?= e(mb_strtoupper(mb_substr($patient['name'], 0, 1))) ?></span><div><strong><?= e($patient['name']) ?></strong><span><?= e($patient['email'] ?: 'Patient') ?></span></div></div></td>
            <td><?= e($patient['contact'] ?: '—') ?></td><td><?= e(format_date($patient['birthday'])) ?></td><td><?= e($patient['blood_type'] ?: '—') ?></td><td><?= e(format_date($patient['created_at'])) ?></td>
            <?php if (can('patients.edit')): ?><td class="row-actions"><button class="icon-button" type="button" title="Edit patient" data-edit-patient='<?= e(json_encode($patient, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)) ?>'>✎</button><form method="post" class="inline-form" data-confirm="Delete this patient record?"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete_patient"><input type="hidden" name="id" value="<?= (int) $patient['id'] ?>"><button class="icon-button icon-danger" type="submit" title="Delete patient">×</button></form></td><?php endif; ?>
        </tr>
        <?php endforeach; ?>
    </tbody></table></div>
    <?php else: ?>
        <div class="empty-state"><span class="empty-icon">◉</span><strong><?= $search !== '' ? 'No patients found.' : 'Your patient directory is ready.' ?></strong><p><?= $search !== '' ? 'Try another name, email, or phone number.' : 'Add your first patient to begin building care records.' ?></p><?php if ($search === '' && can('patients.edit')): ?><button class="button button-primary" type="button" data-open-dialog="patientDialog">＋ Add patient</button><?php endif; ?></div>
    <?php endif; ?>
    <div class="table-footer">Showing <?= number_format(count($patients)) ?> of <?= number_format($totalPatients) ?> patient records <span>Patient information is only visible to authorized staff.</span></div>
</section>
<?php if (can('patients.edit')): ?>
<dialog class="modal-dialog" id="patientDialog"><form method="post" class="modal-card">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="save_patient"><input type="hidden" name="id" value="">
    <div class="modal-heading"><div><p class="eyebrow">PATIENT RECORD</p><h2 class="dialog-title">Add a patient</h2></div><button type="button" class="modal-close" data-close-dialog aria-label="Close">×</button></div>
    <div class="form-grid">
        <label class="field-span">Full name <span class="required">*</span><input name="name" required maxlength="160" placeholder="e.g. Alex Morgan"></label>
        <label>Phone number<input name="contact" maxlength="40" placeholder="+1 (555) 000-0000"></label>
        <label>Email address<input name="email" type="email" maxlength="190" placeholder="patient@example.com"></label>
        <label>Date of birth<input name="birthday" type="date"></label>
        <label>Blood type<select name="blood_type"><option value="">Not recorded</option><?php foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $blood): ?><option><?= e($blood) ?></option><?php endforeach; ?></select></label>
        <label>Gender<input name="gender" maxlength="30" placeholder="Optional"></label>
        <label class="field-span">Address<input name="address" maxlength="255" placeholder="Street address"></label>
        <label class="field-span">Care notes<textarea name="notes" rows="3" maxlength="5000" placeholder="Relevant care details for your team"></textarea></label>
    </div>
    <div class="modal-actions"><button type="button" class="button button-quiet" data-close-dialog>Cancel</button><button type="submit" class="button button-primary">Save patient <span>→</span></button></div>
</form></dialog>
<?php endif; ?>
