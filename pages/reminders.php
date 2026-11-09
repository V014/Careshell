<section class="reminder-grid">
<?php if ($reminders): foreach ($reminders as $reminder): ?>
    <article class="reminder-card">
        <div class="reminder-card-top"><span class="reminder-icon">◷</span><span class="repeat-tag"><?= e($reminder['repeat_rule'] === 'none' ? 'One-time' : ucfirst($reminder['repeat_rule'])) ?></span><?php if (can('reminders.edit')): ?><span class="reminder-actions"><button class="icon-button" type="button" title="Edit reminder" data-edit-reminder='<?= e(json_encode($reminder, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)) ?>'>✎</button><form method="post" class="inline-form" data-confirm="Delete this reminder?"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete_reminder"><input type="hidden" name="id" value="<?= (int) $reminder['id'] ?>"><button class="icon-button icon-danger" type="submit" title="Delete reminder">×</button></form></span><?php endif; ?></div>
        <h2><?= e($reminder['title']) ?></h2><p class="reminder-description"><?= e($reminder['description'] ?: 'No additional details.') ?></p>
        <div class="reminder-date"><strong><?= e(format_date($reminder['reminder_date'])) ?></strong><span><?= e($reminder['reminder_time'] ? date('g:i A', strtotime($reminder['reminder_time'])) : 'Any time') ?></span></div>
        <div class="reminder-author">Added by <?= e($reminder['created_by_name'] ?: 'Care team') ?></div>
    </article>
<?php endforeach; else: ?>
    <div class="panel reminder-empty"><div class="empty-state"><span class="empty-icon">◷</span><strong>No reminders just yet.</strong><p>Keep the small things from slipping through the cracks.</p><?php if (can('reminders.edit')): ?><button class="button button-primary" type="button" data-open-dialog="reminderDialog">＋ Add a reminder</button><?php endif; ?></div></div>
<?php endif; ?>
</section>
<?php if (can('reminders.edit')): ?>
<dialog class="modal-dialog" id="reminderDialog"><form method="post" class="modal-card">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="save_reminder"><input type="hidden" name="id" value="">
    <div class="modal-heading"><div><p class="eyebrow">TEAM REMINDER</p><h2 class="dialog-title">Add a reminder</h2></div><button type="button" class="modal-close" data-close-dialog aria-label="Close">×</button></div>
    <div class="form-grid"><label class="field-span">Title <span class="required">*</span><input name="title" required maxlength="180" placeholder="What should the team remember?"></label>
        <label>Date<input name="reminder_date" type="date"></label><label>Time<input name="reminder_time" type="time"></label>
        <label>Repeat<select name="repeat_rule"><option value="none">Does not repeat</option><option value="daily">Daily</option><option value="weekly">Weekly</option><option value="monthly">Monthly</option></select></label>
        <label class="field-span">Details<textarea name="description" rows="3" maxlength="2000" placeholder="A few more details (optional)"></textarea></label>
    </div>
    <div class="modal-actions"><button type="button" class="button button-quiet" data-close-dialog>Cancel</button><button type="submit" class="button button-primary">Save reminder <span>→</span></button></div>
</form></dialog>
<?php endif; ?>
