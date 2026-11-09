<section class="stats-grid">
    <article class="stat-card"><div class="stat-top"><span class="stat-label">Total patients</span><span class="stat-icon mint">◉</span></div><div class="stat-value"><?= number_format($counts['patients']) ?></div><div class="stat-foot"><span class="stat-foot-dot"></span> Care records in your workspace</div></article>
    <article class="stat-card"><div class="stat-top"><span class="stat-label">Appointments today</span><span class="stat-icon lilac">▦</span></div><div class="stat-value"><?= number_format($counts['appointments']) ?></div><div class="stat-foot"><span class="stat-foot-dot"></span> <?= number_format($counts['upcoming']) ?> upcoming visits scheduled</div></article>
    <article class="stat-card"><div class="stat-top"><span class="stat-label">Upcoming reminders</span><span class="stat-icon peach">◷</span></div><div class="stat-value"><?= number_format($counts['reminders']) ?></div><div class="stat-foot"><span class="stat-foot-dot"></span> Shared with your care team</div></article>
    <article class="stat-card stat-card-note"><span class="stat-label">A little reminder</span><div class="note-quote">“Good care is a team effort.”</div><div class="stat-foot">Every detail makes a difference.</div><span class="note-flower">✳</span></article>
</section>
<section class="dashboard-grid">
    <article class="panel appointments-panel">
        <div class="panel-heading"><div><h2>Today’s appointments</h2><p>Your schedule at a glance</p></div><a class="text-link" href="index.php?page=appointments">View schedule <span>→</span></a></div>
        <?php if ($todayAppointments): ?>
            <div class="appointment-list">
                <?php foreach ($todayAppointments as $appointment): ?>
                    <div class="appointment-row">
                        <div class="appointment-time"><?= e($appointment['appointment_time'] ? date('g:i A', strtotime($appointment['appointment_time'])) : '—') ?></div>
                        <div class="appointment-avatar"><?= e(mb_strtoupper(mb_substr($appointment['linked_patient'] ?: $appointment['interviewee'], 0, 1))) ?></div>
                        <div class="appointment-person"><strong><?= e($appointment['linked_patient'] ?: $appointment['interviewee']) ?></strong><span><?= e($appointment['interviewee_position'] ?: 'Patient visit') ?><?= $appointment['facility'] ? ' · ' . e($appointment['facility']) : '' ?></span></div>
                        <span class="status-pill status-<?= e($appointment['status']) ?>"><?= e(str_replace('_', ' ', $appointment['status'])) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state"><span class="empty-icon">☀</span><strong>A little breathing room.</strong><p>No appointments scheduled for today.</p><?php if (can('appointments.edit')): ?><button class="button button-soft" type="button" data-open-dialog="appointmentDialog">Schedule a visit</button><?php endif; ?></div>
        <?php endif; ?>
    </article>
    <article class="panel activity-panel">
        <div class="panel-heading"><div><h2>Recent activity</h2><p>Updates from your workspace</p></div><?php if (can('activity.view')): ?><a class="text-link" href="index.php?page=activity">View all <span>→</span></a><?php endif; ?></div>
        <?php if ($recentActivity): ?>
            <div class="activity-list">
                <?php foreach ($recentActivity as $activity): ?>
                    <div class="activity-row"><span class="activity-mark"><?= e(mb_strtoupper(mb_substr($activity['user_name'], 0, 1))) ?></span><div class="activity-copy"><p><strong><?= e($activity['user_name']) ?></strong> <?= e($activity['action']) ?> <strong><?= e($activity['entity']) ?></strong><?= $activity['details'] ? ' · ' . e($activity['details']) : '' ?></p><span><?= e(date('M j, g:i A', strtotime($activity['created_at']))) ?></span></div></div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state compact-empty"><span class="empty-icon">✳</span><strong>Your activity will show up here.</strong><p>Changes made by your team are kept in one place.</p></div>
        <?php endif; ?>
    </article>
</section>
<section class="bottom-callout"><div class="callout-symbol">✦</div><div><strong>Make space for what matters.</strong><p>Keep patient details, visits, and team reminders working together.</p></div><a href="index.php?page=patients" class="text-link">Browse patients <span>→</span></a></section>
