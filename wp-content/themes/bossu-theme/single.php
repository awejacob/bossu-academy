<!-- //  Create/edit bossu-theme/single -->
<?php get_header(); ?>
<div class="course-content">
<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
<?php if (isset($_GET['welcome'])) { echo '<div class="welcome-video">[embed autoplay="1"]https://example.com/welcome-video.mp4[/embed]<p>Welcome to Bossu Academy! This is your course overview for Beginners example.</p></div>'; } ?>
<?php the_content(); ?>
<?php endwhile; endif; ?>
</div>
<?php get_footer(); ?>
