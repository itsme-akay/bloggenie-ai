<?php if ( ! defined( 'ABSPATH' ) ) exit;
$logs = BGAI_Logger::get_logs( 100 );
?>
<div class="bgai-wrap">

  <div class="bgai-header">
    <div>
      <h1 class="bgai-title">BlogGenie AI <span class="bgai-ver">v<?php echo BGAI_VERSION; ?></span></h1>
      <p class="bgai-sub">Activity Log</p>
    </div>
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
      <?php wp_nonce_field('bgai_clear_logs'); ?>
      <input type="hidden" name="action" value="bgai_clear_logs">
      <button type="submit" class="bgai-btn-ghost" onclick="return confirm('Clear all logs?')">Clear logs</button>
    </form>
  </div>

  <?php if ( isset($_GET['cleared']) ) : ?>
    <div class="bgai-alert bgai-alert-ok">Log cleared.</div>
  <?php endif; ?>

  <div class="bgai-card">
    <?php if ( empty($logs) ) : ?>
      <p class="bgai-empty">No activity yet. Run the pipeline from the Dashboard to see logs here.</p>
    <?php else : ?>
      <div class="bgai-log-list">
        <div class="bgai-log-header">
          <span>Time</span><span>Level</span><span>Message</span>
        </div>
        <?php foreach ( $logs as $log ) : ?>
          <div class="bgai-log-row bgai-log-<?php echo esc_attr($log->level); ?>">
            <span class="bgai-log-time"><?php echo esc_html( get_date_from_gmt($log->created_at,'d M Y H:i:s') ); ?></span>
            <span class="bgai-log-level"><?php echo esc_html( strtoupper($log->level) ); ?></span>
            <span class="bgai-log-msg"><?php echo esc_html($log->message); ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

</div>
