<?php if ( ! defined( 'ABSPATH' ) ) exit;
$stats   = BGAI_Logger::get_stats();
$has_key = ! empty( bgai_get_api_key() );
$next1   = wp_next_scheduled( 'bgai_run_pipeline_1' );
$next2   = wp_next_scheduled( 'bgai_run_pipeline_2' );
$next    = ( $next1 && $next2 ) ? min( $next1, $next2 ) : ( $next1 ?: $next2 );
?>
<div class="bgai-wrap">

  <div class="bgai-header">
    <div>
      <h1 class="bgai-title">BlogGenie AI <span class="bgai-ver">v<?php echo BGAI_VERSION; ?></span></h1>
      <p class="bgai-sub">GPT-4o powered — Digital Marketing &amp; SEO</p>
    </div>
    <div>
      <?php if ( $has_key ) : ?>
        <span class="bgai-badge bgai-badge-ok">API Connected</span>
      <?php else : ?>
        <span class="bgai-badge bgai-badge-err">API Key Missing</span>
      <?php endif; ?>
    </div>
  </div>

  <?php if ( isset( $_GET['ran'] ) ) : ?>
    <div class="bgai-alert bgai-alert-ok">Pipeline triggered. Check Activity Log for results.</div>
  <?php endif; ?>

  <?php if ( ! $has_key ) : ?>
    <div class="bgai-alert bgai-alert-warn">No API key saved. <a href="<?php echo admin_url('admin.php?page=bloggenie-ai-settings'); ?>">Go to Settings → General</a> to add your OpenAI key.</div>
  <?php endif; ?>

  <div class="bgai-run-bar">
    <div>
      <div class="bgai-run-label">Next scheduled run</div>
      <div class="bgai-run-time">
        <?php echo $next ? esc_html( get_date_from_gmt( date( 'Y-m-d H:i:s', $next ), 'D d M Y \a\t H:i' ) ) : 'Not scheduled — activate plugin'; ?>
      </div>
    </div>
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
      <?php wp_nonce_field('bgai_run'); ?>
      <input type="hidden" name="action" value="bgai_run">
      <button type="submit" class="bgai-run-btn">Run now</button>
    </form>
  </div>

  <div class="bgai-stats">
    <div class="bgai-stat"><div class="bgai-stat-l">Posts this month</div><div class="bgai-stat-v"><?php echo $stats['published_month']; ?></div></div>
    <div class="bgai-stat"><div class="bgai-stat-l">Schedule</div><div class="bgai-stat-v" style="font-size:14px"><?php echo esc_html(get_option('bgai_time1','08:00')); ?> &amp; <?php echo esc_html(get_option('bgai_time2','15:00')); ?></div></div>
    <div class="bgai-stat"><div class="bgai-stat-l">Errors (7 days)</div><div class="bgai-stat-v <?php echo $stats['errors_week'] > 0 ? 'bgai-err' : 'bgai-ok'; ?>"><?php echo $stats['errors_week']; ?></div></div>
    <div class="bgai-stat"><div class="bgai-stat-l">AI model</div><div class="bgai-stat-v" style="font-size:14px">GPT-4o</div></div>
  </div>

  <div class="bgai-card">
    <h2 class="bgai-card-title">Recently published posts</h2>
    <?php
    $posts = get_posts( array(
      'post_type' => 'post', 'post_status' => 'publish',
      'posts_per_page' => 10, 'meta_key' => '_bgai_generated',
      'meta_value' => '1', 'orderby' => 'date', 'order' => 'DESC',
    ) );
    ?>
    <?php if ( empty( $posts ) ) : ?>
      <p class="bgai-empty">No posts published yet. Click <strong>Run now</strong> above to generate your first post.</p>
    <?php else : ?>
      <table class="bgai-table">
        <thead><tr><th>Title</th><th>Keyword</th><th>Published</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ( $posts as $p ) : ?>
          <tr>
            <td><a href="<?php echo get_permalink($p); ?>" target="_blank"><?php echo esc_html(get_the_title($p)); ?></a></td>
            <td><?php echo esc_html( get_post_meta($p->ID,'_bgai_focus_kw',true) ?: '—' ); ?></td>
            <td><?php echo esc_html( get_the_date('d M Y H:i',$p) ); ?></td>
            <td><a href="<?php echo get_edit_post_link($p->ID); ?>">Edit</a> &nbsp; <a href="<?php echo get_permalink($p); ?>" target="_blank">View</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

</div>
