<?php if ( ! defined( 'ABSPATH' ) ) exit;
$active_tab = sanitize_key( $_GET['tab'] ?? 'general' );
$has_key    = ! empty( bgai_get_api_key() );
?>
<div class="bgai-wrap">

  <div class="bgai-header">
    <div>
      <h1 class="bgai-title">BlogGenie AI <span class="bgai-ver">v<?php echo BGAI_VERSION; ?></span></h1>
      <p class="bgai-sub">Settings</p>
    </div>
  </div>

  <?php if ( isset($_GET['saved']) ) : ?><div class="bgai-alert bgai-alert-ok">Settings saved successfully.</div><?php endif; ?>
  <?php if ( isset($_GET['reset']) ) : ?><div class="bgai-alert bgai-alert-warn">API key removed. Enter a new key below.</div><?php endif; ?>

  <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
    <?php wp_nonce_field('bgai_save'); ?>
    <input type="hidden" name="action" value="bgai_save">

    <div class="bgai-tabs">
      <?php
      $tabs = array(
        'general'  => 'General',
        'keywords' => 'Keywords',
        'schedule' => 'Schedule',
        'advanced' => 'Advanced',
      );
      foreach ( $tabs as $slug => $label ) :
      ?>
        <a href="<?php echo admin_url('admin.php?page=bloggenie-ai-settings&tab='.$slug); ?>"
           class="bgai-tab <?php echo $active_tab === $slug ? 'bgai-tab-active' : ''; ?>">
          <?php echo esc_html($label); ?>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="bgai-card bgai-tab-panel">

      <?php if ( $active_tab === 'general' ) : ?>

        <h2 class="bgai-card-title">OpenAI API key</h2>
        <?php if ( $has_key ) : ?>
          <div class="bgai-api-row">
            <span class="bgai-badge bgai-badge-ok">API key saved and encrypted</span>
            <a href="<?php echo wp_nonce_url( admin_url('admin-post.php?action=bgai_reset_key'), 'bgai_reset_key' ); ?>"
               class="bgai-link-danger" onclick="return confirm('Remove the saved API key?')">Reset key</a>
          </div>
          <p class="bgai-desc">Your key is encrypted using your WordPress AUTH_KEY salt. It will never be displayed again.</p>
          <input type="hidden" name="bgai_api_key" value="">
        <?php else : ?>
          <div class="bgai-field">
            <label class="bgai-label">Enter your OpenAI API key</label>
            <input type="password" name="bgai_api_key" class="bgai-input bgai-mono" placeholder="sk-proj-..." autocomplete="new-password">
            <p class="bgai-desc">Get your key from <a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com/api-keys</a>. Load at least $5 credit. After saving, this field is hidden permanently.</p>
          </div>
        <?php endif; ?>

        <h2 class="bgai-card-title" style="margin-top:24px">Writing tone</h2>
        <div class="bgai-field">
          <select name="bgai_tone" class="bgai-input">
            <?php
            $tones = array(
              'professional and informative' => 'Professional and informative',
              'casual and conversational'    => 'Casual and conversational',
              'authoritative and expert'     => 'Authoritative and expert',
              'beginner-friendly'            => 'Beginner-friendly',
            );
            $cur = get_option('bgai_tone','professional and informative');
            foreach ( $tones as $val => $label ) :
            ?>
              <option value="<?php echo esc_attr($val); ?>" <?php selected($cur,$val); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <h2 class="bgai-card-title">Default post category</h2>
        <div class="bgai-field">
          <select name="bgai_category" class="bgai-input">
            <option value="0">— Uncategorised —</option>
            <?php foreach ( get_categories(array('hide_empty'=>false)) as $cat ) : ?>
              <option value="<?php echo $cat->term_id; ?>" <?php selected(get_option('bgai_category',0),$cat->term_id); ?>><?php echo esc_html($cat->name); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

      <?php elseif ( $active_tab === 'keywords' ) : ?>

        <h2 class="bgai-card-title">Seed keywords</h2>
        <?php $kws = get_option('bgai_keywords', array()); ?>
        <div class="bgai-kw-wrap" id="bgai-kw-wrap">
          <?php foreach ($kws as $kw) : ?>
            <span class="bgai-kw-tag"><?php echo esc_html($kw); ?> <span class="bgai-kw-x" onclick="bgaiRemoveKw(this)">&#215;</span></span>
          <?php endforeach; ?>
          <input type="text" id="bgai-kw-input" class="bgai-kw-input" placeholder="Type a keyword, press Enter">
        </div>
        <input type="hidden" name="bgai_keywords_raw" id="bgai-kw-raw" value="<?php echo esc_attr(implode(', ',$kws)); ?>">
        <p class="bgai-desc">Add 10–30 keywords for your niche. Plugin tries Google Trends first, then ChatGPT generates 10 fresh topic ideas if Trends is blocked. Press Enter after each keyword.</p>

        <h2 class="bgai-card-title" style="margin-top:24px">Topic blacklist</h2>
        <div class="bgai-field">
          <input type="text" name="bgai_blacklist" class="bgai-input" placeholder="royalenfield, realme, cricket, bollywood" value="<?php echo esc_attr(get_option('bgai_blacklist','')); ?>">
          <p class="bgai-desc">Comma-separated words. Any topic containing these words will be skipped — even as a fallback. Use this to block off-niche topics.</p>
        </div>

      <?php elseif ( $active_tab === 'schedule' ) : ?>

        <h2 class="bgai-card-title">Daily publishing times</h2>
        <div class="bgai-grid2">
          <div class="bgai-field">
            <label class="bgai-label">Post 1 — time</label>
            <input type="time" name="bgai_time1" class="bgai-input" value="<?php echo esc_attr(get_option('bgai_time1','08:00')); ?>">
          </div>
          <div class="bgai-field">
            <label class="bgai-label">Post 2 — time</label>
            <input type="time" name="bgai_time2" class="bgai-input" value="<?php echo esc_attr(get_option('bgai_time2','15:00')); ?>">
          </div>
        </div>
        <p class="bgai-desc">Times use your WordPress timezone: <strong><?php echo esc_html(get_option('timezone_string','UTC')); ?></strong>. Change it under <a href="<?php echo admin_url('options-general.php'); ?>">Settings → General</a>.</p>

      <?php elseif ( $active_tab === 'advanced' ) : ?>

        <h2 class="bgai-card-title">Feature toggles</h2>
        <?php
        $toggles = array(
          array('bgai_enable_images',  'Generate featured image (DALL-E 3)',  'Creates a unique image per post via DALL-E 3 (~$0.04/image). Falls back to Unsplash if DALL-E fails.'),
          array('bgai_enable_linking', 'Auto internal linking',               'ChatGPT scans all your posts and injects 3–5 contextual links into each new article.'),
          array('bgai_enable_yoast',   'Write to Yoast SEO fields',           'Auto-fills meta title, meta description, focus keyword, Open Graph and Twitter card fields.'),
          array('bgai_enable_faq',     'Add FAQ section to every post',       'Adds 5 questions and answers at the end of each article. Boosts Google People Also Ask.'),
        );
        foreach ($toggles as $t) :
          $on = get_option($t[0],'1') === '1';
        ?>
          <div class="bgai-toggle-row">
            <div class="bgai-toggle-info">
              <span class="bgai-toggle-label"><?php echo esc_html($t[1]); ?></span>
              <span class="bgai-toggle-desc"><?php echo esc_html($t[2]); ?></span>
            </div>
            <label class="bgai-switch">
              <input type="checkbox" name="<?php echo esc_attr($t[0]); ?>" value="1" <?php checked($on); ?>>
              <span class="bgai-switch-track"></span>
            </label>
          </div>
        <?php endforeach; ?>

      <?php endif; ?>

    </div>

    <div style="padding:0 0 24px">
      <button type="submit" class="bgai-btn-primary">Save settings</button>
    </div>

  </form>
</div>
