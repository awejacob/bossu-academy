<?php /**
 * @ Author: Bill Minozzi
 * @ Copyright: 2020 www.BillMinozzi.com
 * @ Modified time: 2020-03-17 15:07:59
 */
if (!defined('ABSPATH')) {
    exit;
}?>
<!DOCTYPE html>
<html>
<head>
    <?php wp_head(); ?>
    <script>
        setTimeout(function() {
            location.reload();
        }, 2000);
    </script>
</head>
<body>
    <div class="container" id="main-content">
        <style>
            .verticalhorizontal {
                width: 100px;
                height: 100px;
                top: 40%;
                left: 50%;
                bottom: 50%;
                right: 50%;
                position: absolute;
            }
            body {
                background-color: white;
            }
        </style>
        <div class="verticalhorizontal">
            <img src="<?php echo ANTIBOTSURL; ?>assets/images/ajax-loader.gif" alt="Please, Wait..." />
        </div>
    </div>
    <?php wp_footer(); ?>
</body>
</html>