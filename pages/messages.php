<section class="panel messages-panel">
    <?php if ($teammates): ?>
        <aside class="conversation-list">
            <div class="conversation-heading"><h2>Conversations</h2><p>Private messages with your team</p></div>
            <?php foreach ($teammates as $teammate): ?>
                <a class="conversation-link <?= (int) $selectedTeammate['id'] === (int) $teammate['id'] ? 'selected' : '' ?>" href="index.php?page=messages&amp;with=<?= (int) $teammate['id'] ?>">
                    <span class="table-avatar"><?= e(mb_strtoupper(mb_substr($teammate['name'], 0, 1))) ?></span><span class="conversation-person"><strong><?= e($teammate['name']) ?></strong><span><?= e(ucfirst($teammate['role'])) ?></span></span>
                    <?php if ((int) $teammate['unread_count'] > 0): ?><span class="unread-dot" title="<?= (int) $teammate['unread_count'] ?> unread messages"></span><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </aside>
        <div class="message-thread">
            <header class="thread-heading"><span class="table-avatar"><?= e(mb_strtoupper(mb_substr($selectedTeammate['name'], 0, 1))) ?></span><div><strong><?= e($selectedTeammate['name']) ?></strong><span><?= e(ucfirst($selectedTeammate['role'])) ?> · Private conversation</span></div><span class="thread-lock">Private</span></header>
            <div class="message-history" id="messageHistory" data-message-history>
                <?php if ($threadMessages): ?>
                    <?php $lastDay = ''; foreach ($threadMessages as $message): $day = date('Y-m-d', strtotime($message['created_at'])); ?>
                        <?php if ($lastDay !== $day): ?><div class="message-day"><span><?= e(date('F j, Y', strtotime($message['created_at']))) ?></span></div><?php $lastDay = $day; endif; ?>
                        <div class="message-line <?= (int) $message['sender_id'] === (int) $user['id'] ? 'message-own' : 'message-theirs' ?>">
                            <div class="message-bubble"><?= nl2br(e($message['body'])) ?><span><?= e(date('g:i A', strtotime($message['created_at']))) ?></span></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state thread-empty"><span class="empty-icon">✉</span><strong>Say hello to <?= e(explode(' ', $selectedTeammate['name'])[0]) ?>.</strong><p>This is the start of your private conversation.</p></div>
                <?php endif; ?>
            </div>
            <form method="post" class="message-compose">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="send_message"><input type="hidden" name="recipient_id" value="<?= (int) $selectedTeammate['id'] ?>">
                <label class="sr-only" for="messageBody">Write a message</label><textarea id="messageBody" name="body" rows="1" maxlength="2000" required placeholder="Write a private message…"></textarea><button type="submit" aria-label="Send message">↑</button>
            </form>
        </div>
    <?php else: ?>
        <div class="panel"><div class="empty-state"><span class="empty-icon">✉</span><strong>No teammates to message yet.</strong><p>When another staff member joins, you can start a private conversation.</p><?php if (can('staff.manage')): ?><a class="button button-soft" href="index.php?page=team">Add a teammate</a><?php endif; ?></div></div>
    <?php endif; ?>
</section>
