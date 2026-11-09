<section class="account-grid">
    <article class="panel account-panel">
        <div class="account-panel-heading"><span class="account-icon">♙</span><div><h2>Personal details</h2><p>Keep your team profile up to date.</p></div></div>
        <form method="post" class="account-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="update_profile">
            <label>Full name<input name="name" value="<?= e($account['name']) ?>" required minlength="2" maxlength="120"></label>
            <label>Work email<input type="email" name="email" value="<?= e($account['email']) ?>" required maxlength="190"></label>
            <label>Your role<input value="<?= e(['admin' => 'Administrator', 'doctor' => 'Doctor', 'nurse' => 'Nurse', 'receptionist' => 'Receptionist'][$account['role']] ?? $account['role']) ?>" disabled></label>
            <div class="account-form-footer"><span>Member since <?= e(format_date($account['created_at'])) ?></span><button type="submit" class="button button-primary">Save profile <span>→</span></button></div>
        </form>
    </article>
    <article class="panel account-panel">
        <div class="account-panel-heading"><span class="account-icon account-icon-lilac">⌑</span><div><h2>Change password</h2><p>Choose a strong password only you know.</p></div></div>
        <form method="post" class="account-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="change_password">
            <label>Current password<input type="password" name="current_password" required autocomplete="current-password"></label>
            <label>New password<input type="password" name="new_password" required minlength="12" autocomplete="new-password"><small>Use at least 12 characters.</small></label>
            <label>Confirm new password<input type="password" name="password_confirmation" required minlength="12" autocomplete="new-password"></label>
            <div class="account-form-footer"><span>Your password is stored as a secure hash.</span><button type="submit" class="button button-primary">Update password <span>→</span></button></div>
        </form>
    </article>
</section>
