<section class="forum-layout">
    <article class="panel start-discussion">
        <div class="compose-avatar"><?= e(mb_strtoupper(mb_substr($user['name'], 0, 1))) ?></div>
        <form method="post" class="discussion-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="create_post">
            <label class="sr-only" for="discussionTitle">Discussion title</label><input id="discussionTitle" name="title" maxlength="180" required placeholder="Start a conversation…">
            <label class="sr-only" for="discussionBody">Discussion message</label><textarea id="discussionBody" name="body" maxlength="4000" rows="2" required placeholder="Share an update, ask a question, or celebrate a win."></textarea>
            <div class="discussion-form-footer"><span>Visible to your care team</span><button class="button button-primary button-small" type="submit">Post update <span>→</span></button></div>
        </form>
    </article>
    <?php if ($discussionPosts): ?>
        <?php foreach ($discussionPosts as $post): ?>
            <article class="panel discussion-card">
                <header class="discussion-author"><span class="table-avatar"><?= e(mb_strtoupper(mb_substr($post['author_name'], 0, 1))) ?></span><div><strong><?= e($post['author_name']) ?></strong><span>Team update · <?= e(date('M j, Y · g:i A', strtotime($post['created_at']))) ?></span></div></header>
                <h2><?= e($post['title']) ?></h2><p class="discussion-body"><?= nl2br(e($post['body'])) ?></p>
                <div class="discussion-replies"><span>↳ <?= (int) $post['comment_count'] ?> <?= (int) $post['comment_count'] === 1 ? 'reply' : 'replies' ?></span></div>
                <?php foreach ($commentsByPost[$post['id']] ?? [] as $comment): ?>
                    <div class="discussion-comment"><span class="comment-avatar"><?= e(mb_strtoupper(mb_substr($comment['author_name'], 0, 1))) ?></span><div class="comment-content"><div><strong><?= e($comment['author_name']) ?></strong><span><?= e(date('M j · g:i A', strtotime($comment['created_at']))) ?></span></div><p><?= nl2br(e($comment['body'])) ?></p></div></div>
                <?php endforeach; ?>
                <form method="post" class="comment-form">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="add_comment"><input type="hidden" name="post_id" value="<?= (int) $post['id'] ?>">
                    <span class="comment-avatar own-avatar"><?= e(mb_strtoupper(mb_substr($user['name'], 0, 1))) ?></span><label class="sr-only" for="reply-<?= (int) $post['id'] ?>">Write a reply</label><input id="reply-<?= (int) $post['id'] ?>" name="body" maxlength="2000" required placeholder="Write a reply…"><button class="reply-submit" type="submit" aria-label="Send reply">→</button>
                </form>
            </article>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="panel"><div class="empty-state"><span class="empty-icon">✳</span><strong>Good conversations start somewhere.</strong><p>Share the first update with your team.</p></div></div>
    <?php endif; ?>
</section>
