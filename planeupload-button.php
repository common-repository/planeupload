<?php defined('ABSPATH') || exit;?>
<?php $options = PlaneUpload::getButtonOptions(isSet($scope) ? $scope : null) ?>
<?php if (null == $key) return;?>
<div data-plane-upload="<?php echo esc_attr($key) ?>"


        data-plane-upload-options='<?php echo json_encode($options) ?>'


    <?php if (isset($cartItemId)): ?>
        data-plane-upload-cart-id="<?php echo esc_attr($cartItemId) ?>"
        <?php if (isSet($cartSet) && $cartSet):?>
        data-plane-upload-cart-set="1"
        <?php endif;?>
    <?php endif; ?>
></div>


<script>
    if (undefined === window["PlaneUpload"]) {
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
</script>

<?php if (isset($cartItemId)): ?>
<script>
    if (undefined === window["_PLANEUPLOAD_CART"]) {
        window["_PLANEUPLOAD_CART"] = true;
        (async () => {
            let mapSent = null;
            const check = async () => {
                const map = {};
                let any = false;

                for(const el of document.querySelectorAll("[data-plane-upload-cart-id]")) {
                    if ("1" === el.getAttribute("data-plane-upload-cart-set")) {
                        continue;
                    }
                    const input = el.querySelector("input[name='PLANE_UPLOAD_KEY']");
                    if (null == input) {
                        return false;
                    }
                    map[el.getAttribute("data-plane-upload-cart-id")] = input.value;
                    any = true;
                }
                if (!any) {
                    return false;
                }
                if (JSON.stringify(map) !== mapSent) {
                    mapSent = JSON.stringify(map);
                    const resp = await (await fetch("?planeupload_cartset=1",{
                        "method": "post",
                        "body": JSON.stringify({
                            map: map
                        })
                    })).json();
                    for(const key in resp) {
                        const planeKey = resp[key];
                        const input = Array.from(document.querySelectorAll("input[name='PLANE_UPLOAD_KEY']")).find(candidate => planeKey === candidate.value);
                        if (null != input) {
                            input.parentNode.setAttribute("data-plane-upload-cart-set","1");
                        }
                    }
                    return true;
                }
                return false;
            };
            while(true) {
                await new Promise(_=>setTimeout(()=>_(),500));
                await check();
            }

        })();


    }
</script>
<?php endif;?>
