document.addEventListener('DOMContentLoaded', () => {

  /**
   * Generic clipboard helper
   */
  const copyText = async (text) => {
    if (!text) return false;
    try {
      await navigator.clipboard.writeText(text);
      return true;
    } catch (e) {
      return false;
    }
  };

  /**
   * FS Grid shortcode generator (only if present)
   */
  const generateBtn = document.getElementById('fs-generate');
  const outputBox = document.getElementById('fs-output');

  if (generateBtn && outputBox) {
    generateBtn.addEventListener('click', () => {
      const brand = (document.getElementById('fs-brand')?.value || '').trim();
      const color = (document.getElementById('fs-color')?.value || '').trim();
      const gender = (document.getElementById('fs-gender')?.value || '').trim();
      const age = (document.getElementById('fs-age')?.value || '').trim();
      const size = (document.getElementById('fs-size')?.value || '').trim();
      const perpage = (document.getElementById('fs-perpage')?.value || '').trim();

      let shortcode = '[fs_grid';

      if (brand) shortcode += ` brand="${brand}"`;
      if (color) shortcode += ` color="${color}"`;
      if (gender) shortcode += ` gender="${gender}"`;
      if (age) shortcode += ` age_group="${age}"`;
      if (size) shortcode += ` size="${size}"`;
      if (perpage) shortcode += ` per_page="${perpage}"`;

      shortcode += ']';

      outputBox.textContent = shortcode;
    });
  }

  /**
   * Generic copy buttons: data-fs-copy="#selector"
   */
  document.querySelectorAll('[data-fs-copy]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const selector = btn.getAttribute('data-fs-copy');
      const target = selector ? document.querySelector(selector) : null;
      const label = btn.getAttribute('data-fs-copy-label') || 'Copiar';
      const done = btn.getAttribute('data-fs-copy-done') || 'Copiado ✓';

      if (!target) return;

      const ok = await copyText(target.textContent || '');
      if (!ok) return;

      btn.textContent = done;
      setTimeout(() => {
        btn.textContent = label;
      }, 1400);
    });
  });

  /**
   * System info copy (no inline script)
   */
  const sysBtn = document.querySelector('[data-fs-copy-system="1"]');
  if (sysBtn) {
    sysBtn.addEventListener('click', async () => {
      const rows = document.querySelectorAll('.fs-admin-wrap .widefat tbody tr');
      let text = '';

      rows.forEach((row) => {
        const cells = row.querySelectorAll('td');
        if (cells.length === 2) {
          text += `${cells[0].innerText}: ${cells[1].innerText}\n`;
        }
      });

      const label = sysBtn.getAttribute('data-fs-copy-label') || 'Copiar información';
      const done = sysBtn.getAttribute('data-fs-copy-done') || 'Copiado ✔';

      const ok = await copyText(text.trim());
      if (!ok) return;

      sysBtn.textContent = done;
      setTimeout(() => {
        sysBtn.textContent = label;
      }, 1600);
    });
  }

});
