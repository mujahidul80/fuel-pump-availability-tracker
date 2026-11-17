<footer class="site-footer">
    <div class="footer-content">
        <div class="footer-left">
            <h3><?= htmlspecialchars($settings['site_name'] ?? 'তেল দিচ্ছে?') ?></h3>
            <p><?= htmlspecialchars($settings['site_desc'] ?? 'চুয়াডাঙ্গা জ্বালানি তথ্য সেবা') ?></p>
            <?php if (!empty($settings['contact_email'])): ?>
                <p>📧 <?= htmlspecialchars($settings['contact_email']) ?></p>
            <?php endif; ?>
        </div>
        <div class="footer-right">
            <?php if (!empty($settings['promo_text'])): ?>
                <div class="promo-box">
                    <?php if (!empty($settings['promo_link'])): ?>
                        <a href="<?= htmlspecialchars($settings['promo_link']) ?>" target="_blank"><?= htmlspecialchars($settings['promo_text']) ?></a>
                    <?php else: ?>
                        <span><?= htmlspecialchars($settings['promo_text']) ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($settings['facebook_link'])): ?>
                <a href="<?= htmlspecialchars($settings['facebook_link']) ?>" target="_blank" class="social-link">Facebook -এ যুক্ত হোন</a>
            <?php endif; ?>
            <a href="https://wa.me/8801571432927" target="_blank" class="social-link" style="background:#25D366;color:#fff;border-color:#25D366;display:inline-flex;align-items:center;gap:6px;"><i class="fa-brands fa-whatsapp"></i> WhatsApp -এ যোগাযোগ</a>
        </div>
    </div>
    <div class="footer-bottom">
        <p class="copyright-text">&copy; <?= htmlspecialchars($settings['copyright_year'] ?? '২০২৬') ?> <?= htmlspecialchars($settings['creator_name'] ?? 'মুজাহিদুল ইসলাম') ?> | সর্বস্বত্ব সংরক্ষিত</p>
        <p class="powered-by">ডিজাইন ও ডেভেলপমেন্ট: <strong><?= htmlspecialchars($settings['creator_name'] ?? 'মুজাহিদুল ইসলাম') ?></strong></p>
    </div>
</footer>

<script src="script.js?v=<?= time() ?>"></script>
</body>
</html>