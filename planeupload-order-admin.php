<?php defined('ABSPATH') || exit;?>
<?php /* @var $order \Automattic\WooCommerce\Admin\Overrides\Order */ ?>
<div class="planeupload-order-files"></div>

<script>
    (() => {
        let any = false;
        <?php foreach($order->get_items() as $item):?>

        (() => {
            <?php $button = planeupload_order_button($order, $item)?>
            <?php if (null == $button) continue?>

            let element = document.querySelector("[data-order_item_id='<?php echo (int)$item->get_id()?>'] .name");
            let generated = document.createElement("div");
            generated.setAttribute("data-plane-upload", "<?php echo esc_attr($button->key)?>");
            generated.setAttribute("data-plane-upload-options",`<?php echo json_encode(PlaneUpload::getButtonOptions())?>`)
            generated.innerHTML = "loading..";
            if (null == element) {
                element = document.querySelector(".planeupload-order-files");
                generated.innerHTML = `<h3>Files for <?php echo esc_html($item->get_name())?></h3>` + generated.innerHTML;
            }
            element.appendChild(generated);
            any = true;
        })();
        <?php endforeach;?>

        if (any && undefined === window["PlaneUpload"]) {
            (function (w, d) {
                var n = "PlaneUpload";
                w[n] || (function () {
                    w[n] = {};
                    var s = d.createElement('script');
                    s.type = 'text/javascript';
                    s.async = true;
                    s.src = 'https://app.planeupload.com/assets/connector.js?t=' + new Date().getTime();
                    d.getElementsByTagName("head")[0].appendChild(s);
                    s.onload = function () {
                        w[n].init();
                    };
                })();
            })(window, document);
        }

    })();
</script>
