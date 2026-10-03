<?php
require __DIR__ . '/inc/layout.php';

const FORM_SERVICES = ['Full 360° campaign', 'Strategy', 'Branding', 'Social media', 'Performance ads', 'SEO', 'Video production', 'Website', 'ATL media', 'BTL / events'];

$email = setting('email');
$phone = setting('phone');
$wa = setting('whatsapp');
$sent = isset($_GET['sent']);
$error = FORM_ERRORS[$_GET['error'] ?? ''] ?? '';

page_start('contact');
page_hero('Contact', 'Contact', heading(t('contact', 'hero_title')), t('contact', 'hero_lead'));
?>

<section class="section" style="padding-top:0">
  <div class="container contact">
    <div class="c-info">
      <div class="label" data-reveal>Say hello</div>
<?php if ($email): ?>      <a class="big" href="mailto:<?= e($email) ?>" data-reveal><?= e($email) ?></a>
<?php endif; if ($phone): ?>      <a class="big" href="<?= is_placeholder($phone) ? '#' : e(tel_href($phone)) ?>" data-reveal><?= e($phone) ?></a>
<?php endif; ?>
      <ul class="c-list" data-reveal>
<?php if ($wa && !is_placeholder($wa)): ?>
        <li><span class="ic"><?= icon('wa') ?></span><div><b>WhatsApp</b><a href="<?= e(wa_href($wa)) ?>" target="_blank" rel="noopener">Chat with us on WhatsApp</a></div></li>
<?php endif; if (setting('address')): ?>
        <li><span class="ic"><?= icon('pin') ?></span><div><b>Studio</b><?= safe_url(setting('map_url')) ? '<a href="' . e(setting('map_url')) . '" target="_blank" rel="noopener">' . e(setting('address')) . '</a>' : '<span>' . e(setting('address')) . '</span>' ?></div></li>
<?php endif; if (setting('hours')): ?>
        <li><span class="ic"><?= icon('clock') ?></span><div><b>Hours</b><span><?= e(setting('hours')) ?></span></div></li>
<?php endif; if (setting('response_time')): ?>
        <li><span class="ic"><?= icon('bolt') ?></span><div><b>Response time</b><span><?= e(setting('response_time')) ?></span></div></li>
<?php endif; ?>
      </ul>
    </div>
    <div class="form" id="contact-form" data-reveal>
      <form data-form<?= $sent ? ' style="display:none"' : '' ?> action="<?= url('contact-submit') ?>" method="post" novalidate>
        <div class="hp" aria-hidden="true"><label>Website <input name="website" tabindex="-1" autocomplete="off"></label></div>
        <div class="form-row">
          <div class="field"><label for="f-name">Your name *</label><input id="f-name" name="name" required maxlength="120" autocomplete="name"></div>
          <div class="field"><label for="f-company">Company / brand *</label><input id="f-company" name="company" required maxlength="160" autocomplete="organization"></div>
        </div>
        <div class="form-row">
          <div class="field"><label for="f-email">Email *</label><input id="f-email" name="email" type="email" required maxlength="160" autocomplete="email"></div>
          <div class="field"><label for="f-phone">Phone</label><input id="f-phone" name="phone" type="tel" maxlength="40" autocomplete="tel"></div>
        </div>
        <div class="field"><label>What do you need?</label><div class="chips"><?php foreach (FORM_SERVICES as $s): ?><label><input type="checkbox" name="services[]" value="<?= e($s) ?>"><span><?= e($s) ?></span></label><?php endforeach; ?></div></div>
        <div class="form-row">
          <div class="field"><label for="f-industry">Industry</label><select id="f-industry" name="industry"><option value="">Select…</option><?php foreach (items('industries') as $ind): ?><option><?= e($ind['name']) ?></option><?php endforeach; ?><option>Other</option></select></div>
          <div class="field"><label for="f-budget">Monthly budget</label><select id="f-budget" name="budget"><option value="">Select…</option><?php foreach (lines(setting('budgets')) as $b): ?><option><?= e($b) ?></option><?php endforeach; ?></select></div>
        </div>
        <div class="field"><label for="f-msg">Tell us about your project *</label><textarea id="f-msg" name="message" required maxlength="5000" placeholder="Goals, timelines, current challenges…"></textarea></div>
        <p class="form-error" role="alert"<?= $error ? '' : ' hidden' ?>><?= e($error) ?></p>
        <button class="btn btn--lime btn--lg" type="submit" style="width:100%" data-magnetic=".1"><?= e(t('contact', 'form_button')) ?> <?= icon('arrow-ur') ?></button>
      </form>
      <div class="form-success<?= $sent ? ' show' : '' ?>" role="status">
        <div class="ok"><?= icon('check') ?></div>
        <h3><?= e(setting('success_title', 'Message received!')) ?></h3>
        <p><?= e(setting('success_text')) ?></p>
      </div>
    </div>
  </div>
</section>

<?php page_end();
